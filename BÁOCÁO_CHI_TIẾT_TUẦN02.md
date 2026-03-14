# BÁOCÁO CHI TIẾT TUẦN 02 - CHATBOT THƯ VIỆN TVU

**Thời gian:** 09/03/2026 - 14/03/2026  
**Người báo cáo:** Nguyễn Huỳnh Kỹ Thuật  
**Người hướng dẫn:** Triệu Nhân Đạt

---

## I. TRANG NGƯỜI DÙNG (PUBLIC CHATBOT) - HOÀN THÀNH

### 1. Chatbot Hỏi — Đáp
- ✅ Người dùng nhập câu hỏi
- ✅ Hệ thống tìm câu trả lời từ cơ sở dữ liệu
- ✅ Trả về câu trả lời ngay lập tức
- ✅ Hiển thị typing indicator khi đang xử lý
- ✅ Lưu tin nhắn vào database (chat_messages)

**File:** `public/assets/js/chatbot.js` (hàm `sendMessage()`)

### 2. Gợi ý / Quick Suggestions
- ✅ Hiển thị các câu hỏi gợi ý khi mở chatbot
- ✅ Người dùng có thể click để gửi câu hỏi nhanh
- ✅ Lấy từ bảng `suggested_questions`

**File:** `public/assets/js/chatbot.js` (hàm `renderSuggestions()`)

### 3. Phân loại theo Danh mục
- ✅ Sidebar hiển thị danh sách danh mục
- ✅ Click vào danh mục để xem câu hỏi
- ✅ Hiển thị câu hỏi inline dưới danh mục
- ✅ Click câu hỏi để gửi vào chatbot
- ✅ Responsive trên mobile (toggle sidebar)

**File:** `public/assets/js/chatbot.js` (hàm `loadCategories()`, `openCategory()`)

### 4. Tự động Gợi Form/Tài liệu
- ✅ Nếu câu hỏi liên quan đến biểu mẫu, hệ thống trả về link
- ✅ Hiển thị danh sách biểu mẫu dưới câu trả lời
- ✅ Người dùng có thể click để tải/điền form

**File:** `public/assets/js/chatbot.js` (hàm `appendMessage()`)

### 5. Lưu Lịch sử Chat trên Trình duyệt
- ✅ Lưu lịch sử vào `sessionStorage` (phiên làm việc hiện tại)
- ✅ F5 vẫn thấy cuộc hội thoại
- ✅ Mỗi tab có lịch sử riêng
- ✅ Lưu session token vào `localStorage`

**File:** `public/assets/js/chatbot.js` (class `ChatHistory`)

### 6. Hỗ trợ Đa Ngôn ngữ (VI/EN)
- ✅ Trả lời có thể dùng bản tiếng Anh nếu có
- ✅ Phát hiện ngôn ngữ từ request
- ✅ Lưu `answer_text_en` trong bảng questions

**File:** `app/controllers/ChatController.php` (hàm `send()`)

### 7. Nhận Diện Giọng Nói (Speech Recognition)
- ✅ Hỗ trợ Web Speech API
- ✅ Kiểm tra trình duyệt có hỗ trợ không
- ✅ Kiểm tra HTTPS/localhost
- ✅ Tự động gửi tin nhắn khi nhận diện xong

**File:** `public/assets/js/chatbot.js` (hàm `initSpeechRecognition()`)

### 8. Áp dụng Chủ đề Sự kiện
- ✅ Tải chủ đề từ API
- ✅ Áp dụng CSS class (theme-tet, theme-halloween, v.v.)
- ✅ Thay đổi màu sắc
- ✅ Hiển thị emoji trang trí
- ✅ Hiển thị banner sự kiện

**File:** `public/assets/js/chatbot.js` (hàm `applyEventTheme()`)

---

## II. TRANG QUẢN TRỊ (ADMIN PANEL) - HOÀN THÀNH

### 1. Dashboard
- ✅ Thống kê tổng quan:
  - Số câu hỏi
  - Số danh mục
  - Số phiên chat
  - Số câu hỏi chưa trả lời
  - Tổng tin nhắn
- ✅ Lấy dữ liệu từ API `/api/admin/dashboard`

**File:** `public/pages/admin/dashboard.html`

### 2. Quản lý Câu hỏi
- ✅ Xem danh sách câu hỏi
- ✅ Tìm kiếm câu hỏi
- ✅ Lọc theo danh mục
- ✅ Lọc theo nguồn (manual/uploaded)
- ✅ Thêm câu hỏi mới
- ✅ Sửa câu hỏi
- ✅ Xóa câu hỏi
- ✅ Xem từ khóa liên quan
- ✅ Rich Text Editor (Quill) cho câu trả lời
- ✅ Hỗ trợ tiếng Việt + tiếng Anh
- ✅ Nhập từ khóa thủ công
- ✅ Thông báo tự động tạo từ khóa

**File:** `public/pages/admin/questions.html`, `public/assets/js/admin.js`

### 3. Quản lý Danh mục
- ✅ Xem danh sách danh mục
- ✅ Thêm danh mục mới
- ✅ Sửa danh mục
- ✅ Xóa danh mục
- ✅ Kích hoạt/vô hiệu hóa danh mục
- ✅ Sắp xếp danh mục

**File:** `public/pages/admin/categories.html`

### 4. Quản lý Biểu mẫu (Forms)
- ✅ Xem danh sách biểu mẫu
- ✅ Tìm kiếm biểu mẫu
- ✅ Thêm biểu mẫu mới
- ✅ Sửa biểu mẫu
- ✅ Xóa biểu mẫu
- ✅ Quản lý từ khóa liên quan

**File:** `public/pages/admin/forms.html`

### 5. Tải Dữ liệu (Datasets)
- ✅ Upload file Word (.docx, .doc)
- ✅ Danh sách lịch sử upload
- ✅ Hiển thị trạng thái xử lý (pending, processing, completed, failed)
- ✅ Hiển thị kích thước file
- ✅ Hiển thị ngày tải
- ✅ Xem chi tiết upload
- ✅ Xóa upload

**File:** `public/pages/admin/datasets.html`

### 6. Quản lý Câu hỏi Chưa Trả lời
- ✅ Danh sách câu hỏi chưa được trả lời
- ✅ Hiển thị số lần được hỏi
- ✅ Tạo trả lời nhanh từ đây
- ✅ Đánh dấu đã xử lý

**File:** `public/pages/admin/unanswered.html`

### 7. Cài đặt & Giao diện (Settings & Themes)
- ✅ Cấu hình thông điệp mặc định
- ✅ Cấu hình số lượng gợi ý
- ✅ Cấu hình các tuỳ chọn hiển thị
- ✅ Quản lý theme sự kiện:
  - Tạo chủ đề mới
  - Sửa chủ đề
  - Xóa chủ đề
  - Chọn màu (primary, secondary, header, v.v.)
  - Nhập thông điệp chào
  - Nhập banner text
  - Chọn ngày bắt đầu/kết thúc
  - Kích hoạt/vô hiệu hóa chủ đề

**File:** `public/pages/admin/settings.html`

### 8. Quản lý Tài khoản (Users)
- ✅ Danh sách tài khoản admin
- ✅ Xem thông tin admin
- ✅ Phân quyền (super_admin, admin, editor)

**File:** `public/pages/admin/users.html`

---

## III. CHỨC NĂNG KỸ THUẬT - HOÀN THÀNH

### 1. SPA (Single Page Application)
- ✅ Chuyển trang bằng AJAX (không reload toàn bộ)
- ✅ Giữ trạng thái JS khi chuyển trang
- ✅ Xử lý browser back/forward
- ✅ Cập nhật URL mà không reload

**File:** `public/assets/js/admin.js` (class `AdminSPA`)

### 2. Lưu Nháp & Trạng thái
- ✅ localStorage để lưu nháp form (TTL 2 giờ)
- ✅ sessionStorage để lưu tạm (ví dụ kết quả upload, lịch sử chat)
- ✅ Khôi phục nháp khi quay lại trang
- ✅ Cảnh báo beforeunload khi có dữ liệu chưa lưu

**File:** `public/assets/js/admin.js` (class `FormDraftManager`, `PageStateManager`)

### 3. Xử lý File Word
- ✅ Hỗ trợ trích xuất .docx bằng Zip/XML
- ✅ Hỗ trợ trích xuất .doc (OLE2) với fallback
- ✅ Tạo Q&A tự động từ nhiều kiểu cấu trúc văn bản
- ✅ Kiểm tra trùng lặp nội dung

**File:** `app/helpers/KeywordGenerator.php` (nếu có)

### 4. API REST
- ✅ GET /api/chat - Lấy thông tin chatbot
- ✅ POST /api/chat/send - Gửi tin nhắn
- ✅ POST /api/chat/new - Tạo phiên mới
- ✅ GET /api/chat/categories - Lấy danh mục
- ✅ GET /api/chat/categoryQuestions/{id} - Lấy câu hỏi theo danh mục
- ✅ GET /api/chat/history/{token} - Lấy lịch sử
- ✅ GET/POST/PUT/DELETE /api/admin/questions - CRUD câu hỏi
- ✅ GET/POST/PUT/DELETE /api/admin/categories - CRUD danh mục
- ✅ GET/POST/PUT/DELETE /api/admin/forms - CRUD biểu mẫu
- ✅ GET/POST /api/admin/datasets - Upload datasets
- ✅ GET/PUT /api/admin/settings - Quản lý cài đặt
- ✅ GET/POST/PUT /api/admin/themes - Quản lý chủ đề
- ✅ GET /api/admin/unanswered - Lấy câu hỏi chưa trả lời
- ✅ GET /api/admin/dashboard - Thống kê dashboard

**File:** `app/controllers/ChatController.php`, `app/controllers/AdminController.php`

### 5. Lưu Phiên Chat trên Server
- ✅ Tạo session token
- ✅ Lưu tin nhắn vào DB
- ✅ Lưu lịch sử vào DB
- ✅ Lấy lịch sử từ DB

**File:** `app/models/ChatModel.php`

---

## IV. THỐNG KÊ CÔNG VIỆC

### Cơ sở Dữ liệu
- 13 bảng dữ liệu
- 1000+ dòng SQL
- Dữ liệu mẫu cho 9 chủ đề sự kiện

### Backend
- 6 Model classes
- 3 Controller classes
- 20+ API endpoints
- 300+ dòng PHP code

### Frontend
- 2 file JavaScript chính (3000+ dòng)
- 8 file HTML giao diện
- Hỗ trợ đa ngôn ngữ (tiếng Việt/Anh)
- Hỗ trợ chủ đề sự kiện
- Hỗ trợ nhận diện giọng nói
- Responsive design (desktop, tablet, mobile)

### Tính năng Nâng cao
- SPA Navigation (AJAX)
- Form Draft Manager (localStorage)
- Page State Manager (localStorage)
- Chat History Manager (sessionStorage)
- Rich Text Editor (Quill)
- Speech Recognition API
- Event Themes (9 chủ đề)

---

## V. KIỂM THỬ

### Chức năng Chatbot
- ✅ Gửi tin nhắn
- ✅ Nhận trả lời
- ✅ Lưu lịch sử
- ✅ Khôi phục lịch sử
- ✅ Gợi ý câu hỏi
- ✅ Danh mục câu hỏi
- ✅ Biểu mẫu liên quan
- ✅ Đa ngôn ngữ
- ✅ Nhận diện giọng nói
- ✅ Chủ đề sự kiện

### Chức năng Admin
- ✅ Đăng nhập
- ✅ CRUD câu hỏi
- ✅ CRUD danh mục
- ✅ CRUD biểu mẫu
- ✅ Upload datasets
- ✅ Quản lý cài đặt
- ✅ Quản lý chủ đề
- ✅ Xem câu hỏi chưa trả lời
- ✅ Xem dashboard

### Giao diện
- ✅ Desktop (1920px+)
- ✅ Tablet (768px - 1024px)
- ✅ Mobile (< 768px)
- ✅ Animations mượt mà
- ✅ Loading indicators
- ✅ Error messages
- ✅ Success messages

---

## VI. CÔNG VIỆC TUẦN 03 (DỰ KIẾN)

1. Xây dựng chức năng tự động tạo từ khóa
2. Xây dựng chức năng import dữ liệu từ file
3. Xây dựng chức năng kiểm tra trùng lặp
4. Xây dựng chức năng quản lý chủ đề sự kiện (hoàn thiện)
5. Tối ưu hóa hiệu suất
6. Xây dựng chức năng báo cáo & thống kê
7. Xây dựng chức năng quản lý tài khoản admin

---

**Ngày báo cáo:** 14/03/2026  
**Người báo cáo:** Nguyễn Huỳnh Kỹ Thuật
