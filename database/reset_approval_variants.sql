-- =====================================================
-- Các biến thể reset trạng thái duyệt
-- Ngày tạo: 2026-03-20
-- =====================================================

-- 1. Reset TẤT CẢ câu hỏi về chưa duyệt
UPDATE questions 
SET approval_status = 'pending';

-- 2. Reset chỉ câu hỏi đã duyệt về chưa duyệt
UPDATE questions 
SET approval_status = 'pending' 
WHERE approval_status = 'approved';

-- 3. Reset câu hỏi theo danh mục cụ thể (ví dụ: category_id = 1)
UPDATE questions 
SET approval_status = 'pending' 
WHERE approval_status = 'approved' 
AND category_id = 1;

-- 4. Reset câu hỏi theo khoảng thời gian (ví dụ: tạo sau ngày 2026-03-01)
UPDATE questions 
SET approval_status = 'pending' 
WHERE approval_status = 'approved' 
AND created_at >= '2026-03-01';

-- 5. Reset câu hỏi theo danh sách ID cụ thể
UPDATE questions 
SET approval_status = 'pending' 
WHERE id IN (1, 2, 3, 4, 5);

-- 6. Reset câu hỏi có từ khóa cụ thể trong câu hỏi
UPDATE questions 
SET approval_status = 'pending' 
WHERE approval_status = 'approved' 
AND question_text LIKE '%mượn sách%';

-- =====================================================
-- Kiểm tra kết quả sau khi chạy
-- =====================================================

-- Xem tổng quan trạng thái
SELECT 
    approval_status,
    COUNT(*) as total,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM questions), 2) as percentage
FROM questions
GROUP BY approval_status;

-- Xem chi tiết theo danh mục
SELECT 
    c.name as category_name,
    q.approval_status,
    COUNT(*) as total
FROM questions q
LEFT JOIN categories c ON q.category_id = c.id
GROUP BY c.name, q.approval_status
ORDER BY c.name, q.approval_status;

-- Xem 10 câu hỏi mới nhất và trạng thái
SELECT 
    id,
    question_text,
    approval_status,
    created_at
FROM questions
ORDER BY created_at DESC
LIMIT 10;
