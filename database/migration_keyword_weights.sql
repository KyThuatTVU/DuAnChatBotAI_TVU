-- =====================================================
-- MIGRATION: Thêm trọng số cho từ khóa
-- Mục đích: Phân biệt từ khóa quan trọng/phổ biến
-- Ngày: 2026-03-18
-- =====================================================

USE chatbot_thuvien;

-- Thêm cột weight (trọng số) cho bảng keywords
ALTER TABLE keywords 
ADD COLUMN weight FLOAT NOT NULL DEFAULT 1.0 COMMENT 'Trọng số từ khóa (1-10, càng cao càng quan trọng)' AFTER keyword;

-- Thêm index để tối ưu tìm kiếm
ALTER TABLE keywords ADD INDEX idx_weight (weight);

-- Cập nhật trọng số cho các từ khóa hiện có (mặc định = 5.0)
UPDATE keywords SET weight = 5.0 WHERE weight = 1.0;

-- Tạo bảng cache IDF để tăng tốc tính toán
CREATE TABLE IF NOT EXISTS keyword_idf_cache (
    keyword VARCHAR(255) PRIMARY KEY,
    idf_score FLOAT NOT NULL COMMENT 'Điểm IDF (Inverse Document Frequency)',
    doc_count INT NOT NULL COMMENT 'Số câu hỏi chứa từ khóa này',
    total_docs INT NOT NULL COMMENT 'Tổng số câu hỏi khi tính',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_updated (updated_at),
    INDEX idx_idf (idf_score)
) ENGINE=InnoDB COMMENT='Cache điểm IDF cho từ khóa';

-- Stored Procedure: Tính toán và cập nhật IDF cache
DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS update_idf_cache()
BEGIN
    DECLARE total INT;
    
    -- Lấy tổng số câu hỏi active
    SELECT COUNT(*) INTO total FROM questions WHERE is_active = 1;
    
    -- Xóa cache cũ
    TRUNCATE TABLE keyword_idf_cache;
    
    -- Tính IDF cho mỗi từ khóa duy nhất
    INSERT INTO keyword_idf_cache (keyword, idf_score, doc_count, total_docs)
    SELECT 
        k.keyword,
        LOG(total / COUNT(DISTINCT k.question_id)) as idf_score,
        COUNT(DISTINCT k.question_id) as doc_count,
        total as total_docs
    FROM keywords k
    JOIN questions q ON k.question_id = q.id
    WHERE q.is_active = 1
    GROUP BY k.keyword;
    
    SELECT CONCAT('IDF cache updated: ', ROW_COUNT(), ' keywords processed') as result;
END$$

DELIMITER ;

-- Chạy lần đầu để tạo cache
CALL update_idf_cache();

-- Tạo Event tự động cập nhật IDF cache mỗi ngày lúc 2h sáng
-- (Chỉ chạy nếu MySQL Event Scheduler được bật)
CREATE EVENT IF NOT EXISTS daily_update_idf_cache
ON SCHEDULE EVERY 1 DAY
STARTS (TIMESTAMP(CURRENT_DATE) + INTERVAL 1 DAY + INTERVAL 2 HOUR)
DO CALL update_idf_cache();

-- Kiểm tra Event Scheduler (cần bật để Event hoạt động)
-- SET GLOBAL event_scheduler = ON;
