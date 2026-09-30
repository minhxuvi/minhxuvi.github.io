---
title: "Kiểm thử phần mềm"
description: "Thuật ngữ kiểm thử, mục tiêu, phân biệt kiểm thử và gỡ lỗi, các giai đoạn của quy trình kiểm thử, và ghi chú theo chuẩn ISO/IEC/IEEE 29119."
---

# Kiểm thử phần mềm

## Thuật ngữ cơ bản

Trước hết là mấy chữ hay bị dùng lẫn:

| Thuật ngữ | Nghĩa |
| --- | --- |
| **Errors** | Sai sót do con người gây ra. |
| **Defects** | Lỗi nằm trong sản phẩm. |
| **Failures** | Sự hỏng, thất bại, tai nạn — có thể xảy ra hoặc không, tùy khuyết điểm. |
| **Root causes** | Nguyên nhân gốc rễ của vấn đề. |
| **Validation** | Xác nhận sản phẩm đáp ứng yêu cầu. Làm đúng đề bài. |
| **Verification** | Kiểm tra sản phẩm có đáp ứng yêu cầu không. Làm bài một cách đúng đắn. |
| **Static testing** | Kiểm thử tĩnh — kiểm tra mà không chạy chương trình. |
| **Dynamic testing** | Kiểm thử động — kiểm tra thông qua việc chạy chương trình. |
| **Test case** | Một bộ dữ liệu đầu vào và kết quả mong đợi. |
| **Test suite** | Một tập hợp các test case. |
| **Test plan** | Tài liệu mô tả cách thức thực hiện kiểm thử. |
| **Traceability** | Khả năng truy nguyên: liên kết giữa yêu cầu, thiết kế, mã nguồn và kiểm thử. |
| **Test coverage** | Phần trăm mã nguồn được kiểm thử. |

Chỗ dễ lẫn nhất là **validation** và **verification**: một cái hỏi "có làm đúng thứ khách cần không", cái kia hỏi "có làm đúng như đã đặc tả không". Một sản phẩm có thể pass hết verification mà vẫn fail validation, vì đặc tả sai ngay từ đầu.

## Mục tiêu của kiểm thử

- Tìm lỗi trong sản phẩm.
- Nâng cao độ tin cậy của phần mềm.
- Đảm bảo các yêu cầu của phần mềm được thỏa mãn.
- Kiểm tra các vấn đề hợp đồng, pháp lý, quy định.
- Cung cấp thông tin cho các bên liên quan.
- Xác minh rằng phần mềm hoạt động đúng như mong đợi.

## Kiểm thử và gỡ lỗi là hai việc khác nhau

**Kiểm thử để tìm ra lỗi; gỡ lỗi để sửa lỗi.** Gỡ lỗi là một hoạt động phát triển phần mềm, không phải kiểm thử. Gộp hai việc làm một thường dẫn tới chỗ người viết test bắt đầu sửa code trong lúc viết test, và mất luôn khả năng phát hiện lỗi một cách độc lập.

## Bảo đảm chất lượng và kiểm thử

**Bảo đảm chất lượng là một quy trình; kiểm thử là một phần trong quy trình đó.** Nói cách khác: kiểm thử không tạo ra chất lượng, nó đo chất lượng. Muốn chất lượng tốt thì phải sửa quy trình, không chỉ thêm test.

## Các giai đoạn của quy trình kiểm thử

### Lập kế hoạch và thiết kế

- Rà lại yêu cầu để loại bỏ những test case vô nghĩa.
- Kiểm thử theo thứ tự: yêu cầu → thiết kế → code. Kiểm thử yêu cầu trước, vì lỗi ở tầng yêu cầu là loại đắt nhất.
- Ưu tiên hóa các test case.
- Phát triển test case mới.
- Rà soát và cải thiện test case định kỳ.

### Triển khai kiểm thử

- Viết test case và script tự động.
- Lên lịch chạy kiểm thử.
- Dựng môi trường gần với môi trường khách hàng.
- Chuẩn bị dữ liệu kiểm thử.
- Xác minh và cập nhật truy vết hai chiều (bi-directional traceability).

### Thực thi kiểm thử

- Tự động hóa kiểm thử hồi quy (regression test).

### Kết thúc kiểm thử

- Lưu trữ môi trường kiểm thử — việc này hay bị bỏ sót.

## Hướng đáng thử: dùng AI trong khâu kiểm thử

Ghi chú của mình có mấy việc muốn thử, xếp theo thứ tự đáng làm:

- Dùng AI để rà yêu cầu trước khi viết test — tìm chỗ mơ hồ, chỗ thiếu điều kiện biên.
- Dùng AI để rà và cải thiện bộ test case định kỳ, thay vì chỉ viết thêm.
- Giữ việc ưu tiên hóa test case ở người: máy có xu hướng đánh đồng mọi case.

Hướng mình nghĩ là hữu ích nhất: để AI lo phần đọc lại và đặt câu hỏi, còn phần quyết định cái gì quan trọng thì vẫn phải là người hiểu hệ thống.

## Tài liệu tham khảo

- ISO/IEC/IEEE 29119 *Software Testing Standard*, Part 2: Test Processes.

Đây là chuẩn, không phải sách dạy nghề — đọc để tra thuật ngữ và đối chiếu quy trình, đừng đọc như công thức.
