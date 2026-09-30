---
title: "Embedded C"
description: "Ghi chú Embedded C: storage class, con trỏ và hằng, căn chỉnh struct, các vùng nhớ, số dấu phẩy động, độ phủ MCDC và typedef struct."
---

# Embedded C

Ghi chú này là những chỗ mình hay phải nhớ lại khi làm việc với vi điều khiển.

## Storage class

### `static`

1. **Biến static cục bộ** — giá trị được giữ lại giữa các lần gọi hàm.
2. **Biến static toàn cục và hàm static** — phạm vi chỉ giới hạn trong file khai báo.

### `extern`

Dùng để khai báo một biến hoặc hàm được định nghĩa ở file khác.

## Con trỏ và `const`

Ba cách đặt `const` cho ra ba nghĩa khác nhau, và đọc từ phải sang trái sẽ rõ hơn:

```c
// p là con trỏ tới một biến uint8_t không đổi
uint8_t const *p = (uint8_t *)0x1234;

// p là con trỏ không đổi, trỏ tới một biến uint8_t
uint8_t *const p = (uint8_t *)0x1234;

// p vừa là con trỏ không đổi, vừa trỏ tới dữ liệu không đổi
uint8_t const *const p = (uint8_t *)0x1234;
```

Chỗ này quan trọng trong code nhúng vì địa chỉ thanh ghi thường cố định, còn giá trị trong đó thì thay đổi.

## Cấu trúc và căn chỉnh

Bộ biên dịch chèn padding để các trường được căn chỉnh theo địa chỉ. Muốn giảm lượng padding:

- **Xếp các trường cùng kiểu gần nhau.**
- **Sắp xếp theo kích thước, lớn nhất trước.**

Cách này giảm kích thước struct mà không cần tới `#pragma pack`, vốn có thể làm chậm truy cập.

## Các vùng nhớ

```
+------------------+
|   Text Segment   |  // Mã và dữ liệu chỉ đọc
+------------------+
| Initialized Data |  // Biến toàn cục và static có giá trị khởi tạo
+------------------+
| Uninitialized    |  // Biến toàn cục và static không có giá trị khởi tạo (BSS)
| Data (BSS)       |
+------------------+
|      Heap        |  // Cấp phát động (mọc lên)
+------------------+
|      Stack       |  // Biến cục bộ, tham số hàm (mọc xuống)
+------------------+
```

Với vi điều khiển, kích thước của mấy vùng này thường là ràng buộc thật, không phải chi tiết lý thuyết. Trên nhiều hệ thống nhúng, heap bị bỏ hẳn.

## Số dấu phẩy động

- `float`, `double`, `long double` dùng để biểu diễn số thực.
- Phải cân nhắc hiệu năng, bộ nhớ và độ chính xác khi dùng phép tính dấu phẩy động trong hệ nhúng.
- Phải chắc chắn vi điều khiển và trình biên dịch hỗ trợ số học dấu phẩy động.

```c
#include <stdio.h>

int main() {
    float a = 3.14f;
    double b = 3.141592653589793;
    long double c = 3.14159265358979323846L;

    printf("float: %f\n", a);
    printf("double: %lf\n", b);
    printf("long double: %Lf\n", c);

    return 0;
}
```

Nhiều vi điều khiển nhỏ không có đơn vị dấu phẩy động phần cứng. Lúc đó mọi phép tính dấu phẩy động được mô phỏng bằng phần mềm, và cái giá về thời gian thực thi thường lớn hơn người viết tưởng.

## Độ phủ MCDC

**MCDC** (Modified Condition/Decision Coverage) bảo đảm mỗi điều kiện trong một quyết định đã được kiểm chứng là có thể độc lập ảnh hưởng tới kết quả của quyết định đó.

Điểm thực hành: MCDC có thể đạt được bằng các công cụ gcov/lcov thông thường **nếu** các điều kiện trong code chỉ được viết ở dạng cây. Code trộn nhiều điều kiện trong một biểu thức phức tạp sẽ khó đo hơn nhiều.

## `typedef struct` và `struct`

- **`struct`**: phải viết từ khóa `struct` mỗi lần khai báo. Cách này làm rõ rằng đây là một kiểu cấu trúc.
- **`typedef struct`**: tạo bí danh cho kiểu, code gọn hơn. Cách này phổ biến trong thư viện và API.

Không có cái nào "đúng" hơn; quan trọng là nhất quán trong một codebase.

## Mức truy cập của vi xử lý

Mặc định code chạy ở chế độ đặc quyền (privileged mode). Hiểu điều này giúp giải thích vì sao một số lệnh chỉ chạy được trong kernel hoặc trong trình xử lý ngắt, còn code ứng dụng thì không.
