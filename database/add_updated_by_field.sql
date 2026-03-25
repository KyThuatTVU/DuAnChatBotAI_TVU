-- =====================================================
-- Thêm trường updated_by để lưu người chỉnh sửa cuối cùng
-- File: add_updated_by_field.sql
-- Mục đích: Theo dõi người chỉnh sửa cuối cùng thay vì chỉ người duyệt
-- =====================================================

-- Kiểm tra và thêm cột updated_by nếu chưa có
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'questions' 
  AND COLUMN_NAME = 'updated_by';

SET @sql = IF(@col_exists = 0,
    "ALTER TABLE questions ADD COLUMN updated_by INT NULL COMMENT 'ID admin chỉnh sửa cuối cùng' AFTER created_by",
    "SELECT 'Column updated_by already exists' AS message");
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Kiểm tra và thêm foreign key cho updated_by nếu chưa có
SET @fk_exists = 0;
SELECT COUNT(*) INTO @fk_exists 
FROM information_schema.TABLE_CONSTRAINTS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'questions' 
  AND CONSTRAINT_NAME = 'fk_questions_updated_by';

SET @sql = IF(@fk_exists = 0,
    "ALTER TABLE questions ADD CONSTRAINT fk_questions_updated_by FOREIGN KEY (updated_by) REFERENCES admins(id) ON DELETE SET NULL",
    "SELECT 'Foreign key fk_questions_updated_by already exists' AS message");
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Kiểm tra và thêm index cho updated_by nếu chưa có
SET @idx_exists = 0;
SELECT COUNT(*) INTO @idx_exists 
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'questions' 
  AND INDEX_NAME = 'idx_updated_by';

SET @sql = IF(@idx_exists = 0,
    "ALTER TABLE questions ADD INDEX idx_updated_by (updated_by)",
    "SELECT 'Index idx_updated_by already exists' AS message");
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Hiển thị kết quả
SELECT 'Migration completed successfully!' AS status;
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'questions' 
  AND COLUMN_NAME IN ('created_by', 'updated_by', 'approved_by', 'created_at', 'updated_at', 'approved_at')
ORDER BY ORDINAL_POSITION;
