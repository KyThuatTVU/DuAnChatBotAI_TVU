-- =====================================================
-- MIGRATION: Thêm chủ đề sự kiện trong năm
-- Chạy sau khi đã có bảng event_themes
-- =====================================================

-- 1. Thêm cột theme_key và decorations
ALTER TABLE event_themes 
ADD COLUMN theme_key VARCHAR(50) NULL COMMENT 'CSS class key (vd: tet, halloween...)' AFTER theme_name,
ADD COLUMN decorations TEXT NULL COMMENT 'Emoji/ký tự trang trí (JSON array)' AFTER bot_avatar_url,
ADD COLUMN banner_text VARCHAR(500) NULL COMMENT 'Nội dung banner sự kiện' AFTER decorations;

-- 2. Cập nhật theme Mặc định
UPDATE event_themes SET theme_key = 'mac-dinh', decorations = NULL, banner_text = NULL WHERE theme_name = 'Mặc định';

-- 3. Xóa các theme mẫu cũ (nếu cần)
DELETE FROM event_themes WHERE theme_name IN ('Tết Nguyên Đán', 'Khai giảng') AND theme_key IS NULL;

-- 4. Thêm 10 chủ đề sự kiện
INSERT INTO event_themes (theme_name, theme_key, primary_color, secondary_color, header_bg_color, header_text_color, user_bubble_color, bot_bubble_color, button_color, welcome_message, decorations, banner_text, is_active) VALUES

-- Tết Nguyên Đán
('Tết Nguyên Đán', 'tet', '#c0392b', '#FFD700', '#c0392b', '#FFFFFF', '#ffe8e0', '#fff9f0', '#c0392b',
 '🎊 Chúc mừng năm mới! Chúc bạn năm mới vạn sự như ý! Tôi là trợ lý thư viện, bạn cần giúp gì?',
 '["🧧","🌸","🎆","🎋","🏮","🎊","🌺","🧨","🎇","🎉"]',
 '🧧 Chúc Mừng Năm Mới — Happy New Year! 🎊', 0),

-- Trung Thu
('Tết Trung Thu', 'trung-thu', '#6b21a8', '#fbbf24', '#6b21a8', '#FFFFFF', '#f3e8ff', '#fefbf0', '#6b21a8',
 '🌕 Chào mừng Tết Trung Thu! Tôi là trợ lý thư viện, bạn cần giúp gì?',
 '["🏮","🌕","🐰","🥮","⭐","🎑","🌙","🏮","✨","🎋"]',
 '🏮 Vui Tết Trung Thu — Happy Mid-Autumn! 🌕', 0),

-- Halloween
('Halloween', 'halloween', '#e67e22', '#1a1a2e', '#d35400', '#FFFFFF', '#2c2c54', '#2c2c54', '#e67e22',
 '🎃 Happy Halloween! Tôi là trợ lý thư viện, bạn cần giúp gì?',
 '["🎃","👻","🦇","🕷️","💀","🕸️","🧙","🌙","⚡","🔮"]',
 '🎃 Happy Halloween — Lễ Hội Ma Quái! 👻', 0),

-- Giáng Sinh
('Giáng Sinh', 'giang-sinh', '#c62828', '#2e7d32', '#c62828', '#FFFFFF', '#ffebee', '#f1f8e9', '#2e7d32',
 '🎄 Merry Christmas! Giáng sinh an lành! Tôi là trợ lý thư viện, bạn cần giúp gì?',
 '["❄️","🎄","🎅","⛄","🎁","🌟","🔔","❄️","✨","🎄"]',
 '🎄 Merry Christmas — Giáng Sinh An Lành! 🎅', 0),

-- Ngày 8/3
('Quốc tế Phụ nữ 8/3', '8-3', '#db2777', '#fce7f3', '#db2777', '#FFFFFF', '#fce7f3', '#fdf2f8', '#ec4899',
 '🌹 Chúc mừng ngày Quốc tế Phụ nữ 8/3! Tôi là trợ lý thư viện, bạn cần giúp gì?',
 '["🌹","💐","🌸","🌺","💕","🎀","🌷","💖","🌼","✨"]',
 '🌹 Chúc Mừng Ngày Quốc Tế Phụ Nữ 8/3 💐', 0),

-- Ngày 20/10
('Phụ nữ Việt Nam 20/10', '20-10', '#7c3aed', '#ec4899', '#7c3aed', '#FFFFFF', '#ede9fe', '#f5f3ff', '#8b5cf6',
 '🌺 Chúc mừng ngày Phụ nữ Việt Nam 20/10! Tôi là trợ lý thư viện, bạn cần giúp gì?',
 '["🌺","💜","🎀","🌸","💐","🌹","✨","🎊","💕","🌷"]',
 '🌺 Chúc Mừng Ngày Phụ Nữ Việt Nam 20/10 💜', 0),

-- Ngày 20/11
('Nhà giáo Việt Nam 20/11', '20-11', '#1e40af', '#eab308', '#1e40af', '#FFFFFF', '#dbeafe', '#eff6ff', '#2563eb',
 '📚 Chúc mừng ngày Nhà giáo Việt Nam 20/11! Tôi là trợ lý thư viện, bạn cần giúp gì?',
 '["📚","✏️","🎓","📖","🏫","📝","🎒","📕","🌟","💐"]',
 '📚 Chúc Mừng Ngày Nhà Giáo Việt Nam 20/11 🎓', 0),

-- Ngày 30/4
('Giải phóng miền Nam 30/4', '30-4', '#b91c1c', '#eab308', '#b91c1c', '#fbbf24', '#fee2e2', '#fef9c3', '#dc2626',
 '🇻🇳 Kỷ niệm ngày Giải phóng miền Nam 30/4! Tôi là trợ lý thư viện, bạn cần giúp gì?',
 '["🇻🇳","⭐","🎆","🎉","🎊","🏴","✨","🎇","🌟","🎗️"]',
 '🇻🇳 Kỷ Niệm Ngày Giải Phóng Miền Nam 30/4 ⭐', 0),

-- Ngày 1/5
('Quốc tế Lao động 1/5', '1-5', '#991b1b', '#FFFFFF', '#991b1b', '#FFFFFF', '#fee2e2', '#fef2f2', '#b91c1c',
 '✊ Chúc mừng ngày Quốc tế Lao động 1/5! Tôi là trợ lý thư viện, bạn cần giúp gì?',
 '["✊","⚒️","🌟","💪","🎉","🏗️","⭐","🎊","🛠️","✨"]',
 '✊ Chúc Mừng Ngày Quốc Tế Lao Động 1/5 🌟', 0),

-- Ngày 2/9
('Quốc khánh 2/9', '2-9', '#dc2626', '#eab308', '#991b1b', '#fbbf24', '#fee2e2', '#fffbeb', '#dc2626',
 '🇻🇳 Chúc mừng ngày Quốc khánh 2/9! Tôi là trợ lý thư viện, bạn cần giúp gì?',
 '["🇻🇳","⭐","🎆","🎇","🎉","🎊","✨","🌟","🎗️","🏴"]',
 '🇻🇳 Chúc Mừng Ngày Quốc Khánh 2/9 — Độc Lập Tự Do ⭐', 0);
