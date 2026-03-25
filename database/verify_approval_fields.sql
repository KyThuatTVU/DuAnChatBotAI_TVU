-- =====================================================
-- Kiểm tra và đảm bảo các trường phê duyệt tồn tại
-- File: verify_approval_fields.sql
-- Mục đích: Đảm bảo bảng questions có đầy đủ các trường để lưu thông tin người duyệt
-- =====================================================

-- Kiểm tra và thêm cột approval_status nếu chưa có
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'questions' 
  AND COLUMN_NAME = 'approval_status';

SET @sql = IF(@col_exists = 0,
    "ALTER TABLE questions ADD COLUMN approval_status ENUM('approved', 'pending') NOT NULL DEFAULT 'pending' COMMENT 'Trạng thái phê duyệt' AFTER is_active",
    "SELECT 'Column approval_status already exists' AS message");
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Kiểm tra và thêm cột approved_by nếu chưa có
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'questions' 
  AND COLUMN_NAME = 'approved_by';

SET @sql = IF(@col_exists = 0,
    "ALTER TABLE questions ADD COLUMN approved_by INT NULL COMMENT 'ID admin phê duyệt' AFTER approval_status",
    "SELECT 'Column approved_by already exists' AS message");
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Kiểm tra và thêm cột approved_at nếu chưa có
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'questions' 
  AND COLUMN_NAME = 'approved_at';

SET @sql = IF(@col_exists = 0,
    "ALTER TABLE questions ADD COLUMN approved_at DATETIME NULL COMMENT 'Thời gian phê duyệt' AFTER approved_by",
    "SELECT 'Column approved_at already exists' AS message");
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Kiểm tra và thêm foreign key cho approved_by nếu chưa có
SET @fk_exists = 0;
SELECT COUNT(*) INTO @fk_exists 
FROM information_schema.TABLE_CONSTRAINTS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'questions' 
  AND CONSTRAINT_NAME = 'fk_questions_approved_by';

SET @sql = IF(@fk_exists = 0,
    "ALTER TABLE questions ADD CONSTRAINT fk_questions_approved_by FOREIGN KEY (approved_by) REFERENCES admins(id) ON DELETE SET NULL",
    "SELECT 'Foreign key fk_questions_approved_by already exists' AS message");
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Kiểm tra và thêm index cho approval_status nếu chưa có
SET @idx_exists = 0;
SELECT COUNT(*) INTO @idx_exists 
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'questions' 
  AND INDEX_NAME = 'idx_approval_status';

SET @sql = IF(@idx_exists = 0,
    "ALTER TABLE questions ADD INDEX idx_approval_status (approval_status)",
    "SELECT 'Index idx_approval_status already exists' AS message");
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Hiển thị kết quả
SELECT 'Verification completed successfully!' AS status;
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'questions' 
  AND COLUMN_NAME IN ('approval_status', 'approved_by', 'approved_at')
ORDER BY ORDINAL_POSITION;
