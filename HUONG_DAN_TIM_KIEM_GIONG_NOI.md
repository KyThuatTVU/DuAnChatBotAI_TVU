# Hướng dẫn Tìm kiếm bằng Giọng nói

## Tổng quan

Chatbot CELRAS TVU đã được tích hợp chức năng tìm kiếm bằng giọng nói, cho phép người dùng đặt câu hỏi bằng cách nói thay vì gõ phím.

## Tính năng

### 1. Nhận diện giọng nói tự động
- Hỗ trợ tiếng Việt và tiếng Anh
- Tự động chuyển đổi ngôn ngữ nhận diện theo cài đặt ngôn ngữ giao diện
- Hiển thị kết quả nhận diện theo thời gian thực

### 2. Gửi tin nhắn tự động
- Sau khi nhận diện xong, tin nhắn sẽ tự động được gửi đến chatbot
- Không cần nhấn nút gửi thủ công

### 3. Giao diện trực quan
- Nút microphone với hiệu ứng animation khi đang lắng nghe
- Thông báo lỗi rõ ràng nếu có vấn đề

## Cách sử dụng

### Bước 1: Nhấn nút Microphone
- Tìm nút microphone (🎤) bên cạnh nút gửi tin nhắn
- Nhấn vào nút để bắt đầu nhận diện giọng nói

### Bước 2: Nói câu hỏi
- Khi nút chuyển sang màu đỏ và có hiệu ứng pulse, bạn có thể bắt đầu nói
- Nói rõ ràng và với tốc độ vừa phải
- Ví dụ: "Thư viện mở cửa lúc mấy giờ?"

### Bước 3: Chờ kết quả
- Hệ thống sẽ tự động nhận diện và điền câu hỏi vào ô input
- Tin nhắn sẽ tự động được gửi sau khi nhận diện hoàn tất

## Yêu cầu kỹ thuật

### Trình duyệt hỗ trợ
- ✅ Google Chrome (khuyến nghị)
- ✅ Microsoft Edge
- ✅ Safari (iOS 14.5+)
- ✅ Opera
- ❌ Firefox (chưa hỗ trợ đầy đủ)

### Quyền truy cập
- Cần cấp quyền truy cập microphone cho trình duyệt
- Lần đầu sử dụng, trình duyệt sẽ yêu cầu cho phép truy cập microphone

## Xử lý lỗi

### Lỗi "Không nhận được giọng nói"
**Nguyên nhân:**
- Microphone không hoạt động
- Nói quá nhỏ hoặc môi trường quá ồn
- Không nói trong thời gian chờ

**Giải pháp:**
- Kiểm tra microphone đang hoạt động
- Nói to và rõ ràng hơn
- Thử lại ngay sau khi nhấn nút

### Lỗi "Quyền truy cập microphone bị từ chối"
**Nguyên nhân:**
- Chưa cấp quyền truy cập microphone cho trình duyệt

**Giải pháp:**
1. Nhấn vào biểu tượng khóa/thông tin bên trái thanh địa chỉ
2. Tìm mục "Microphone" và chọn "Allow" (Cho phép)
3. Tải lại trang và thử lại

### Lỗi "Trình duyệt không hỗ trợ nhận diện giọng nói"
**Nguyên nhân:**
- Trình duyệt không hỗ trợ Web Speech API

**Giải pháp:**
- Chuyển sang sử dụng Google Chrome hoặc Microsoft Edge
- Cập nhật trình duyệt lên phiên bản mới nhất

## Ngôn ngữ hỗ trợ

### Tiếng Việt (vi-VN)
- Nhận diện giọng nói tiếng Việt chuẩn
- Hỗ trợ các giọng miền Bắc, Trung, Nam
- Độ chính xác cao với câu hỏi ngắn gọn

### Tiếng Anh (en-US)
- Nhận diện giọng nói tiếng Anh Mỹ
- Tự động chuyển đổi khi người dùng chọn ngôn ngữ EN

## Mẹo sử dụng hiệu quả

1. **Nói rõ ràng và chậm rãi**: Giúp hệ thống nhận diện chính xác hơn
2. **Môi trường yên tĩnh**: Giảm nhiễu để tăng độ chính xác
3. **Câu hỏi ngắn gọn**: Câu hỏi dài có thể bị cắt hoặc nhận diện sai
4. **Kiểm tra kết quả**: Xem lại text được nhận diện trước khi gửi (nếu cần chỉnh sửa, có thể dừng và gõ thủ công)

## Kỹ thuật triển khai

### Web Speech API
Chức năng sử dụng Web Speech API của trình duyệt:
- `SpeechRecognition` hoặc `webkitSpeechRecognition`
- Không cần cài đặt thêm plugin hay extension
- Xử lý hoàn toàn trên trình duyệt (client-side)

### Cấu hình
```javascript
recognition.continuous = false;      // Dừng sau khi nhận được kết quả
recognition.interimResults = true;   // Hiển thị kết quả tạm thời
recognition.lang = 'vi-VN';          // Ngôn ngữ nhận diện
```

### Bảo mật
- Không ghi âm hoặc lưu trữ giọng nói
- Chỉ xử lý text được nhận diện
- Tuân thủ chính sách bảo mật của trình duyệt

## Liên hệ hỗ trợ

Nếu gặp vấn đề khi sử dụng chức năng giọng nói, vui lòng liên hệ:

📧 Email: trungtamhoclieu@tvu.edu.vn  
📞 Điện thoại: 0294 3855 246 (máy lẻ 142)  
🏢 Địa chỉ: Trung tâm Học liệu và Hỗ trợ Học thuật, Đại học Trà Vinh

---

**Phiên bản:** 1.0  
**Ngày cập nhật:** 10/03/2026
