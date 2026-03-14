# 🆕 CHỨC NĂNG MỚI: MATCHING CÂU HỎI THÔNG MINH

**Ngày cập nhật:** 14/03/2026  
**Phiên bản:** 1.1  
**Trạng thái:** Hoàn thành Tuần 02

---

## 📌 TÓM TẮT

Hệ thống chatbot thư viện vừa được cập nhật với **chức năng matching câu hỏi thông minh** - giúp chatbot trả lời chính xác hơn và nhanh hơn.

---

## ❌ VẤN ĐỀ CŨ

Trước đây, khi người dùng hỏi chatbot:

```
User: "Mượn sách được bao lâu?"

Chatbot trả về 20 câu gợi ý:
1. Mượn sách được bao lâu?
2. Gia hạn sách mấy lần?
3. Thẻ thư viện có hạn không?
4. Phòng đọc ở đâu?
5. Có thể mượn bao nhiêu cuốn?
... (15 câu khác nữa)
```

**Vấn đề:**
- 😫 Quá nhiều câu → người dùng phải cuộn, đọc hết
- ⏱️ Mất thời gian → trải nghiệm không tốt
- 📊 Dữ liệu tải lên nhiều → chậm
- ❓ Không biết câu nào là đúng nhất

---

## ✅ GIẢI PHÁP MỚI

**Bây giờ**, hệ thống dùng **"từ khóa"** để tìm câu trả lời:

### **Bước 1: Admin nhập từ khóa**

Khi tạo câu hỏi, admin nhập từ khóa liên quan:

```
Câu hỏi: "Mượn sách được bao lâu?"
Từ khóa: mượn, sách, bao lâu, gia hạn, thời hạn, hạn mượn
```

### **Bước 2: Người dùng hỏi**

```
User: "Mượn sách được bao lâu?"
```

### **Bước 3: Chatbot so sánh từ khóa**

Hệ thống tự động trích từ khóa từ câu hỏi của user:

```
Từ khóa của user: mượn, sách, bao, lâu
```

Sau đó so sánh với từ khóa trong database:

```
Câu 1: "Mượn sách được bao lâu?"
Keywords: mươn, sách, bao lâu, gia hạn, thời hạn
Match: mượn ✓, sách ✓, bao lâu ✓ = 3/4 từ khóa (75%)
⭐⭐⭐ TỐTNH ẤT

Câu 2: "Gia hạn sách mấy lần?"
Keywords: mượn, sách, gia hạn
Match: mượn ✓, sách ✓ = 2/3 từ khóa (66%)
⭐⭐ TỐT

Câu 3: "Thẻ thư viện có hạn không?"
Keywords: thẻ, hạn
Match: không có = 0/2 từ khóa (0%)
❌ KHÔNG MATCH
```

### **Bước 4: Chatbot trả lời**

**Nếu match 100%:**
```
Chatbot trả lời ngay:
"Thời hạn mượn sách là 14 ngày. Bạn có thể gia hạn thêm 7 ngày 
nếu sách chưa có người đặt trước."
```

**Nếu không match 100%:**
```
Chatbot hiển thị top 5 gợi ý:
"Bạn muốn hỏi về:"
1. Mượn sách được bao lâu? (75% match)
2. Gia hạn sách mấy lần? (66% match)
3. Có thể mượn bao nhiêu cuốn? (50% match)
4. Phạt trễ hạn là bao nhiêu? (40% match)
5. Mất sách phải làm sao? (30% match)
```

---

## 📊 SO SÁNH

| Tiêu chí | **Cũ** | **Mới** |
|---|---|---|
| **Số câu gợi ý** | 20 câu | 5 câu |
| **Cách tìm** | Tìm kiếm toàn văn bản | So sánh từ khóa |
| **Độ chính xác** | Thấp (có thể sai) | Cao (chính xác hơn) |
| **Tốc độ** | Chậm | Nhanh |
| **Dữ liệu tải lên** | Nhiều | Ít |
| **Trải nghiệm** | Phải chọn | Trả lời ngay |

---

## 🎯 LỢI ÍCH

### **Cho người dùng:**
- ✅ **Nhanh hơn** - Chatbot trả lời đúng ngay, không cần chọn
- ✅ **Gọn hơn** - Chỉ 5 câu gợi ý thay vì 20 câu
- ✅ **Chính xác hơn** - Dùng từ khóa thay vì tìm kiếm mơ hồ
- ✅ **Tiết kiệm dữ liệu** - Tải ít dữ liệu hơn

### **Cho admin:**
- ✅ **Dễ quản lý** - Chỉ cần nhập từ khóa khi tạo câu hỏi
- ✅ **Tự động ranking** - Hệ thống tự động xếp hạng
- ✅ **Giảm câu hỏi chưa trả lời** - Chatbot trả lời chính xác hơn

---

## 💡 CÁCH SỬ DỤNG

### **Khi tạo/sửa câu hỏi:**

1. **Vào Admin Panel** → **Quản lý câu hỏi**
2. **Click "Thêm câu hỏi"** hoặc **sửa câu hỏi cũ**
3. **Nhập câu hỏi:**
   ```
   "Mượn sách được bao lâu?"
   ```
4. **Nhập từ khóa** (cách nhau bằng dấu phẩy):
   ```
   mượn, sách, bao lâu, gia hạn, thời hạn, hạn mượn
   ```
5. **Nhập câu trả lời:**
   ```
   "Thời hạn mượn sách là 14 ngày. Bạn có thể gia hạn thêm 7 ngày 
   nếu sách chưa có người đặt trước."
   ```
6. **Click "Lưu"**

### **Khi người dùng hỏi:**

1. **Mở chatbot**
2. **Nhập câu hỏi:**
   ```
   "Mượn sách được bao lâu?"
   ```
3. **Chatbot tự động:**
   - Trích từ khóa: `mượn, sách, bao, lâu`
   - So sánh với database
   - Trả lời hoặc hiển thị gợi ý

---

## 📝 VÍ DỤ THỰC TẾ

### **Ví dụ 1: Match 100% → Trả lời ngay**

```
User: "Mượn sách được bao lâu?"
Keywords: mượn, sách, bao, lâu

Câu 1 match: mượn ✓, sách ✓, bao lâu ✓ = 100%
→ Chatbot trả lời ngay:
   "Thời hạn mượn sách là 14 ngày..."
```

### **Ví dụ 2: Không match 100% → Hiển thị gợi ý**

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

### **Ví dụ 3: Không match → Hiển thị thông báo**

```
User: "Phòng tự học ở đâu?"
Keywords: phòng, tự học, đâu

Không có câu nào match từ khóa này
→ Chatbot hiển thị:
   "Xin lỗi, mình không tìm thấy câu trả lời chính xác.
   Bạn có thể thử hỏi lại hoặc liên hệ trực tiếp với thủ thư."
```

---

## 🔧 HƯỚNG DẪN NHẬP TỪ KHÓA

### **Từ khóa tốt:**
```
Câu hỏi: "Làm thế nào để mượn sách?"
Từ khóa: mượn sách, mượn, borrow, thẻ thư viện, quy trình mượn
✅ Đầy đủ, rõ ràng, dễ hiểu
```

### **Từ khóa kém:**
```
Câu hỏi: "Làm thế nào để mượn sách?"
Từ khóa: a, b, c, xyz
❌ Quá ngắn, không liên quan
```

### **Mẹo nhập từ khóa:**
1. **Từ chính:** mượn, sách, thẻ
2. **Từ phụ:** borrow, book, library card
3. **Từ liên quan:** gia hạn, thời hạn, quy trình
4. **Từ đồng nghĩa:** mượn = borrow, sách = book

---

## ❓ CÂU HỎI THƯỜNG GẶP

**Q: Nếu không nhập từ khóa thì sao?**
A: Hệ thống vẫn hoạt động bình thường, nhưng sẽ dùng tìm kiếm toàn văn bản (cách cũ). Để có kết quả tốt nhất, hãy nhập từ khóa.

**Q: Có thể nhập bao nhiêu từ khóa?**
A: Không giới hạn, nhưng nên nhập 5-10 từ khóa chính để tránh quá tải.

**Q: Từ khóa có phân biệt hoa/thường không?**
A: Không, hệ thống tự động chuyển thành chữ thường để so sánh.

**Q: Nếu user hỏi bằng tiếng Anh thì sao?**
A: Hệ thống hỗ trợ cả tiếng Việt và tiếng Anh. Hãy nhập từ khóa tiếng Anh nếu có.

**Q: Tại sao chỉ hiển thị 5 câu gợi ý?**
A: Để tránh quá tải thông tin. 5 câu tốt nhất đã đủ để người dùng tìm được câu trả lời.

---

## 📞 LIÊN HỆ & HỖ TRỢ

Nếu có câu hỏi hoặc cần hỗ trợ, vui lòng liên hệ:

- **Email:** celras@tvu.edu.vn
- **Điện thoại:** (02943) 855 246 (ext. 142)
- **Website:** https://celri.tvu.edu.vn

---

**Ngày cập nhật:** 14/03/2026  
**Phiên bản:** 1.1  
**Trạng thái:** Hoàn thành Tuần 02
