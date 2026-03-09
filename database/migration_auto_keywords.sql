-- =====================================================
-- MIGRATION: Bổ sung chức năng tự động tạo từ khóa
-- Ngày: 2026-03-09
-- =====================================================

USE chatbot_thuvien;

-- Thêm cột phân biệt từ khóa tự động và thủ công
ALTER TABLE keywords 
ADD COLUMN is_auto TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Từ khóa tự động (1) hay thủ công (0)' AFTER keyword,
ADD COLUMN language ENUM('vi', 'en', 'both') NOT NULL DEFAULT 'vi' COMMENT 'Ngôn ngữ từ khóa' AFTER is_auto;

-- Thêm index cho tìm kiếm nhanh
ALTER TABLE keywords ADD INDEX idx_language (language);
ALTER TABLE keywords ADD INDEX idx_is_auto (is_auto);

-- Cập nhật các từ khóa hiện có là thủ công
UPDATE keywords SET is_auto = 0, language = 'vi' WHERE is_auto = 0;
