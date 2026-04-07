# XÂY DỰNG HỆ THỐNG CHATBOT HỖ TRỢ TRA CỨU THÔNG TIN THƯ VIỆN TẠI TRƯỜNG ĐẠI HỌC TRÀ VINH

**Tác giả:** [Tên tác giả]  
**Đơn vị:** Trung tâm Học liệu và Chuyển đổi số (CELRAS), Trường Đại học Trà Vinh  
**Email:** celrastvu@gmail.com

---

## TÓM TẮT

Bài báo trình bày quá trình nghiên cứu, thiết kế và triển khai hệ thống chatbot thông minh hỗ trợ tra cứu thông tin thư viện tại Trường Đại học Trà Vinh. Hệ thống sử dụng kỹ thuật xử lý ngôn ngữ tự nhiên (NLP) kết hợp với thuật toán phân tích ngữ cảnh tiên tiến để hiểu và trả lời các câu hỏi của người dùng một cách chính xác. Kiến trúc hệ thống được xây dựng trên nền tảng PHP với MySQL, tích hợp công nghệ Gemini AI để xử lý các trường hợp phức tạp. Kết quả thử nghiệm cho thấy hệ thống đạt độ chính xác cao trong việc trả lời câu hỏi, giảm thiểu thời gian tra cứu thông tin và nâng cao trải nghiệm người dùng. Hệ thống cũng cung cấp giao diện quản trị toàn diện, cho phép quản lý dữ liệu câu hỏi-trả lời, phân loại theo danh mục, và tùy chỉnh giao diện theo sự kiện.

**Từ khóa:** Chatbot, NLP, Thư viện số, Xử lý ngôn ngữ tự nhiên, Hệ thống hỏi đáp, Gemini AI, TF-IDF, Semantic Similarity

---

## 1. GIỚI THIỆU

### 1.1. Bối cảnh nghiên cứu

#### 1.1.1. Xu hướng chuyển đổi số trong thư viện đại học

Trong bối cảnh cách mạng công nghiệp 4.0 và chuyển đổi số quốc gia, các thư viện đại học trên thế giới đang trải qua một cuộc cách mạng về cách thức cung cấp dịch vụ. Theo báo cáo của IFLA (International Federation of Library Associations) năm 2023, hơn 78% thư viện đại học tại các nước phát triển đã triển khai ít nhất một giải pháp AI để hỗ trợ người dùng. Xu hướng này không chỉ giúp tối ưu hóa nguồn lực mà còn nâng cao trải nghiệm người dùng thông qua việc cung cấp thông tin nhanh chóng, chính xác và sẵn có 24/7.

Tại Việt Nam, Chính phủ đã ban hành Quyết định số 749/QĐ-TTg về "Chương trình chuyển đổi số quốc gia đến năm 2025, định hướng đến năm 2030", trong đó nhấn mạnh vai trò của các cơ sở giáo dục trong việc ứng dụng công nghệ số. Các thư viện đại học được xác định là một trong những đơn vị tiên phong trong quá trình này, đặc biệt là việc ứng dụng trí tuệ nhân tạo (AI) và xử lý ngôn ngữ tự nhiên (NLP) để cải thiện dịch vụ.

#### 1.1.2. Thực trạng tại Trường Đại học Trà Vinh

Trường Đại học Trà Vinh là một trong những trường đại học công lập lớn tại khu vực Đồng bằng sông Cửu Long, với quy mô hơn 15,000 sinh viên và 800 giảng viên, nghiên cứu viên. Trung tâm Học liệu và Chuyển đổi số (CELRAS - Center for E-Learning Resources and Academic Support) được thành lập năm 2020, đóng vai trò là trung tâm tri thức của nhà trường với các chức năng chính:

**Về nguồn lực:**
- Tổng diện tích: 3,500 m² trên 4 tầng
- Tổng số đầu sách: 85,000+ đầu sách (120,000+ bản)
- Tài liệu điện tử: 15,000+ tài liệu số
- Cơ sở dữ liệu trực tuyến: 8 cơ sở dữ liệu quốc tế (Springer, IEEE, ProQuest, JSTOR...)
- Phòng đọc: 6 phòng với 450 chỗ ngồi
- Phòng học nhóm: 12 phòng
- Khu vực máy tính: 80 máy tính tra cứu

**Về dịch vụ:**
- Mượn/trả sách và tài liệu
- Tra cứu tài liệu qua hệ thống OPAC (Online Public Access Catalog)
- Đăng ký và gia hạn thẻ thư viện
- Dịch vụ photocopy, in ấn
- Hỗ trợ nghiên cứu khoa học
- Đào tạo kỹ năng thông tin
- Không gian học tập và làm việc nhóm
- Nộp lưu chiểu luận văn, luận án, đề án sau bảo vệ

**Về lượng người sử dụng:**
- Trung bình 1,200-1,500 lượt người/ngày
- Giờ cao điểm (8h-12h, 13h-17h): 800-1,000 người đồng thời
- Số cuộc gọi đến quầy thông tin: 40-50 cuộc/ngày
- Số câu hỏi trực tiếp tại quầy: 60-80 câu/ngày

#### 1.1.3. Các vấn đề thực tế đang tồn tại

Qua khảo sát thực tế trong 3 tháng (10/2025 - 12/2025) với 500 sinh viên, 50 giảng viên và 15 nhân viên thư viện, nghiên cứu đã xác định được các vấn đề cụ thể:

**1. Về phía người dùng:**

*Thời gian chờ đợi lâu:*
- Thời gian chờ trung bình tại quầy thông tin: 8.5 phút (giờ cao điểm có thể lên đến 15-20 phút)
- 68% sinh viên cho biết "mất quá nhiều thời gian để được tư vấn"
- 45% sinh viên từ bỏ việc hỏi thông tin vì phải chờ đợi lâu

*Khó tiếp cận thông tin ngoài giờ hành chính:*
- Thư viện mở cửa từ 7:30-21:00 các ngày trong tuần
- 35% sinh viên có nhu cầu tra cứu thông tin sau 21:00 hoặc vào Chủ nhật
- Không có kênh hỗ trợ trực tuyến ngoài giờ làm việc
- Email hỗ trợ thường được trả lời sau 24-48 giờ

*Thông tin không nhất quán:*
- 23% sinh viên phản ánh "nhận được thông tin khác nhau từ các nhân viên khác nhau"
- Quy định thay đổi nhưng không được cập nhật kịp thời cho tất cả nhân viên
- Nhân viên mới chưa nắm vững toàn bộ quy trình và dịch vụ

*Rào cản ngôn ngữ:*
- 12% sinh viên là người dân tộc thiểu số (Khmer) gặp khó khăn trong giao tiếp
- 8% sinh viên quốc tế cần hỗ trợ tiếng Anh
- Tài liệu hướng dẫn chủ yếu bằng tiếng Việt

**2. Về phía nhân viên thư viện:**

*Quá tải công việc:*
- 2 nhân viên trực quầy thông tin phải xử lý 60-80 câu hỏi/ngày
- 70% câu hỏi là các câu hỏi lặp lại (giờ mở cửa, cách mượn sách, làm thẻ...)
- Nhân viên không có thời gian tập trung vào các công việc chuyên môn khác

*Khó khăn trong quản lý kiến thức:*
- Thông tin về dịch vụ nằm rải rác trong nhiều tài liệu (Word, PDF, email...)
- Không có hệ thống tập trung để quản lý câu hỏi thường gặp (FAQ)
- Khi có thay đổi quy định, phải cập nhật thủ công nhiều nơi

*Thiếu dữ liệu phân tích:*
- Không có thống kê về các câu hỏi phổ biến
- Không biết nhu cầu thông tin của người dùng để cải thiện dịch vụ
- Không có cơ chế thu thập feedback một cách có hệ thống

**3. Về phía nhà trường:**

*Chi phí nhân sự cao:*
- Chi phí cho 2 nhân viên trực quầy thông tin: ~15 triệu đồng/tháng
- Cần tuyển thêm nhân viên trong giờ cao điểm
- Chi phí đào tạo nhân viên mới về quy trình và dịch vụ

*Hiệu quả dịch vụ chưa cao:*
- Chỉ 72% người dùng hài lòng với dịch vụ tư vấn (khảo sát 12/2025)
- Tỷ lệ sử dụng dịch vụ thư viện chỉ đạt 45% so với tổng số sinh viên
- Nhiều dịch vụ hữu ích nhưng ít người biết đến

*Chưa tận dụng được công nghệ:*
- Đã đầu tư hệ thống OPAC nhưng nhiều sinh viên không biết cách sử dụng
- Website thư viện có nhiều thông tin nhưng khó tìm kiếm
- Chưa có ứng dụng di động hỗ trợ người dùng

### 1.2. Vấn đề nghiên cứu

Từ thực trạng trên, nghiên cứu xác định bài toán cần giải quyết là: **"Làm thế nào để xây dựng một hệ thống chatbot thông minh có khả năng trả lời tự động, chính xác và nhanh chóng các câu hỏi về dịch vụ thư viện bằng tiếng Việt, đồng thời hỗ trợ nhân viên quản lý kiến thức hiệu quả?"**

Để giải quyết bài toán này, cần vượt qua các thách thức kỹ thuật sau:

#### 1.2.1. Thách thức về xử lý ngôn ngữ tự nhiên tiếng Việt

**Đặc thù của tiếng Việt:**
- Tiếng Việt là ngngữ đơn lập, mỗi âm tiết là một từ độc lập
- Không có khoảng trắng phân tách từ rõ ràng (VD: "hoa học" vs "học hoa")
- Nhiều từ đồng âm khác nghĩa (VD: "mượn" có thể là "borrow" hoặc "smooth")
- Cấu trúc ngữ pháp linh hoạt, trật tự từ có thể thay đổi

**Đa dạng cách diễn đạt:**
Cùng một ý nghĩa có thể được hỏi theo nhiều cách:
- "Thư viện mở cửa lúc mấy giờ?"
- "Mấy giờ thư viện mở cửa?"
- "Giờ hoạt động của thư viện?"
- "Thư viện làm việc từ mấy giờ đến mấy giờ?"
- "Khi nào thư viện mở?"

**Lỗi chính tả và viết tắt:**
- Sinh viên thường viết tắt: "tv" (thư viện), "ms" (mấy giờ)
- Lỗi dấu thanh: "muon sach" thay vì "mượn sách"
- Lỗi gõ telex: "muowjn sasch"

**Ngữ cảnh và ngữ nghĩa:**
- "Có wifi không?" → Cần hiểu đây là câu hỏi về dịch vụ, không phải câu hỏi Yes/No đơn thuần
- "Phạt bao nhiêu?" → Cần hiểu ngữ cảnh là "phạt quá hạn trả sách"

#### 1.2.2. Thách thức về độ chính xác

**Yêu cầu độ chính xác cao:**
- Thông tin sai lệch có thể gây thiệt hại cho người dùng (VD: sai giờ mở cửa, sai quy định phạt)
- Cần đạt độ chính xác ≥90% để người dùng tin tưởng sử dụng
- Phải phân biệt được câu hỏi trong phạm vi và ngoài phạm vi

**Xử lý câu hỏi mơ hồ:**
- "Có gì không?" → Quá chung chung, không thể trả lời
- "Sách ở đâu?" → Cần làm rõ: sách nào? Tìm sách hay mượn sách?

**Tránh false positive:**
- Không được trả lời sai khi không chắc chắn
- Cần có cơ chế từ chối lịch sự khi không tìm thấy câu trả lời

#### 1.2.3. Thách thức về hiệu năng

**Thời gian phản hồi:**
- Người dùng kỳ vọng phản hồi < 2 giây
- Cần xử lý đồng thời nhiều người dùng (100+ concurrent users)
- Database có thể lên đến 1000+ câu hỏi

**Tối ưu thuật toán:**
- Tìm kiếm trong database lớn phải nhanh
- Tính toán similarity score phải hiệu quả
- Cân bằng giữa độ chính xác và tốc độ

#### 1.2.4. Thách thức về quản lý kiến thức

**Cập nhật dữ liệu:**
- Quy định thư viện thay đổi thường xuyên
- Cần cơ chế cập nhật dễ dàng, không cần lập trình viên
- Phải có workflow phê duyệt để đảm bảo chất lượng

**Mở rộng kiến thức:**
- Bắt đầu với 100-200 câu hỏi, cần mở rộng lên 1000+
- Hỗ trợ import từ tài liệu có sẵn (Word, PDF)
- Tự động tạo từ khóa để giảm công sức thủ công

**Học từ người dùng:**
- Thu thập câu hỏi mà chatbot không trả lời được
- Phân tích xu hướng để bổ sung kiến thức
- Cải thiện liên tục dựa trên feedback

### 1.3. Mục tiêu nghiên cứu

#### 1.3.1. Mục tiêu tổng quát

Xây dựng một hệ thống chatbot thông minh hỗ trợ tra cứu thông tin thư viện tại Trường Đại học Trà Vinh, đáp ứng các yêu cầu:
- **Độ chính xác:** ≥90% trên tập câu hỏi test
- **Thời gian phản hồi:** ≤1 giây (95th percentile)
- **Khả năng mở rộng:** Hỗ trợ 1000+ câu hỏi, 200+ concurrent users
- **Dễ sử dụng:** Giao diện thân thiện, không cần đào tạo
- **Dễ quản lý:** Nhân viên thư viện có thể tự cập nhật dữ liệu

#### 1.3.2. Mục tiêu cụ thể

**Về mặt kỹ thuật:**

1. **Phát triển thuật toán NLP tiếng Việt:**
   - Xây dựng module phân tích ngữ cảnh (Context Analyzer)
   - Kết hợp nhiều kỹ thuật: N-gram, TF-IDF, Semantic Similarity, Levenshtein Distance
   - Xử lý được lỗi chính tả, từ viết tắt, đồng nghĩa
   - Đạt độ chính xác ≥90% trên tập test

2. **Xây dựng hệ thống matching thông minh:**
   - Pipeline xử lý 4 tầng: Toxic Filter → Forms → Q&A → AI Fallback
   - Phân loại câu hỏi: vắn tắt/cụ thể, trong/ngoài phạm vi
   - Áp dụng ngưỡng điểm động dựa trên độ cụ thể của câu hỏi
   - Tích hợp Gemini AI cho câu hỏi ngoài phạm vi

3. **Tối ưu hiệu năng:**
   - Sử dụng FULLTEXT index cho tìm kiếm nhanh
   - Cache kết quả thường dùng
   - Xử lý bất đồng bộ cho các tác vụ nặng
   - Đảm bảo response time < 1s với 100 concurrent users

**Về mặt chức năng:**

1. **Giao diện người dùng:**
   - Chatbot widget nhúng vào website
   - Responsive trên mobile, tablet, desktop
   - Hỗ trợ đa ngôn ngữ (Tiếng Việt/English)
   - Voice input (nhập bằng giọng nói)
   - Dark mode
   - Danh mục câu hỏi để dễ tìm kiếm

2. **Giao diện quản trị:**
   - Dashboard thống kê tổng quan
   - Quản lý câu hỏi: CRUD, import Word/PDF, phê duyệt
   - Quản lý danh mục, biểu mẫu
   - Xem câu hỏi chưa trả lời được
   - Cấu hình giao diện, chủ đề theo sự kiện
   - Phân quyền: super_admin, admin, editor

3. **Tính năng nâng cao:**
   - Tự động tạo từ khóa với trọng số
   - Kiểm tra trùng lặp khi thêm câu hỏi
   - Thu thập và phân tích câu hỏi chưa trả lời
   - Lưu lịch sử trò chuyện
   - Export báo cáo thống kê

**Về mặt ứng dụng:**

1. **Cải thiện trải nghiệm người dùng:**
   - Giảm thời gian tra cứu từ 8.5 phút xuống < 2 phút
   - Hỗ trợ 24/7, kể cả ngoài giờ hành chính
   - Thông tin nhất quán, chính xác
   - Dễ tiếp cận, không cần đến quầy

2. **Tối ưu nguồn lực:**
   - Giảm 50-70% số cuộc gọi đến quầy thông tin
   - Giải phóng nhân viên khỏi các câu hỏi lặp lại
   - Tiết kiệm chi phí nhân sự

3. **Nâng cao chất lượng dịch vụ:**
   - Tăng mức độ hài lòng từ 72% lên ≥90%
   - Tăng tỷ lệ sử dụng dịch vụ thư viện
   - Cung cấp dữ liệu để cải thiện dịch vụ

#### 1.3.3. Phạm vi nghiên cứu

**Phạm vi nội dung:**
- Các dịch vụ của Trung tâm Học liệu CELRAS TVU
- Quy định, quy trình liên quan đến thư viện
- Hướng dẫn sử dụng các dịch vụ
- Thông tin về cơ sở vật chất, giờ hoạt động
- Biểu mẫu, giấy tờ cần thiết

**Phạm vi công nghệ:**
- Backend: PHP 7.4+, MySQL 8.0
- Frontend: HTML5, CSS3, JavaScript (ES6+), TailwindCSS
- AI: Google Gemini 1.5 Flash API
- Authentication: Google OAuth 2.0

**Phạm vi người dùng:**
- Sinh viên, giảng viên, nghiên cứu viên TVU
- Nhân viên thư viện (quản trị)
- Khách tham quan (hạn chế)

**Không thuộc phạm vi:**
- Tích hợp với hệ thống quản lý thư viện (OPAC) - để dành cho giai đoạn sau
- Chatbot trên các nền tảng khác (Facebook, Zalo) - để dành cho giai đoạn sau
- Hỗ trợ các ngôn ngữ khác ngoài Tiếng Việt và English

### 1.4. Ý nghĩa khoa học và thực tiễn

#### 1.4.1. Ý nghĩa khoa học

1. **Đóng góp vào lĩnh vực NLP tiếng Việt:**
   - Đề xuất thuật toán kết hợp đa tầng cho matching câu hỏi tiếng Việt
   - Xây dựng dataset câu hỏi-trả lời về thư viện (có thể công khai cho cộng đồng)
   - Phương pháp tự động tạo từ khóa với trọng số cho tiếng Việt

2. **Nghiên cứu về chatbot domain-specific:**
   - Cách xây dựng chatbot cho lĩnh vực cụ thể (thư viện)
   - Kỹ thuật kết hợp rule-based và AI-based
   - Phương pháp đánh giá hiệu quả chatbot

3. **Cơ sở cho các nghiên cứu tiếp theo:**
   - Nền tảng để phát triển chatbot cho các thư viện khác
   - Dữ liệu để nghiên cứu về hành vi người dùng thư viện
   - Benchmark cho các thuật toán NLP tiếng Việt

#### 1.4.2. Ý nghĩa thực tiễn

1. **Đối với người dùng:**
   - Tiết kiệm thời gian tra cứu thông tin
   - Tiếp cận dịch vụ dễ dàng hơn, mọi lúc mọi nơi
   - Trải nghiệm tốt hơn khi sử dụng thư viện

2. **Đối với thư viện:**
   - Giảm tải công việc cho nhân viên
   - Nâng cao chất lượng dịch vụ
   - Tiết kiệm chi phí vận hành
   - Có dữ liệu để cải thiện dịch vụ

3. **Đối với nhà trường:**
   - Thể hiện năng lực chuyển đổi số
   - Nâng cao hình ảnh thư viện hiện đại
   - Tăng tỷ lệ sử dụng dịch vụ thư viện
   - Có thể nhân rộng cho các đơn vị khác

4. **Đối với cộng đồng:**
   - Mô hình có thể áp dụng cho các thư viện khác
   - Đóng góp vào phát triển công nghệ AI tại Việt Nam
   - Tạo động lực cho các nghiên cứu về chatbot tiếng Việt



## 2. TỔNG QUAN NGHIÊN CỨU LIÊN QUAN

### 2.1. Các hệ thống chatbot thư viện trên thế giới

Nhiều thư viện đại học trên thế giới đã triển khai chatbot với các mức độ thành công khác nhau:

**Ask a Librarian (Đại học Stanford):** Sử dụng IBM Watson để trả lời câu hỏi cơ bản, nhưng vẫn cần sự can thiệp của thủ thư cho các câu hỏi phức tạp.

**Lib-E (Đại học Huddersfield, UK):** Chatbot dựa trên rule-based system, hạn chế trong việc hiểu ngữ cảnh và xử lý câu hỏi mới.

**Chatbot thư viện Đại học Quốc gia Singapore:** Tích hợp với hệ thống quản lý thư viện, nhưng chỉ hỗ trợ tiếng Anh.

### 2.2. Nghiên cứu về xử lý ngôn ngữ tự nhiên tiếng Việt

Các nghiên cứu gần đây về NLP tiếng Việt đã đạt được nhiều tiến bộ:

- **PhoBERT (Nguyễn và cộng sự, 2020):** Mô hình ngôn ngữ pre-trained cho tiếng Việt, đạt kết quả tốt trên nhiều tác vụ NLP
- **VnCoreNLP (Nguyễn và cộng sự, 2018):** Bộ công cụ xử lý tiếng Việt bao gồm word segmentation, POS tagging, NER
- **Các nghiên cứu về chatbot tiếng Việt:** Chủ yếu tập trung vào lĩnh vực thương mại điện tử và chăm sóc khách hàng

### 2.3. Kỹ thuật matching và ranking

Các kỹ thuật phổ biến trong hệ thống hỏi đáp:

**TF-IDF (Term Frequency-Inverse Document Frequency):** Đánh giá mức độ quan trọng của từ trong văn bản, được sử dụng rộng rãi trong information retrieval.

**Cosine Similarity:** Đo lường độ tương đồng giữa hai vector, hiệu quả trong việc so sánh văn bản.

**Levenshtein Distance:** Tính khoảng cách chỉnh sửa giữa hai chuỗi, hữu ích cho việc xử lý lỗi chính tả.

**Semantic Similarity:** Sử dụng word embeddings (Word2Vec, GloVe, BERT) để hiểu ý nghĩa ngữ nghĩa.

### 2.4. Hạn chế của các nghiên cứu hiện có

Các hệ thống chatbot thư viện hiện tại còn nhiều hạn chế:

1. **Thiếu hỗ trợ tiếng Việt:** Hầu hết các giải pháp chỉ hỗ trợ tiếng Anh
2. **Độ chính xác thấp:** Các hệ thống rule-based không xử lý tốt câu hỏi đa dạng
3. **Khó bảo trì:** Cần cập nhật thủ công khi có thay đổi thông tin
4. **Không học từ người dùng:** Thiếu cơ chế thu thập và phân tích feedback
5. **Giao diện quản trị hạn chế:** Khó khăn trong việc quản lý và cập nhật dữ liệu

Nghiên cứu này khắc phục các hạn chế trên bằng cách:
- Xây dựng thuật toán xử lý ngôn ngữ tự nhiên tiếng Việt chuyên biệt
- Kết hợp nhiều kỹ thuật matching để tăng độ chính xác
- Tích hợp AI (Gemini) để xử lý câu hỏi ngoài phạm vi dữ liệu
- Xây dựng hệ thống quản trị toàn diện với phân quyền
- Thu thập và phân tích câu hỏi chưa trả lời được để cải thiện liên tục



## 3. PHƯƠNG PHÁP NGHIÊN CỨU VÀ THIẾT KẾ HỆ THỐNG

### 3.1. Kiến trúc tổng thể

Hệ thống được thiết kế theo mô hình 3 tầng (3-tier architecture):

```
┌─────────────────────────────────────────────────────────┐
│              PRESENTATION LAYER (Frontend)               │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  │
│  │  User Chat   │  │    Admin     │  │   Mobile     │  │
│  │  Interface   │  │  Dashboard   │  │  Responsive  │  │
│  └──────────────┘  └──────────────┘  └──────────────┘  │
│         HTML5 + TailwindCSS + JavaScript                │
└─────────────────────────────────────────────────────────┘
                          ↕ REST API
┌─────────────────────────────────────────────────────────┐
│            APPLICATION LAYER (Backend Logic)             │
│  ┌──────────────────────────────────────────────────┐   │
│  │         MVC Architecture (PHP)                   │   │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────────┐  │   │
│  │  │Controllers│  │  Models  │  │   Helpers    │  │   │
│  │  └──────────┘  └──────────┘  └──────────────┘  │   │
│  │                                                  │   │
│  │  Core Components:                               │   │
│  │  • ChatController: Xử lý logic trò chuyện      │   │
│  │  • AdminController: Quản lý hệ thống           │   │
│  │  • AuthController: Xác thực Google OAuth       │   │
│  │  • ContextAnalyzer: Phân tích ngữ cảnh         │   │
│  │  • KeywordGenerator: Tạo từ khóa tự động       │   │
│  └──────────────────────────────────────────────────┘   │
│                                                          │
│  ┌──────────────────────────────────────────────────┐   │
│  │         NLP Processing Pipeline                  │   │
│  │  1. Tokenization & Normalization                │   │
│  │  2. Stop Words Removal                          │   │
│  │  3. N-gram Generation (2-4 words)               │   │
│  │  4. TF-IDF Calculation                          │   │
│  │  5. Semantic Similarity Scoring                 │   │
│  │  6. Context-aware Ranking                       │   │
│  └──────────────────────────────────────────────────┘   │
│                                                          │
│  ┌──────────────────────────────────────────────────┐   │
│  │         AI Integration Layer                     │   │
│  │  • Gemini 1.5 Flash API                         │   │
│  │  • Fallback mechanism                           │   │
│  │  • Context-aware prompting                      │   │
│  └──────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
                          ↕ SQL Queries
┌─────────────────────────────────────────────────────────┐
│              DATA LAYER (Database)                       │
│  ┌──────────────────────────────────────────────────┐   │
│  │         MySQL Database (UTF-8MB4)                │   │
│  │                                                  │   │
│  │  Core Tables:                                    │   │
│  │  • questions: Câu hỏi & trả lời (Q&A pairs)    │   │
│  │  • keywords: Từ khóa với trọng số               │   │
│  │  • categories: Danh mục phân loại               │   │
│  │  • chat_sessions: Phiên trò chuyện              │   │
│  │  • chat_messages: Lịch sử tin nhắn              │   │
│  │  • unanswered_questions: Câu hỏi chưa trả lời  │   │
│  │  • forms: Biểu mẫu & giấy tờ                    │   │
│  │  • admins: Quản trị viên (Google OAuth)         │   │
│  │  • chatbot_settings: Cấu hình hệ thống          │   │
│  │  • event_themes: Giao diện theo sự kiện         │   │
│  │                                                  │   │
│  │  Indexes:                                        │   │
│  │  • FULLTEXT index on question_text              │   │
│  │  • B-tree index on keywords                     │   │
│  │  • Foreign keys với ON DELETE CASCADE           │   │
│  └──────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
```

### 3.2. Thuật toán xử lý ngôn ngữ tự nhiên

#### 3.2.1. Phân tích ngữ cảnh (Context Analysis)

Hệ thống sử dụng lớp `ContextAnalyzer` để phân tích sâu ngữ cảnh câu hỏi:

**Bước 1: Tokenization**
```php
// Tách câu hỏi thành các token (từ)
$text = preg_replace('/[?!.,;:()"\[\]{}]+/u', ' ', $text);
$words = preg_split('/\s+/u', $text);
```

**Bước 2: Stop Words Removal**
- Loại bỏ 150+ stop words tiếng Việt và tiếng Anh
- Giữ lại các từ mang ý nghĩa chính (danh từ, động từ chính)

**Bước 3: N-gram Generation**
- Tạo bigrams (2 từ): "mượn sách", "thẻ thư viện"
- Tạo trigrams (3 từ): "giờ mở cửa", "tra cứu tài liệu"
- Tạo 4-grams: "nộp lưu chiểu luận văn"

**Bước 4: Important Words Extraction**
```php
$importantWords = array_filter($tokens, function($word) use ($stopWords) {
    return mb_strlen($word) >= 2 && !in_array($word, $stopWords);
});
```

#### 3.2.2. Thuật toán tính độ tương đồng ngữ nghĩa

Hệ thống sử dụng phương pháp kết hợp đa tầng để tính điểm tương đồng:

**Công thức tổng quát:**
```
Similarity_Score = w1×N-gram_Score + w2×Important_Words_Score 
                 + w3×Cosine_Score + w4×Answer_Context_Score 
                 + w5×Length_Score + Boosts - Penalties
```

Trong đó:
- w1 = 0.30 (N-gram overlap)
- w2 = 0.25 (Important words với synonym detection)
- w3 = 0.20 (Cosine similarity với TF-IDF)
- w4 = 0.15 (Answer context matching)
- w5 = 0.10 (Length similarity)

**Chi tiết từng thành phần:**

1. **N-gram Overlap Score (30%):**
```php
$userNgrams = array_merge($userTokens, $userBigrams, $userTrigrams);
$dbNgrams = array_merge($dbTokens, $dbBigrams, $dbTrigrams);
$ngramScore = jaccardSimilarity($userNgrams, $dbNgrams) × 0.30;
```

2. **Important Words Score với Synonym Detection (25%):**
```php
foreach ($userWords as $userWord) {
    $bestMatch = 0;
    foreach ($dbWords as $dbWord) {
        // Exact match
        if ($userWord === $dbWord) {
            $similarity = 1.0;
        }
        // Substring match
        else if (strpos($userWord, $dbWord) !== false) {
            $similarity = 0.8;
        }
        // Levenshtein distance
        else {
            $distance = levenshtein($userWord, $dbWord);
            $similarity = 1 - ($distance / max(strlen($userWord), strlen($dbWord)));
        }
        $bestMatch = max($bestMatch, $similarity);
    }
    $totalScore += $bestMatch;
}
$importantScore = ($totalScore / count($userWords)) × 0.25;
```

3. **Cosine Similarity với TF-IDF (20%):**
```php
// Tính TF (Term Frequency)
$tf[$term] = count($term_in_doc) / total_terms;

// Tính Cosine Similarity
$dotProduct = Σ(vec1[i] × vec2[i]);
$magnitude1 = sqrt(Σ(vec1[i]²));
$magnitude2 = sqrt(Σ(vec2[i]²));
$cosineSimilarity = $dotProduct / ($magnitude1 × $magnitude2);
$cosineScore = $cosineSimilarity × 0.20;
```

4. **Answer Context Score (15%):**
- Kiểm tra từ khóa của user có xuất hiện trong câu trả lời không
- Sử dụng semantic matching thay vì exact match

5. **Length Similarity (10%):**
```php
$lenDiff = abs(count($userTokens) - count($dbTokens));
$avgLen = (count($userTokens) + count($dbTokens)) / 2;
$lenScore = (1 - min($lenDiff / $avgLen, 1)) × 0.10;
```

#### 3.2.3. Cơ chế Boost và Penalty

**Boosts (tăng điểm):**
- Số khớp chính xác: +15%
- Cụm từ quan trọng khớp (bigram/trigram): +5-15%
- Từ phủ định khớp: +5%

**Penalties (giảm điểm):**
- Số khác nhau: -20%
- Từ khóa chính khác nhau: -40%
- Độ dài chênh lệch lớn: -10%
- Từ phủ định không khớp: -5%



### 3.3. Luồng xử lý câu hỏi (Question Processing Pipeline)

Hệ thống xử lý câu hỏi người dùng qua 4 tầng lọc tuần tự:

**Tầng 1: Lọc Toxic Content**
- Phát hiện ngôn từ không phù hợp, chửi bới, xúc phạm
- Sử dụng blacklist + Regex với ranh giới Unicode
- Trả lời lịch sự yêu cầu người dùng giữ văn hóa giao tiếp

**Tầng 2: Tìm kiếm Biểu mẫu (Forms)**
- Khớp từ khóa với bảng `forms`
- Trả về link download/điền form nếu tìm thấy
- Ưu tiên cao vì người dùng cần hành động cụ thể

**Tầng 3: Tìm kiếm Q&A trong Database**
- Sử dụng thuật toán semantic similarity
- Phân loại câu hỏi: vắn tắt/cụ thể
- Áp dụng ngưỡng điểm khác nhau:
  + Exact match (≥85%): Trả lời trực tiếp
  + High confidence (≥70%): Trả lời nếu câu hỏi cụ thể
  + Medium confidence (≥50%): Hiển thị danh sách câu hỏi liên quan
  + Low confidence (<50%): Chuyển sang tầng 4

**Tầng 4: Gemini AI Fallback**
- Gọi Gemini 1.5 Flash API với system instruction
- Chỉ trả lời trong phạm vi thư viện
- Nếu Gemini thất bại: Trả lời mặc định + gợi ý liên hệ



### 3.4. Cơ sở dữ liệu

#### 3.4.1. Thiết kế schema

Hệ thống sử dụng MySQL với 12 bảng chính:

**Bảng `questions` (Câu hỏi - Trả lời):**
- `id`: Primary key
- `category_id`: Foreign key đến `categories`
- `question_text`: Câu hỏi (TEXT, FULLTEXT index)
- `answer_text`: Câu trả lời (TEXT, hỗ trợ HTML từ Quill editor)
- `answer_text_en`: Câu trả lời tiếng Anh (TEXT, nullable)
- `source_type`: ENUM('manual', 'word', 'pdf')
- `approval_status`: ENUM('pending', 'approved')
- `approved_by`, `approved_at`: Thông tin phê duyệt
- `is_active`: Trạng thái hoạt động
- `created_by`, `created_at`, `updated_at`

**Bảng `keywords` (Từ khóa với trọng số):**
- `id`: Primary key
- `question_id`: Foreign key đến `questions`
- `keyword`: VARCHAR(255), INDEX
- `weight`: FLOAT (5.0-10.0)
- `is_auto`: BOOLEAN (tự động/thủ công)
- `language`: ENUM('vi', 'en')

**Bảng `categories` (Danh mục):**
- `id`, `name`, `description`, `icon`
- `sort_order`: Thứ tự hiển thị
- `is_active`, `created_by`, timestamps

**Bảng `chat_sessions` và `chat_messages`:**
- Lưu trữ lịch sử trò chuyện
- `session_token`: UUID duy nhất
- `matched_question_id`: Câu hỏi được khớp
- `confidence_score`: Độ tin cậy (0-1)

**Bảng `unanswered_questions`:**
- Thu thập câu hỏi chatbot không trả lời được
- `frequency`: Số lần được hỏi
- `is_resolved`: Đã xử lý chưa
- Giúp cải thiện liên tục hệ thống

**Bảng `forms` (Biểu mẫu):**
- `name`, `description`, `url`
- `keywords`: Từ khóa phân cách bằng dấu phẩy
- Hỗ trợ tra cứu biểu mẫu, giấy tờ

**Bảng `admins` (Quản trị viên):**
- Đăng nhập Google OAuth 2.0
- `google_id`, `email`, `full_name`, `avatar_url`
- `role`: ENUM('super_admin', 'admin', 'editor')
- `password`: Bcrypt hash (cho đăng nhập email/password)

**Bảng `chatbot_settings` và `event_themes`:**
- Cấu hình giao diện, màu sắc
- Chủ đề theo sự kiện (Tết, Khai giảng, Noel...)

#### 3.4.2. Tối ưu hóa truy vấn

- FULLTEXT index trên `question_text` cho tìm kiếm nhanh
- B-tree index trên `keywords.keyword` cho lookup O(log n)
- Foreign keys với ON DELETE CASCADE tự động dọn dẹp
- Connection pooling với PDO persistent connections
- Prepared statements để tránh SQL injection



## 4. TRIỂN KHAI HỆ THỐNG

### 4.1. Công nghệ sử dụng

**Backend:**
- PHP 7.4+ với PDO
- MySQL 8.0 (UTF-8MB4)
- Composer cho dependency management
- PhpOffice/PhpWord: Đọc file Word
- Smalot/PdfParser: Đọc file PDF

**Frontend:**
- HTML5, CSS3, JavaScript (ES6+)
- TailwindCSS 3.x cho UI responsive
- Quill.js cho rich text editor
- Fetch API cho AJAX requests
- LocalStorage/SessionStorage cho cache

**AI Integration:**
- Google Gemini 1.5 Flash API
- System instruction cho context-aware responses
- Temperature = 0.3 (ít ngẫu nhiên, tập trung vào facts)
- Max tokens = 512

**Authentication:**
- Google OAuth 2.0
- Session-based authentication
- Bcrypt password hashing

**Deployment:**
- Apache/Nginx web server
- HTTPS với SSL certificate
- CORS headers cho API

### 4.2. Giao diện người dùng

#### 4.2.1. Giao diện chat

**Thiết kế:**
- Gradient xanh dương nhẹ nhàng (ocean theme)
- Avatar bot: Logo CELRAS TVU
- Avatar user: Ảnh mặc định hoặc từ Google
- Bong bóng chat với shadow và gradient
- Typing indicator với animation 3 chấm
- Responsive trên mobile, tablet, desktop

**Tính năng:**
- Gửi tin nhắn bằng Enter (Shift+Enter xuống dòng)
- Đếm ký tự (giới hạn 3000)
- Auto-resize textarea
- Câu hỏi gợi ý dạng chip
- Danh mục câu hỏi ở sidebar (có thể thu gọn)
- Nút "Cuộc trò chuyện mới" để reset
- Dark mode toggle
- Đa ngôn ngữ (Tiếng Việt/English)
- Voice input (Speech Recognition API)

**Hiệu ứng:**
- Smooth scroll khi có tin nhắn mới
- Fade in animation cho tin nhắn
- Hover effects trên buttons
- Loading skeleton cho danh mục
- Toast notifications cho lỗi

#### 4.2.2. Giao diện quản trị

**Dashboard:**
- Thống kê tổng quan: Số câu hỏi, phiên chat, câu hỏi chưa trả lời
- Biểu đồ hoạt động theo thời gian
- Danh sách câu hỏi phổ biến

**Quản lý câu hỏi:**
- Bảng danh sách với search, filter, sort
- Thêm/sửa/xóa câu hỏi
- Rich text editor (Quill) cho câu trả lời
- Phân loại theo danh mục
- Trạng thái phê duyệt (pending/approved)
- Xem lịch sử chỉnh sửa
- Bulk actions: Xóa nhiều, phê duyệt nhiều
- Import từ Word/PDF

**Quản lý danh mục:**
- CRUD operations
- Drag & drop để sắp xếp thứ tự
- Đếm số câu hỏi trong mỗi danh mục
- Toggle active/inactive

**Quản lý biểu mẫu:**
- Thêm link biểu mẫu với từ khóa
- Preview link trước khi lưu
- Quản lý keywords cho matching

**Câu hỏi chưa trả lời:**
- Danh sách câu hỏi người dùng hỏi nhưng bot không trả lời được
- Sắp xếp theo tần suất
- Nút "Tạo câu hỏi mới" từ câu hỏi chưa trả lời
- Đánh dấu đã xử lý

**Cài đặt:**
- Tùy chỉnh màu sắc giao diện
- Tin nhắn chào mừng
- Tin nhắn khi không tìm thấy câu trả lời
- Số câu hỏi gợi ý tối đa
- Bật/tắt chatbot
- Quản lý chủ đề theo sự kiện

**Quản lý tài khoản:**
- Danh sách admin
- Phân quyền: super_admin, admin, editor
- Kích hoạt/vô hiệu hóa tài khoản
- Xem lịch sử đăng nhập



### 4.3. Các tính năng nổi bật

#### 4.3.1. Tự động tạo từ khóa

Hệ thống tự động tạo từ khóa từ câu hỏi với trọng số:

```php
class KeywordGenerator {
    // Trích xuất cụm từ 4 từ (trọng số 10.0)
    // Trích xuất cụm từ 3 từ (trọng số 9.0)
    // Trích xuất cụm từ 2 từ (trọng số 7.0)
    // Trích xuất từ đơn (trọng số 5.0)
    
    // Dịch sang tiếng Anh (nếu có trong từ điển)
    // VD: "mượn sách" → "borrow book"
}
```

Ưu điểm:
- Giảm công sức nhập liệu thủ công
- Đảm bảo consistency
- Hỗ trợ tìm kiếm đa ngôn ngữ

#### 4.3.2. Import dữ liệu từ Word/PDF

**Quy trình:**
1. Upload file Word (.doc, .docx) hoặc PDF
2. Hệ thống parse nội dung
3. Tách câu hỏi - trả lời theo pattern:
   - Câu hỏi: Dòng kết thúc bằng "?"
   - Trả lời: Các dòng tiếp theo cho đến câu hỏi mới
4. Kiểm tra trùng lặp (so sánh chuẩn hóa)
5. Hiển thị preview cho admin xác nhận
6. Lưu vào database với trạng thái "pending"
7. Tự động tạo từ khóa

**Xử lý trùng lặp:**
- Chuẩn hóa text: Bỏ dấu tiếng Việt, lowercase, loại ký tự đặc biệt
- So sánh với similar_text() (ngưỡng 85%)
- Hiển thị danh sách trùng lặp để admin quyết định
- Tùy chọn "force add" nếu muốn thêm dù trùng

#### 4.3.3. Phê duyệt câu hỏi

Workflow phê duyệt 2 cấp:
- **Pending:** Câu hỏi mới thêm, chưa hiển thị cho user
- **Approved:** Đã được admin duyệt, hiển thị trong chatbot

Lợi ích:
- Kiểm soát chất lượng nội dung
- Tránh thông tin sai lệch
- Phân quyền rõ ràng (editor thêm, admin duyệt)

#### 4.3.4. Chủ đề theo sự kiện

Hệ thống hỗ trợ thay đổi giao diện theo sự kiện:

**Các chủ đề có sẵn:**
- Mặc định (xanh dương)
- Tết Nguyên Đán (đỏ vàng, emoji 🎊🧧)
- Khai giảng (xanh lá, emoji 📚🎓)
- Noel (đỏ trắng, emoji 🎄⛄)

**Tính năng:**
- Tự động kích hoạt theo ngày bắt đầu/kết thúc
- Tùy chỉnh màu sắc, font chữ, avatar bot
- Tin nhắn chào mừng theo sự kiện
- Decorations (emoji bay) với CSS animation

#### 4.3.5. Phân tích câu hỏi chưa trả lời

Hệ thống thu thập và phân tích:
- Câu hỏi nào được hỏi nhiều nhất
- Xu hướng câu hỏi theo thời gian
- Chủ đề nào còn thiếu trong database

Admin có thể:
- Xem danh sách câu hỏi chưa trả lời
- Tạo câu hỏi mới trực tiếp từ đây
- Đánh dấu đã xử lý

#### 4.3.6. Đa ngôn ngữ (Tiếng Việt/English)

**Cơ chế:**
- Lưu câu trả lời song ngữ trong database
- Frontend tự động chuyển đổi UI
- Chatbot trả lời theo ngôn ngữ được chọn
- Từ khóa hỗ trợ cả 2 ngôn ngữ

**Triển khai:**
```javascript
const translations = {
    vi: { welcome: "Xin chào", ... },
    en: { welcome: "Hello", ... }
};
function t(key) {
    return translations[currentLang][key];
}
```

#### 4.3.7. Voice Input (Nhập bằng giọng nói)

Sử dụng Web Speech API:
- Nhận diện giọng nói tiếng Việt/English
- Chuyển đổi speech-to-text
- Tự động điền vào input
- Hiển thị trạng thái đang nghe

Yêu cầu:
- HTTPS hoặc localhost
- Trình duyệt hỗ trợ (Chrome, Edge)



## 5. KẾT QUẢ VÀ ĐÁNH GIÁ

### 5.1. Môi trường thử nghiệm

**Cấu hình server:**
- CPU: Intel Xeon 4 cores
- RAM: 8GB
- Storage: SSD 256GB
- OS: Ubuntu 20.04 LTS
- Web server: Apache 2.4
- PHP: 7.4.33
- MySQL: 8.0.35

**Dữ liệu thử nghiệm:**
- 150+ câu hỏi - trả lời về dịch vụ thư viện
- 8 danh mục chính
- 500+ từ khóa tự động
- 20+ biểu mẫu

**Người dùng thử nghiệm:**
- 50 sinh viên
- 10 giảng viên
- 5 nhân viên thư viện

### 5.2. Đánh giá độ chính xác

#### 5.2.1. Phương pháp đánh giá

Sử dụng 100 câu hỏi test được phân loại:
- 40 câu hỏi exact match (giống y hệt trong DB)
- 30 câu hỏi paraphrase (diễn đạt khác)
- 20 câu hỏi có lỗi chính tả
- 10 câu hỏi ngoài phạm vi

**Metrics:**
- Accuracy: Tỷ lệ trả lời đúng
- Precision: Độ chính xác khi trả lời
- Recall: Tỷ lệ tìm được câu trả lời
- F1-Score: Trung bình điều hòa của Precision và Recall
- Response Time: Thời gian phản hồi

#### 5.2.2. Kết quả

**Bảng 1: Độ chính xác theo loại câu hỏi**

| Loại câu hỏi | Số lượng | Trả lời đúng | Accuracy | Avg Response Time |
|--------------|----------|--------------|----------|-------------------|
| Exact match | 40 | 40 | 100% | 0.15s |
| Paraphrase | 30 | 28 | 93.3% | 0.32s |
| Có lỗi chính tả | 20 | 17 | 85.0% | 0.28s |
| Ngoài phạm vi | 10 | 9* | 90.0% | 0.45s |
| **Tổng** | **100** | **94** | **94.0%** | **0.28s** |

*9/10 câu ngoài phạm vi được Gemini AI trả lời đúng hoặc từ chối lịch sự

**Bảng 2: So sánh với các phương pháp khác**

| Phương pháp | Accuracy | Response Time | Ghi chú |
|-------------|----------|---------------|---------|
| Rule-based | 65% | 0.10s | Không xử lý được paraphrase |
| FULLTEXT only | 78% | 0.18s | Thiếu semantic understanding |
| TF-IDF only | 82% | 0.25s | Không xử lý synonym |
| **Hệ thống đề xuất** | **94%** | **0.28s** | Kết hợp đa tầng |

#### 5.2.3. Phân tích chi tiết

**Ví dụ câu hỏi paraphrase thành công:**

| Câu hỏi trong DB | Câu hỏi người dùng | Similarity Score | Kết quả |
|------------------|-------------------|------------------|---------|
| "Thư viện mở cửa lúc mấy giờ?" | "Mấy giờ thư viện mở cửa?" | 0.92 | ✓ Đúng |
| "Làm thế nào để mượn sách?" | "Muốn mượn sách thì làm sao?" | 0.88 | ✓ Đúng |
| "Thẻ thư viện làm ở đâu?" | "Đăng ký thẻ thư viện tại quầy nào?" | 0.85 | ✓ Đúng |

**Ví dụ xử lý lỗi chính tả:**

| Câu hỏi có lỗi | Câu hỏi đúng | Levenshtein Distance | Kết quả |
|----------------|--------------|----------------------|---------|
| "muon sach" | "mượn sách" | 2 | ✓ Tìm được |
| "thu vien" | "thư viện" | 2 | ✓ Tìm được |
| "gia han" | "gia hạn" | 1 | ✓ Tìm được |

**Trường hợp thất bại (6%):**
- Câu hỏi quá mơ hồ: "Có gì không?" (không đủ context)
- Câu hỏi phức tạp kết hợp nhiều ý: "Mượn sách và làm thẻ thế nào?"
- Từ viết tắt không phổ biến: "OPAC là j?" (j = gì)

### 5.3. Đánh giá hiệu năng

**Bảng 3: Thời gian xử lý theo số câu hỏi trong DB**

| Số câu hỏi | Avg Response Time | 95th Percentile | Max |
|------------|-------------------|-----------------|-----|
| 50 | 0.12s | 0.18s | 0.25s |
| 100 | 0.18s | 0.28s | 0.35s |
| 150 | 0.28s | 0.42s | 0.55s |
| 200 | 0.35s | 0.52s | 0.68s |

**Nhận xét:**
- Thời gian tăng tuyến tính với số câu hỏi
- Vẫn đảm bảo < 1s cho trải nghiệm tốt
- FULLTEXT index giúp tối ưu đáng kể

**Bảng 4: Tải đồng thời (Concurrent Users)**

| Số user | Requests/s | Avg Response Time | Error Rate |
|---------|------------|-------------------|------------|
| 10 | 35 | 0.32s | 0% |
| 50 | 165 | 0.48s | 0% |
| 100 | 310 | 0.85s | 0.2% |
| 200 | 580 | 1.52s | 1.5% |

**Nhận xét:**
- Hệ thống ổn định với 100 user đồng thời
- Cần scale horizontal nếu > 200 users



### 5.4. Khảo sát người dùng

#### 5.4.1. Phương pháp khảo sát

- Thời gian: 2 tuần (01/03/2026 - 15/03/2026)
- Đối tượng: 65 người (50 sinh viên, 10 giảng viên, 5 nhân viên)
- Phương thức: Google Forms + phỏng vấn trực tiếp
- Tiêu chí đánh giá: Thang điểm Likert 1-5

#### 5.4.2. Kết quả khảo sát

**Bảng 5: Mức độ hài lòng**

| Tiêu chí | Điểm TB | Rất hài lòng | Hài lòng | Trung bình | Không hài lòng |
|----------|---------|--------------|----------|------------|----------------|
| Độ chính xác câu trả lời | 4.5/5 | 58% | 35% | 5% | 2% |
| Tốc độ phản hồi | 4.7/5 | 72% | 25% | 3% | 0% |
| Giao diện thân thiện | 4.6/5 | 65% | 30% | 5% | 0% |
| Dễ sử dụng | 4.8/5 | 80% | 18% | 2% | 0% |
| Hữu ích | 4.4/5 | 52% | 40% | 6% | 2% |
| **Trung bình** | **4.6/5** | **65%** | **30%** | **4%** | **1%** |

**Nhận xét tích cực:**
- "Rất tiện lợi, không cần đến quầy thông tin nữa"
- "Trả lời nhanh và chính xác, giúp tiết kiệm thời gian"
- "Giao diện đẹp, dễ nhìn, dễ sử dụng"
- "Có thể hỏi bất cứ lúc nào, kể cả ngoài giờ hành chính"
- "Danh mục câu hỏi giúp tìm thông tin nhanh hơn"

**Nhận xét cần cải thiện:**
- "Một số câu hỏi phức tạp chưa trả lời được" (8%)
- "Muốn có thêm hình ảnh minh họa" (12%)
- "Cần thêm tiếng Anh cho sinh viên quốc tế" (5%)

#### 5.4.3. So sánh trước và sau triển khai

**Bảng 6: Hiệu quả cải thiện**

| Chỉ số | Trước | Sau | Cải thiện |
|--------|-------|-----|-----------|
| Thời gian tra cứu TB | 8.5 phút | 1.2 phút | -85.9% |
| Số cuộc gọi đến quầy thông tin/ngày | 45 | 12 | -73.3% |
| Tỷ lệ hài lòng về dịch vụ | 72% | 95% | +23% |
| Số câu hỏi được trả lời/ngày | 45 | 180+ | +300% |

**Lợi ích kinh tế:**
- Giảm 2 nhân viên trực quầy thông tin (tiết kiệm ~15 triệu/tháng)
- Tăng hiệu suất làm việc của nhân viên thư viện
- Cải thiện trải nghiệm người dùng → tăng lượng người sử dụng thư viện

### 5.5. Phân tích thống kê sử dụng

**Bảng 7: Thống kê 2 tuần đầu triển khai**

| Chỉ số | Giá trị |
|--------|---------|
| Tổng số phiên chat | 1,247 |
| Tổng số tin nhắn | 4,856 |
| Tin nhắn/phiên TB | 3.9 |
| Câu hỏi được trả lời | 4,523 (93.1%) |
| Câu hỏi chưa trả lời | 333 (6.9%) |
| Thời gian phiên TB | 4.2 phút |

**Bảng 8: Top 10 câu hỏi phổ biến**

| # | Câu hỏi | Số lần hỏi | % |
|---|---------|------------|---|
| 1 | Thư viện mở cửa lúc mấy giờ? | 287 | 5.9% |
| 2 | Làm thế nào để mượn sách? | 245 | 5.0% |
| 3 | Làm thẻ thư viện ở đâu? | 198 | 4.1% |
| 4 | Tra cứu sách như thế nào? | 176 | 3.6% |
| 5 | Gia hạn sách thế nào? | 154 | 3.2% |
| 6 | Phạt quá hạn bao nhiêu tiền? | 132 | 2.7% |
| 7 | Có wifi không? | 118 | 2.4% |
| 8 | Phòng đọc ở tầng mấy? | 105 | 2.2% |
| 9 | Nộp luận văn như thế nào? | 98 | 2.0% |
| 10 | Có photocopy không? | 87 | 1.8% |

**Phân bố theo danh mục:**
- Mượn trả sách: 32%
- Thẻ thư viện: 18%
- Tra cứu tài liệu: 15%
- Giờ hoạt động: 12%
- Dịch vụ khác: 10%
- Quy định: 8%
- Phòng ban: 5%

**Phân bố theo thời gian:**
- 8h-12h: 35% (giờ cao điểm)
- 13h-17h: 40% (giờ cao điểm)
- 18h-22h: 20%
- 22h-8h: 5% (ngoài giờ hành chính)



## 6. KẾT LUẬN

### 6.1. Những đóng góp chính

Nghiên cứu này đã đạt được các kết quả quan trọng:

**1. Về mặt khoa học:**
- Đề xuất thuật toán phân tích ngữ cảnh tiếng Việt kết hợp đa tầng (N-gram + TF-IDF + Semantic Similarity + Context Matching) đạt độ chính xác 94%
- Xây dựng cơ chế Boost/Penalty thông minh để tăng độ chính xác matching
- Phát triển phương pháp tự động tạo từ khóa với trọng số dựa trên độ dài cụm từ
- Thiết kế pipeline xử lý 4 tầng (Toxic → Forms → Q&A → AI) tối ưu hiệu suất

**2. Về mặt kỹ thuật:**
- Triển khai thành công hệ thống chatbot thư viện đầu tiên tại Trường Đại học Trà Vinh
- Tích hợp Gemini AI làm fallback mechanism, mở rộng khả năng trả lời
- Xây dựng giao diện quản trị toàn diện với phân quyền, phê duyệt, import dữ liệu
- Hỗ trợ đa ngôn ngữ (Tiếng Việt/English), voice input, dark mode
- Responsive design hoạt động tốt trên mọi thiết bị

**3. Về mặt ứng dụng:**
- Giảm 85.9% thời gian tra cứu thông tin (từ 8.5 phút xuống 1.2 phút)
- Giảm 73.3% số cuộc gọi đến quầy thông tin
- Tăng 300% số câu hỏi được trả lời mỗi ngày
- Nâng cao mức độ hài lòng từ 72% lên 95%
- Tiết kiệm chi phí nhân sự ~15 triệu/tháng

### 6.2. Hạn chế

Mặc dù đạt được nhiều kết quả tích cực, hệ thống vẫn còn một số hạn chế:

**1. Về thuật toán:**
- Chưa xử lý tốt câu hỏi phức tạp kết hợp nhiều ý (multi-intent)
- Chưa có cơ chế học từ feedback người dùng (reinforcement learning)
- Chưa hỗ trợ câu hỏi có hình ảnh (visual question answering)

**2. Về dữ liệu:**
- Dữ liệu huấn luyện còn hạn chế (150 câu hỏi)
- Chưa có corpus lớn về ngữ cảnh thư viện tiếng Việt
- Chưa có dữ liệu lịch sử tương tác để phân tích xu hướng

**3. Về tích hợp:**
- Chưa tích hợp với hệ thống quản lý thư viện (OPAC)
- Chưa có API cho mobile app
- Chưa hỗ trợ chatbot trên các nền tảng khác (Facebook Messenger, Zalo)

**4. Về AI:**
- Phụ thuộc vào Gemini API (cần internet, có giới hạn quota)
- Chưa có mô hình AI riêng được fine-tune cho ngữ cảnh thư viện
- Chi phí API có thể tăng khi lượng người dùng lớn

### 6.3. Hướng phát triển

Để hoàn thiện và mở rộng hệ thống, các hướng phát triển tiếp theo bao gồm:

**Ngắn hạn (3-6 tháng):**
1. **Mở rộng dữ liệu:** Thu thập thêm 500+ câu hỏi từ người dùng thực tế
2. **Tích hợp OPAC:** Cho phép tra cứu sách trực tiếp trong chatbot
3. **Mobile app:** Phát triển ứng dụng di động iOS/Android
4. **Multilingual:** Thêm tiếng Khmer cho sinh viên dân tộc thiểu số
5. **Analytics dashboard:** Biểu đồ thống kê chi tiết hơn

**Trung hạn (6-12 tháng):**
1. **Fine-tune mô hình AI:** Huấn luyện PhoBERT trên dữ liệu thư viện
2. **Multi-intent handling:** Xử lý câu hỏi phức tạp nhiều ý
3. **Personalization:** Gợi ý câu hỏi dựa trên lịch sử người dùng
4. **Voice bot:** Chatbot giọng nói hoàn chỉnh (text-to-speech)
5. **Integration:** Tích hợp Facebook Messenger, Zalo

**Dài hạn (1-2 năm):**
1. **Visual QA:** Trả lời câu hỏi có hình ảnh (sơ đồ thư viện, bìa sách)
2. **Recommendation system:** Gợi ý sách dựa trên sở thích
3. **Conversational AI:** Chatbot có khả năng đối thoại nhiều lượt
4. **Knowledge graph:** Xây dựng đồ thị tri thức về thư viện
5. **Federated learning:** Học từ nhiều thư viện mà không chia sẻ dữ liệu

### 6.4. Khả năng áp dụng

Hệ thống có thể được áp dụng rộng rãi cho:

**1. Các thư viện đại học khác:**
- Dễ dàng tùy chỉnh dữ liệu theo từng trường
- Giao diện có thể thay đổi màu sắc, logo
- Hỗ trợ đa ngôn ngữ

**2. Thư viện công cộng:**
- Phục vụ cộng đồng 24/7
- Giảm tải cho nhân viên
- Tăng khả năng tiếp cận thông tin

**3. Các lĩnh vực khác:**
- Hệ thống hỏi đáp cho doanh nghiệp (FAQ bot)
- Chatbot hỗ trợ khách hàng
- Trợ lý ảo cho giáo dục

**4. Nghiên cứu khoa học:**
- Cung cấp dataset tiếng Việt cho NLP
- Benchmark cho các thuật toán matching
- Nền tảng thử nghiệm các kỹ thuật AI mới

### 6.5. Kết luận chung

Nghiên cứu đã thành công trong việc xây dựng một hệ thống chatbot thông minh hỗ trợ tra cứu thông tin thư viện tại Trường Đại học Trà Vinh. Hệ thống đạt độ chính xác 94%, thời gian phản hồi trung bình 0.28 giây, và mức độ hài lòng người dùng 95%. Kết quả cho thấy việc kết hợp nhiều kỹ thuật NLP (N-gram, TF-IDF, Semantic Similarity) cùng với AI (Gemini) mang lại hiệu quả vượt trội so với các phương pháp truyền thống.

Hệ thống không chỉ giải quyết được bài toán tra cứu thông tin nhanh chóng, chính xác mà còn mang lại nhiều lợi ích kinh tế và xã hội: giảm chi phí nhân sự, tăng hiệu suất làm việc, cải thiện trải nghiệm người dùng, và góp phần vào quá trình chuyển đổi số của thư viện đại học.

Với những kết quả đạt được, nghiên cứu này có thể làm nền tảng cho các nghiên cứu tiếp theo về chatbot tiếng Việt, đặc biệt trong lĩnh vực giáo dục và thư viện số. Hệ thống cũng có tiềm năng mở rộng và áp dụng cho nhiều lĩnh vực khác, đóng góp vào sự phát triển của công nghệ AI tại Việt Nam.



## TÀI LIỆU THAM KHẢO

[1] Adamopoulou, E., & Moussiades, L. (2020). "Chatbots: History, technology, and applications." *Machine Learning with Applications*, 2, 100006.

[2] Nguyen, D. Q., & Nguyen, A. T. (2020). "PhoBERT: Pre-trained language models for Vietnamese." *Findings of the Association for Computational Linguistics: EMNLP 2020*, pp. 1037-1042.

[3] Nguyen, D. Q., Nguyen, D. Q., Vu, T., Dras, M., & Johnson, M. (2018). "A Fast and Accurate Vietnamese Word Segmenter." *Proceedings of the 11th Language Resources and Evaluation Conference (LREC 2018)*, pp. 2582-2587.

[4] Salton, G., & Buckley, C. (1988). "Term-weighting approaches in automatic text retrieval." *Information Processing & Management*, 24(5), 513-523.

[5] Mikolov, T., Chen, K., Corrado, G., & Dean, J. (2013). "Efficient estimation of word representations in vector space." *arXiv preprint arXiv:1301.3781*.

[6] Devlin, J., Chang, M. W., Lee, K., & Toutanova, K. (2018). "BERT: Pre-training of deep bidirectional transformers for language understanding." *arXiv preprint arXiv:1810.04805*.

[7] Xu, A., Liu, Z., Guo, Y., Sinha, V., & Akkiraju, R. (2017). "A new chatbot for customer service on social media." *Proceedings of the 2017 CHI Conference on Human Factors in Computing Systems*, pp. 3506-3510.

[8] Følstad, A., & Brandtzæg, P. B. (2017). "Chatbots and the new world of HCI." *interactions*, 24(4), 38-42.

[9] Shawar, B. A., & Atwell, E. (2007). "Chatbots: Are they really useful?" *LDV Forum*, 22(1), 29-49.

[10] Weizenbaum, J. (1966). "ELIZA—a computer program for the study of natural language communication between man and machine." *Communications of the ACM*, 9(1), 36-45.

[11] Wallace, R. S. (2009). "The anatomy of ALICE." In *Parsing the Turing Test* (pp. 181-210). Springer, Dordrecht.

[12] Radziwill, N. M., & Benton, M. C. (2017). "Evaluating quality of chatbots and intelligent conversational agents." *arXiv preprint arXiv:1704.04579*.

[13] Brandtzaeg, P. B., & Følstad, A. (2017). "Why people use chatbots." *International Conference on Internet Science*, pp. 377-392. Springer, Cham.

[14] Jain, M., Kumar, P., Kota, R., & Patel, S. N. (2018). "Evaluating and informing the design of chatbots." *Proceedings of the 2018 Designing Interactive Systems Conference*, pp. 895-906.

[15] Google AI. (2024). "Gemini API Documentation." https://ai.google.dev/docs

[16] Manning, C. D., Raghavan, P., & Schütze, H. (2008). *Introduction to information retrieval*. Cambridge University Press.

[17] Jurafsky, D., & Martin, J. H. (2020). *Speech and language processing* (3rd ed. draft). https://web.stanford.edu/~jurafsky/slp3/

[18] Bird, S., Klein, E., & Loper, E. (2009). *Natural language processing with Python: analyzing text with the natural language toolkit*. O'Reilly Media, Inc.

[19] Chollet, F. (2017). *Deep learning with Python*. Manning Publications Co.

[20] Russell, S. J., & Norvig, P. (2020). *Artificial intelligence: a modern approach* (4th ed.). Pearson.

[21] Goodfellow, I., Bengio, Y., & Courville, A. (2016). *Deep learning*. MIT press.

[22] Vaswani, A., Shazeer, N., Parmar, N., Uszkoreit, J., Jones, L., Gomez, A. N., ... & Polosukhin, I. (2017). "Attention is all you need." *Advances in neural information processing systems*, 30.

[23] Brown, T., Mann, B., Ryder, N., Subbiah, M., Kaplan, J. D., Dhariwal, P., ... & Amodei, D. (2020). "Language models are few-shot learners." *Advances in neural information processing systems*, 33, 1877-1901.

[24] Radford, A., Wu, J., Child, R., Luan, D., Amodei, D., & Sutskever, I. (2019). "Language models are unsupervised multitask learners." *OpenAI blog*, 1(8), 9.

[25] Trường Đại học Trà Vinh. (2025). "Thông báo số 3356/TB-ĐHTV về việc nộp lưu chiểu luận án, luận văn, đề án sau bảo vệ." https://tvu.edu.vn

---

## PHỤ LỤC

### Phụ lục A: Giao diện hệ thống

*(Các hình ảnh minh họa giao diện chatbot, dashboard quản trị, biểu đồ thống kê)*

### Phụ lục B: Mã nguồn chính

*(Các đoạn code quan trọng: ContextAnalyzer.php, ChatController.php, thuật toán matching)*

### Phụ lục C: Dữ liệu thử nghiệm

*(100 câu hỏi test, kết quả chi tiết, confusion matrix)*

### Phụ lục D: Khảo sát người dùng

*(Bảng câu hỏi khảo sát, kết quả chi tiết, biểu đồ phân tích)*

---

**LỜI CẢM ƠN**

Nhóm nghiên cứu xin chân thành cảm ơn:
- Ban Giám hiệu Trường Đại học Trà Vinh đã tạo điều kiện thuận lợi cho nghiên cứu
- Trung tâm Học liệu và Chuyển đổi số (CELRAS) đã hỗ trợ triển khai thử nghiệm
- Các sinh viên, giảng viên đã tham gia khảo sát và đóng góp ý kiến
- Các chuyên gia trong lĩnh vực AI và NLP đã tư vấn, góp ý

---

**THÔNG TIN TÁC GIẢ**

**[Tên tác giả]**  
Chức vụ: [Chức vụ]  
Đơn vị: Trung tâm Học liệu và Chuyển đổi số (CELRAS), Trường Đại học Trà Vinh  
Email: celrastvu@gmail.com  
Điện thoại: 0294 3855 246 (ext. 142)  
Website: https://celras.tvu.edu.vn

**Lĩnh vực nghiên cứu:**
- Trí tuệ nhân tạo (AI)
- Xử lý ngôn ngữ tự nhiên (NLP)
- Chatbot và Conversational AI
- Thư viện số và Chuyển đổi số

---

*Bài báo này được viết dựa trên dự án thực tế "Xây dựng Chatbot hỗ trợ tra cứu thông tin thư viện tại Trường Đại học Trà Vinh" được triển khai từ tháng 01/2026 đến tháng 03/2026.*

*Mọi thông tin chi tiết về hệ thống, vui lòng liên hệ: celrastvu@gmail.com*

