# Hướng dẫn Chức năng Hiển thị Người Chỉnh Sửa Cuối Cùng

## Tổng quan
Chức năng này cho phép hệ thống hiển thị thông tin người chỉnh sửa cuối cùng cho mỗi câu hỏi, bao gồm:
- Tên người chỉnh sửa cuối cùng
- Email người chỉnh sửa (hiển thị khi hover)
- Thời gian chỉnh sửa cuối (định dạng thân thiện)

## Tại sao "Người chỉnh sửa" thay vì "Người duyệt"?

### Người duyệt (approved_by)
- Chỉ lưu khi thay đổi trạng thái duyệt (approved/pending)
- Không cập nhật khi sửa nội dung câu hỏi
- Chỉ phản ánh ai duyệt, không phản ánh ai sửa

### Người chỉnh sửa (updated_by)
- Lưu mỗi khi có bất kỳ thay đổi nào:
  - Sửa câu hỏi
  - Sửa câu trả lời
  - Thay đổi danh mục
  - Sửa từ khóa
  - Duyệt/bỏ duyệt
- Phản ánh chính xác ai là người tác động cuối cùng
- Hữu ích hơn cho audit trail

## Ví dụ thực tế

### Trường hợp 1: Sửa câu hỏi
```
User A tạo câu hỏi → created_by = A
User B sửa câu hỏi → updated_by = B, updated_at = now
User C duyệt câu hỏi → approved_by = C, approved_at = now, updated_by = C

→ Hiển thị: "User C" (người chỉnh sửa cuối = người duyệt)
```

### Trường hợp 2: Sửa sau khi duyệt
```
User A tạo câu hỏi → created_by = A
User B duyệt câu hỏi → approved_by = B, updated_by = B
User C sửa câu hỏi → updated_by = C, updated_at = now

→ Hiển thị: "User C" (người chỉnh sửa cuối ≠ người duyệt)
→ Trạng thái vẫn "Đã duyệt" (approved_by = B không đổi)
```

## Migration Database

Chạy file migration:
```bash
mysql -u root -p chatbot_thuvien < database/add_updated_by_field.sql
```

## Files đã thay đổi
1. `database/add_updated_by_field.sql` - Migration thêm trường updated_by
2. `app/models/QuestionModel.php` - Cập nhật query và update
3. `app/controllers/AdminController.php` - Truyền updated_by khi update
4. `public/assets/js/admin.js` - Logic hiển thị người chỉnh sửa
5. `public/pages/admin/questions.html` - Cập nhật cache version

## Tác giả
- **Ngày cập nhật**: 25/03/2026
- **Phiên bản**: 2.0
- **Hệ thống**: CELRAS TVU Chatbot
