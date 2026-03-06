-- =====================================================
-- CƠ SỞ DỮ LIỆU CHATBOT THƯ VIỆN
-- Ngày tạo: 2026-03-03
-- =====================================================

CREATE DATABASE IF NOT EXISTS chatbot_thuvien
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE chatbot_thuvien;

-- =====================================================
-- 1. BẢNG QUẢN TRỊ VIÊN (Đăng nhập Google)
-- =====================================================
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    google_id VARCHAR(255) NULL UNIQUE COMMENT 'Google OAuth ID',
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NULL COMMENT 'Mật khẩu băm (bcrypt) – NULL nếu chỉ dùng Google',
    full_name VARCHAR(255) NOT NULL,
    avatar_url TEXT NULL COMMENT 'Ảnh đại diện từ Google',
    role ENUM('super_admin', 'admin', 'editor') NOT NULL DEFAULT 'admin' COMMENT 'Phân quyền',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    reset_token VARCHAR(255) NULL COMMENT 'Token đặt lại mật khẩu',
    reset_token_expiry DATETIME NULL COMMENT 'Hạn token đặt lại mật khẩu',
    last_login DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='Bảng quản trị viên - đăng nhập bằng Google hoặc Email';

-- Migration: Thêm cột password và reset_token cho bảng admins đã tồn tại
-- ALTER TABLE admins MODIFY google_id VARCHAR(255) NULL;
-- ALTER TABLE admins ADD COLUMN password VARCHAR(255) NULL AFTER email;
-- ALTER TABLE admins ADD COLUMN reset_token VARCHAR(255) NULL AFTER is_active;
-- ALTER TABLE admins ADD COLUMN reset_token_expiry DATETIME NULL AFTER reset_token;

-- =====================================================
-- 2. BẢNG DANH MỤC CÂU HỎI
-- =====================================================
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'Tên danh mục (VD: Mượn sách, Thẻ thư viện...)',
    description TEXT NULL,
    icon VARCHAR(100) NULL COMMENT 'Icon hiển thị',
    sort_order INT NOT NULL DEFAULT 0 COMMENT 'Thứ tự sắp xếp',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Danh mục nhóm câu hỏi';

-- =====================================================
-- 3. BẢNG BỘ CÂU HỎI - TRẢ LỜI (Q&A)
-- =====================================================
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NULL,
    question_text TEXT NOT NULL COMMENT 'Nội dung câu hỏi',
    answer_text TEXT NOT NULL COMMENT 'Nội dung trả lời',
    source_type ENUM('manual', 'word', 'pdf') NOT NULL DEFAULT 'manual' COMMENT 'Phương thức nhập',
    dataset_id INT NULL COMMENT 'ID bộ dữ liệu nếu import từ file',
    priority INT NOT NULL DEFAULT 0 COMMENT 'Độ ưu tiên hiển thị',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL,
    FULLTEXT INDEX ft_question (question_text),
    FULLTEXT INDEX ft_answer (answer_text)
) ENGINE=InnoDB COMMENT='Bộ câu hỏi và trả lời cho chatbot';

-- =====================================================
-- 4. BẢNG TỪ KHÓA (Matching câu hỏi người dùng)
-- =====================================================
CREATE TABLE keywords (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    keyword VARCHAR(255) NOT NULL COMMENT 'Từ khóa liên quan đến câu hỏi',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    INDEX idx_keyword (keyword)
) ENGINE=InnoDB COMMENT='Từ khóa để matching câu hỏi người dùng';

-- =====================================================
-- 5. BẢNG CÂU HỎI GỢI Ý (Quick Replies)
-- =====================================================
CREATE TABLE suggested_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_text VARCHAR(500) NOT NULL COMMENT 'Câu hỏi gợi ý hiển thị cho người dùng',
    linked_question_id INT NULL COMMENT 'Liên kết đến câu hỏi có sẵn',
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (linked_question_id) REFERENCES questions(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Câu hỏi gợi ý hiển thị khi người dùng mở chatbot';

-- =====================================================
-- 6. BẢNG BỘ DỮ LIỆU IMPORT (Word, PDF)
-- =====================================================
CREATE TABLE datasets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_name VARCHAR(500) NOT NULL COMMENT 'Tên file gốc',
    file_path TEXT NOT NULL COMMENT 'Đường dẫn lưu trữ file',
    file_type ENUM('word', 'pdf') NOT NULL COMMENT 'Loại file',
    file_size BIGINT NOT NULL DEFAULT 0 COMMENT 'Kích thước file (bytes)',
    total_questions INT NOT NULL DEFAULT 0 COMMENT 'Số câu hỏi được import',
    status ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    error_message TEXT NULL COMMENT 'Thông báo lỗi nếu import thất bại',
    uploaded_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Quản lý file dữ liệu được upload (Word, PDF)';

-- Thêm FK cho questions.dataset_id
ALTER TABLE questions
ADD FOREIGN KEY (dataset_id) REFERENCES datasets(id) ON DELETE SET NULL;

-- =====================================================
-- 7. BẢNG CÀI ĐẶT GIAO DIỆN CHATBOT
-- =====================================================
CREATE TABLE chatbot_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE COMMENT 'Khóa cài đặt',
    setting_value TEXT NOT NULL COMMENT 'Giá trị cài đặt',
    setting_type ENUM('color', 'text', 'boolean', 'number', 'json') NOT NULL DEFAULT 'text',
    description VARCHAR(500) NULL COMMENT 'Mô tả cài đặt',
    updated_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Cài đặt giao diện và hiển thị chatbot';

-- =====================================================
-- 8. BẢNG CHỦ ĐỀ GIAO DIỆN THEO SỰ KIỆN
-- =====================================================
CREATE TABLE event_themes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    theme_name VARCHAR(255) NOT NULL COMMENT 'Tên chủ đề (VD: Tết, Noel, Khai giảng...)',
    primary_color VARCHAR(20) NOT NULL DEFAULT '#1976D2' COMMENT 'Màu chính',
    secondary_color VARCHAR(20) NOT NULL DEFAULT '#FFFFFF' COMMENT 'Màu phụ',
    header_bg_color VARCHAR(20) NOT NULL DEFAULT '#1976D2' COMMENT 'Màu nền header',
    header_text_color VARCHAR(20) NOT NULL DEFAULT '#FFFFFF' COMMENT 'Màu chữ header',
    user_bubble_color VARCHAR(20) NOT NULL DEFAULT '#E3F2FD' COMMENT 'Màu bong bóng người dùng',
    bot_bubble_color VARCHAR(20) NOT NULL DEFAULT '#F5F5F5' COMMENT 'Màu bong bóng bot',
    button_color VARCHAR(20) NOT NULL DEFAULT '#1976D2' COMMENT 'Màu nút chatbot',
    font_family VARCHAR(100) NULL DEFAULT 'Roboto, sans-serif',
    bot_avatar_url TEXT NULL COMMENT 'Ảnh avatar chatbot',
    welcome_message TEXT NULL COMMENT 'Tin nhắn chào mừng',
    start_date DATE NULL COMMENT 'Ngày bắt đầu áp dụng',
    end_date DATE NULL COMMENT 'Ngày kết thúc áp dụng',
    is_active TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Đang được kích hoạt',
    created_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Chủ đề giao diện chatbot theo sự kiện trong năm';

-- =====================================================
-- 9. BẢNG PHIÊN TRÒ CHUYỆN
-- =====================================================
CREATE TABLE chat_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_token VARCHAR(255) NOT NULL UNIQUE COMMENT 'Token định danh phiên',
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL COMMENT 'Thông tin trình duyệt',
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ended_at DATETIME NULL,
    total_messages INT NOT NULL DEFAULT 0,
    INDEX idx_session_token (session_token),
    INDEX idx_started_at (started_at)
) ENGINE=InnoDB COMMENT='Phiên trò chuyện của người dùng';

-- =====================================================
-- 10. BẢNG TIN NHẮN TRÒ CHUYỆN
-- =====================================================
CREATE TABLE chat_messages (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    sender_type ENUM('user', 'bot') NOT NULL COMMENT 'Người gửi: user hoặc bot',
    message_text TEXT NOT NULL COMMENT 'Nội dung tin nhắn',
    matched_question_id INT NULL COMMENT 'Câu hỏi được match (nếu bot trả lời)',
    confidence_score DECIMAL(5,4) NULL COMMENT 'Độ chính xác matching (0-1)',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES chat_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (matched_question_id) REFERENCES questions(id) ON DELETE SET NULL,
    INDEX idx_session_id (session_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB COMMENT='Lịch sử tin nhắn trò chuyện';

-- =====================================================
-- 11. BẢNG NHẬT KÝ HOẠT ĐỘNG QUẢN TRỊ
-- =====================================================
CREATE TABLE admin_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NULL,
    action VARCHAR(100) NOT NULL COMMENT 'Hành động (create, update, delete, login, ...)',
    target_table VARCHAR(100) NULL COMMENT 'Bảng bị tác động',
    target_id INT NULL COMMENT 'ID bản ghi bị tác động',
    old_value JSON NULL COMMENT 'Giá trị cũ',
    new_value JSON NULL COMMENT 'Giá trị mới',
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_admin_id (admin_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB COMMENT='Nhật ký hoạt động quản trị viên';

-- =====================================================
-- 12. BẢNG CÂU HỎI CHƯA TRẢ LỜI ĐƯỢC
-- =====================================================
CREATE TABLE unanswered_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NULL,
    question_text TEXT NOT NULL COMMENT 'Câu hỏi người dùng mà bot không trả lời được',
    frequency INT NOT NULL DEFAULT 1 COMMENT 'Số lần được hỏi',
    is_resolved TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Đã được xử lý chưa',
    resolved_by INT NULL,
    resolved_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES chat_sessions(id) ON DELETE SET NULL,
    FOREIGN KEY (resolved_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Lưu câu hỏi mà chatbot không trả lời được để admin bổ sung';

-- =====================================================
-- DỮ LIỆU MẪU BAN ĐẦU
-- =====================================================

-- Cài đặt mặc định cho chatbot
INSERT INTO chatbot_settings (setting_key, setting_value, setting_type, description) VALUES
('chatbot_enabled', 'true', 'boolean', 'Bật/Tắt hiển thị chatbot trên website'),
('chatbot_title', 'Chatbot Thư Viện', 'text', 'Tiêu đề chatbot'),
('welcome_message', 'Xin chào! Tôi là trợ lý thư viện. Bạn cần tôi giúp gì?', 'text', 'Tin nhắn chào mừng'),
('primary_color', '#1976D2', 'color', 'Màu chính của chatbot'),
('secondary_color', '#FFFFFF', 'color', 'Màu phụ của chatbot'),
('header_bg_color', '#1976D2', 'color', 'Màu nền header chatbot'),
('header_text_color', '#FFFFFF', 'color', 'Màu chữ header chatbot'),
('user_bubble_color', '#E3F2FD', 'color', 'Màu bong bóng chat người dùng'),
('bot_bubble_color', '#F5F5F5', 'color', 'Màu bong bóng chat bot'),
('button_color', '#1976D2', 'color', 'Màu nút mở chatbot'),
('button_position', '{"bottom": "20px", "right": "20px"}', 'json', 'Vị trí nút chatbot'),
('bot_avatar', '/assets/bot-avatar.png', 'text', 'Đường dẫn avatar bot'),
('no_answer_message', 'Cảm ơn bạn đã đặt câu hỏi! Hiện tại mình chưa tìm thấy thông tin phù hợp cho câu hỏi này. Bạn có thể thử hỏi lại theo cách khác, hoặc liên hệ trực tiếp với thủ thư qua số (02943) 855 246 hoặc email celras@tvu.edu.vn để được hỗ trợ tận tình nhé! 😊', 'text', 'Tin nhắn khi không tìm thấy câu trả lời'),
('max_suggestions', '5', 'number', 'Số câu hỏi gợi ý tối đa');

-- Danh mục mẫu
INSERT INTO categories (name, description, sort_order) VALUES
('Mượn trả sách', 'Các câu hỏi về quy trình mượn, trả sách', 1),
('Thẻ thư viện', 'Các câu hỏi về đăng ký, gia hạn thẻ thư viện', 2),
('Tra cứu tài liệu', 'Hướng dẫn tra cứu sách, tài liệu', 3),
('Giờ hoạt động', 'Thông tin giờ mở cửa, lịch nghỉ', 4),
('Dịch vụ khác', 'Photocopy, phòng đọc, wifi...', 5);

-- Câu hỏi mẫu
INSERT INTO questions (category_id, question_text, answer_text, source_type) VALUES
(1, 'Làm thế nào để mượn sách?', 'Để mượn sách, bạn cần có thẻ thư viện hợp lệ. Mang sách đến quầy mượn/trả và xuất trình thẻ thư viện. Bạn có thể mượn tối đa 5 cuốn sách trong 14 ngày.', 'manual'),
(1, 'Thời hạn mượn sách là bao lâu?', 'Thời hạn mượn sách là 14 ngày. Bạn có thể gia hạn thêm 7 ngày nếu sách chưa có người đặt trước.', 'manual'),
(2, 'Làm thẻ thư viện ở đâu?', 'Bạn có thể đăng ký làm thẻ thư viện tại quầy tiếp nhận (tầng 1). Cần mang theo CMND/CCCD và 1 ảnh 3x4. Phí làm thẻ là 50.000đ.', 'manual'),
(4, 'Thư viện mở cửa lúc mấy giờ?', 'Thư viện mở cửa từ 7:30 đến 21:00 các ngày trong tuần (Thứ 2 - Thứ 7). Chủ nhật nghỉ.', 'manual'),
(3, 'Làm sao tra cứu sách trên hệ thống?', 'Bạn có thể tra cứu sách qua hệ thống OPAC trên website thư viện hoặc tại các máy tính tra cứu đặt tại tầng 1.', 'manual');

-- Từ khóa mẫu
INSERT INTO keywords (question_id, keyword) VALUES
(1, 'mượn sách'), (1, 'mượn'), (1, 'borrow'),
(2, 'thời hạn'), (2, 'hạn mượn'), (2, 'bao lâu'),
(3, 'làm thẻ'), (3, 'đăng ký thẻ'), (3, 'thẻ thư viện'),
(4, 'giờ mở cửa'), (4, 'mấy giờ'), (4, 'thời gian'),
(5, 'tra cứu'), (5, 'tìm sách'), (5, 'OPAC');

-- Câu hỏi gợi ý
INSERT INTO suggested_questions (question_text, linked_question_id, sort_order) VALUES
('Làm thế nào để mượn sách?', 1, 1),
('Thư viện mở cửa lúc mấy giờ?', 4, 2),
('Làm thẻ thư viện ở đâu?', 3, 3),
('Tra cứu sách như thế nào?', 5, 4);

-- =====================================================
-- BẢNG BIỂU MẪU / GIẤY TỜ (Forms)
-- =====================================================
CREATE TABLE IF NOT EXISTS forms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'Tên biểu mẫu / giấy tờ',
    description TEXT NULL COMMENT 'Mô tả nội dung biểu mẫu',
    url VARCHAR(1000) NOT NULL COMMENT 'Đường dẫn tới trang biểu mẫu',
    keywords TEXT NULL COMMENT 'Từ khóa nhận diện, phân cách bằng dấu phẩy',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Biểu mẫu / giấy tờ – chatbot trả về link khi hỏi';

-- Dữ liệu mẫu forms
INSERT INTO forms (name, description, url, keywords) VALUES
('Đơn đăng ký mượn tài liệu đặc biệt', 'Mẫu đăng ký mượn tài liệu quý hiếm, luận văn, đề tài nghiên cứu', 'https://celri.tvu.edu.vn/bieu-mau', 'đơn mượn tài liệu,mẫu mượn,tài liệu đặc biệt,luận văn'),
('Đơn đăng ký làm thẻ thư viện', 'Mẫu đăng ký làm mới hoặc gia hạn thẻ thư viện', 'https://celri.tvu.edu.vn/bieu-mau', 'làm thẻ,đăng ký thẻ,thẻ thư viện,gia hạn thẻ'),
('Phiếu xác nhận sử dụng dịch vụ thư viện', 'Phiếu xác nhận cho sinh viên làm thủ tục tốt nghiệp', 'https://celri.tvu.edu.vn/bieu-mau', 'xác nhận thư viện,phiếu xác nhận,tốt nghiệp,thủ tục tốt nghiệp');

-- Chủ đề sự kiện mẫu
INSERT INTO event_themes (theme_name, primary_color, secondary_color, header_bg_color, welcome_message, is_active) VALUES
('Mặc định', '#1976D2', '#FFFFFF', '#1976D2', 'Xin chào! Tôi là trợ lý thư viện. Bạn cần tôi giúp gì?', 1),
('Tết Nguyên Đán', '#D32F2F', '#FFD700', '#D32F2F', '🎊 Chúc mừng năm mới! Tôi là trợ lý thư viện. Bạn cần tôi giúp gì?', 0),
('Khai giảng', '#388E3C', '#FFFFFF', '#388E3C', '📚 Chào mừng năm học mới! Tôi là trợ lý thư viện. Bạn cần tôi giúp gì?', 0);
