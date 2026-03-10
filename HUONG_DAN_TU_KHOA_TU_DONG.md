# Hướng Dẫn Sử Dụng Chức Năng Tự Động Tạo Từ Khóa

## Tổng Quan

Hệ thống chatbot thư viện đã được bổ sung chức năng **tự động tạo từ khóa** cho mỗi câu hỏi. Khi admin tạo hoặc cập nhật câu hỏi, hệ thống sẽ:

1. ✅ Tự động phân tích nội dung câu hỏi
2. ✅ Trích xuất các từ khóa quan trọng (tiếng Việt)
3. ✅ Tự động dịch sang tiếng Anh (dựa trên từ điển tích hợp)
4. ✅ Lưu tất cả từ khóa vào database để tăng độ chính xác tìm kiếm

## Cài Đặt

### Bước 1: Chạy Migration Database

```bash
mysql -u root -p chatbot_thuvien < database/migration_auto_keywords.sql
```

Hoặc import file `migration_auto_keywords.sql` qua phpMyAdmin.

Migration này sẽ:
- Thêm cột `is_auto` (phân biệt từ khóa tự động/thủ công)
- Thêm cột `language` (vi/en/both)
- Tạo các index để tối ưu tìm kiếm

### Bước 2: Kiểm Tra File Đã Được Tạo

Đảm bảo các file sau đã có trong dự án:

```
app/helpers/KeywordGenerator.php          ← Class tạo từ khóa tự động
database/migration_auto_keywords.sql      ← Migration database
HUONG_DAN_TU_KHOA_TU_DONG.md             ← File này
```

## Cách Sử Dụng

### 1. Tạo Câu Hỏi Mới

Khi admin tạo câu hỏi mới qua API:

```javascript
POST /api/admin/questions
{
  "question_text": "Làm thế nào để mượn sách tại thư viện?",
  "answer_text": "Để mượn sách, bạn cần có thẻ thư viện hợp lệ...",
  "category_id": 1,
  "keywords": ["mượn sách", "thủ tục"]  // Từ khóa thủ công (tùy chọn)
}
```

**Response:**
```json
{
  "success": true,
  "id": 123,
  "auto_keywords": {
    "vi": [
      "mượn sách",
      "thư viện",
      "làm thế nào",
      "mượn",
      "sách"
    ],
    "en": [
      "borrow book",
      "library",
      "borrow"
    ]
  }
}
```

Hệ thống tự động:
- ✅ Lưu từ khóa thủ công (nếu có) với `is_auto = 0`
- ✅ Tạo và lưu từ khóa tiếng Việt với `is_auto = 1, language = 'vi'`
- ✅ Tạo và lưu từ khóa tiếng Anh với `is_auto = 1, language = 'en'`

### 2. Cập Nhật Câu Hỏi

Khi cập nhật câu hỏi, hệ thống sẽ:
- Xóa tất cả từ khóa cũ (cả thủ công và tự động)
- Tạo lại từ khóa tự động từ nội dung câu hỏi mới

```javascript
PUT /api/admin/question/123
{
  "question_text": "Quy trình mượn sách như thế nào?",
  "answer_text": "...",
  "keywords": ["quy trình", "mượn sách"]
}
```

### 3. Xem Từ Khóa Của Câu Hỏi

```javascript
GET /api/admin/keywords/123
```

**Response:**
```json
{
  "keywords": {
    "manual": [
      {
        "id": 1,
        "keyword": "quy trình",
        "is_auto": 0,
        "language": "vi",
        "created_at": "2026-03-09 10:00:00"
      }
    ],
    "auto_vi": [
      {
        "id": 2,
        "keyword": "mượn sách",
        "is_auto": 1,
        "language": "vi",
        "created_at": "2026-03-09 10:00:00"
      },
      {
        "id": 3,
        "keyword": "quy trình",
        "is_auto": 1,
        "language": "vi",
        "created_at": "2026-03-09 10:00:00"
      }
    ],
    "auto_en": [
      {
        "id": 4,
        "keyword": "borrow book",
        "is_auto": 1,
        "language": "en",
        "created_at": "2026-03-09 10:00:00"
      }
    ]
  }
}
```

### 4. Tạo Lại Từ Khóa Tự Động

Nếu muốn tạo lại từ khóa tự động cho một câu hỏi (ví dụ sau khi cập nhật từ điển):

```javascript
POST /api/admin/regenerateKeywords/123
```

**Response:**
```json
{
  "success": true,
  "auto_keywords": {
    "vi": ["mượn sách", "thư viện", ...],
    "en": ["borrow book", "library", ...]
  },
  "message": "Đã tạo lại từ khóa tự động"
}
```

### 5. Quản Lý Từ Điển Dịch

#### Xem từ điển hiện tại:

```javascript
GET /api/admin/dictionary
```

**Response:**
```json
{
  "dictionary": {
    "mượn": "borrow",
    "trả": "return",
    "sách": "book",
    "thư viện": "library",
    ...
  }
}
```

#### Thêm từ mới vào từ điển:

```javascript
POST /api/admin/dictionary
{
  "vi": "gia hạn",
  "en": "renew"
}
```

**Lưu ý:** Từ điển được lưu trong code (KeywordGenerator.php). Để lưu vĩnh viễn, cần cập nhật file hoặc lưu vào database.

## Cách Hoạt Động

### Thuật Toán Tạo Từ Khóa Tiếng Việt

1. **Chuẩn hóa text:** Chuyển về chữ thường, loại bỏ dấu câu
2. **Trích xuất cụm từ:** Ưu tiên cụm 2-3 từ (ví dụ: "mượn sách", "thư viện")
3. **Trích xuất từ đơn:** Lọc bỏ stop words (là, và, của, cho...)
4. **Sắp xếp:** Ưu tiên cụm từ trước, từ đơn sau
5. **Giới hạn:** Tối đa 15 từ khóa tiếng Việt

### Thuật Toán Dịch Sang Tiếng Anh

1. **Tra từ điển:** Tìm từ/cụm từ trong từ điển tích hợp
2. **Dịch cụm từ:** Dịch từng từ trong cụm, sau đó ghép lại
3. **Loại bỏ trùng lặp**
4. **Giới hạn:** Tối đa 10 từ khóa tiếng Anh

### Tìm Kiếm Với Từ Khóa

Khi người dùng hỏi câu hỏi, hệ thống tìm kiếm theo thứ tự:

1. **Exact match:** So khớp chính xác toàn bộ câu hỏi
2. **FULLTEXT search:** Tìm kiếm toàn văn (MySQL FULLTEXT)
3. **Keyword match:** Tìm theo từ khóa (cả tiếng Việt và tiếng Anh)
   - Ưu tiên từ khóa thủ công (`is_auto = 0`)
   - Sau đó đến từ khóa tự động (`is_auto = 1`)
4. **LIKE search:** Tìm kiếm mờ (fallback)

## Ví Dụ Thực Tế

### Ví Dụ 1: Câu Hỏi Tiếng Việt

**Input:**
```
"Làm thế nào để gia hạn thẻ thư viện?"
```

**Từ khóa tự động:**
- **Tiếng Việt:** gia hạn thẻ, thẻ thư viện, gia hạn, thẻ, thư viện
- **Tiếng Anh:** renew card, library card, renew, library

**Người dùng có thể hỏi:**
- "gia hạn thẻ" → Tìm thấy
- "renew card" → Tìm thấy (tiếng Anh)
- "extend library card" → Không tìm thấy (chưa có trong từ điển)

### Ví Dụ 2: Câu Hỏi Phức Tạp

**Input:**
```
"Quy trình đăng ký mượn tài liệu luận văn tốt nghiệp như thế nào?"
```

**Từ khóa tự động:**
- **Tiếng Việt:** 
  - Cụm từ: đăng ký mượn, tài liệu luận văn, luận văn tốt nghiệp, quy trình đăng ký
  - Từ đơn: quy trình, đăng ký, mượn, tài liệu, luận văn, tốt nghiệp
- **Tiếng Anh:** 
  - register borrow, thesis document, graduate thesis, procedure register

### Ví Dụ 3: Upload File Word

Khi admin upload file Word chứa nhiều câu hỏi, hệ thống sẽ:

1. Trích xuất text từ file
2. Tự động tạo Q&A
3. **Tự động tạo từ khóa cho mỗi câu hỏi**
4. Lưu tất cả vào database

## Tùy Chỉnh

### Thêm Stop Words

Chỉnh sửa file `app/helpers/KeywordGenerator.php`:

```php
private static $stopWordsVi = [
    'là', 'và', 'của', 'cho', 'với',
    // Thêm stop words mới ở đây
    'từ_mới_1', 'từ_mới_2',
];
```

### Mở Rộng Từ Điển

Thêm từ vào từ điển trong `KeywordGenerator.php`:

```php
private static $viToEnDict = [
    'mượn' => 'borrow',
    'trả' => 'return',
    // Thêm từ mới
    'từ_tiếng_việt' => 'english_word',
];
```

### Thay Đổi Giới Hạn Từ Khóa

```php
// Trong KeywordGenerator.php

// Giới hạn từ khóa tiếng Việt (mặc định: 15)
return array_slice($result, 0, 20); // Tăng lên 20

// Giới hạn từ khóa tiếng Anh (mặc định: 10)
return array_slice($enKeywords, 0, 15); // Tăng lên 15
```

## Lợi Ích

✅ **Tăng độ chính xác tìm kiếm:** Người dùng có thể hỏi theo nhiều cách khác nhau

✅ **Hỗ trợ đa ngôn ngữ:** Tự động dịch sang tiếng Anh, hỗ trợ sinh viên quốc tế

✅ **Tiết kiệm thời gian:** Admin không cần nhập từ khóa thủ công

✅ **Tự động cập nhật:** Khi sửa câu hỏi, từ khóa được tạo lại tự động

✅ **Linh hoạt:** Vẫn cho phép admin thêm từ khóa thủ công nếu cần

## Lưu Ý

⚠️ **Từ điển dịch:** Hiện tại từ điển được lưu trong code. Để lưu vĩnh viễn, cần:
- Tạo bảng `translation_dictionary` trong database
- Cập nhật KeywordGenerator để đọc từ database

⚠️ **Performance:** Với hàng nghìn câu hỏi, nên:
- Tạo index cho bảng `keywords`
- Cache từ điển trong Redis/Memcached

⚠️ **Chất lượng dịch:** Từ điển tích hợp chỉ bao gồm các từ phổ biến trong thư viện. Cần bổ sung thêm từ theo ngữ cảnh cụ thể.

## Hỗ Trợ

Nếu có vấn đề, kiểm tra:

1. ✅ Database đã chạy migration chưa?
2. ✅ File `KeywordGenerator.php` đã được include đúng chưa?
3. ✅ Có lỗi trong log PHP không? (kiểm tra `error_log`)

## Tác Giả

Chức năng được phát triển bởi: **Kiro AI Assistant**  
Ngày: 09/03/2026  
Phiên bản: 1.0
