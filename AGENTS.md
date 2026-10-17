# AGENTS.md

Website tiếng Việt thống nhất bằng Laravel 13 + Blade. Public và `/private` dùng chung bộ hiển thị Markdown. JavaScript thuần chỉ phục vụ trình soạn, xem trước và giữ bản nháp. Không duy trì Nextra, Next.js, React hoặc MDX thực thi.

## Lệnh

- `composer install` — cài dependency theo lock, PHP 8.5 phù hợp hosting.
- `cp .env.example .env` rồi `php artisan key:generate` — cấu hình local; khi dùng HTTP local đặt `SESSION_SECURE_COOKIE=false`.
- `php artisan serve` — chạy local.
- `php artisan test` — kiểm tra route, quyền truy cập, renderer, lưu/xung đột và lỗi API.
- `php artisan view:cache` — kiểm tra biên dịch Blade.
- `python3 scripts/package.py` — tạo gói production có vendor, không có bí mật/dữ liệu runtime.

## Phân định nguồn

Repo này chỉ giữ mã và nội dung an toàn để công khai. Kho canonical riêng được khai báo bằng tham chiếu chỉ đọc trong `opencode.jsonc` (git-ignored); dùng `opencode.example.jsonc` làm mẫu.

Không sửa kho riêng, không sao chép note riêng nguyên văn hoặc đưa vào fixture/dữ liệu mẫu. Trước khi thêm nội dung, cân nhắc có thể nhận diện trẻ em, gia đình, đồng nghiệp, khách hàng hoặc lộ sức khỏe, tài chính, nhật ký, công việc nội bộ, đường dẫn máy cá nhân hay thông tin xác thực không. Nếu chưa chắc, bỏ qua và báo rõ.

Minh đã giao chuyển production sang `minh.xuvi.vn` và kết nối kho thật `minhxuvi/minhxuvi` ngày 17/10/2026. GitHub App chỉ được chọn kho này, token tiếp tục giới hạn đúng repo. Kho canonical trên Mac vẫn chỉ đọc trong chat chuyển đổi; không tự pull, commit hoặc hợp nhất thay đổi có sẵn. Không sao chép nội dung riêng vào repo website, kể cả thư mục ignored. Mã website đã được commit/push lên `minhxuvi/minhxuvi.github.io` ngày 17/10/2026 theo chỉ đạo của Minh; các lần push sau vẫn chờ Minh xác nhận.

## Nội dung và route

- Bài public là `.md` trong `content/`; front matter `title`, `description`. `index.md` là URL thư mục.
- Menu tiếng Việt và thứ tự nằm trong `config/navigation.json`; không dùng `_meta.js` nữa.
- Route tập trung ở `routes/web.php`; Blade trong `resources/views`; CSS/JS thuần trong `public/assets`.
- Giữ URL cũ; link nội bộ public dùng site-absolute, ví dụ `/giao-duc`. Có redirect cho URL `.md`, `.mdx`, `/index.md` tương ứng.
- Nội dung private lấy qua GitHub App Contents đọc/ghi, chỉ cài repo thật đã được Minh duyệt. Xác thực GitHub kiểm ID ổn định phía server. Tất cả route private (note, ảnh, tìm kiếm, API) phải đi qua `PrivateAccess`.
- Không cho phép HTML tùy ý, MDX, SVG private, symlink hoặc vượt thư mục. Ảnh private phải đi qua route có xác thực, không trả URL raw GitHub hoặc token cho browser.
- Lưu phải kèm SHA đã mở; chỉ báo thành công sau khi GitHub trả commit. Xung đột/lỗi mạng phải giữ bản đang soạn.

## Runtime và triển khai

- Document root chỉ trỏ `public/`. `.env`, `vendor`, `content`, `storage`, session/cache và GitHub App PEM nằm ngoài web root. Không tạo symlink `public/storage` cho dữ liệu private.
- `APP_DEBUG=false`, HTTPS, cookie Secure/HttpOnly/SameSite, CSRF trên POST. Response private phải `no-store`; không trộn chỉ mục public/private.
- Session/cache dùng file, không cần database hay dịch vụ nền. GitHub là nguồn Markdown online; cache có thể tạo lại.
- cPanel dùng PHP theo request, không root, Docker hoặc Node runtime. Đóng gói dependency ở local.
- Production có cấu hình không bí mật trong `deploy/production.json`, quy trình ở `deploy/README.md`. Domain chính là `minh.xuvi.vn`, document root `apps/minh/public`. Không tạo lại môi trường/domain thử.
- Workflow mã nguồn chỉ kiểm Laravel; không tự bật lại pipeline build/deploy Nextra.
- Bí mật và cấu hình riêng máy đặt trong `.env` hoặc `deploy/local/`, không đưa vào Git, log hoặc gói phát hành.

## Đồng bộ

`scripts/sync-notes.py` khóa repo thật theo `production.json`, dùng `.git/minh-sync.lock`, nhận thay đổi bằng fast-forward và gửi các file được chọn sau khi xem plan. Không force push, reset, tự stash hoặc tự gom mọi thay đổi. Giữ staging ngoài phạm vi; từ chối file được chọn đang staged. Local bẩn hoặc lịch sử phân nhánh phải dừng an toàn. Kiểm chứng ở clone tách biệt ngoài repo website; kho canonical Mac chỉ đọc nếu chưa có chỉ dẫn khác.

Cache riêng gắn với SHA blob, ngoài public. Archive làm ấm chỉ nhận từ GitHub, không giải nén đường dẫn, xác minh hash rồi xóa file tạm. Không log nội dung note, token hoặc signed URL. Note 2 MB, ảnh raster 8 MB; SVG và HTML không thực thi. Wikilink chỉ hoạt động trong renderer riêng, tên trùng cần chọn qua tìm kiếm. Duyệt thư mục từng cấp và phân trang; không tải toàn kho bằng các request browser rời rạc.
