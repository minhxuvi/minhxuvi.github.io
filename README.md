# Website Markdown của Minh

Website chính: https://minh.xuvi.vn

Một ứng dụng Laravel 13 + Blade cho nội dung công khai và ghi chú riêng. PHP 8.5, CSS/JavaScript thuần; không có Next.js hoặc frontend riêng. Nội dung public ở `content/`, menu ở `config/navigation.json`. Kho GitHub private là nguồn Markdown của `/private`; không chép note riêng vào repo mã nguồn này.

Đọc [hướng dẫn vận hành và đồng bộ](deploy/README.md), [kết quả kiểm chứng](deploy/verification.md) và [hướng dẫn tác nhân](AGENTS.md).

Đăng nhập GitHub chỉ cho ID chủ sở hữu. Note, ảnh, tìm kiếm và API đều kiểm quyền ở server. Mỗi lần lưu kiểm SHA và chỉ xác nhận sau khi GitHub trả commit. Cache Markdown riêng nằm ngoài web root, có thể tạo lại; không thay thế GitHub.
