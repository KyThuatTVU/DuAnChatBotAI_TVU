-- =====================================================
-- FIX: Thêm cột weight cho bảng keywords
-- Ngày: 2026-03-19
-- =====================================================

USE chatbot_thuvien;

-- Thêm cột weight (bỏ qua lỗi nếu cột đã tồn tại)
SET @sql = 'ALTER TABLE keywords ADD COLUMN weight FLOAT NOT NULL DEFAULT 1.0 COMMENT ''Trọng số từ khóa'' AFTER keyword';
SET @check = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_SCHEMA = 'chatbot_thuvien' 
              AND TABLE_NAME = 'keywords' 
              AND COLUMN_NAME = 'weight');

-- Chỉ chạy nếu cột chưa tồn tại
SET @sql = IF(@check = 0, @sql, 'SELECT ''Cột weight đã tồn tại'' as status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Thêm index (bỏ qua lỗi nếu index đã tồn tại)
SET @sql_idx = 'ALTER TABLE keywords ADD INDEX idx_weight (weight)';
SET @check_idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
                  WHERE TABLE_SCHEMA = 'chatbot_thuvien' 
                  AND TABLE_NAME = 'keywords' 
                  AND INDEX_NAME = 'idx_weight');

SET @sql_idx = IF(@check_idx = 0, @sql_idx, 'SELECT ''Index idx_weight đã tồn tại'' as status');
PREPARE stmt_idx FROM @sql_idx;
EXECUTE stmt_idx;
DEALLOCATE PREPARE stmt_idx;

-- Cập nhật trọng số mặc định cho các từ khóa hiện có
UPDATE keywords SET weight = 1.0 WHERE weight IS NULL OR weight = 0;

SELECT 'Migration hoàn tất!' as status;
