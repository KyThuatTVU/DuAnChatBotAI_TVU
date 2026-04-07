-- Tạo bảng thùng rác cho câu hỏi đã xóa
CREATE TABLE IF NOT EXISTS trash_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_question_id INT NOT NULL,
    question_text TEXT NOT NULL,
    answer_text TEXT,
    answer_text_en TEXT,
    category_id INT,
    keywords TEXT,
    auto_keywords_vi TEXT,
    auto_keywords_en TEXT,
    source VARCHAR(50) DEFAULT 'manual',
    approval_status VARCHAR(20) DEFAULT 'approved',
    created_at DATETIME,
    updated_at DATETIME,
    updated_by INT,
    deleted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    deleted_by INT,
    expires_at DATETIME,
    INDEX idx_expires_at (expires_at),
    INDEX idx_deleted_at (deleted_at),
    INDEX idx_original_id (original_question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng thùng rác cho danh mục đã xóa
CREATE TABLE IF NOT EXISTS trash_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    icon VARCHAR(100),
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME,
    deleted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    deleted_by INT,
    expires_at DATETIME,
    INDEX idx_expires_at (expires_at),
    INDEX idx_deleted_at (deleted_at),
    INDEX idx_original_id (original_category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng thùng rác cho biểu mẫu đã xóa
CREATE TABLE IF NOT EXISTS trash_forms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_form_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(500),
    file_size INT,
    download_count INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME,
    deleted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    deleted_by INT,
    expires_at DATETIME,
    INDEX idx_expires_at (expires_at),
    INDEX idx_deleted_at (deleted_at),
    INDEX idx_original_id (original_form_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo event tự động xóa các mục đã hết hạn (sau 24 giờ)
DELIMITER $$

DROP EVENT IF EXISTS auto_clean_trash$$

CREATE EVENT auto_clean_trash
ON SCHEDULE EVERY 1 HOUR
DO
BEGIN
    DELETE FROM trash_questions WHERE expires_at < NOW();
    DELETE FROM trash_categories WHERE expires_at < NOW();
    DELETE FROM trash_forms WHERE expires_at < NOW();
END$$

DELIMITER ;
