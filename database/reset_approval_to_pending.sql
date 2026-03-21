-- =====================================================
-- Reset tất cả câu hỏi đã duyệt về trạng thái chưa duyệt
-- Ngày tạo: 2026-03-20
-- Mục đích: Chuyển tất cả câu hỏi có approval_status = 'approved' về 'pending'
-- =====================================================

-- Cập nhật tất cả câu hỏi đã duyệt về trạng thái chưa duyệt
UPDATE questions 
SET approval_status = 'pending' 
WHERE approval_status = 'approved';

-- Kiểm tra kết quả
SELECT 
    approval_status,
    COUNT(*) as total
FROM questions
GROUP BY approval_status;

-- =====================================================
-- Kết quả mong đợi:
-- - Tất cả câu hỏi sẽ có approval_status = 'pending'
-- =====================================================
