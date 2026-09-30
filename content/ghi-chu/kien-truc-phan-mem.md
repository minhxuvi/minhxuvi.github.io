---
title: "Kiến trúc phần mềm"
description: "Ghi chú về công việc của kiến trúc sư phần mềm: mục tiêu, quy trình từ yêu cầu đến thiết kế, cách viết tài liệu kiến trúc và một vài mẫu thiết kế."
---

# Kiến trúc phần mềm

Ghi chú này là cách mình hiểu công việc thiết kế hệ thống. Nó không theo một trường phái cụ thể nào; nó là những gì mình thấy dùng được khi phải ngồi xuống và quyết định.

## Bốn mục tiêu

Mọi hệ thống mình làm đều nhắm tới bốn thứ, và chúng thường xung đột với nhau:

- **Nhanh** — đủ nhanh cho công việc thật mà người dùng làm.
- **An toàn** — không để lộ dữ liệu, không để người lạ làm được việc họ không được phép làm.
- **Đáng tin** — chạy đúng, và khi sai thì báo cho biết.
- **Dễ bảo trì** — người sau đọc hiểu được và sửa được.

Khi bốn thứ này kéo nhau, thứ tự ưu tiên phải do bối cảnh quyết định, không do sở thích của người thiết kế.

## Cách nghĩ

- **Hiểu việc kinh doanh, không chỉ hiểu yêu cầu kỹ thuật.** Yêu cầu kỹ thuật thường là bản dịch đã mất mát của một vấn đề thật.
- **Xác định mục tiêu của hệ thống trước khi vẽ gì.** Mục tiêu là thứ quan trọng nhất cần đạt được.
- **Làm việc cho khách hàng của khách hàng.** Người trả tiền và người dùng thường không phải một người; thiết kế tốt phục vụ người dùng cuối.
- **Nói chuyện với đúng người, bằng đúng ngôn ngữ của họ.** Nói với người kinh doanh bằng ngôn ngữ kinh doanh, với kỹ sư bằng ngôn ngữ kỹ thuật.

## Quy trình

### 1. Hiểu yêu cầu chức năng

Hệ thống phải làm được gì, luồng nghiệp vụ đi thế nào. Việc này làm ngay sau khi chốt mục tiêu.

### 2. Hiểu yêu cầu phi chức năng

Hệ thống phải chịu được những gì. Đây là phần hay bị bỏ qua và hay làm hỏng dự án:

- **Hiệu năng:** độ trễ (thời gian xong một việc) và thông lượng (số việc trong một khoảng thời gian). Hai thứ này khác nhau và tối ưu khác nhau.
- **Tải**, khối lượng dữ liệu, số người dùng đồng thời.
- **SLA** — cam kết mức dịch vụ.
- **Chất lượng:** khả năng mở rộng, khả năng quản lý (hệ thống phải tự báo vấn đề), tính mô-đun, khả năng mở rộng thêm, khả năng kiểm thử.

> Đừng bao giờ bắt đầu làm một hệ thống trước khi chốt yêu cầu.

### 3. Vẽ ra các thành phần

Web app, web API, ứng dụng di động, ứng dụng desktop, ứng dụng console, các service. Việc này chỉ là liệt kê; đừng để nó thành thiết kế.

### 4. Chọn công nghệ

Bốn câu hỏi để tự trả lời:

1. Công nghệ này có giải quyết được vấn đề không?
2. Nó đã chín chưa?
3. Nó có phổ biến không?
4. Nó có dễ bảo trì không?

Phổ biến quan trọng hơn nghe có vẻ: nghĩa là có tài liệu, có cộng đồng, và có người tuyển được. Riêng phần dữ liệu thì câu hỏi đầu tiên luôn là SQL hay NoSQL — và câu trả lời nên dựa trên cách dữ liệu thật sự được truy vấn.

### 5. Thiết kế hệ thống

#### Kiến trúc thành phần

Chia thành các thành phần ít phụ thuộc nhau, mỗi cái chịu một việc:

- **Giao diện người dùng** — xử lý JSON, xác thực.
- **Logic nghiệp vụ** — kiểm tra hợp lệ, làm giàu dữ liệu, tính toán.
- **Truy cập dữ liệu** — cơ sở dữ liệu, hệ thống tệp, service bên ngoài.

Cần chú ý tới giao diện giữa các thành phần, tiêm phụ thuộc (DI), nguyên tắc SOLID và quy ước đặt tên.

#### Xử lý ngoại lệ

- Đừng bọc try/catch chỉ để ghi log.
- Chỉ bắt những ngoại lệ cụ thể mà mình biết cách xử lý.
- Đặt try/catch quanh khối code nhỏ nhất có thể.

#### Ghi log

- Theo dõi lỗi.
- Thu thập dữ liệu: hiệu năng, mô-đun nào được dùng nhiều nhất, luồng đi của người dùng.

#### Bộ nhớ đệm, bảo mật

Hai phần này thường được nhắc tới sau cùng nhưng lại hay quyết định chi phí vận hành.

#### Mẫu thiết kế

Cần đọc thêm về Factory, Repository, Facade — nhưng đọc để hiểu vấn đề chúng giải quyết, không phải để áp vào chỗ không cần.

### 6. Viết tài liệu kiến trúc

Viết bằng ngôn ngữ đơn giản nhất có thể. Có chỗ nên vẽ hình.

Cấu trúc mình dùng:

- **Bối cảnh** — theo góc nhìn kinh doanh: hệ thống này là gì, vai trò của nó, lý do cần thay đổi, tác động dự kiến. Phần "vì sao" giúp kiểm chứng quan điểm và bảo đảm hai bên đang hiểu giống nhau.
- **Yêu cầu**
  - Chức năng: không quá năm mục, mỗi mục không quá ba dòng. Ngắn và súc tích là đủ.
  - Phi chức năng: phải cực kỳ chính xác và cụ thể.
- **Tóm tắt điều hành** — viết theo ngôn ngữ của người đọc, dùng biểu đồ nếu cần, và **viết sau cùng**, khi các phần khác đã xong.
- **Tổng quan kiến trúc**
  - Mô tả chung: loại hệ thống, yêu cầu chính.
  - Sơ đồ mức cao: chỉ sơ đồ logic, đừng trộn với phần cứng vật lý.
  - Cách hệ thống vận hành: các thành phần tương tác với nhau ra sao.
- **Các thành phần** — phần quan trọng nhất.
  - Vai trò.
  - Ngăn xếp công nghệ.
  - Kiến trúc bên trong: mô tả API (kể cả tên phương thức), mô tả các tầng theo SOLID.
  - Hướng dẫn phát triển.
- **Triển khai hệ thống.**

## Một vài mẫu thiết kế

### Singleton

Lớp chỉ có đúng một thực thể, và cung cấp một điểm truy cập toàn cục tới nó. Dùng khi cần tải lười hoặc thật sự chỉ cần một thực thể.

### Factory

Nhà máy tạo đối tượng. Thường đi kèm với tiêm phụ thuộc.

### Repository

Mô-đun không liên quan tới kho dữ liệu thì không cần biết dữ liệu được lấy ra thế nào.

### Facade

Chia thành tầng để che đi sự phức tạp bên dưới.

### Command

Mọi thông tin cần để thực hiện một hành động được đóng gói trong một đối tượng lệnh. Bên gọi lệnh không cần biết lệnh đó làm gì — chỉ cần thực thi nó.

## Ghi chú cuối

Mấy cái này nghe hiển nhiên khi viết ra, nhưng phần khó là giữ được chúng khi dự án đang cháy. Chỗ mình thấy hữu ích nhất trong cả danh sách là yêu cầu phi chức năng: viết chúng ra sớm, cụ thể, và bắt mọi người đọc trước khi code.
