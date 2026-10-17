# Kiểm chứng website — 17/10/2026

URL: **https://minh.xuvi.vn**. Ứng dụng Laravel + Blade chạy trên cPanel, độc lập với Mac. Bản thử `minh-thu.xuvi.vn` đã được gỡ. Mã website đã commit/push ngày 17/10/2026.

## Production minh.xuvi.vn

| Phạm vi | Kết quả |
| --- | --- |
| Chuyển máy chủ | DNS `minh.xuvi.vn` chuyển từ GitHub Pages sang cPanel (`103.130.216.71`), HTTPS hợp lệ; document root chỉ `apps/minh/public`. Phản hồi 403 lúc đầu đến từ bộ lọc bot khi request thiếu header trình duyệt, không phải lỗi ứng dụng; quyền thư mục hiện có đã đủ, không đổi hàng loạt. |
| GitHub App | App **Minh Notes 2026** (ID 5350849, installation 172214212) do Minh duyệt tạo/cài/Authorize. Callback chính xác `https://minh.xuvi.vn/auth/callback`; installation chỉ chọn `minhxuvi/minhxuvi`; server khóa installation token vào đúng repo cấu hình và kiểm ID chủ sở hữu ổn định 18323356. |
| Đăng nhập, kho thật | Đăng nhập GitHub trên `minh.xuvi.vn` thành công. Ghi chú hướng dẫn `Projects/Sử dụng website minh.xuvi.vn.md` được tạo qua website; GitHub xác nhận commit [`e757345`](https://github.com/minhxuvi/minhxuvi/commit/e757345). |
| Đồng bộ hai chiều | Kiểm chứng bằng clone tách biệt ngoài repo website: web → clone (fast-forward) và clone → web (`f48c330`) đều qua. Xung đột thật: website giữ SHA cũ, lưu trả HTTP 409 và giữ bản đang soạn kèm bản GitHub; hợp nhất có chủ đích thành `46c16ba`, cả hai phần sửa còn nguyên. Kho canonical trên Mac giữ nguyên, chỉ đọc. |
| Gỡ bản thử | `minh-thu.xuvi.vn` đã xóa khỏi cPanel, DNS không còn trả bản ghi; cấu hình triển khai thử đã xóa. Workflow Nextra **Deploy to GitHub Pages** đã Disabled trên GitHub bằng xác nhận riêng của Minh; bản Pages hiện có được giữ để hostname `minhxuvi.github.io` tiếp tục phục vụ URL cũ. |
| An toàn truy cập | Chưa đăng nhập: `/private/note`, `/private/asset`, `/private/search` trả JSON 401; trang `/private` chuyển tới đăng nhập. URL công khai trả 200; `/giao-duc/index.md` chuyển hướng về `/giao-duc`. |
| Sửa cuối ngày — tên note chứa `?` | Một note trong kho thật có dấu `?` trong tên (nhóm Archive) làm tìm kiếm toàn kho trả 422. Đã cho phép `?` trong đường dẫn, mã hóa từng đoạn khi ghi qua GitHub Contents API, và `scripts/sync-notes.py` dùng `--literal-pathspecs` để xử lý đúng tên file đặc biệt. |
| Sửa cuối ngày — ảnh `.img` | Các `.img` sẵn có trong kho được nhận khi MIME thực (fileinfo) là PNG/JPEG/GIF/WebP/AVIF; nội dung khác trả 415. Chưa note nào nhúng `.img`; mở trực tiếp qua endpoint có quyền. |
| Kiểm chứng sau sửa | Hosting: cây 705 mục; note `?` đọc được (678 byte); tìm `Sử dụng website minh` trả 1 kết quả, không lỗi; `.img` đầu tiên nhận diện `image/jpeg`. Qua HTTPS có đăng nhập: tìm kiếm trả 200 và thấy đúng ghi chú hướng dẫn; note `?` trả 200 và mở được trình soạn (khung soạn focus sẵn, nút Thu gọn hiện); asset `.img` trả 200 `image/png` đúng chữ ký PNG; liên kết tới note `?` trong duyệt thư mục và tìm kiếm đều mã hóa `%3F` đúng. Toàn bộ 21 URL công khai trả 200. |
| Triển khai | Gói 17/10 09:53 được xác minh bằng SHA-256: `app/Services/GitHubNotes.php` trên hosting khớp bản local; `bootstrap/cache` và view cache được tạo lại trong cùng lượt deploy. |
| Tự động | 18 tests / 152 assertions qua; Blade biên dịch qua; JS dùng cú pháp ES5 + XMLHttpRequest cho Android cũ. Workflow **Validate Laravel** trên GitHub Actions đã được bật lại và chạy xanh cho commit `4515c86`. |

## Thiết bị và vấn đề vận hành

- Minh xác nhận **iPhone SE 2016** và browser mặc định **Hisense A5 HLTE202N** đọc, sửa và lưu được; chế độ tập trung khi Sửa dành chỗ cho bàn phím, nút **Thu gọn** khôi phục điều hướng.
- Trong lượt QA, Python urllib bị cPGuard nhận là bot và IP mạng cũ bị chặn cổng web. Đã gửi [ticket Tino #474151](https://tino.vn/clientarea/tickets/view/474151) nhờ gỡ đúng IP, giữ nguyên WAF và bảo vệ khác; mạng hiện tại truy cập bình thường. Chưa tắt firewall hay whitelist toàn mạng.
- CLI trên hosting cần PHPRC, PHP_INI_SCAN_DIR và PHP binary trong `deploy/production.json` vì cấu hình CLI mặc định cũ không khớp; không đổi PHP chung.

## Chưa làm ngoài phạm vi

Mã website đã commit/push ngày 17/10/2026; workflow Nextra đã tắt để không build/deploy Nextra về sau. Chưa có upload ảnh, chỉnh offline hoặc tự hợp nhất lịch sử Git phân nhánh. Ảnh đã theo dõi trong Git được đọc; PDF/audio ngoài Git chưa hỗ trợ. Kho canonical trên Mac còn thay đổi chưa commit và được giữ nguyên. Dữ liệu QA, gói deploy và cấu hình máy/bí mật nằm trong `deploy/local/` và `deploy/artifacts/`, đã bị Git bỏ qua.

## Bản thử minh-thu.xuvi.vn (đã gỡ) — hồ sơ lịch sử

Bảng dưới ghi lại kết quả kiểm chứng của bản thử trước khi chuyển production; bản thử đã được gỡ và không còn là môi trường triển khai.

| Phạm vi | Kết quả |
| --- | --- |
| Public | 21 URL hiện có trả HTTP 200 qua HTTPS. Title, description và thứ tự/nhãn menu được chuyển từ nguồn public. Đối chiếu 248 anchor mục lục với bản export trước: không thiếu anchor. URL `/giao-duc/index.md` trả 301 về `/giao-duc`. |
| Hosting | PHP web 8.5.9, LiteSpeed; đủ cURL, OpenSSL, DOM, mbstring, fileinfo, PDO, tokenizer, XML và các extension Laravel cần. Ghi session/cache ngoài public được. CLI dùng cấu hình PHP riêng; không đổi PHP chung. |
| GitHub App | Minh trực tiếp duyệt tạo/cài/Authorize. Xác minh installation token chỉ thấy `minhxuvi/minh-notes-demo`. Kiểm ID chủ sở hữu ở server, không dựa trên tên tài khoản. |
| Đọc/sửa/lưu | Chrome online đăng nhập thành công; sửa note tiếng Việt, xem trước, lưu, tải lại thấy nội dung đã ghi. GitHub xác nhận commit [82a84c1](https://github.com/minhxuvi/minh-notes-demo/commit/82a84c1f072f54649240a08c209d4753c45760b1). |
| Tạo note | Tạo file và thư mục có dấu `Thử-nghiệm/Ghi-chú-điện-thoại.md`; xem trước bảng, checkbox, ảnh riêng và link tương đối. GitHub xác nhận [ab239d9](https://github.com/minhxuvi/minh-notes-demo/commit/ab239d9118a1f44578fffae23bf17cf42e0237d4). Tìm kiếm private theo `MOBILE-CREATE-01` trả đúng note. |
| Web → Mac | Helper fetch + fast-forward nhận commit từ web; thấy marker `WEB-TO-MAC-01` trong bản local mẫu. Note mới tiếng Việt cũng được nhận về clone mẫu. |
| Mac → web | Helper gửi file mẫu được chọn, tạo [e6208dc](https://github.com/minhxuvi/minh-notes-demo/commit/e6208dc02eedda31df28b52168ee6f1f22a42747). Server và trình duyệt online đều thấy nội dung mới. |
| An toàn đồng bộ | Giữ nguyên staging của file ngoài phạm vi và thay đổi chưa staged khác. Khóa đang được giữ khiến helper từ chối. Chỉ hỗ trợ ff-only khi pull, không force/reset/stash; lịch sử phân nhánh hoặc worktree bẩn cần xử lý có chủ đích. |
| Xung đột thật | Giữ browser ở SHA cũ, gửi phiên bản khác từ clone mẫu rồi bấm Lưu: nhận 409; textarea vẫn giữ `CONFLICT-DRAFT-01`, panel đối chiếu có `CONFLICT-REMOTE-01`. Hợp nhất có chủ đích và lưu lại: [51d20da](https://github.com/minhxuvi/minh-notes-demo/commit/51d20daa8ff9c215ebf4f9f4af5c84d8a7c9b170), cả hai marker còn trên GitHub/local. |
| Lỗi API thật | Tạm cấu hình bản thử trỏ nhánh không tồn tại. GitHub trả lỗi; UI giữ bản đang soạn, không tạo commit mới. Đã khôi phục `.env` về main và xóa backup thử lỗi. |
| Mất mạng thật trong browser | Chrome DevTools đặt Offline cho riêng tab: POST lưu nhận `ERR_INTERNET_DISCONNECTED`, nội dung giữ nguyên, có nút sao chép/thử lại. Khôi phục No throttling và lưu thành công. |
| Bản nháp | Reload khi note mới chưa lưu hiện cảnh báo; sau xác nhận reload, tên file và toàn bộ Markdown được phục hồi từ sessionStorage của tab rồi lưu thành công. |
| Chưa đăng nhập | HTTP trực tiếp note, ảnh, tìm kiếm, tạo note trả 401 khi yêu cầu JSON; trang `/private` redirect login. POST API không session/CSRF trả 419. `/storage/app/github-app.json`, `/content/index.md`, `/vendor/autoload.php` trả 404. |
| Tách tìm kiếm | Tìm public theo marker riêng `MOBILE-CREATE-01` trả 0 kết quả; tìm private trả đúng note mẫu. |
| Renderer | Cùng CommonMark cho public/private, GFM bảng/checkbox. Tests kiểm script/HTML bị loại, URL javascript bị chặn, ảnh ngoài trong note private bị chặn, ảnh nội bộ đi qua route có quyền. Không thực thi MDX. |
| Bí mật | `.env` và credentials App ở ngoài document root, mode 0600; thư mục credentials/session mode 0700. Không có `public/storage`. Probe runtime và backup lỗi API đã gỡ; setup key đã xóa, route setup tự đóng sau khi có credentials. |
| Tự động | 13 tests / 129 assertions qua; Blade biên dịch qua; JS kiểm tra cú pháp qua. Bản JS tương thích cũ phân tích được với ECMAScript 5 (Acorn); không cần Node trên hosting. |
