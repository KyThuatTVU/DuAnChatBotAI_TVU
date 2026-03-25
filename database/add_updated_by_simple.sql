-- =====================================================
-- Thêm trường updated_by (Phiên bản đơn giản)
-- File: add_updated_by_simple.sql
-- Không cần quyền information_schema
-- =====================================================

-- Thêm cột updated_by
-- Nếu cột đã tồn tại, MySQL sẽ báo lỗi nhưng không sao, bỏ qua và chạy tiếp
ALTER TABLE questions 
ADD COLUMN updated_by INT NULL 
COMMENT 'ID admin chỉnh sửa cuối cùng' 
AFTER created_by;

-- Thêm foreign key
-- Nếu đã tồn tại, sẽ báo lỗi nhưng không sao
ALTER TABLE questions 
ADD CONSTRAINT fk_questions_updated_by 
FOREIGN KEY (updated_by) REFERENCES admins(id) ON DELETE SET NULL;

-- Thêm index
-- Nếu đã tồn tại, sẽ báo lỗi nhưng không sao
ALTER TABLE questions 
ADD INDEX idx_updated_by (updated_by);

-- Kiểm tra kết quả
SELECT 'Migration completed! Checking results...' AS status;

DESCRIBE questions;
