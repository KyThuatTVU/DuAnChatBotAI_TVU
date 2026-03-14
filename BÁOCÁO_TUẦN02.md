CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM
Độc lập – Tự do – Hạnh phúc

---

# DỰ KIẾN CÔNG VIỆC TUẦN 02

**Người hướng dẫn:** Triệu Nhân Đạt  
**Thời gian báo cáo:** Từ ngày 09/03/2026 đến ngày 14/03/2026  
**Người báo cáo:** Nguyễn Huỳnh Kỹ Thuật

---

## NỘI DUNG BÁO CÁO

Trong tuần 2 của thời gian thực hiện đề tài, em đã thực hiện các công việc sau:

### 1. Thiết kế cấu trúc cơ sở dữ liệu cho hệ thống chatbot

**Công việc cụ thể:**
- Phân tích các dữ liệu cần thiết cho hệ thống chatbot thư viện
- Thiết kế 13 bảng dữ liệu chính trong MySQL:
  - **Bảng admins**: Quản lý tài khoản quản trị viên (hỗ trợ đăng nhập Google OAuth + Email/Password)
  - **Bảng categories**: Danh mục nhóm câu hỏi (Mượn trả sách, Thẻ thư viện, v.v.)
  - **Bảng questions**: Bộ câu hỏi - trả lời chính (hỗ trợ tiếng Việt + tiếng Anh, FULLTEXT INDEX)
  - **Bảng keywords**: Từ khóa để matching câu hỏi người dùng (INDEX tối ưu)
  - **Bảng suggested_questions**: Câu hỏi gợi ý hiển thị khi mở chatbot
  - **Bảng datasets**: Quản lý file dữ liệu import (Word, PDF) với trạng thái xử lý
  - **Bảng chatbot_settings**: Cài đặt giao diện chatbot (màu, thông điệp, v.v.)
  - **Bảng event_themes**: Chủ đề giao diện theo sự kiện (Tết, Noel, 8/3, 20/10, v.v.)
  - **Bảng chat_sessions**: Phiên trò chuyện của người dùng (session token, IP, user agent)
  - **Bảng chat_messages**: Lịch sử tin nhắn trò chuyện (BIGINT, INDEX tối ưu)
  - **Bảng admin_logs**: Nhật ký hoạt động quản trị viên (audit trail)
  - **Bảng unanswered_questions**: Lưu câu hỏi chưa được trả lời (frequency tracking)
  - **Bảng forms**: Biểu mẫu / giấy tờ liên quan (keywords matching)
- Tạo các chỉ mục (INDEX) và khóa ngoại (FOREIGN KEY) để tối ưu hiệu suất truy vấn
- Dữ liệu mẫu ban đầu: 5 danh mục, 5 câu hỏi mẫu, 9 chủ đề sự kiện, 3 biểu mẫu mẫu
- Cài đặt mặc định: 13 setting cho chatbot (màu, thông điệp, số gợi ý, v.v.)

**Kết quả:** File `database/chatbot_thuvien.sql` hoàn thành (1000+ dòng SQL)

---

### 2. Xây dựng các chức năng cơ bản của hệ thống

**Công việc cụ thể:**

**A. Backend - Model Classes:**
- **QuestionModel**: Quản lý câu hỏi (CRUD, tìm kiếm, matching, related questions)
- **CategoryModel**: Quản lý danh mục (CRUD, lấy danh mục có số câu hỏi)
- **ChatModel**: Quản lý phiên chat (tạo session, lưu tin nhắn, lấy lịch sử)
- **FormModel**: Quản lý biểu mẫu (CRUD, tìm biểu mẫu liên quan)
- **SettingModel**: Quản lý cài đặt (lấy/cập nhật settings)
- **AdminModel**: Quản lý tài khoản admin (đăng nhập, đăng ký, profile)

**B. Backend - Controller Classes:**
- **ChatController**: Xử lý API chatbot
  - `POST /api/chat/send` - Gửi tin nhắn và nhận trả lời
  - `GET /api/chat` - Lấy thông tin chatbot (settings, suggestions)
  - `POST /api/chat/new` - Tạo cuộc trò chuyện mới
  - `GET /api/chat/categories` - Lấy danh sách danh mục
  - `GET /api/chat/categoryQuestions/{id}` - Lấy câu hỏi theo danh mục
  - `GET /api/chat/history/{token}` - Lấy lịch sử chat
- **AdminController**: Xử lý API quản trị
  - `GET /api/admin/dashboard` - Thống kê dashboard
  - `GET/POST/PUT/DELETE /api/admin/questions` - CRUD câu hỏi
  - `GET/POST/PUT/DELETE /api/admin/categories` - CRUD danh mục
  - `GET/POST/PUT/DELETE /api/admin/forms` - CRUD biểu mẫu
  - `GET/POST /api/admin/datasets` - Upload và quản lý datasets
  - `GET/PUT /api/admin/settings` - Quản lý cài đặt
  - `GET/POST/PUT /api/admin/themes` - Quản lý chủ đề sự kiện
  - `GET /api/admin/unanswered` - Lấy câu hỏi chưa trả lời
- **AuthController**: Xử lý đăng nhập/đăng xuất

**C. Logic Matching Câu Hỏi:**
- Tìm kiếm câu hỏi chính xác bằng từ khóa (keyword matching)
- Tìm câu hỏi liên quan khi không có kết quả chính xác (similarity search)
- Kiểm tra độ liên quan của câu hỏi với thư viện (library-related keywords)
- Hỗ trợ trả lời bằng tiếng Việt hoặc tiếng Anh (language detection)
- Loại bỏ câu hỏi bị lồng ở đầu câu trả lời (stripEmbeddedQuestion)

**Kết quả:** 
- File `app/controllers/ChatController.php` hoàn thành (300+ dòng)
- File `app/controllers/AdminController.php` hoàn thành
- File `app/controllers/AuthController.php` hoàn thành
- 6 Model classes hoàn thành

---

### 3. Xây dựng giao diện quản lý dữ liệu chatbot

**Công việc cụ thể:**

**A. Trang Quản lý Câu hỏi (Questions):**
- Bảng dữ liệu hiển thị danh sách câu hỏi với cột: #, Câu hỏi, Câu trả lời, Danh mục, Nguồn, Thao tác
- Thanh tìm kiếm và lọc theo danh mục, nguồn dữ liệu (manual/uploaded)
- Modal form để thêm/sửa câu hỏi với:
  - Rich Text Editor (Quill) cho câu trả lời tiếng Việt
  - Rich Text Editor (Quill) cho câu trả lời tiếng Anh
  - Nhập từ khóa thủ công
  - Thông báo tự động tạo từ khóa
- Nút thao tác: Xem từ khóa, Sửa, Xóa

**B. Trang Quản lý Danh mục (Categories):**
- Danh sách danh mục với cột: Tên, Mô tả, Số câu hỏi, Thao tác
- Nút thêm danh mục mới
- Modal form để thêm/sửa danh mục
- Nút kích hoạt/vô hiệu hóa danh mục

**C. Trang Quản lý Biểu mẫu (Forms):**
- Danh sách biểu mẫu với cột: Tên, Mô tả, URL, Từ khóa, Thao tác
- Thanh tìm kiếm biểu mẫu
- Nút thêm biểu mẫu mới
- Modal form để thêm/sửa biểu mẫu

**D. Trang Tải dữ liệu (Datasets):**
- Khu vực upload file Word (.docx, .doc)
- Danh sách lịch sử upload với cột: Tên file, Loại, Kích thước, Trạng thái, Ngày tải, Thao tác
- Hiển thị trạng thái xử lý (pending, processing, completed, failed)
- Preview Q&A sau khi upload

**E. Trang Cài đặt & Chủ đề (Settings & Themes):**
- Form cài đặt: Tiêu đề chatbot, Thông điệp chào, Số gợi ý, v.v.
- Danh sách chủ đề sự kiện với cột: Tên, Ngày bắt đầu, Ngày kết thúc, Trạng thái, Thao tác
- Modal form để thêm/sửa chủ đề với:
  - Chọn màu (primary, secondary, header, v.v.)
  - Nhập thông điệp chào
  - Nhập banner text
  - Chọn ngày bắt đầu/kết thúc
  - Kích hoạt/vô hiệu hóa

**F. Trang Câu hỏi Chưa trả lời (Unanswered):**
- Danh sách câu hỏi chưa được trả lời với cột: Câu hỏi, Số lần hỏi, Trạng thái, Thao tác
- Nút tạo trả lời nhanh từ đây

**G. Trang Dashboard:**
- Thống kê tổng quan: Số câu hỏi, Danh mục, Phiên chat, Câu hỏi chưa trả lời, Tổng tin nhắn

**H. Giao diện chung:**
- Sidebar điều hướng với các menu chính
- Gradient colors và shadow effects hiện đại
- Animations và transitions mượt mà
- Loading indicator khi tải dữ liệu
- Thông báo lỗi và thành công
- Xác nhận trước khi xóa
- Giao diện responsive cho mobile
- Mobile topbar với nút toggle sidebar

**Kết quả:** 
- File `public/pages/admin/questions.html` hoàn thành (600+ dòng)
- File `public/pages/admin/categories.html` hoàn thành
- File `public/pages/admin/forms.html` hoàn thành
- File `public/pages/admin/datasets.html` hoàn thành
- File `public/pages/admin/settings.html` hoàn thành
- File `public/pages/admin/unanswered.html` hoàn thành
- File `public/pages/admin/dashboard.html` hoàn thành
- File `public/pages/admin/users.html` hoàn thành

---

### 4. Kết nối giao diện với cơ sở dữ liệu

**Công việc cụ thể:**

**A. Frontend - JavaScript:**
- **chatbot.js** (1000+ dòng):
  - Khởi tạo chatbot (initChatbot)
  - Gửi tin nhắn (sendMessage)
  - Lưu lịch sử chat vào sessionStorage (ChatHistory)
  - Tải danh mục câu hỏi (loadCategories)
  - Hiển thị câu hỏi gợi ý (renderSuggestions)
  - Xử lý danh mục sidebar (openCategory, closeCategoryQuestions)
  - Hỗ trợ nhận diện giọng nói (Speech Recognition API)
  - Áp dụng cài đặt giao diện (applySettings)
  - Áp dụng chủ đề sự kiện (applyEventTheme)
  - Xử lý đa ngôn ngữ (tiếng Việt/Anh)

- **admin.js** (2000+ dòng):
  - Quản lý nháp form (FormDraftManager)
  - Quản lý trạng thái trang (PageStateManager)
  - SPA Navigation (AdminSPA) - chuyển trang bằng AJAX
  - Tải dữ liệu từ API (loadQuestions, loadCategories, loadForms, loadDatasets, loadSettings, loadThemes)
  - Render dữ liệu lên bảng (renderQuestions, renderCategories, v.v.)
  - Xử lý form (openAddModal, saveQuestion, deleteQuestion, v.v.)
  - Xử lý upload file (uploadDataset)
  - Xử lý Rich Text Editor (Quill)
  - Xử lý sidebar mobile (toggleSidebar, closeSidebarOnMobile)
  - Xử lý authentication (loadAdminPage, checkAuth)

**B. API Integration:**
- Tất cả các trang admin kết nối với API endpoints:
  - `/api/admin/questions` - CRUD câu hỏi
  - `/api/admin/categories` - CRUD danh mục
  - `/api/admin/forms` - CRUD biểu mẫu
  - `/api/admin/datasets` - Upload datasets
  - `/api/admin/settings` - Quản lý cài đặt
  - `/api/admin/themes` - Quản lý chủ đề
  - `/api/admin/unanswered` - Lấy câu hỏi chưa trả lời
  - `/api/admin/dashboard` - Thống kê dashboard
  - `/api/chat/*` - API chatbot người dùng

**C. Tính năng nâng cao:**
- Lưu nháp form vào localStorage (FormDraftManager)
- Lưu trạng thái tìm kiếm/lọc vào localStorage (PageStateManager)
- Lưu lịch sử chat vào sessionStorage (ChatHistory)
- SPA Navigation - không reload trang khi chuyển menu
- Khôi phục nháp khi quay lại trang
- Cảnh báo beforeunload khi có dữ liệu chưa lưu

**Kết quả:** 
- File `public/assets/js/chatbot.js` hoàn thành (1000+ dòng)
- File `public/assets/js/admin.js` hoàn thành (2000+ dòng)
- Tất cả các trang admin kết nối thành công với API

---

### 5. Kiểm tra và sửa lỗi hệ thống

**Công việc cụ thể:**

**A. Kiểm tra chức năng Chatbot người dùng:**
- ✅ Gửi tin nhắn và nhận trả lời
- ✅ Lưu lịch sử chat trên trình duyệt (sessionStorage)
- ✅ Khôi phục lịch sử khi load lại trang
- ✅ Hiển thị câu hỏi gợi ý
- ✅ Duyệt câu hỏi theo danh mục
- ✅ Tự động gợi biểu mẫu liên quan
- ✅ Hỗ trợ đa ngôn ngữ (tiếng Việt/Anh)
- ✅ Nhận diện giọng nói (Speech Recognition)
- ✅ Áp dụng chủ đề sự kiện

**B. Kiểm tra chức năng Admin:**
- ✅ Đăng nhập/Đăng xuất
- ✅ Xem danh sách câu hỏi
- ✅ Thêm câu hỏi mới
- ✅ Sửa câu hỏi
- ✅ Xóa câu hỏi
- ✅ Tìm kiếm câu hỏi
- ✅ Lọc theo danh mục/nguồn
- ✅ Quản lý danh mục
- ✅ Quản lý biểu mẫu
- ✅ Quản lý cài đặt
- ✅ Quản lý chủ đề sự kiện
- ✅ Xem câu hỏi chưa trả lời
- ✅ Xem thống kê dashboard

**C. Kiểm tra API endpoints:**
- ✅ `POST /api/chat/send` - Gửi tin nhắn
- ✅ `GET /api/chat` - Lấy settings
- ✅ `POST /api/chat/new` - Tạo phiên mới
- ✅ `GET /api/chat/categories` - Lấy danh mục
- ✅ `GET /api/chat/categoryQuestions/{id}` - Lấy câu hỏi theo danh mục
- ✅ `GET /api/chat/history/{token}` - Lấy lịch sử
- ✅ `GET /api/admin/questions` - Lấy danh sách câu hỏi
- ✅ `POST /api/admin/questions` - Thêm câu hỏi
- ✅ `PUT /api/admin/questions/{id}` - Sửa câu hỏi
- ✅ `DELETE /api/admin/questions/{id}` - Xóa câu hỏi

**D. Kiểm tra giao diện:**
- ✅ Hiển thị dữ liệu chính xác
- ✅ Responsive trên các thiết bị khác nhau (desktop, tablet, mobile)
- ✅ Animations và transitions hoạt động mượt mà
- ✅ Loading indicator hiển thị đúng
- ✅ Thông báo lỗi/thành công hiển thị đúng
- ✅ Modal form hoạt động đúng
- ✅ Rich Text Editor (Quill) hoạt động đúng

**E. Sửa các lỗi phát sinh:**
- ✅ Lỗi kết nối cơ sở dữ liệu
- ✅ Lỗi validation dữ liệu
- ✅ Lỗi hiển thị giao diện
- ✅ Lỗi API endpoints
- ✅ Lỗi CORS (nếu có)
- ✅ Lỗi encoding UTF-8

**Kết quả:** Hệ thống hoạt động ổn định, tất cả chức năng chính đã được kiểm tra

---

### 6. Báo cáo tiến độ thực hiện tuần 02

**Công việc cụ thể:**
- Tổng hợp nội dung công việc đã thực hiện trong tuần
- Báo cáo với giảng viên hướng dẫn
- Chuẩn bị cho tuần tiếp theo

---

## TỔNG KẾT TUẦN 02

| Công việc | Trạng thái | Chi tiết |
|-----------|-----------|---------|
| Thiết kế CSDL | ✅ Hoàn thành | 13 bảng, 1000+ dòng SQL, dữ liệu mẫu |
| Backend - Models | ✅ Hoàn thành | 6 Model classes (Question, Category, Chat, Form, Setting, Admin) |
| Backend - Controllers | ✅ Hoàn thành | 3 Controller classes (Chat, Admin, Auth) với 20+ API endpoints |
| Frontend - Chatbot | ✅ Hoàn thành | chatbot.js (1000+ dòng), giao diện người dùng |
| Frontend - Admin | ✅ Hoàn thành | admin.js (2000+ dòng), 8 trang quản trị |
| Giao diện quản lý | ✅ Hoàn thành | Dashboard, Questions, Categories, Forms, Datasets, Settings, Themes, Unanswered |
| Kết nối CSDL | ✅ Hoàn thành | API integration, AJAX, SPA Navigation |
| Kiểm tra & sửa lỗi | ✅ Hoàn thành | Tất cả chức năng chính đã kiểm tra |

**Tổng cộng:**
- 13 bảng dữ liệu
- 6 Model classes
- 3 Controller classes
- 20+ API endpoints
- 8 trang quản trị
- 2 file JavaScript chính (3000+ dòng)
- 8 file HTML giao diện
- Hỗ trợ đa ngôn ngữ (tiếng Việt/Anh)
- Hỗ trợ chủ đề sự kiện
- Hỗ trợ nhận diện giọng nói

---

## CÔNG VIỆC TUẦN 03 (Dự kiến)

Bắt đầu từ thứ 2, ngày 17/03/2026:

### 1. Xây dựng chức năng tự động tạo từ khóa
- Sử dụng NLP hoặc API để phân tích câu hỏi
- Tự động tạo từ khóa tiếng Việt + tiếng Anh
- Lưu từ khóa vào bảng keywords
- Hiển thị từ khóa đã tạo trong form câu hỏi

### 2. Xây dựng chức năng import dữ liệu từ file
- Hỗ trợ import từ file Word (.docx)
- Hỗ trợ import từ file PDF (.pdf)
- Tự động trích xuất Q&A từ file
- Kiểm tra trùng lặp so với DB
- Hiển thị preview Q&A sau khi upload
- Lưu lịch sử upload

### 3. Xây dựng chức năng kiểm tra trùng lặp
- So sánh nội dung câu hỏi (exact match + similarity)
- Chuẩn hóa tiếng Việt (bỏ dấu, loại ký tự, lowercase)
- Báo cáo số câu hỏi trùng lặp
- Cho phép bỏ qua hoặc cập nhật câu hỏi trùng

### 4. Xây dựng chức năng quản lý chủ đề sự kiện
- Tạo, sửa, xóa chủ đề sự kiện
- Cài đặt ngày bắt đầu và kết thúc
- Kích hoạt/vô hiệu hóa chủ đề
- Áp dụng chủ đề trên giao diện chatbot

### 5. Tối ưu hóa hiệu suất
- Caching dữ liệu (Redis hoặc file cache)
- Tối ưu truy vấn cơ sở dữ liệu
- Nén hình ảnh
- Minify CSS/JS
- Lazy loading cho danh sách dài

### 6. Xây dựng chức năng báo cáo & thống kê
- Thống kê số câu hỏi, danh mục, phiên chat
- Thống kê câu hỏi được hỏi nhiều nhất
- Thống kê câu hỏi chưa trả lời
- Xuất báo cáo (CSV, PDF)

### 7. Xây dựng chức năng quản lý tài khoản admin
- Thêm/sửa/xóa tài khoản admin
- Phân quyền (super_admin, admin, editor)
- Xem nhật ký hoạt động (admin logs)
- Đặt lại mật khẩu

---

## LỜI CẢM ƠN

Em xin chân thành cảm ơn Quý Thầy/Cô đã hướng dẫn và hỗ trợ em trong quá trình thực hiện công việc của tuần 2. Mong Quý Thầy/Cô tiếp tục hướng dẫn và góp ý để em hoàn thành tốt các nhiệm vụ trong thời gian tới.

---

**Ý kiến của người hướng dẫn:**

_[Để trống cho người hướng dẫn điền]_

---

**Người báo cáo**  
Nguyễn Huỳnh Kỹ Thuật

**Ngày báo cáo:** 14/03/2026
