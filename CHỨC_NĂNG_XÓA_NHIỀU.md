# Chức năng Xóa Nhiều - Hệ thống Admin

## Tổng quan
Đã thêm chức năng xóa nhiều mục cùng lúc cho tất cả các phần quản lý trong admin.

## Các phần đã cập nhật

### 1. ✅ Quản lý Câu hỏi (Questions)
- **Backend:**
  - `QuestionModel::deleteMultiple()` - Xóa nhiều câu hỏi theo danh sách ID
  - `AdminController::deleteMultipleQuestions()` - Endpoint `/api/admin/deleteMultipleQuestions`
  - Tự động xóa từ khóa liên quan trước khi xóa câu hỏi

- **Frontend:**
  - Checkbox "Chọn tất cả" ở header bảng
  - Checkbox cho mỗi dòng câu hỏi
  - Nút "Xóa đã chọn" hiển thị số lượng đã chọn
  - Hàm `toggleSelectAll()`, `updateSelectedCount()`, `deleteMultipleQuestions()`

### 2. ✅ Quản lý Danh mục (Categories)
- **Backend:**
  - `CategoryModel::deleteMultiple()` - Xóa nhiều danh mục
  - `AdminController::deleteMultipleCategories()` - Endpoint `/api/admin/deleteMultipleCategories`
  - Tự động gỡ liên kết với câu hỏi (set category_id = NULL)

- **Frontend:**
  - Hàm `deleteMultipleCategories()` - Xóa nhiều danh mục đã chọn

### 3. ✅ Quản lý Biểu mẫu (Forms)
- **Backend:**
  - `FormModel::deleteMultiple()` - Xóa nhiều biểu mẫu
  - `AdminController::deleteMultipleForms()` - Endpoint `/api/admin/deleteMultipleForms`

- **Frontend:**
  - Hàm `deleteMultipleForms()` - Xóa nhiều biểu mẫu đã chọn

### 4. ✅ Câu hỏi chưa trả lời (Unanswered)
- **Backend:**
  - `AdminController::deleteMultipleUnanswered()` - Endpoint `/api/admin/deleteMultipleUnanswered`

- **Frontend:**
  - Hàm `deleteMultipleUnanswered()` - Xóa nhiều câu hỏi chưa trả lời đã chọn

## Tính năng chung

### Xác nhận trước khi xóa
- Hiển thị số lượng mục sẽ bị xóa
- Cảnh báo về hành động không thể hoàn tác
- Thông báo đặc biệt cho danh mục (gỡ liên kết câu hỏi)

### Xử lý lỗi
- Validate danh sách ID trước khi xóa
- Xử lý exception và trả về thông báo lỗi rõ ràng
- Kiểm tra quyền admin trước khi thực hiện

### Trải nghiệm người dùng
- Tự động tải lại danh sách sau khi xóa thành công
- Reset checkbox "Chọn tất cả" sau khi xóa
- Hiển thị/ẩn nút xóa dựa trên số lượng đã chọn
- Cập nhật số lượng đã chọn real-time

## API Endpoints

```
POST /api/admin/deleteMultipleQuestions
POST /api/admin/deleteMultipleCategories
POST /api/admin/deleteMultipleForms
POST /api/admin/deleteMultipleUnanswered
```

### Request Body
```json
{
  "ids": [1, 2, 3, 4, 5]
}
```

### Response Success
```json
{
  "success": true,
  "message": "Đã xóa 5 câu hỏi",
  "deleted_count": 5
}
```

### Response Error
```json
{
  "error": "Danh sách ID không hợp lệ"
}
```

## Cách sử dụng

### Cho người dùng:
1. Chọn các mục muốn xóa bằng checkbox
2. Click nút "Xóa đã chọn (X)" ở góc trên
3. Xác nhận trong hộp thoại
4. Hệ thống tự động xóa và tải lại danh sách

### Cho developer:
- Tất cả các hàm xóa nhiều đều có pattern tương tự
- Dễ dàng mở rộng cho các module khác
- Code đã được validate và không có lỗi syntax

## Ghi chú
- Chức năng đã được test và không có lỗi syntax
- Tương thích với hệ thống routing hiện tại
- Tuân thủ chuẩn code của dự án
