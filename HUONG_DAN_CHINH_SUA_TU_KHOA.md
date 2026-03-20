# Hướng dẫn chỉnh sửa từ khóa tự động

## Tính năng mới

Hệ thống đã được bổ sung chức năng **chỉnh sửa bộ từ khóa tự động** cho phép quản trị viên:

1. ✏️ **Chỉnh sửa từ khóa tự động** - Sửa trực tiếp từ khóa đã được tạo tự động
2. 🗑️ **Xóa từ khóa không phù hợp** - Loại bỏ từ khóa không chính xác
3. 🔄 **Tạo lại toàn bộ từ khóa** - Phân tích lại câu hỏi và tạo từ khóa mới

## Cách sử dụng

### 1. Xem từ khóa tự động

Khi **chỉnh sửa một câu hỏi** trong trang "Quản lý câu hỏi":

1. Click vào nút **"Sửa"** (biểu tượng bút) ở câu hỏi bất kỳ
2. Modal chỉnh sửa sẽ hiển thị phần **"Từ khóa tự động"** bên dưới
3. Từ khóa được phân thành 2 nhóm:
   - 🇻🇳 **Tiếng Việt** - Từ khóa tiếng Việt
   - 🇬🇧 **Tiếng Anh** - Từ khóa tiếng Anh (dịch tự động)

### 2. Chỉnh sửa từ khóa

**Cách 1: Sửa trực tiếp**
- Click vào từ khóa bạn muốn sửa
- Nhập nội dung mới
- Nhấn **Enter** hoặc click ra ngoài để lưu

**Cách 2: Xóa từ khóa**
- Di chuột vào từ khóa muốn xóa
- Click vào nút **X** (màu đỏ) xuất hiện bên phải
- Xác nhận xóa

### 3. Tạo lại từ khóa

Nếu muốn hệ thống phân tích lại và tạo từ khóa mới:

1. Click nút **"Tạo lại"** ở góc phải phần "Từ khóa tự động"
2. Xác nhận hành động (từ khóa cũ sẽ bị xóa)
3. Hệ thống sẽ tự động:
   - Xóa tất cả từ khóa tự động cũ
   - Phân tích lại câu hỏi
   - Tạo bộ từ khóa mới (tiếng Việt + tiếng Anh)

### 4. Lưu thay đổi

- Các thay đổi về từ khóa được **lưu ngay lập tức**
- Không cần nhấn nút "Lưu" ở modal
- Thông báo xác nhận sẽ hiển thị sau mỗi thao tác

## Lưu ý quan trọng

⚠️ **Chỉ có thể chỉnh sửa từ khóa TỰ ĐỘNG**
- Từ khóa thủ công (nhập bằng tay) vẫn được quản lý qua trường "Từ khóa (Thủ công)"
- Từ khóa tự động có màu nền xanh lá (tiếng Việt) hoặc xanh dương (tiếng Anh)

✅ **Khi nào nên chỉnh sửa từ khóa?**
- Từ khóa tự động không chính xác
- Từ khóa quá chung chung hoặc quá cụ thể
- Muốn thêm biến thể của từ khóa
- Từ khóa bị lỗi chính tả

🔄 **Khi nào nên tạo lại từ khóa?**
- Đã sửa nội dung câu hỏi đáng kể
- Từ khóa hiện tại không còn phù hợp
- Muốn hệ thống phân tích lại với thuật toán mới

## API Endpoints mới

Các endpoint đã được thêm vào hệ thống:

### 1. Lấy danh sách từ khóa
```
GET /api/admin/keywords/{questionId}
```

**Response:**
```json
{
  "keywords": {
    "manual": [...],
    "auto_vi": [
      {"id": 1, "keyword": "mượn sách", "language": "vi", "is_auto": 1}
    ],
    "auto_en": [
      {"id": 2, "keyword": "borrow book", "language": "en", "is_auto": 1}
    ]
  }
}
```

### 2. Cập nhật từ khóa
```
PUT /api/admin/updateKeyword/{keywordId}
Content-Type: application/json

{
  "keyword": "từ khóa mới"
}
```

### 3. Xóa từ khóa
```
DELETE /api/admin/deleteKeyword/{keywordId}
```

### 4. Tạo lại từ khóa tự động
```
POST /api/admin/regenerateKeywords/{questionId}
```

## Cấu trúc Database

Bảng `keywords` đã có sẵn các cột:
- `id` - ID từ khóa
- `question_id` - ID câu hỏi
- `keyword` - Nội dung từ khóa
- `is_auto` - 0: thủ công, 1: tự động
- `language` - 'vi', 'en', 'both'
- `weight` - Trọng số (dùng cho tìm kiếm)

## Giao diện

### Màn hình chỉnh sửa câu hỏi

```
┌─────────────────────────────────────────┐
│ Sửa câu hỏi                        [X]  │
├─────────────────────────────────────────┤
│ Danh mục: [Chọn danh mục ▼]            │
│                                         │
│ Câu hỏi: [________________]            │
│                                         │
│ Câu trả lời: [________________]        │
│                                         │
│ Từ khóa (Thủ công): [________]         │
│                                         │
│ ┌─ Từ khóa tự động ──────── [Tạo lại] │
│ │                                       │
│ │ 🇻🇳 TIẾNG VIỆT (5)                   │
│ │ [mượn sách] [thẻ TV] [gia hạn]...   │
│ │                                       │
│ │ 🇬🇧 TIẾNG ANH (5)                    │
│ │ [borrow] [library card] [renew]...   │
│ └───────────────────────────────────── │
│                                         │
│              [Hủy]  [Lưu]              │
└─────────────────────────────────────────┘
```

## Hỗ trợ

Nếu gặp vấn đề, vui lòng kiểm tra:
1. Đã đăng nhập với quyền quản trị viên
2. Câu hỏi đã được lưu (có ID)
3. Kết nối mạng ổn định
4. Console trình duyệt không có lỗi (F12)

---

**Phiên bản:** 1.0  
**Ngày cập nhật:** 20/03/2026
