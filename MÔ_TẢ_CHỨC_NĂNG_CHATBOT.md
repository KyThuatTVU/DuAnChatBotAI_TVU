# MÔ TẢ CHỨC NĂNG CHATBOT THƯ VIỆN TVU

**Phiên bản:** 1.0  
**Ngày cập nhật:** 14/03/2026  
**Trạng thái:** Hoàn thành Tuần 02

---

## I. GIỚI THIỆU CHUNG

Chatbot Thư viện TVU là một hệ thống trợ lý ảo được thiết kế để hỗ trợ sinh viên và giảng viên của Đại học Trà Vinh trong việc tìm kiếm thông tin liên quan đến dịch vụ thư viện. Hệ thống sử dụng công nghệ AI matching để tìm câu trả lời phù hợp nhất từ cơ sở dữ liệu, đồng thời hỗ trợ đa ngôn ngữ (tiếng Việt và tiếng Anh).

---

## II. CHỨC NĂNG CHÍNH

### A. PHÍA NGƯỜI DÙNG (Public Chatbot)

#### 1. Giao diện Chat Chính
**Mô tả:** Người dùng có thể nhập câu hỏi và nhận trả lời từ chatbot

**Tính năng:**
- 💬 Nhập câu hỏi tự do (tối đa 3000 ký tự)
- 🤖 Chatbot tự động tìm kiếm và trả lời
- ⏱️ Hiệu ứng typing indicator khi đang xử lý
- 📝 Lưu toàn bộ cuộc hội thoại
- 🔄 Tạo cuộc trò chuyện mới bất cứ lúc nào
- 🎤 Hỗ trợ nhập giọng nói (Speech Recognition)

**Ví dụ:**
```
Người dùng: "Làm thế nào để mượn sách?"
Chatbot: "Để mượn sách, bạn cần có thẻ thư viện hợp lệ. 
Mang sách đến quầy mượn/trả và xuất trình thẻ thư viện. 
Bạn có thể mượn tối đa 5 cuốn sách trong 14 ngày."
```

---

#### 2. Gợi Ý Câu Hỏi Nhanh (Quick Suggestions)
**Mô tả:** Hiển thị các câu hỏi phổ biến khi mở chatbot

**Tính năng:**
- 🎯 Hiển thị 5 câu hỏi gợi ý hàng đầu
- 👆 Click để gửi câu hỏi ngay
- 🔄 Cập nhật dựa trên câu hỏi được hỏi nhiều nhất
- 📱 Responsive trên mọi thiết bị

**Ví dụ gợi ý:**
- "Làm thế nào để mượn sách?"
- "Thư viện mở cửa lúc mấy giờ?"
- "Làm thẻ thư viện ở đâu?"
- "Tra cứu sách như thế nào?"
- "Phòng tự học ở tầng mấy?"

---

#### 3. Duyệt Câu Hỏi Theo Danh Mục
**Mô tả:** Người dùng có thể xem câu hỏi được phân loại theo chủ đề

**Danh mục có sẵn:**
1. 📚 **Mượn trả sách** - Quy trình mượn, trả, gia hạn sách
2. 🎫 **Thẻ thư viện** - Đăng ký, gia hạn, cấp lại thẻ
3. 🔍 **Tra cứu tài liệu** - Hướng dẫn tìm kiếm sách, tài liệu
4. ⏰ **Giờ hoạt động** - Thời gian mở cửa, lịch nghỉ
5. 🛠️ **Dịch vụ khác** - Photocopy, phòng đọc, WiFi, v.v.

**Tính năng:**
- 📂 Sidebar hiển thị danh sách danh mục
- 📊 Hiển thị số câu hỏi trong mỗi danh mục
- 🎨 Icon đẹp và màu sắc phân biệt
- 📱 Toggle sidebar trên mobile
- ⚡ Load nhanh, không reload trang

**Ví dụ:**
```
Danh mục: Mượn trả sách (5 câu hỏi)
├─ Làm thế nào để mượn sách?
├─ Thời hạn mượn sách là bao lâu?
├─ Có thể gia hạn sách không?
├─ Phạt trễ hạn là bao nhiêu?
└─ Mất sách phải làm sao?
```

---

#### 4. Tự Động Gợi Biểu Mẫu / Giấy Tờ
**Mô tả:** Khi câu hỏi liên quan đến biểu mẫu, chatbot tự động gợi link tải

**Biểu mẫu có sẵn:**
- 📋 Đơn đăng ký mượn tài liệu đặc biệt
- 📋 Đơn đăng ký làm thẻ thư viện
- 📋 Phiếu xác nhận sử dụng dịch vụ thư viện

**Tính năng:**
- 🔗 Hiển thị link tải trực tiếp
- 📥 Người dùng có thể tải hoặc điền online
- 🎯 Matching tự động dựa trên từ khóa
- 📱 Responsive trên mọi thiết bị

**Ví dụ:**
```
Người dùng: "Tôi muốn làm thẻ thư viện"
Chatbot: "Dưới đây là biểu mẫu liên quan:
📋 Đơn đăng ký làm thẻ thư viện [Tải]
📋 Phiếu xác nhận sử dụng dịch vụ [Tải]"
```

---

#### 5. Lưu Lịch Sử Chat Trên Trình Duyệt
**Mô tả:** Lưu toàn bộ cuộc hội thoại để người dùng có thể xem lại

**Tính năng:**
- 💾 Lưu tự động vào sessionStorage (phiên làm việc hiện tại)
- 🔄 F5 vẫn thấy cuộc hội thoại
- 📑 Mỗi tab có lịch sử riêng
- 🗑️ Xóa lịch sử khi tạo cuộc trò chuyện mới
- ☁️ Lưu session token vào localStorage (14 ngày)

**Ví dụ:**
```
Phiên 1 (Tab 1):
- Người dùng: "Mượn sách?"
- Chatbot: "Để mượn sách..."
- Người dùng: "Gia hạn được không?"
- Chatbot: "Có, bạn có thể gia hạn..."

Phiên 2 (Tab 2):
- Người dùng: "Giờ mở cửa?"
- Chatbot: "Thư viện mở từ 7:30..."
```

---

#### 6. Hỗ Trợ Đa Ngôn Ngữ (Tiếng Việt / Tiếng Anh)
**Mô tả:** Chatbot có thể trả lời bằng tiếng Anh nếu người dùng yêu cầu

**Tính năng:**
- 🌐 Phát hiện ngôn ngữ từ request
- 🇻🇳 Trả lời tiếng Việt (mặc định)
- 🇬🇧 Trả lời tiếng Anh (nếu có)
- 🔄 Chuyển đổi ngôn ngữ dễ dàng

**Ví dụ:**
```
Tiếng Việt:
Người dùng: "Làm thế nào để mượn sách?"
Chatbot: "Để mượn sách, bạn cần có thẻ thư viện hợp lệ..."

Tiếng Anh:
User: "How to borrow books?"
Chatbot: "To borrow books, you need a valid library card..."
```

---

#### 7. Nhận Diện Giọng Nói (Speech Recognition)
**Mô tả:** Người dùng có thể nói thay vì gõ

**Tính năng:**
- 🎤 Nút micro để bắt đầu nhận diện
- 🔊 Hỗ trợ tiếng Việt và tiếng Anh
- ⏹️ Tự động dừng khi nhận diện xong
- 📝 Hiển thị text được nhận diện
- 🚀 Tự động gửi tin nhắn

**Yêu cầu:**
- ✅ Trình duyệt hỗ trợ Web Speech API (Chrome, Edge, Safari)
- ✅ HTTPS hoặc localhost
- ✅ Cấp quyền truy cập microphone

---

#### 8. Áp Dụng Chủ Đề Sự Kiện
**Mô tả:** Giao diện chatbot thay đổi theo sự kiện trong năm

**Chủ đề có sẵn:**
1. 🧧 **Tết Nguyên Đán** - Màu đỏ vàng, emoji trang trí
2. 🏮 **Tết Trung Thu** - Màu tím vàng, emoji lồng đèn
3. 🎃 **Halloween** - Màu cam đen, emoji ma quái
4. 🎄 **Giáng Sinh** - Màu đỏ xanh, emoji Noel
5. 🌹 **Quốc tế Phụ nữ 8/3** - Màu hồng, emoji hoa
6. 🌺 **Phụ nữ Việt Nam 20/10** - Màu tím, emoji hoa
7. 📚 **Nhà giáo Việt Nam 20/11** - Màu xanh, emoji sách
8. 🇻🇳 **Giải phóng miền Nam 30/4** - Màu đỏ vàng, emoji cờ
9. ✊ **Quốc tế Lao động 1/5** - Màu đỏ, emoji lao động

**Tính năng:**
- 🎨 Thay đổi màu sắc giao diện
- 🎉 Hiển thị emoji trang trí
- 🎊 Hiển thị banner sự kiện
- 💬 Thay đổi thông điệp chào
- 📅 Tự động kích hoạt theo ngày

**Ví dụ:**
```
Tết Nguyên Đán:
🧧 Chúc mừng năm mới! Chúc bạn năm mới vạn sự như ý! 
Tôi là trợ lý thư viện, bạn cần giúp gì?
[Emoji trang trí: 🧧 🌸 🎆 🎋 🏮 🎊 🌺 🧨 🎇 🎉]
```

---

### B. PHÍA QUẢN TRỊ (Admin Panel)

#### 1. Dashboard - Thống Kê Tổng Quan
**Mô tả:** Hiển thị các chỉ số quan trọng của hệ thống

**Thống kê:**
- 📊 Tổng số câu hỏi
- 📂 Tổng số danh mục
- 💬 Tổng số phiên chat
- ❓ Số câu hỏi chưa trả lời
- 📨 Tổng số tin nhắn

**Tính năng:**
- 🔄 Cập nhật real-time
- 📈 Hiển thị dạng card đẹp
- 📱 Responsive trên mọi thiết bị

---

#### 2. Quản Lý Câu Hỏi
**Mô tả:** Thêm, sửa, xóa, tìm kiếm câu hỏi

**Tính năng:**
- ➕ **Thêm câu hỏi mới:**
  - Chọn danh mục
  - Nhập câu hỏi
  - Nhập câu trả lời (Rich Text Editor)
  - Nhập câu trả lời tiếng Anh (tùy chọn)
  - Nhập từ khóa thủ công
  - Thông báo tự động tạo từ khóa

- 🔍 **Tìm kiếm:**
  - Tìm kiếm theo tên câu hỏi
  - Lọc theo danh mục
  - Lọc theo nguồn (nhập tay / tải lên)

- ✏️ **Sửa câu hỏi:**
  - Cập nhật nội dung
  - Cập nhật danh mục
  - Cập nhật từ khóa

- 🗑️ **Xóa câu hỏi:**
  - Xác nhận trước khi xóa
  - Xóa từ khóa liên quan

- 🔑 **Xem từ khóa:**
  - Hiển thị danh sách từ khóa
  - Thêm/xóa từ khóa

**Ví dụ:**
```
Câu hỏi: "Làm thế nào để mượn sách?"
Danh mục: Mượn trả sách
Câu trả lời: "Để mượn sách, bạn cần có thẻ thư viện hợp lệ..."
Từ khóa: mượn sách, mượn, borrow, thẻ thư viện
```

---

#### 2.1 🆕 CHỨC NĂNG MỚI: Matching Câu Hỏi Thông Minh (Keywords Matching)

**Mô tả:** Hệ thống tự động so sánh từ khóa của người dùng với từ khóa trong database để tìm câu trả lời chính xác nhất.

**Vấn đề cũ:**
- ❌ Chatbot trả về 20 câu gợi ý → quá nhiều
- ❌ Người dùng phải cuộn, đọc hết → mất thời gian
- ❌ Dữ liệu tải lên nhiều → chậm
- ❌ Không biết câu nào là đúng nhất

**Giải pháp mới:**
- ✅ Chatbot chỉ trả về 5 câu tốt nhất
- ✅ Ranking theo độ phù hợp từ khóa
- ✅ Nếu match 100% → trả lời ngay (không cần chọn)
- ✅ Nếu không match 100% → hiển thị top 5 gợi ý
- ✅ Tải dữ liệu ít hơn → nhanh hơn

**Cách hoạt động:**

**Bước 1: Admin nhập từ khóa khi tạo câu hỏi**
```
Câu hỏi: "Mượn sách được bao lâu?"
Từ khóa: mượn, sách, bao lâu, gia hạn, thời hạn, hạn mượn
```

**Bước 2: Người dùng hỏi chatbot**
```
Người dùng: "Mượn sách được bao lâu?"
```

**Bước 3: Chatbot so sánh từ khóa**
```
Từ khóa của user: mượn, sách, bao, lâu

So sánh với database:
┌─ Câu 1: "Mượn sách được bao lâu?"
│  Keywords: mượn, sách, bao lâu, gia hạn, thời hạn
│  Match: mượn ✓, sách ✓, bao lâu ✓ = 3/4 từ khóa (75%)
│  ⭐ TỐTNH ẤT
│
├─ Câu 2: "Gia hạn sách mấy lần?"
│  Keywords: mượn, sách, gia hạn
│  Match: mượn ✓, sách ✓ = 2/3 từ khóa (66%)
│  ⭐⭐ TỐT
│
└─ Câu 3: "Thẻ thư viện có hạn không?"
   Keywords: thẻ, hạn
   Match: không có = 0/2 từ khóa (0%)
   ❌ KHÔNG MATCH
```

**Bước 4: Chatbot trả lời**
```
Câu 1 match 75% (tốt nhất) → Chatbot trả lời ngay!

Nếu không có câu nào match 100%, chatbot hiển thị:
"Bạn muốn hỏi về:"
1. Mượn sách được bao lâu? (75% match)
2. Gia hạn sách mấy lần? (66% match)
3. ... (3 câu khác)
```

**Lợi ích:**

| Tiêu chí | Cũ | Mới |
|---|---|---|
| Số câu gợi ý | 20 câu | 5 câu |
| Cách tìm | Tìm kiếm toàn văn bản | So sánh từ khóa |
| Độ chính xác | Thấp (có thể sai) | Cao (chính xác hơn) |
| Tốc độ | Chậm | Nhanh |
| Dữ liệu tải lên | Nhiều | Ít |
| Trải nghiệm | Phải chọn | Trả lời ngay |

**Ví dụ thực tế:**

**Trường hợp 1: Match 100% → Trả lời ngay**
```
User: "Mượn sách được bao lâu?"
Keywords: mượn, sách, bao, lâu

Câu 1 match: mượn ✓, sách ✓, bao lâu ✓ = 100%
→ Chatbot trả lời ngay: "Thời hạn mượn sách là 14 ngày..."
```

**Trường hợp 2: Không match 100% → Hiển thị gợi ý**
```
User: "Sách có thể mượn bao lâu?"
Keywords: sách, mượn, bao, lâu

Câu 1 match: sách ✓, mượn ✓, bao lâu ✓ = 75%
Câu 2 match: sách ✓, mượn ✓ = 66%
Câu 3 match: sách ✓ = 33%

→ Chatbot hiển thị:
"Bạn muốn hỏi về:"
1. Mượn sách được bao lâu? (75%)
2. Gia hạn sách mấy lần? (66%)
3. Có thể mượn bao nhiêu cuốn? (33%)
4. ...
5. ...
```

**Trường hợp 3: Không match → Hiển thị danh sách**
```
User: "Phòng tự học ở đâu?"
Keywords: phòng, tự học, đâu

Không có câu nào match từ khóa này
→ Chatbot hiển thị: "Xin lỗi, mình không tìm thấy câu trả lời chính xác.
Bạn có thể thử hỏi lại hoặc liên hệ trực tiếp với thủ thư."
```

**Cách sử dụng:**

1. **Khi tạo/sửa câu hỏi:**
   - Nhập câu hỏi
   - Nhập từ khóa liên quan (cách nhau bằng dấu phẩy)
   - Lưu

2. **Khi người dùng hỏi:**
   - Hệ thống tự động so sánh từ khóa
   - Trả lời hoặc hiển thị gợi ý

3. **Kết quả:**
   - Chatbot chính xác hơn
   - Người dùng tìm được câu trả lời nhanh hơn
   - Trải nghiệm tốt hơn

---

#### 3. Quản Lý Danh Mục
**Mô tả:** Tạo, sửa, xóa danh mục câu hỏi

**Tính năng:**
- ➕ Thêm danh mục mới
- ✏️ Sửa tên, mô tả danh mục
- 🗑️ Xóa danh mục
- ✅ Kích hoạt/vô hiệu hóa danh mục
- 📊 Hiển thị số câu hỏi trong mỗi danh mục
- 🔢 Sắp xếp danh mục

**Ví dụ:**
```
Danh mục 1: Mượn trả sách (5 câu hỏi)
Danh mục 2: Thẻ thư viện (3 câu hỏi)
Danh mục 3: Tra cứu tài liệu (4 câu hỏi)
```

---

#### 4. Quản Lý Biểu Mẫu / Giấy Tờ
**Mô tả:** Quản lý các biểu mẫu liên quan đến thư viện

**Tính năng:**
- ➕ Thêm biểu mẫu mới
- ✏️ Sửa biểu mẫu
- 🗑️ Xóa biểu mẫu
- 🔍 Tìm kiếm biểu mẫu
- 🔗 Quản lý URL tải
- 🔑 Quản lý từ khóa liên quan

**Ví dụ:**
```
Biểu mẫu 1: Đơn đăng ký mượn tài liệu đặc biệt
URL: https://celri.tvu.edu.vn/bieu-mau
Từ khóa: đơn mượn, tài liệu đặc biệt, luận văn

Biểu mẫu 2: Đơn đăng ký làm thẻ thư viện
URL: https://celri.tvu.edu.vn/bieu-mau
Từ khóa: làm thẻ, đăng ký thẻ, gia hạn thẻ
```

---

#### 5. Tải Dữ Liệu (Upload Datasets)
**Mô tả:** Import câu hỏi từ file Word hoặc PDF

**Tính năng:**
- 📤 Upload file Word (.docx, .doc)
- 📤 Upload file PDF (.pdf)
- 🔄 Tự động trích xuất Q&A từ file
- ✅ Kiểm tra trùng lặp tự động
- 👁️ Preview Q&A trước khi lưu
- 📊 Hiển thị trạng thái xử lý (pending, processing, completed, failed)
- 📈 Hiển thị số câu hỏi được import
- 📅 Lưu lịch sử upload

**Ví dụ:**
```
Upload: "Hướng dẫn sử dụng thư viện.docx"
Kích thước: 2.5 MB
Trạng thái: Processing...
Số câu hỏi: 15
Trùng lặp: 2 (bỏ qua)
Kết quả: 13 câu hỏi được thêm
```

---

#### 6. Câu Hỏi Chưa Trả Lời
**Mô tả:** Xem danh sách câu hỏi mà chatbot không tìm được trả lời

**Tính năng:**
- 📋 Danh sách câu hỏi chưa trả lời
- 📊 Hiển thị số lần được hỏi
- ✏️ Tạo trả lời nhanh từ đây
- ✅ Đánh dấu đã xử lý
- 🗑️ Xóa câu hỏi

**Ví dụ:**
```
Câu hỏi: "Có thể mượn sách online không?"
Số lần hỏi: 5
Trạng thái: Chưa xử lý
[Tạo trả lời] [Xóa]
```

---

#### 7. Cài Đặt & Chủ Đề Sự Kiện
**Mô tả:** Cấu hình giao diện và chủ đề chatbot

**Cài đặt:**
- 📝 Tiêu đề chatbot
- 💬 Thông điệp chào mặc định
- 🎯 Số câu hỏi gợi ý tối đa
- 🎨 Màu sắc giao diện
- 📧 Thông tin liên hệ

**Quản lý Chủ đề Sự kiện:**
- ➕ Tạo chủ đề mới
- ✏️ Sửa chủ đề
- 🗑️ Xóa chủ đề
- 🎨 Chọn màu (primary, secondary, header, v.v.)
- 💬 Nhập thông điệp chào
- 🎊 Nhập banner text
- 📅 Chọn ngày bắt đầu/kết thúc
- ✅ Kích hoạt/vô hiệu hóa chủ đề

**Ví dụ:**
```
Chủ đề: Tết Nguyên Đán
Màu chính: #c0392b (đỏ)
Màu phụ: #FFD700 (vàng)
Thông điệp: "🎊 Chúc mừng năm mới! Chúc bạn năm mới vạn sự như ý!"
Banner: "🧧 Chúc Mừng Năm Mới — Happy New Year! 🎊"
Ngày bắt đầu: 01/02/2026
Ngày kết thúc: 10/02/2026
Trạng thái: Kích hoạt
```

---

#### 8. Quản Lý Tài Khoản Admin
**Mô tả:** Quản lý tài khoản quản trị viên

**Tính năng:**
- 👤 Xem danh sách admin
- ➕ Thêm admin mới
- ✏️ Sửa thông tin admin
- 🗑️ Xóa admin
- 🔐 Phân quyền (super_admin, admin, editor)
- 📊 Xem nhật ký hoạt động

---

### C. CHỨC NĂNG KỸ THUẬT NÂNG CAO

#### 1. SPA Navigation (Single Page Application)
**Mô tả:** Chuyển trang admin bằng AJAX mà không reload

**Lợi ích:**
- ⚡ Tải trang nhanh hơn
- 💾 Giữ trạng thái JavaScript
- 🔄 Xử lý browser back/forward
- 📍 Cập nhật URL mà không reload

---

#### 2. Lưu Nháp Form (Form Draft Manager)
**Mô tả:** Tự động lưu dữ liệu form khi user đang nhập

**Tính năng:**
- 💾 Lưu tự động vào localStorage
- ⏰ TTL 2 giờ
- 🔄 Khôi phục khi quay lại trang
- ⚠️ Cảnh báo beforeunload khi có dữ liệu chưa lưu
- 🔔 Toast notification khi có nháp cũ

**Ví dụ:**
```
User nhập câu hỏi → Tự động lưu vào localStorage
User đóng tab → Quay lại trang → Nháp được khôi phục
User rời trang → Cảnh báo "Bạn có dữ liệu chưa lưu"
```

---

#### 3. Lưu Trạng Thái Trang (Page State Manager)
**Mô tả:** Lưu trạng thái tìm kiếm/lọc khi chuyển trang

**Tính năng:**
- 🔍 Lưu từ khóa tìm kiếm
- 🏷️ Lưu bộ lọc danh mục
- 📊 Lưu bộ lọc nguồn
- 🔄 Khôi phục khi quay lại trang
- ⏰ TTL 24 giờ

**Ví dụ:**
```
User tìm kiếm "mượn sách" → Lọc danh mục "Mượn trả sách"
User chuyển sang trang khác → Quay lại trang Questions
Kết quả: Tìm kiếm và lọc được khôi phục
```

---

#### 4. Rich Text Editor (Quill)
**Mô tả:** Editor để nhập câu trả lời với định dạng

**Tính năng:**
- 🎨 Bold, Italic, Underline, Strike
- 📝 Blockquote, Code block
- 📋 Heading 1, Heading 2
- 📑 Ordered list, Bullet list
- 🎯 Alignment
- 🌈 Color, Background
- 🔗 Link
- 🧹 Clear formatting

---

#### 5. Chat History Manager
**Mô tả:** Lưu lịch sử chat vào sessionStorage

**Tính năng:**
- 💾 Lưu tự động
- 🔄 Khôi phục khi load lại trang
- 📑 Mỗi tab có lịch sử riêng
- 🗑️ Xóa khi tạo cuộc trò chuyện mới

---

#### 6. API REST
**Mô tả:** Các endpoint API để kết nối frontend và backend

**Chatbot API:**
- `GET /api/chat` - Lấy settings, suggestions
- `POST /api/chat/send` - Gửi tin nhắn
- `POST /api/chat/new` - Tạo phiên mới
- `GET /api/chat/categories` - Lấy danh mục
- `GET /api/chat/categoryQuestions/{id}` - Lấy câu hỏi theo danh mục
- `GET /api/chat/history/{token}` - Lấy lịch sử

**Admin API:**
- `GET /api/admin/dashboard` - Thống kê
- `GET/POST/PUT/DELETE /api/admin/questions` - CRUD câu hỏi
- `GET/POST/PUT/DELETE /api/admin/categories` - CRUD danh mục
- `GET/POST/PUT/DELETE /api/admin/forms` - CRUD biểu mẫu
- `GET/POST /api/admin/datasets` - Upload datasets
- `GET/PUT /api/admin/settings` - Cài đặt
- `GET/POST/PUT /api/admin/themes` - Chủ đề sự kiện
- `GET /api/admin/unanswered` - Câu hỏi chưa trả lời

---

## III. CÔNG NGHỆ SỬ DỤNG

### Backend
- **PHP 7.4+** - Ngôn ngữ lập trình
- **MySQL 5.7+** - Cơ sở dữ liệu
- **MVC Pattern** - Kiến trúc ứng dụng

### Frontend
- **HTML5** - Cấu trúc
- **CSS3 + Tailwind CSS** - Styling
- **JavaScript (ES6+)** - Tương tác
- **Quill.js** - Rich Text Editor
- **Web Speech API** - Nhận diện giọng nói

### Database
- **13 bảng dữ liệu**
- **FULLTEXT INDEX** - Tìm kiếm nhanh
- **Foreign Keys** - Toàn vẹn dữ liệu
- **UTF-8 Encoding** - Hỗ trợ tiếng Việt

---

## IV. TÍNH NĂNG BẢO MẬT

- ✅ Xác thực admin (Google OAuth + Email/Password)
- ✅ Phân quyền (super_admin, admin, editor)
- ✅ Validation dữ liệu
- ✅ Escape HTML (XSS protection)
- ✅ Session token (CSRF protection)
- ✅ Nhật ký hoạt động (audit trail)

---

## V. TÍNH NĂNG HIỆU SUẤT

- ⚡ SPA Navigation (AJAX)
- 💾 Caching dữ liệu
- 🗜️ Minify CSS/JS
- 📦 Lazy loading
- 🔄 Pagination cho danh sách dài
- 📱 Responsive design

---

## VI. HỖ TRỢ TRÌNH DUYỆT

- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)

---

## VII. HƯỚNG PHÁT TRIỂN TIẾP THEO (TUẦN 03+)

1. 🤖 Tự động tạo từ khóa (NLP)
2. 📄 Import từ PDF
3. 🔍 Kiểm tra trùng lặp nâng cao
4. 📊 Báo cáo & thống kê chi tiết
5. 🎯 Phân tích hành vi người dùng
6. 💬 Chatbot multi-language
7. 🔔 Thông báo real-time
8. 📱 Mobile app

---

## VIII. LIÊN HỆ & HỖ TRỢ

**Email:** celras@tvu.edu.vn  
**Điện thoại:** (02943) 855 246 (ext. 142)  
**Website:** https://celri.tvu.edu.vn

---

**Ngày cập nhật:** 14/03/2026  
**Phiên bản:** 1.0  
**Trạng thái:** Hoàn thành Tuần 02
