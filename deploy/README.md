# Vận hành website chính

URL: https://minh.xuvi.vn. Một ứng dụng Laravel + Blade chạy trên cPanel, độc lập với Mac. Document root chỉ là `apps/minh/public`. `.env`, vendor, nội dung nguồn, session/cache và credentials GitHub nằm ngoài public. Domain thử không còn là môi trường triển khai.

## Tiếp nối và triển khai

Đọc `AGENTS.md`, `deploy/production.json` và `deploy/verification.md`. Bí mật chỉ ở `.env` trên hosting và `storage/app/github-app.json`; không in ra log, đưa vào Git hoặc gói deploy. File credentials và môi trường cần quyền 0600; runtime riêng cần 0700. Quyền 0750/group nobody cho các thư mục đi qua document root do cPanel quản lý; không đổi hàng loạt. Script deploy dùng `--no-overwrite-dir` để giữ quyền thư mục.

Local cần PHP 8.5, Composer và Python 3. `composer install`, tạo `.env` từ mẫu, `php artisan key:generate`, `php artisan test`, `php artisan view:cache`. Với HTTP local đặt APP_URL local và SESSION_SECURE_COOKIE=false. Các lệnh không cần Node runtime; JS dùng cú pháp ES5 + XMLHttpRequest cho Android cũ. Khi đổi asset, tăng query version trong Blade.

```sh
python3 scripts/package.py
python3 scripts/deploy.py
```

Có thể đặt `PHP_BINARY` tới PHP 8.5 ổn định và `COMPOSER_CACHE_DIR` tới cache ghi được khi đóng gói. Không dùng PHP 8.6 RC trên máy để suy ra khả năng hosting. Gói production chỉ gồm mã, public content và vendor theo Composer lock; không có `.env`, runtime, notes riêng hoặc cấu hình máy.

Hosting web PHP 8.5.9/LiteSpeed; CLI dùng PHPRC, PHP_INI_SCAN_DIR và PHP binary trong `production.json` vì cấu hình CLI mặc định cũ không khớp. Không cần root, Docker, database, dịch vụ nền hay cron. Không tự commit/push mã website. Giữ gói phiên bản trước ngoài public khi cần rollback mã; giữ nguyên `.env`, credentials và dữ liệu runtime khi rollback. Việc lưu note đã commit trên GitHub không bị rollback mã website.

## GitHub App và quyền

App **Minh Notes 2026**, ID 5350849, installation 172214212, do Minh sở hữu. Homepage `https://minh.xuvi.vn`, callback chính xác `https://minh.xuvi.vn/auth/callback`. Không dùng wildcard callback. Installation chỉ chọn **minhxuvi/minhxuvi**, Contents đọc/ghi và metadata đọc; đã bỏ repo mẫu. Server tiếp tục giới hạn installation token vào đúng repo cấu hình, kiểm kho private và ID GitHub ổn định 18323356.

`NOTES_REPOSITORY=minhxuvi/minhxuvi`, `NOTES_BRANCH=main`, `NOTES_DEMO_ONLY=false`. Mặc định khi thiếu cấu hình vẫn khóa ở repo mẫu để tránh vô tình nối kho thật. Không cấp All repositories. `/setup/*` không còn route production; thay credentials cần thao tác vận hành riêng. Có thể thu hồi App trong GitHub Settings → Applications.

## Dùng website

Mở URL → Khu riêng → GitHub. Chọn thư mục hoặc tìm ghi chú, Sửa → Xem trước → Lưu vào GitHub. Chỉ báo thành công khi GitHub trả commit, có link phiên bản. Trình soạn mở chế độ tập trung để chừa chỗ cho bàn phím; Thu gọn hiện điều hướng. Minh đã dùng được trên iPhone SE 2016 và browser mặc định Hisense A5.

Tên note mới kết thúc `.md`, có thể chứa thư mục và tiếng Việt. Markdown GFM có bảng/checkbox. Wikilink riêng được giải quyết theo đường dẫn tương đối, từ gốc hoặc basename duy nhất; tên trùng/không tìm thấy dẫn tới tìm kiếm để chọn. Inline code và code fence không bị biến thành wikilink; không nhúng thực thi note khác. HTML/MDX bị vô hiệu hóa. Ảnh raster trong Git đi qua endpoint có quyền; ảnh ngoài trong note riêng bị chặn để tránh theo dõi.

Bản nháp ở sessionStorage của tab, có thể phục hồi khi tải lại; đăng xuất xóa bản nháp trong tab đó. Không đóng tab trước khi lưu hoặc sao chép. Mất mạng/lỗi GitHub vẫn giữ nội dung. Hết phiên 401/419 cần sao chép và đăng nhập lại. Bản nháp trình duyệt không mã hóa; dùng thiết bị của mình. Không hỗ trợ chỉnh offline hoặc upload ảnh.

## Tìm kiếm và cache riêng

Danh sách file gắn với commit GitHub; mỗi lần mở kiểm ref nhánh, cache cây theo commit. Blob Markdown cache bằng SHA trong `storage/app/private-notes`, quyền 0600 trong thư mục 0700. Kho nhiều note được làm ấm từ ZIP của commit: chỉ nhận HTTPS từ codeload.github.com, tải tạm ngoài public, không giải nén file ra đường dẫn archive, chỉ lấy Markdown có SHA blob khớp cây, rồi xóa ZIP. Các thay đổi nhỏ chỉ cần lấy blob mới. Không phục vụ cache trực tiếp, không có `public/storage`.

```sh
# Gọi qua SSH, dùng PHP/env runtime trong production.json:
php artisan notes:warm
```

Tìm kiếm toàn bộ Markdown được hỗ trợ, không còn giới hạn 80 note. Mỗi thư mục hiển thị tối đa 50 note/trang; các thư mục con được duyệt từng cấp. Note tối đa 2 MB, ảnh raster tối đa 8 MB. Đuôi `.img` đang có trong kho được nhận nếu MIME thực là PNG/JPEG/WebP/ảnh raster cho phép. SVG, PDF/audio/Office và định dạng khác chưa được phục vụ. Trước khi tăng giới hạn phải kiểm hiệu năng và MIME.

## Đồng bộ chủ động với Mac

`scripts/sync-notes.py` chỉ chấp nhận origin của repo thật đã cấu hình trong production.json. Không để clone hoặc bản sao nội dung riêng trong repo website, kể cả thư mục Git-ignored. Dùng đường dẫn kho riêng qua biến `NOTES_DIR` của phiên shell.

```sh
python3 scripts/sync-notes.py --repo "$NOTES_DIR" status
python3 scripts/sync-notes.py --repo "$NOTES_DIR" pull
python3 scripts/sync-notes.py --repo "$NOTES_DIR" plan "duong-dan/note.md"
# Đọc diff và mã REVIEW, rồi gửi đúng file đã duyệt:
python3 scripts/sync-notes.py --repo "$NOTES_DIR" publish --review <mã> "duong-dan/note.md"
```

Helper giữ `.git/minh-sync.lock`, fetch trước đối chiếu, plan khóa HEAD/online/hash file. Publish từ chối nếu dữ liệu đổi sau plan hoặc file chọn đã staged; chỉ commit file chỉ định và giữ staging ngoài phạm vi. Pull chỉ fast-forward khi worktree sạch; local bẩn hoặc lịch sử phân nhánh dừng an toàn. Không force push/reset/tự stash, không chạy nền. Sau mỗi lần sửa web cần nhận thay đổi trước khi sửa local.

Trong chat chuyển production, kho canonical trên Mac tiếp tục chỉ đọc. Có thay đổi chưa commit trước chuyển đổi; không tự pull/commit/hợp nhất chúng. Kiểm chứng hai chiều dùng clone tách biệt ngoài repo website và chỉ sửa ghi chú hướng dẫn được tạo cho lần đưa vào sử dụng này.
