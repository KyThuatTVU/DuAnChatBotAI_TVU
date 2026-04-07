# Hướng dẫn sử dụng chức năng Xóa nhiều câu hỏi chưa trả lời

## Tổng quan
Chức năng này cho phép admin chọn và xóa nhiều câu hỏi chưa trả lời cùng lúc, giúp quản lý hiệu quả hơn.

## Các thành phần đã cài đặt

### 1. Giao diện (unanswered.html)
- **Checkbox "Chọn tất cả"**: Ở đầu bảng để chọn/bỏ chọn tất cả câu hỏi
- **Checkbox từng hàng**: Mỗi câu hỏi có checkbox riêng để chọn
- **Nút "Xóa đã chọn"**: Hiện ở header khi có ít nhất 1 câu hỏi được chọn
- **Hiển thị số lượng**: Hiển thị số câu hỏi đã chọn trên nút xóa
- **Mobile support**: Có thanh chọn riêng cho mobile với nút xóa thu gọn

### 2. JavaScript (admin.js)

#### Hàm `renderUnanswered(items)`
- Render bảng câu hỏi với checkbox ở cột đầu tiên
- Mỗi checkbox có class `unanswered-checkbox` và `data-id` chứa ID câu hỏi
- Gọi `updateUnansweredSelectionUI()` khi checkbox thay đổi

#### Hàm `toggleSelectAllUnanswered(checkbox)`
- Chọn/bỏ chọn tất cả checkbox trong bảng
- Cập nhật UI sau khi thay đổi

#### Hàm `updateUnansweredSelectionUI()`
- Cập nhật trạng thái nút "Xóa đã chọn" (hiện/ẩn)
- Cập nhật số lượng câu hỏi đã chọn
- Cập nhật trạng thái checkbox "Chọn tất cả" (checked/indeterminate)
- Hỗ trợ cả desktop và mobile

#### Hàm `deleteMultipleUnanswered()`
- Lấy danh sách ID các câu hỏi đã chọn
- Hiển thị confirm dialog
- Gọi API `POST /api/admin/deleteMultipleUnanswered` với body `{ ids: [...] }`
- Hiển thị toast notification khi thành công
- Reload danh sách và reset UI

### 3. Backend API (AdminController.php)
- Endpoint: `POST /api/admin/deleteMultipleUnanswered`
- Input: JSON `{ "ids": [1, 2, 3, ...] }`
- Output: JSON `{ "success": true, "message": "..." }` hoặc `{ "error": "..." }`
- Xóa nhiều câu hỏi trong một transaction

## Cách sử dụng

### Cho người dùng:
1. Vào trang "Câu hỏi chưa trả lời" từ menu admin
2. Chọn các câu hỏi muốn xóa bằng cách tick vào checkbox
3. Hoặc tick "Chọn tất cả" để chọn tất cả câu hỏi
4. Nhấn nút "Xóa đã chọn (X)" ở góc trên bên phải
5. Xác nhận trong dialog
6. Hệ thống sẽ xóa và hiển thị thông báo thành công

### Trên mobile:
1. Thanh "Chọn tất cả" sẽ hiện ở trên bảng
2. Nút xóa thu gọn sẽ hiện bên cạnh khi có câu hỏi được chọn
3. Thao tác tương tự như desktop

## Tính năng nổi bật

✅ **Chọn nhiều**: Có thể chọn bất kỳ số lượng câu hỏi nào
✅ **Chọn tất cả**: Một click để chọn/bỏ chọn tất cả
✅ **Indeterminate state**: Checkbox "Chọn tất cả" hiển thị trạng thái trung gian khi chỉ chọn một phần
✅ **Real-time counter**: Hiển thị số lượng đã chọn trên nút xóa
✅ **Responsive**: Hoạt động tốt trên cả desktop và mobile
✅ **Toast notification**: Thông báo đẹp mắt khi xóa thành công
✅ **Confirm dialog**: Xác nhận trước khi xóa để tránh nhầm lẫn
✅ **Auto reload**: Tự động reload danh sách sau khi xóa

## Cấu trúc code

```
public/pages/admin/unanswered.html
├── Header với nút "Xóa đã chọn"
├── Mobile select bar
└── Table với checkbox column

public/assets/js/admin.js
├── renderUnanswered() - Render bảng với checkbox
├── toggleSelectAllUnanswered() - Toggle chọn tất cả
├── updateUnansweredSelectionUI() - Cập nhật UI
└── deleteMultipleUnanswered() - Xóa nhiều câu hỏi

app/controllers/AdminController.php
└── deleteMultipleUnanswered() - API endpoint
```

## Lưu ý kỹ thuật

- Sử dụng `data-id` attribute để lưu ID câu hỏi
- Checkbox có class `unanswered-checkbox` để dễ query
- Hỗ trợ indeterminate state cho UX tốt hơn
- Sử dụng `showToast()` thay vì `alert()` cho thông báo đẹp
- Transaction trong backend để đảm bảo data integrity
- Reset UI sau khi xóa để tránh state cũ

## Version
- Cài đặt: 06/04/2026
- Version admin.js: v=20260406
