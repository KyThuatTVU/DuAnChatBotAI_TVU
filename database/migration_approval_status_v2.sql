-- =====================================================
-- Migration V2: Đơn giản hóa - Chỉ 2 trạng thái
-- Ngày cập nhật: 2026-03-20
-- Lệnh SQL tối ưu duy nhất (chạy 1 lần)
-- =====================================================

-- Bước 1: Cập nhật các câu hỏi rejected (nếu có) thành pending TRƯỚC
UPDATE questions 
SET approval_status = 'pending' 
WHERE approval_status = 'rejected';

-- Bước 2: Sửa lại cột approval_status chỉ còn 2 giá trị SAU
ALTER TABLE questions 
MODIFY COLUMN approval_status ENUM('pending', 'approved') NOT NULL DEFAULT 'pending' 
COMMENT 'Trạng thái phê duyệt: pending (chưa duyệt), approved (đã duyệt)';

-- =====================================================
-- Hoàn tất - Chỉ cần chạy file này 1 lần duy nhất
-- =====================================================
