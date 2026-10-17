@extends('layout')
@section('content')
<h1>{{ $title }}</h1>
<div id="note-app" data-sha="{{ $note['sha'] }}" data-repository="{{ config('notes.repository') }}" data-creating="{{ ($creating ?? false) ? '1' : '0' }}">
<label for="note-path">Đường dẫn Markdown</label><input id="note-path" value="{{ $note['path'] }}" placeholder="Thu-muc/Ghi-chu.md" @if(!($creating ?? false)) readonly @endif maxlength="500" autocomplete="off">
<div class="toolbar editor-toolbar"><button id="edit-note" type="button">{{ ($creating ?? false) ? 'Đang soạn' : 'Sửa ghi chú' }}</button><button id="preview-note" type="button" class="secondary">Xem trước</button><button id="save-note" type="button">Lưu vào GitHub</button><button id="copy-note" type="button" class="secondary">Sao chép bản đang soạn</button><button id="exit-focus" type="button" class="secondary" hidden>Thu gọn</button></div>
<p id="save-status" role="status" aria-live="polite">{{ ($creating ?? false) ? 'Chưa lưu vào GitHub.' : 'Đã mở phiên bản '.substr($note['sha'], 0, 10).'.' }}</p>
<p id="draft-warning" class="notice" hidden>Bản nháp đang ở trong tab trình duyệt này. Đăng xuất sẽ xóa bản nháp.</p>
<div id="editor-pane" @if(!($creating ?? false)) hidden @endif><label for="note-source">Nội dung Markdown</label><textarea id="note-source" spellcheck="false" autocapitalize="off" autocomplete="off">{{ $note['content'] }}</textarea></div>
<div id="conflict-panel" class="notice" hidden><h2>GitHub có phiên bản khác</h2><p>Bản đang soạn được giữ ở phía trên. Sao chép các phần cần giữ, đối chiếu bản GitHub ở dưới, rồi chọn dùng phiên bản mới làm mốc để lưu phần đã hợp nhất.</p><label for="remote-source">Bản GitHub mới nhất (chỉ đọc)</label><textarea id="remote-source" readonly></textarea><button id="rebase-note" type="button" class="secondary">Đã đối chiếu — dùng mốc GitHub mới</button></div>
<article id="note-preview" class="prose">{!! $html !!}</article>
<noscript><p>Cần JavaScript để sửa và lưu ghi chú.</p></noscript>
</div>
@endsection
