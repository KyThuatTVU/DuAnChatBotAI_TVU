<?php
/**
 * KeywordGenerator - Tự động tạo từ khóa từ câu hỏi
 * Hỗ trợ cả tiếng Việt và tiếng Anh
 */
class KeywordGenerator
{
    // Stop words tiếng Việt (từ không mang ý nghĩa chính)
    private static $stopWordsVi = [
        'là', 'và', 'của', 'cho', 'với', 'trong', 'ngoài', 'trên', 'dưới',
        'từ', 'đến', 'được', 'bị', 'để', 'rằng', 'mà', 'thì', 'cũng',
        'đã', 'sẽ', 'đang', 'vẫn', 'còn', 'nếu', 'khi', 'hay', 'hoặc',
        'này', 'kia', 'đó', 'ấy', 'nọ', 'các', 'những', 'một', 'hai', 'ba',
        'tôi', 'mình', 'bạn', 'em', 'anh', 'chị', 'ông', 'bà',
        'gì', 'nào', 'đâu', 'sao', 'không', 'có', 'bao', 'nhiêu', 'mấy',
        'thế', 'làm', 'như', 'thế', 'nào', 'ra', 'sao', 'thì', 'nhỉ',
        'xin', 'vui', 'lòng', 'ơi', 'nhé', 'nha', 'ạ', 'hả', 'à',
        'muốn', 'cần', 'phải', 'nên', 'thể', 'hỏi', 'biết', 'hãy',
        'rất', 'quá', 'lắm', 'nhất', 'hơn', 'tất', 'cả', 'mỗi',
    ];

    // Stop words tiếng Anh
    private static $stopWordsEn = [
        'a', 'an', 'the', 'is', 'are', 'was', 'were', 'be', 'been', 'being',
        'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'should',
        'can', 'could', 'may', 'might', 'must', 'shall',
        'i', 'you', 'he', 'she', 'it', 'we', 'they', 'me', 'him', 'her', 'us', 'them',
        'my', 'your', 'his', 'its', 'our', 'their',
        'this', 'that', 'these', 'those',
        'what', 'which', 'who', 'when', 'where', 'why', 'how',
        'in', 'on', 'at', 'to', 'for', 'of', 'with', 'from', 'by', 'about',
        'and', 'or', 'but', 'if', 'as', 'so', 'than',
        'very', 'too', 'much', 'many', 'more', 'most', 'some', 'any', 'all',
    ];

    // Từ điển dịch tiếng Việt -> tiếng Anh (các từ phổ biến trong thư viện)
    private static $viToEnDict = [
        'mượn' => 'borrow',
        'trả' => 'return',
        'sách' => 'book',
        'thư viện' => 'library',
        'thẻ' => 'card',
        'đăng ký' => 'register',
        'gia hạn' => 'renew',
        'tra cứu' => 'search',
        'tìm kiếm' => 'search',
        'tài liệu' => 'document',
        'luận văn' => 'thesis',
        'đề tài' => 'project',
        'nghiên cứu' => 'research',
        'giờ' => 'time',
        'mở cửa' => 'open',
        'đóng cửa' => 'close',
        'phòng đọc' => 'reading room',
        'kho' => 'storage',
        'tầng' => 'floor',
        'quầy' => 'desk',
        'thủ thư' => 'librarian',
        'sinh viên' => 'student',
        'giảng viên' => 'lecturer',
        'phí' => 'fee',
        'miễn phí' => 'free',
        'quy định' => 'regulation',
        'nội quy' => 'rule',
        'hướng dẫn' => 'guide',
        'thủ tục' => 'procedure',
        'biểu mẫu' => 'form',
        'đơn' => 'application',
        'xác nhận' => 'confirm',
        'tốt nghiệp' => 'graduate',
        'học kỳ' => 'semester',
        'năm học' => 'academic year',
        'khoa' => 'faculty',
        'chuyên ngành' => 'major',
        'photocopy' => 'photocopy',
        'in ấn' => 'print',
        'wifi' => 'wifi',
        'máy tính' => 'computer',
        'internet' => 'internet',
        'cơ sở dữ liệu' => 'database',
        'tạp chí' => 'journal',
        'báo' => 'newspaper',
        'điện tử' => 'electronic',
        'online' => 'online',
        'mật khẩu' => 'password',
        'tài khoản' => 'account',
        'đăng nhập' => 'login',
        'quên' => 'forget',
        'mất' => 'lost',
        'hỏng' => 'damaged',
        'phạt' => 'fine',
        'trễ hạn' => 'overdue',
        'đặt trước' => 'reserve',
        'yêu cầu' => 'request',
        'liên hệ' => 'contact',
        'email' => 'email',
        'điện thoại' => 'phone',
        'địa chỉ' => 'address',
    ];

    /**
     * Tạo từ khóa tự động từ câu hỏi (có trọng số)
     * @param string $questionText Nội dung câu hỏi
     * @return array ['vi' => [['keyword' => '...', 'weight' => 8.0], ...], 'en' => [...]]
     */
    public static function generate(string $questionText): array
    {
        $keywordsVi = self::extractVietnameseKeywords($questionText);
        $keywordsEn = self::translateToEnglish($keywordsVi);
        
        return [
            'vi' => $keywordsVi,
            'en' => $keywordsEn,
        ];
    }

    /**
     * Trích xuất từ khóa tiếng Việt từ câu hỏi (có trọng số)
     */
    private static function extractVietnameseKeywords(string $text): array
    {
        // Chuẩn hóa text
        $text = mb_strtolower(trim($text));
        
        // Loại bỏ dấu câu
        $text = preg_replace('/[?!.,;:()"\[\]{}]+/u', ' ', $text);
        
        // Tách từ
        $words = preg_split('/\s+/u', $text);
        
        $keywords = [];
        
        // Trích xuất cụm từ 4 từ (trọng số cao nhất: 10.0)
        for ($i = 0; $i < count($words) - 3; $i++) {
            $phrase4 = $words[$i] . ' ' . $words[$i + 1] . ' ' . $words[$i + 2] . ' ' . $words[$i + 3];
            if (self::isValidPhrase($phrase4)) {
                $keywords[] = ['keyword' => $phrase4, 'weight' => 10.0];
            }
        }
        
        // Trích xuất cụm từ 3 từ (trọng số: 9.0)
        for ($i = 0; $i < count($words) - 2; $i++) {
            $phrase3 = $words[$i] . ' ' . $words[$i + 1] . ' ' . $words[$i + 2];
            if (self::isValidPhrase($phrase3)) {
                $keywords[] = ['keyword' => $phrase3, 'weight' => 9.0];
            }
        }
        
        // Trích xuất cụm từ 2 từ (trọng số: 7.0)
        for ($i = 0; $i < count($words) - 1; $i++) {
            $phrase2 = $words[$i] . ' ' . $words[$i + 1];
            if (self::isValidPhrase($phrase2)) {
                $keywords[] = ['keyword' => $phrase2, 'weight' => 7.0];
            }
        }
        
        // Trích xuất từ đơn (trọng số: 5.0)
        foreach ($words as $word) {
            $word = trim($word);
            
            // Bỏ từ quá ngắn
            if (mb_strlen($word) < 2) continue;
            
            // Bỏ stop words
            if (in_array($word, self::$stopWordsVi)) continue;
            
            // Bỏ từ chỉ có số
            if (preg_match('/^\d+$/', $word)) continue;
            
            $keywords[] = ['keyword' => $word, 'weight' => 5.0];
        }
        
        // Loại bỏ trùng lặp (giữ trọng số cao nhất)
        $uniqueKeywords = [];
        foreach ($keywords as $item) {
            $kw = $item['keyword'];
            if (!isset($uniqueKeywords[$kw]) || $uniqueKeywords[$kw]['weight'] < $item['weight']) {
                $uniqueKeywords[$kw] = $item;
            }
        }
        
        $result = array_values($uniqueKeywords);
        
        // Sắp xếp theo trọng số giảm dần
        usort($result, function($a, $b) {
            return $b['weight'] <=> $a['weight'];
        });
        
        // Giới hạn tối đa 15 từ khóa tiếng Việt
        return array_slice($result, 0, 15);
    }

    /**
     * Kiểm tra cụm từ có hợp lệ không
     */
    private static function isValidPhrase(string $phrase): bool
    {
        // Bỏ cụm từ quá ngắn
        if (mb_strlen($phrase) < 5) return false;
        
        // Bỏ cụm từ chỉ chứa stop words
        $words = explode(' ', $phrase);
        $hasContent = false;
        foreach ($words as $word) {
            if (!in_array($word, self::$stopWordsVi)) {
                $hasContent = true;
                break;
            }
        }
        
        return $hasContent;
    }

    /**
     * Dịch từ khóa tiếng Việt sang tiếng Anh (giữ trọng số)
     */
    private static function translateToEnglish(array $viKeywords): array
    {
        $enKeywords = [];
        
        foreach ($viKeywords as $item) {
            $viKeyword = $item['keyword'];
            $weight = $item['weight'];
            
            // Tìm trong từ điển
            if (isset(self::$viToEnDict[$viKeyword])) {
                $enKeywords[] = ['keyword' => self::$viToEnDict[$viKeyword], 'weight' => $weight];
                continue;
            }
            
            // Dịch cụm từ (tách từng từ)
            $words = explode(' ', $viKeyword);
            $translatedWords = [];
            $allTranslated = true;
            
            foreach ($words as $word) {
                if (isset(self::$viToEnDict[$word])) {
                    $translatedWords[] = self::$viToEnDict[$word];
                } else {
                    $allTranslated = false;
                    break;
                }
            }
            
            // Nếu dịch được toàn bộ cụm từ
            if ($allTranslated && !empty($translatedWords)) {
                $enKeywords[] = ['keyword' => implode(' ', $translatedWords), 'weight' => $weight];
            }
        }
        
        // Loại bỏ trùng lặp (giữ trọng số cao nhất)
        $uniqueKeywords = [];
        foreach ($enKeywords as $item) {
            $kw = $item['keyword'];
            if (!isset($uniqueKeywords[$kw]) || $uniqueKeywords[$kw]['weight'] < $item['weight']) {
                $uniqueKeywords[$kw] = $item;
            }
        }
        
        $result = array_values($uniqueKeywords);
        
        // Giới hạn tối đa 10 từ khóa tiếng Anh
        return array_slice($result, 0, 10);
    }

    /**
     * Thêm từ vào từ điển dịch (cho admin tùy chỉnh)
     */
    public static function addToDictionary(string $vi, string $en): void
    {
        self::$viToEnDict[$vi] = $en;
    }

    /**
     * Lấy từ điển hiện tại
     */
    public static function getDictionary(): array
    {
        return self::$viToEnDict;
    }
}
