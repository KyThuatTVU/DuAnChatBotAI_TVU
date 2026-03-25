# ⚠️ QUAN TRỌNG: Chạy Migration Ngay

## Tình trạng hiện tại
Hệ thống đang hoạt động ở chế độ tương thích ngược (fallback mode):
- ✅ Có thể xem danh sách câu hỏi
- ✅ Có thể duyệt câu hỏi
- ✅ Có thể sửa câu hỏi
- ⚠️ NHƯNG không lưu được thông tin người chỉnh sửa cuối cùng

## Tại sao cần chạy migration?
Trường `updated_by` chưa tồn tại trong database, nên:
- Không lưu được người chỉnh sửa cuối cùng
- Chỉ hiển thị người duyệt (approved_by)
- Mất thông tin audit trail quan trọng

## Cách chạy migration

### ⚠️ Lỗi "Access denied to information_schema"?
Nếu gặp lỗi này, xem file: **HUONG_DAN_MIGRATION_BUOC_BUOC.md**

### Cách 1: File đơn giản (Khuyến nghị)
Dùng file migration đơn giản không cần kiểm tra điều kiện:

**Qua phpMyAdmin:**
1. Mở phpMyAdmin: http://localhost/phpmyadmin
2. Đăng nhập với user `root` (hoặc user có quyền ALTER)
3. Chọn database `chatbot_thuvien`
4. Click tab "SQL"
5. Copy toàn bộ nội dung file `database/add_updated_by_simple.sql`
6. Paste vào ô SQL
7. Click "Go" hoặc "Thực hiện"

**Qua Command Line:**
```bash
# Windows (XAMPP)
cd C:\xampp\htdocs\DuAnChatbotThuVien
C:\xampp\mysql\bin\mysql.exe -u root -p chatbot_thuvien < database/add_updated_by_simple.sql

# Linux/Mac
cd /path/to/DuAnChatbotThuVien
mysql -u root -p chatbot_thuvien < database/add_updated_by_simple.sql
```

### Cách 2: Chạy từng câu lệnh (An toàn nhất)
Xem hướng dẫn chi tiết trong file: **HUONG_DAN_MIGRATION_BUOC_BUOC.md**

Tóm tắt:
```sql
-- 1. Thêm cột
ALTER TABLE questions 
ADD COLUMN updated_by INT NULL 
COMMENT 'ID admin chỉnh sửa cuối cùng' 
AFTER created_by;

-- 2. Thêm foreign key
ALTER TABLE questions 
ADD CONSTRAINT fk_questions_updated_by 
FOREIGN KEY (updated_by) REFERENCES admins(id) ON DELETE SET NULL;

-- 3. Thêm index
ALTER TABLE questions 
ADD INDEX idx_updated_by (updated_by);

-- 4. Kiểm tra
DESCRIBE questions;
```

## Kiểm tra sau khi chạy

### 1. Kiểm tra cột đã tồn tại
```sql
SHOW COLUMNS FROM questions LIKE 'updated_by';
```

Kết quả mong đợi:
```
Field       | Type    | Null | Key | Default | Extra
updated_by  | int     | YES  | MUL | NULL    |
```

### 2. Kiểm tra foreign key
```sql
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'questions'
  AND CONSTRAINT_NAME = 'fk_questions_updated_by';
```

### 3. Test thử
1. Vào trang quản lý câu hỏi
2. Sửa một câu hỏi bất kỳ
3. Lưu lại
4. Kiểm tra database:
```sql
SELECT id, question_text, updated_by, updated_at 
FROM questions 
ORDER BY updated_at DESC 
LIMIT 5;
```

Nếu thấy `updated_by` có giá trị (ID admin) là thành công!

## Sau khi chạy migration thành công

### Reload trang
1. Mở trang quản lý câu hỏi
2. Nhấn Ctrl+F5 (hard refresh) để clear cache
3. Kiểm tra cột "Trạng thái" có hiển thị tên người chỉnh sửa không

### Xóa code tương thích ngược (Optional)
Sau khi chạy migration thành công, có thể xóa các đoạn code kiểm tra `SHOW COLUMNS` trong `QuestionModel.php` để code gọn hơn. Nhưng giữ lại cũng không sao, nó không ảnh hưởng hiệu suất.

## Troubleshooting

### Lỗi: "Column 'updated_by' already exists"
→ Cột đã tồn tại rồi, không cần chạy migration nữa. Reload trang là được.

### Lỗi: "Cannot add foreign key constraint"
→ Có thể do:
1. Bảng `admins` không tồn tại
2. Cột `admins.id` không phải PRIMARY KEY
3. Có giá trị `updated_by` không tồn tại trong `admins.id`

Giải quyết:
```sql
-- Kiểm tra bảng admins
SHOW TABLES LIKE 'admins';

-- Kiểm tra cấu trúc
DESCRIBE admins;

-- Xóa giá trị không hợp lệ (nếu có)
UPDATE questions SET updated_by = NULL WHERE updated_by NOT IN (SELECT id FROM admins);

-- Thử lại
ALTER TABLE questions 
ADD CONSTRAINT fk_questions_updated_by 
FOREIGN KEY (updated_by) REFERENCES admins(id) ON DELETE SET NULL;
```

### Lỗi: "Access denied"
→ User MySQL không có quyền ALTER TABLE. Dùng user `root` hoặc user có quyền cao hơn.

## Liên hệ
Nếu gặp vấn đề, kiểm tra:
1. File log PHP: `C:\xampp\apache\logs\error.log`
2. File log MySQL: `C:\xampp\mysql\data\*.err`
3. Console browser (F12) để xem lỗi JavaScript

---
**Lưu ý**: Hệ thống vẫn hoạt động bình thường ngay cả khi chưa chạy migration, nhưng sẽ không lưu được thông tin người chỉnh sửa cuối cùng.
