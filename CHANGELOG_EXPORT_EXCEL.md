# Changelog - Chức năng xuất Excel

## Ngày: 2026-03-19

### Tính năng mới: Xuất toàn bộ dữ liệu câu hỏi ra file Excel

#### Các file đã thêm/sửa đổi:

1. **app/controllers/AdminController.php**
   - Thêm method `exportQuestions()` để xuất Excel
   - Thêm method `generateExcelXML()` để tạo file Excel XML format
   - Sử dụng Excel 2003 XML format - KHÔNG CẦN thư viện bên ngoài
   - Xuất đầy đủ thông tin: ID, câu hỏi, câu trả lời (VI/EN), danh mục, từ khóa, nguồn, trạng thái, ngày tạo/cập nhật

2. **public/pages/admin/questions.html**
   - Thêm nút "Xuất Excel" (màu xanh lá) vào header
   - Đặt giữa nút "Xóa đã chọn" và "Thêm câu hỏi"

3. **public/assets/js/admin.js**
   - Thêm hàm `exportQuestionsToExcel()` để gọi API và tải file
   - Hiển thị loading spinner khi đang xuất
   - Xử lý lỗi và thông báo cho người dùng

#### Cấu trúc file Excel xuất ra:

| Cột | Nội dung | Độ rộng |
|-----|----------|---------|
| A | ID | 50 |
| B | Câu hỏi | 300 |
| C | Câu trả lời (Tiếng Việt) | 400 |
| D | Câu trả lời (Tiếng Anh) | 400 |
| E | Danh mục | 150 |
| F | Từ khóa thủ công | 200 |
| G | Từ khóa tự động (VI) | 200 |
| H | Từ khóa tự động (EN) | 200 |
| I | Nguồn | 100 |
| J | Trạng thái | 100 |
| K | Ngày tạo | 150 |
| L | Ngày cập nhật | 150 |

#### Tính năng:
- ✅ Header có màu xanh dương (#1976D2), chữ trắng, in đậm
- ✅ Border cho toàn bộ bảng
- ✅ Wrap text tự động cho các cột dài
- ✅ Loại bỏ HTML tags khỏi câu trả lời
- ✅ Tên file tự động: `DuLieuCauHoi_YYYY-MM-DD_HHMMSS.xls`
- ✅ Hỗ trợ tiếng Việt đầy đủ (UTF-8 BOM)
- ✅ Xuất tất cả câu hỏi (không phân trang)
- ✅ **KHÔNG CẦN cài đặt thư viện** - sử dụng PHP thuần

#### API Endpoint:
```
GET /api/admin/exportQuestions
```

**Response**: File Excel (.xls) được tải về trực tiếp

**Lỗi có thể gặp**:
- 404: Không có dữ liệu để xuất
- 500: Lỗi khi tạo file Excel

#### Cách sử dụng:

**Sử dụng ngay** - KHÔNG CẦN cài đặt gì:
- Đăng nhập vào trang quản trị
- Vào "Quản lý câu hỏi"
- Click nút "Xuất Excel"
- File sẽ tự động tải về

#### Yêu cầu hệ thống:
- PHP >= 7.4
- **KHÔNG CẦN** Composer
- **KHÔNG CẦN** thư viện bên ngoài

#### Công nghệ:
- Excel XML format (Excel 2003)
- PHP thuần - không dependency
- UTF-8 BOM để hỗ trợ tiếng Việt

#### Lưu ý:
- File Excel format .xls (Excel 2003 XML)
- Mở được bằng Microsoft Excel, LibreOffice Calc, WPS Office
- Không giới hạn số lượng câu hỏi xuất
- Hỗ trợ xuất cả câu hỏi đã tắt (is_active = 0)

#### Testing:
- ✅ Xuất với dữ liệu đầy đủ
- ✅ Xuất khi không có dữ liệu
- ✅ Kiểm tra encoding tiếng Việt
- ✅ Kiểm tra format Excel
- ✅ Kiểm tra style và màu sắc

#### Tương thích:
- Microsoft Excel 2003+
- LibreOffice Calc
- Google Sheets
- WPS Office

---

## Hỗ trợ

Nếu gặp vấn đề, liên hệ:
- Email: celras@tvu.edu.vn
- Điện thoại: (02943) 855 246
