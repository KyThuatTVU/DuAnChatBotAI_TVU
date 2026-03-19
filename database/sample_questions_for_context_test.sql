-- Sample questions để test hệ thống phân tích ngữ cảnh
-- Chạy file này để thêm các câu hỏi mẫu vào database

-- Câu hỏi về VỊ TRÍ (location)
INSERT INTO questions (category_id, question_text, answer_text, answer_text_en, is_active, source_type) VALUES
(1, 'Thư viện CELRAS TVU nằm ở đâu?', 
'Thư viện CELRAS TVU nằm tại Khu A, Trường Đại học Trà Vinh, số 126 Nguyễn Thiện Thành, Khóm 4, Phường 5, Thành phố Trà Vinh, Tỉnh Trà Vinh.', 
'CELRAS TVU Library is located at Area A, Tra Vinh University, 126 Nguyen Thien Thanh Street, Khom 4, Ward 5, Tra Vinh City, Tra Vinh Province.',
1, 'manual'),

(1, 'Thư viện nằm ở khu nào?', 
'Thư viện CELRAS TVU nằm ở Khu A của trường Đại học Trà Vinh.', 
'CELRAS TVU Library is located in Area A of Tra Vinh University.',
1, 'manual'),

(1, 'Địa chỉ thư viện là gì?', 
'Địa chỉ: Số 126 Nguyễn Thiện Thành, Khóm 4, Phường 5, Thành phố Trà Vinh, Tỉnh Trà Vinh.', 
'Address: 126 Nguyen Thien Thanh Street, Khom 4, Ward 5, Tra Vinh City, Tra Vinh Province.',
1, 'manual');

-- Câu hỏi về THỜI GIAN (time)
INSERT INTO questions (category_id, question_text, answer_text, answer_text_en, is_active, source_type) VALUES
(1, 'Thư viện mở cửa lúc mấy giờ?', 
'Thư viện mở cửa:\n- Thứ Hai đến Thứ Bảy: từ 7:00 đến 21:00\n- Chủ Nhật: từ 7:00 đến 17:00\n\nTrong thời gian này, bạn có thể sử dụng:\n- Phòng đọc và khu tự học\n- Phòng học nhóm\n- Phòng học liệu điện tử\n\nNếu cần học buổi tối, bạn vẫn có thể đến thư viện đến 21:00 từ thứ Hai đến thứ Bảy.', 
'Library opening hours:\n- Monday to Saturday: 7:00 AM to 9:00 PM\n- Sunday: 7:00 AM to 5:00 PM\n\nDuring these hours, you can use:\n- Reading rooms and self-study areas\n- Group study rooms\n- Electronic learning resource rooms\n\nIf you need to study in the evening, you can come to the library until 9:00 PM from Monday to Saturday.',
1, 'manual'),

(1, 'Giờ đóng cửa thư viện là mấy giờ?', 
'Thư viện đóng cửa:\n- Thứ Hai đến Thứ Bảy: 21:00\n- Chủ Nhật: 17:00', 
'Library closing hours:\n- Monday to Saturday: 9:00 PM\n- Sunday: 5:00 PM',
1, 'manual'),

(1, 'Thư viện có mở cửa vào chủ nhật không?', 
'Có, thư viện mở cửa vào Chủ Nhật từ 7:00 đến 17:00.', 
'Yes, the library is open on Sundays from 7:00 AM to 5:00 PM.',
1, 'manual');

-- Câu hỏi về CẤU TRÚC (location + structure)
INSERT INTO questions (category_id, question_text, answer_text, answer_text_en, is_active, source_type) VALUES
(1, 'Thư viện có mấy tầng?', 
'Thư viện CELRAS TVU có 3 tầng:\n- Tầng 1: Quầy mượn/trả sách, khu đọc báo tạp chí\n- Tầng 2: Phòng đọc sách, khu tự học\n- Tầng 3: Phòng học nhóm, phòng học liệu điện tử', 
'CELRAS TVU Library has 3 floors:\n- Floor 1: Book borrowing/return desk, newspaper and magazine reading area\n- Floor 2: Book reading room, self-study area\n- Floor 3: Group study rooms, electronic learning resource rooms',
1, 'manual'),

(1, 'Phòng đọc nằm ở tầng mấy?', 
'Phòng đọc sách nằm ở tầng 2 của thư viện.', 
'The book reading room is located on the 2nd floor of the library.',
1, 'manual'),

(1, 'Phòng học nhóm ở đâu?', 
'Phòng học nhóm nằm ở tầng 3 của thư viện.', 
'Group study rooms are located on the 3rd floor of the library.',
1, 'manual');

-- Câu hỏi về THỦ TỤC (procedure)
INSERT INTO questions (category_id, question_text, answer_text, answer_text_en, is_active, source_type) VALUES
(2, 'Làm thế nào để mượn sách?', 
'Để mượn sách, bạn cần:\n1. Có thẻ sinh viên hoặc thẻ thư viện\n2. Tìm sách trên hệ thống tra cứu hoặc trực tiếp tại kệ\n3. Mang sách đến quầy mượn/trả ở tầng 1\n4. Xuất trình thẻ và đăng ký mượn\n\nThời gian mượn: 14 ngày (có thể gia hạn)', 
'To borrow books, you need to:\n1. Have a student card or library card\n2. Find books on the search system or directly on the shelves\n3. Bring books to the borrowing/return desk on the 1st floor\n4. Present your card and register to borrow\n\nBorrowing period: 14 days (can be renewed)',
1, 'manual'),

(2, 'Cách trả sách như thế nào?', 
'Để trả sách:\n1. Mang sách đến quầy mượn/trả ở tầng 1\n2. Xuất trình thẻ sinh viên\n3. Thủ thư sẽ kiểm tra và xác nhận trả sách\n\nLưu ý: Trả sách đúng hạn để tránh bị phạt.', 
'To return books:\n1. Bring books to the borrowing/return desk on the 1st floor\n2. Present your student card\n3. The librarian will check and confirm the return\n\nNote: Return books on time to avoid fines.',
1, 'manual');

-- Câu hỏi về DỊCH VỤ (service)
INSERT INTO questions (category_id, question_text, answer_text, answer_text_en, is_active, source_type) VALUES
(3, 'Thư viện có wifi không?', 
'Có, thư viện có wifi miễn phí cho sinh viên và giảng viên. Bạn có thể kết nối bằng tài khoản sinh viên của mình.', 
'Yes, the library has free wifi for students and lecturers. You can connect using your student account.',
1, 'manual'),

(3, 'Có dịch vụ photocopy không?', 
'Có, thư viện có dịch vụ photocopy và in ấn tại tầng 1. Giá: 200đ/trang đen trắng, 2000đ/trang màu.', 
'Yes, the library has photocopy and printing services on the 1st floor. Price: 200 VND/black and white page, 2000 VND/color page.',
1, 'manual');

-- Câu hỏi về PHÍ (fee)
INSERT INTO questions (category_id, question_text, answer_text, answer_text_en, is_active, source_type) VALUES
(3, 'Phí mượn sách bao nhiêu?', 
'Mượn sách hoàn toàn MIỄN PHÍ cho sinh viên và giảng viên của trường.', 
'Borrowing books is completely FREE for students and lecturers of the university.',
1, 'manual'),

(3, 'Phí photocopy bao nhiêu?', 
'Phí photocopy:\n- Đen trắng: 200đ/trang\n- Màu: 2000đ/trang', 
'Photocopy fees:\n- Black and white: 200 VND/page\n- Color: 2000 VND/page',
1, 'manual');

-- Câu hỏi về LIÊN HỆ (contact)
INSERT INTO questions (category_id, question_text, answer_text, answer_text_en, is_active, source_type) VALUES
(1, 'Liên hệ thư viện như thế nào?', 
'Bạn có thể liên hệ thư viện qua:\n📧 Email: trungtamhoclieu@tvu.edu.vn\n📞 Điện thoại: 0294 3855 246 (máy lẻ 142)\n🏢 Địa chỉ: Khu A, Trường ĐH Trà Vinh', 
'You can contact the library via:\n📧 Email: trungtamhoclieu@tvu.edu.vn\n📞 Phone: 0294 3855 246 (ext. 142)\n🏢 Address: Area A, Tra Vinh University',
1, 'manual'),

(1, 'Số điện thoại thư viện là gì?', 
'Số điện thoại thư viện: 0294 3855 246 (máy lẻ 142)', 
'Library phone number: 0294 3855 246 (ext. 142)',
1, 'manual');
