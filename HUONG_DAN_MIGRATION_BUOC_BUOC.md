# Hướng dẫn Migration Từng Bước (Không cần quyền information_schema)

## Vấn đề
User `exampleuser` không có quyền truy cập `information_schema`, nên không thể chạy file migration có điều kiện kiểm tra.

## Giải pháp: Chạy từng câu lệnh

### Bước 1: Mở phpMyAdmin
1. Truy cập: http://localhost/phpmyadmin
2. Đăng nhập với user có quyền (thường là `root`)
3. Chọn database `chatbot_thuvien` ở sidebar bên trái

### Bước 2: Thêm cột updated_by

Vào tab "SQL" và chạy câu lệnh này:

```sql
ALTER TABLE questions 
ADD COLUMN updated_by INT NULL 
COMMENT 'ID admin chỉnh sửa cuối cùng' 
AFTER created_by;
```

**Kết quả mong đợi:**
- ✅ "Query OK, X rows affected" → Thành công
- ⚠️ "Duplicate column name 'updated_by'" → Cột đã tồn tại, bỏ qua, chuyển bước 3

### Bước 3: Thêm Foreign Key

Chạy câu lệnh này:

```sql
ALTER TABLE questions 
ADD CONSTRAINT fk_questions_updated_by 
FOREIGN KEY (updated_by) REFERENCES admins(id) ON DELETE SET NULL;
```

**Kết quả mong đợi:**
- ✅ "Query OK" → Thành công
- ⚠️ "Duplicate key name" hoặc "Constraint already exists" → Đã tồn tại, bỏ qua, chuyển bước 4

**Nếu lỗi "Cannot add foreign key constraint":**

Có thể do có giá trị `updated_by` không hợp lệ. Chạy câu này trước:

```sql
-- Xóa giá trị không hợp lệ
UPDATE questions 
SET updated_by = NULL 
WHERE updated_by IS NOT NULL 
  AND updated_by NOT IN (SELECT id FROM admins);
```

Sau đó chạy lại câu ALTER TABLE ở trên.

### Bước 4: Thêm Index

Chạy câu lệnh này:

```sql
ALTER TABLE questions 
ADD INDEX idx_updated_by (updated_by);
```

**Kết quả mong đợi:**
- ✅ "Query OK" → Thành công
- ⚠️ "Duplicate key name" → Index đã tồn tại, bỏ qua

### Bước 5: Kiểm tra kết quả

Chạy câu lệnh này để xem cột đã được tạo chưa:

```sql
DESCRIBE questions;
```

Tìm dòng có `Field` = `updated_by`. Nếu thấy là thành công!

Hoặc chạy câu này để xem chi tiết:

```sql
SHOW COLUMNS FROM questions LIKE 'updated_by';
```

**Kết quả mong đợi:**
```
Field       | Type | Null | Key | Default | Extra
updated_by  | int  | YES  | MUL | NULL    |
```

### Bước 6: Test thử

1. Reload trang quản lý câu hỏi (Ctrl+F5)
2. Sửa một câu hỏi bất kỳ
3. Lưu lại
4. Quay lại phpMyAdmin, chạy:

```sql
SELECT id, question_text, updated_by, updated_at 
FROM questions 
ORDER BY updated_at DESC 
LIMIT 5;
```

Nếu thấy `updated_by` có giá trị (ID admin của bạn) là thành công!

## Nếu không có quyền root

### Cách 1: Xin quyền từ admin
Liên hệ admin server để được cấp quyền:
```sql
GRANT ALTER, REFERENCES, INDEX ON chatbot_thuvien.* TO 'exampleuser'@'%';
FLUSH PRIVILEGES;
```

### Cách 2: Dùng user root tạm thời
1. Mở phpMyAdmin
2. Đăng xuất user hiện tại
3. Đăng nhập lại với:
   - Username: `root`
   - Password: (để trống hoặc password root của bạn)
4. Chạy migration
5. Đăng xuất và đăng nhập lại với user thường

### Cách 3: Chỉnh file config
Nếu bạn là người quản lý server, sửa file `.env`:

```env
# Thay đổi từ
DB_USER=exampleuser

# Sang
DB_USER=root
```

Sau khi chạy migration xong, đổi lại.

## Kiểm tra quyền hiện tại

Để xem user hiện tại có quyền gì:

```sql
SHOW GRANTS FOR CURRENT_USER();
```

Hoặc:

```sql
SHOW GRANTS FOR 'exampleuser'@'%';
```

## Troubleshooting

### Lỗi: "Access denied for user 'exampleuser'"
→ User không có quyền ALTER TABLE. Dùng user `root` hoặc xin quyền từ admin.

### Lỗi: "Table 'questions' doesn't exist"
→ Sai database. Đảm bảo đã chọn database `chatbot_thuvien`.

### Lỗi: "Cannot add foreign key constraint"
→ Chạy câu UPDATE ở Bước 3 để xóa giá trị không hợp lệ.

### Lỗi: "Duplicate column name 'updated_by'"
→ Cột đã tồn tại rồi! Không cần làm gì, chuyển sang kiểm tra kết quả.

## Sau khi hoàn thành

1. **Reload trang**: Ctrl+F5 trên trang quản lý câu hỏi
2. **Kiểm tra**: Sửa một câu hỏi và xem có hiển thị tên người chỉnh sửa không
3. **Xóa file migration cũ** (optional): Các file có điều kiện kiểm tra không cần nữa

---

**Lưu ý quan trọng**: 
- Luôn backup database trước khi chạy migration
- Nếu không chắc chắn, hãy test trên database dev trước
- Có thể chạy từng câu lệnh một để dễ debug nếu có lỗi
