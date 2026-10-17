(function () {
  'use strict';
  var prefix = 'minh-note-draft:';
  function clearDrafts() {
    try {
      var keys = Object.keys(sessionStorage);
      for (var i = 0; i < keys.length; i++) if (keys[i].indexOf(prefix) === 0) sessionStorage.removeItem(keys[i]);
    } catch (error) {}
  }
  var logout = document.querySelector('[data-logout]');
  if (document.querySelector('#logged-out')) clearDrafts();
  if (logout) logout.addEventListener('submit', clearDrafts);
  if (document.body.getAttribute('data-private') === '1') {
    window.addEventListener('pageshow', function (event) { if (event.persisted) location.reload(); });
    window.addEventListener('pagehide', function () { var main = document.querySelector('#main'); if (main) main.setAttribute('hidden', ''); });
  }
  var toc = document.querySelector('.toc details');
  if (window.matchMedia && window.matchMedia('(max-width: 760px)').matches && toc) toc.removeAttribute('open');
  var app = document.querySelector('#note-app'); if (!app) return;
  var source = document.querySelector('#note-source'), path = document.querySelector('#note-path');
  var status = document.querySelector('#save-status'), warning = document.querySelector('#draft-warning');
  var editor = document.querySelector('#editor-pane'), preview = document.querySelector('#note-preview');
  var save = document.querySelector('#save-note'), conflict = document.querySelector('#conflict-panel');
  var remoteSource = document.querySelector('#remote-source'), exitFocus = document.querySelector('#exit-focus');
  var sha = app.getAttribute('data-sha') || null, savedSource = source.value, dirty = false, remote;
  var owner = document.body.getAttribute('data-owner'), repository = app.getAttribute('data-repository');
  var key = prefix + owner + ':' + repository + ':' + (path.value || '__new__');
  var pending = false;
  function setStatus(message) { status.textContent = message; }
  function focusMode(enabled) {
    if (enabled) document.body.classList.add('note-editing'); else document.body.classList.remove('note-editing');
    if (exitFocus) exitFocus.hidden = !enabled;
  }
  function keepDraft() {
    dirty = source.value !== savedSource || app.getAttribute('data-creating') === '1';
    warning.hidden = !dirty;
    try {
      if (dirty) sessionStorage.setItem(key, JSON.stringify({path: path.value, content: source.value, sha: sha}));
      else sessionStorage.removeItem(key);
    } catch (error) { setStatus('Trình duyệt không lưu được bản nháp trong tab. Hãy sao chép nội dung trước khi rời trang.'); }
  }
  try {
    var draft = JSON.parse(sessionStorage.getItem(key) || 'null');
    if (draft && (draft.content !== savedSource || draft.path !== path.value)) {
      source.value = draft.content; if (!path.readOnly) path.value = draft.path;
      sha = draft.sha; dirty = true; warning.hidden = false; editor.hidden = false; focusMode(true);
      setStatus('Đã khôi phục bản nháp trong tab. Khi lưu, ứng dụng sẽ kiểm tra lại phiên bản GitHub.');
    }
  } catch (error) {}
  source.addEventListener('input', keepDraft); path.addEventListener('input', keepDraft);
  window.addEventListener('beforeunload', function (event) { if (dirty) { keepDraft(); event.preventDefault(); event.returnValue = ''; } });
  // ES5 syntax and XMLHttpRequest also work in older Android/e-ink browsers.
  function api(endpoint, payload, done) {
    var xhr = new XMLHttpRequest(), finished = false;
    function finish(code, result) { if (finished) return; finished = true; done(code, result); }
    xhr.open('POST', endpoint, true); xhr.timeout = 90000;
    xhr.setRequestHeader('Content-Type', 'application/json'); xhr.setRequestHeader('Accept', 'application/json');
    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);
    xhr.onload = function () {
      var result;
      try { result = JSON.parse(xhr.responseText); }
      catch (error) { result = {message: 'Máy chủ chưa trả lời đúng. Bản đang soạn được giữ lại.'}; }
      finish(xhr.status, result);
    };
    xhr.onerror = function () { finish(0, {message: 'Mất mạng. Bản đang soạn được giữ trong tab; có thể thử lại hoặc sao chép.'}); };
    xhr.ontimeout = function () { finish(0, {message: 'Chưa nhận được xác nhận từ GitHub. Bản nháp còn nguyên; hãy kiểm tra lại trước khi thử lưu tiếp.'}); };
    try { xhr.send(JSON.stringify(payload)); } catch (error) { finish(0, {message: 'Không kết nối được. Bản đang soạn được giữ lại.'}); }
  }
  document.querySelector('#edit-note').addEventListener('click', function () {
    editor.hidden = false; focusMode(true); source.focus();
  });
  if (exitFocus) exitFocus.addEventListener('click', function () { focusMode(false); });
  document.querySelector('#preview-note').addEventListener('click', function () {
    keepDraft();
    api('/private/api/preview', {path: path.value, content: source.value}, function (code, result) {
      if (code < 200 || code >= 300) { setStatus(result.message || 'Không xem trước được.'); return; }
      preview.innerHTML = result.html;
      setStatus('Đã cập nhật xem trước. Nội dung chưa lưu vẫn ở trong trình soạn.');
      preview.scrollIntoView(true);
    });
  });
  save.addEventListener('click', function () {
    if (pending) return;
    keepDraft(); var submitted = source.value, submittedPath = path.value;
    pending = true; save.disabled = true; path.readOnly = true; setStatus('Đang chờ GitHub xác nhận…');
    api('/private/api/save', {path: submittedPath, content: submitted, sha: sha}, function (code, result) {
      pending = false; save.disabled = false;
      if (code === 409) {
        if (Object.prototype.hasOwnProperty.call(result, 'remote')) {
          remote = result.remote; remoteSource.value = remote ? remote.content : '(File đã bị xóa trên GitHub)'; conflict.hidden = false;
        }
        editor.hidden = false;
        setStatus(result.message || 'Có xung đột phiên bản. Bản đang soạn được giữ lại; hãy đối chiếu bản GitHub bên dưới.');
      } else if (code < 200 || code >= 300) {
        setStatus(code === 419 ? 'Phiên bảo mật đã hết. Sao chép bản nháp và đăng nhập lại trước khi lưu.' : result.message || 'Không lưu được. Hãy thử lại.');
      } else {
        sha = result.sha; savedSource = submitted; app.setAttribute('data-creating', '0');
        try { sessionStorage.removeItem(key); } catch (error) {}
        key = prefix + owner + ':' + repository + ':' + submittedPath;
        conflict.hidden = true; remote = undefined;
        history.replaceState(null, '', '/private/note?path=' + encodeURIComponent(submittedPath));
        setStatus('GitHub đã xác nhận phiên bản ' + result.commit.slice(0, 10) + '. ');
        var link = document.createElement('a'); link.href = result.commit_url; link.textContent = 'Xem phiên bản trên GitHub ↗'; link.target = '_blank'; link.rel = 'noopener noreferrer'; status.appendChild(link);
      }
      keepDraft();
      if (app.getAttribute('data-creating') === '1') path.readOnly = false;
    });
  });
  document.querySelector('#rebase-note').addEventListener('click', function () {
    if (remote === undefined) return;
    sha = remote ? remote.sha : null; conflict.hidden = true; keepDraft();
    setStatus('Đã dùng mốc GitHub mới. Bản đang soạn giữ nguyên; hãy hoàn tất hợp nhất rồi bấm Lưu.');
  });
  function selectForCopy() { editor.hidden = false; source.focus(); source.select(); setStatus('Hãy chọn Sao chép từ menu của điện thoại.'); }
  document.querySelector('#copy-note').addEventListener('click', function () {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      try { navigator.clipboard.writeText(source.value).then(function () { setStatus('Đã sao chép toàn bộ bản đang soạn.'); }, selectForCopy); }
      catch (error) { selectForCopy(); }
    } else selectForCopy();
  });
})();
