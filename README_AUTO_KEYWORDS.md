# Chức Năng Tự Động Tạo Từ Khóa - Quick Start

## 🚀 Cài Đặt Nhanh

### 1. Chạy Migration Database
```bash
mysql -u root -p chatbot_thuvien < database/migration_auto_keywords.sql
```

### 2. Test Chức Năng
```bash
php test_keyword_generator.php
```

## 📋 Các File Mới

```
database/migration_auto_keywords.sql      ← Migration database
app/helpers/KeywordGenerator.php          ← Class tạo từ khóa
test_keyword_generator.php                ← File test
HUONG_DAN_TU_KHOA_TU_DONG.md             ← Hướng dẫn chi tiết
README_AUTO_KEYWORDS.md                   ← File này
```

## ✨ Tính Năng

✅ Tự động tạo từ khóa tiếng Việt từ câu hỏi  
✅ Tự động dịch sang tiếng Anh  
✅ Hỗ trợ tìm kiếm đa ngôn ngữ  
✅ Phân biệt từ khóa tự động/thủ công  
✅ API quản lý từ khóa và từ điển  

## 🔧 API Endpoints Mới

```
GET    /api/admin/keywords/{questionId}           ← Xem từ khóa
POST   /api/admin/regenerateKeywords/{questionId} ← Tạo lại từ khóa
GET    /api/admin/dictionary                      ← Xem từ điển
POST   /api/admin/dictionary                      ← Thêm từ vào từ điển
```

## 📖 Ví Dụ Sử Dụng

### Tạo câu hỏi mới (tự động tạo từ khóa)
```javascript
POST /api/admin/questions
{
  "question_text": "Làm thế nào để mượn sách?",
  "answer_text": "Để mượn sách, bạn cần có thẻ thư viện...",
  "category_id": 1
}

// Response
{
  "success": true,
  "id": 123,
  "auto_keywords": {
    "vi": ["mượn sách", "làm thế nào", "mượn", "sách"],
    "en": ["borrow book", "borrow"]
  }
}
```

### Xem từ khóa của câu hỏi
```javascript
GET /api/admin/keywords/123

// Response
{
  "keywords": {
    "manual": [],           // Từ khóa thủ công
    "auto_vi": [...],       // Từ khóa tự động (tiếng Việt)
    "auto_en": [...]        // Từ khóa tự động (tiếng Anh)
  }
}
```

## 🎯 Cách Hoạt Động

1. **Admin tạo/sửa câu hỏi** → Hệ thống tự động phân tích
2. **Trích xuất từ khóa tiếng Việt** → Loại bỏ stop words, ưu tiên cụm từ
3. **Dịch sang tiếng Anh** → Dựa trên từ điển tích hợp
4. **Lưu vào database** → Đánh dấu `is_auto = 1`, `language = 'vi'/'en'`
5. **Tìm kiếm** → Hỗ trợ cả tiếng Việt và tiếng Anh

## 📊 Cấu Trúc Database

```sql
keywords
├── id
├── question_id
├── keyword
├── is_auto          ← 0: thủ công, 1: tự động
├── language         ← 'vi', 'en', 'both'
└── created_at
```

## 🔍 Ví Dụ Thực Tế

**Câu hỏi:** "Làm thế nào để gia hạn thẻ thư viện?"

**Từ khóa tự động:**
- 🇻🇳 Tiếng Việt: `gia hạn thẻ`, `thẻ thư viện`, `gia hạn`, `thẻ`, `thư viện`
- 🇬🇧 Tiếng Anh: `renew card`, `library card`, `renew`, `library`

**Người dùng có thể hỏi:**
- "gia hạn thẻ" ✅
- "renew card" ✅
- "extend library card" ❌ (chưa có trong từ điển)

## 🛠️ Tùy Chỉnh

### Thêm từ vào từ điển
```php
// Trong KeywordGenerator.php
private static $viToEnDict = [
    'mượn' => 'borrow',
    'từ_mới' => 'new_word',  // Thêm ở đây
];
```

### Thay đổi số lượng từ khóa
```php
// Giới hạn từ khóa tiếng Việt (mặc định: 15)
return array_slice($result, 0, 20);

// Giới hạn từ khóa tiếng Anh (mặc định: 10)
return array_slice($enKeywords, 0, 15);
```

## 📚 Tài Liệu Chi Tiết

Xem file `HUONG_DAN_TU_KHOA_TU_DONG.md` để biết thêm chi tiết về:
- Thuật toán tạo từ khóa
- API endpoints đầy đủ
- Ví dụ nâng cao
- Troubleshooting

## ⚡ Performance Tips

- ✅ Đã tạo index cho `keywords.language` và `keywords.is_auto`
- ✅ Ưu tiên từ khóa thủ công trong tìm kiếm
- ✅ Giới hạn số lượng từ khóa để tránh spam

## 🐛 Troubleshooting

**Lỗi: "Column 'is_auto' not found"**
→ Chưa chạy migration. Chạy: `mysql -u root -p chatbot_thuvien < database/migration_auto_keywords.sql`

**Không tạo được từ khóa tiếng Anh**
→ Từ chưa có trong từ điển. Thêm vào `KeywordGenerator.php` hoặc dùng API `/api/admin/dictionary`

**Từ khóa không chính xác**
→ Kiểm tra stop words trong `KeywordGenerator.php`, có thể cần bổ sung

## 📞 Liên Hệ

Nếu có vấn đề, kiểm tra:
1. Database đã chạy migration chưa?
2. File `KeywordGenerator.php` có lỗi syntax không?
3. Log PHP có báo lỗi gì không?

---

**Phát triển bởi:** Kiro AI Assistant  
**Ngày:** 09/03/2026  
**Version:** 1.0
