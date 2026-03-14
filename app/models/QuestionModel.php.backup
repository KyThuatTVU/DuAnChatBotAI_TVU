<?php
require_once __DIR__ . '/../core/BaseModel.php';

class QuestionModel extends BaseModel
{
    protected $table = 'questions';

    /**
     * Lấy tất cả câu hỏi kèm danh mục
     */
    public function getAllWithCategory()
    {
        $sql = "SELECT q.*, c.name as category_name 
                FROM {$this->table} q 
                LEFT JOIN categories c ON q.category_id = c.id 
                ORDER BY q.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Tìm câu trả lời phù hợp nhất (FULLTEXT search + kiểm tra ngữ cảnh)
     * Trả về false nếu câu hỏi quá mơ hồ hoặc không tìm thấy match chính xác
     */
    public function findAnswer($userMessage)
    {
        $messageLength = mb_strlen(trim($userMessage));

        // 0. Tin nhắn rỗng → không tìm
        if ($messageLength < 1) {
            return false;
        }

        // 1. Tìm chính xác (exact match) - so khớp toàn bộ câu hỏi
        $sql = "SELECT * FROM {$this->table} 
                WHERE is_active = 1 AND LOWER(TRIM(question_text)) = LOWER(TRIM(?))
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userMessage]);
        $result = $stmt->fetch();

        if ($result) {
            return $result;
        }

        // 2. Kiểm tra xem có bao nhiêu câu hỏi chứa các từ khóa từ câu hỏi người dùng
        // Nếu có nhiều câu → hiển thị danh sách thay vì trả lời 1 câu
        $keywords = $this->extractKeywordsFromMessage($userMessage);
        if (!empty($keywords)) {
            $countSql = "SELECT COUNT(DISTINCT q.id) as total FROM {$this->table} q
                        WHERE q.is_active = 1 AND (";
            $conditions = [];
            $params = [];
            
            foreach ($keywords as $keyword) {
                $conditions[] = "LOWER(q.question_text) LIKE CONCAT('%', LOWER(?), '%')";
                $params[] = $keyword;
            }
            
            $countSql .= implode(" OR ", $conditions) . ")";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $count = $countStmt->fetch()['total'];
            
            // Nếu có nhiều hơn 1 câu hỏi chứa các từ khóa này → câu hỏi quá mơ hồ
            if ($count > 1) {
                return false;
            }
        }

        // 3. Tìm bằng FULLTEXT (chỉ khi tin nhắn đủ dài - tối thiểu 15 ký tự)
        // Để tránh match quá rộng với câu hỏi ngắn
        // Tăng ngưỡng relevance lên 2.0 để chỉ match những câu rất liên quan
        if ($messageLength >= 15) {
            $sql = "SELECT q.*, MATCH(question_text) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
                    FROM {$this->table} q 
                    WHERE q.is_active = 1 
                    AND MATCH(question_text) AGAINST(? IN NATURAL LANGUAGE MODE)
                    HAVING relevance > 2.0
                    ORDER BY relevance DESC 
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userMessage, $userMessage]);
            $result = $stmt->fetch();

            if ($result) {
                return $result;
            }
        }

        // 4. Tìm bằng LIKE - chỉ khi tin nhắn đủ dài (>= 20 ký tự) để tránh match quá rộng
        if ($messageLength >= 20) {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE is_active = 1 AND (
                        LOWER(question_text) LIKE CONCAT('%', LOWER(?), '%')
                    )
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userMessage]);
            $result = $stmt->fetch();

            if ($result) {
                return $result;
            }
        }

        // Không tìm thấy câu trả lời phù hợp
        return false;
    }

    /**
     * Trích xuất các từ khóa chính từ câu hỏi (loại bỏ stop words)
     */
    private function extractKeywordsFromMessage(string $message): array
    {
        $stopWords = [
            // Từ hỏi
            'gì', 'nào', 'đâu', 'sao', 'không', 'có', 'bao', 'nhiêu', 'mấy',
            'thế', 'làm', 'bằng',
            // Từ nối / chức năng
            'là', 'và', 'của', 'cho', 'với', 'trong', 'ngoài', 'trên', 'dưới',
            'từ', 'đến', 'được', 'bị', 'để', 'rằng', 'mà', 'thì', 'cũng',
            'đã', 'sẽ', 'đang', 'vẫn', 'còn', 'nếu', 'khi', 'hay', 'hoặc',
            'này', 'kia', 'đó', 'ấy', 'nọ',
            // Đại từ
            'tôi', 'mình', 'bạn', 'em', 'anh', 'chị',
            // Từ phổ biến khác
            'các', 'những', 'một', 'hai', 'ba', 'mỗi', 'tất', 'cả',
            'rất', 'quá', 'lắm', 'nhất', 'hơn',
            'xin', 'vui', 'lòng', 'ơi', 'nhé', 'nha', 'ạ', 'hả',
            'muốn', 'cần', 'phải', 'nên', 'thể',
            'hỏi', 'biết', 'hãy',
        ];

        $message = mb_strtolower(trim($message));
        // Loại bỏ dấu câu
        $message = preg_replace('/[?!.,;:]+/u', '', $message);

        // Tách từ
        $words = preg_split('/\s+/u', $message);

        $keywords = [];
        foreach ($words as $word) {
            $word = trim($word);
            if (mb_strlen($word) < 2) continue;
            if (in_array($word, $stopWords)) continue;
            $keywords[] = $word;
        }

        return $keywords;
    }

    /**
     * Tìm các câu hỏi liên quan (dùng khi câu hỏi quá vắng tắt)
     * Trả về danh sách câu hỏi để người dùng chọn
     * Không duyệt từ khóa để tránh match nhầm
     */
    public function findRelatedQuestions($userMessage, $limit = 20)
    {
        $messageLength = mb_strlen(trim($userMessage));
        $results = [];

        // 1. Tìm bằng FULLTEXT (nếu đủ dài) - ngưỡng thấp hơn để linh hoạt hơn
        if ($messageLength >= 3) {
            $sql = "SELECT q.*, MATCH(question_text) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
                    FROM {$this->table} q 
                    WHERE q.is_active = 1 
                    AND MATCH(question_text) AGAINST(? IN NATURAL LANGUAGE MODE)
                    HAVING relevance > 0.1
                    ORDER BY relevance DESC 
                    LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userMessage, $userMessage, $limit]);
            $results = $stmt->fetchAll();
        }

        // 2. Nếu không có kết quả FULLTEXT, tìm bằng LIKE rộng hơn
        if (empty($results) && $messageLength >= 3) {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE is_active = 1 AND (
                        LOWER(question_text) LIKE CONCAT('%', LOWER(?), '%')
                    )
                    LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userMessage, $limit]);
            $results = $stmt->fetchAll();
        }

        return $results;
    }

    /**
     * Trích xuất từ khóa nội dung (loại bỏ stop words tiếng Việt)
     * Giữ lại các từ chủ đề quan trọng để so sánh ngữ cảnh
     */
    private function extractContentWords(string $message): array
    {
        $stopWords = [
            // Từ hỏi
            'gì', 'nào', 'đâu', 'sao', 'không', 'có', 'bao', 'nhiêu', 'mấy',
            'thế', 'nào', 'làm', 'thế', 'nào', 'bằng',
            // Từ nối / chức năng
            'là', 'và', 'của', 'cho', 'với', 'trong', 'ngoài', 'trên', 'dưới',
            'từ', 'đến', 'được', 'bị', 'để', 'rằng', 'mà', 'thì', 'cũng',
            'đã', 'sẽ', 'đang', 'vẫn', 'còn', 'nếu', 'khi', 'hay', 'hoặc',
            'này', 'kia', 'đó', 'ấy', 'nọ',
            // Đại từ
            'tôi', 'mình', 'bạn', 'em', 'anh', 'chị',
            // Từ phổ biến khác
            'các', 'những', 'một', 'hai', 'ba', 'mỗi', 'tất', 'cả',
            'rất', 'quá', 'lắm', 'nhất', 'hơn',
            'xin', 'vui', 'lòng', 'ơi', 'nhé', 'nha', 'ạ', 'hả',
            'muốn', 'cần', 'phải', 'nên', 'thể',
            'hỏi', 'biết', 'cho', 'hãy',
            // Dấu câu và ký tự đặc biệt
            'lâu', 'bao lâu',
        ];

        $message = mb_strtolower(trim($message));
        // Loại bỏ dấu câu
        $message = preg_replace('/[?!.,;:]+/u', '', $message);

        // Tách từ
        $words = preg_split('/\s+/u', $message);

        $contentWords = [];
        foreach ($words as $word) {
            $word = trim($word);
            if (mb_strlen($word) < 2) continue;
            if (in_array($word, $stopWords)) continue;
            $contentWords[] = $word;
        }

        return $contentWords;
    }

    /**
     * Lấy câu hỏi theo danh mục
     */
    public function getByCategory($categoryId)
    {
        return $this->getAll('category_id = ? AND is_active = 1', [$categoryId]);
    }

    /**
     * Lấy câu hỏi gợi ý
     */
    public function getSuggestions($limit = 5)
    {
        $sql = "SELECT sq.*, q.answer_text 
                FROM suggested_questions sq 
                LEFT JOIN questions q ON sq.linked_question_id = q.id 
                WHERE sq.is_active = 1 
                ORDER BY sq.sort_order ASC 
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
