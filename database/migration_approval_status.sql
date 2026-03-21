-- =====================================================
-- Migration: Thêm chức năng phê duyệt câu hỏi
-- Ngày tạo: 2026-03-20
-- Mô tả: Thêm cột approval_status vào bảng questions
-- =====================================================

-- Thêm cột approval_status vào bảng questions
ALTER TABLE questions 
ADD COLUMN approval_status ENUM('pending', 'approved') NOT NULL DEFAULT 'pending' 
COMMENT 'Trạng thái phê duyệt: pending (chưa duyệt), approved (đã duyệt)' 
AFTER is_active;

-- Thêm cột approved_by để lưu người phê duyệt
ALTER TABLE questions 
ADD COLUMN approved_by INT NULL 
COMMENT 'ID admin phê duyệt' 
AFTER approval_status;

-- Thêm cột approved_at để lưu thời gian phê duyệt
ALTER TABLE questions 
ADD COLUMN approved_at DATETIME NULL 
COMMENT 'Thời gian phê duyệt' 
AFTER approved_by;

-- Thêm foreign key cho approved_by
ALTER TABLE questions 
ADD CONSTRAINT fk_questions_approved_by 
FOREIGN KEY (approved_by) REFERENCES admins(id) ON DELETE SET NULL;

-- Thêm index cho approval_status để tăng tốc query
ALTER TABLE questions 
ADD INDEX idx_approval_status (approval_status);

-- Cập nhật tất cả câu hỏi hiện có thành trạng thái 'approved' (đã duyệt)
-- Vì đây là dữ liệu cũ, mặc định coi như đã được duyệt
UPDATE questions 
SET approval_status = 'approved', 
    approved_at = created_at;

-- =====================================================
-- Hoàn tất migration
-- =====================================================
