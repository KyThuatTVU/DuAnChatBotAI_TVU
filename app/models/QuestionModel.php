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

        // 2. Tìm bằng FULLTEXT (chỉ khi tin nhắn đủ dài cho FULLTEXT - tối thiểu 3 ký tự)
        if ($messageLength >= 3) {
            $sql = "SELECT q.*, MATCH(question_text) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
                    FROM {$this->table} q 
                    WHERE q.is_active = 1 
                    AND MATCH(question_text) AGAINST(? IN NATURAL LANGUAGE MODE)
                    HAVING relevance > 0.5
                    ORDER BY relevance DESC 
                    LIMIT 5";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userMessage, $userMessage]);
            $results = $stmt->fetchAll();

            if (!empty($results)) {
                // Kiểm tra từ khóa nội dung: ưu tiên câu hỏi có chứa từ chủ đề
                $contentWords = $this->extractContentWords($userMessage);
                
                if (!empty($contentWords)) {
                    // Tìm kết quả có từ khóa nội dung trùng khớp
                    foreach ($results as $r) {
                        $questionLower = mb_strtolower($r['question_text']);
                        foreach ($contentWords as $word) {
                            if (mb_strpos($questionLower, $word) !== false) {
                                return $r;
                            }
                        }
                    }
                    // Không có kết quả nào khớp từ chủ đề → bỏ qua FULLTEXT
                } else {
                    // Không có từ nội dung đặc biệt → dùng kết quả relevance cao nhất
                    return $results[0];
                }
            }
        }

        // 3a. Tìm bằng từ khóa ngắn (< 3 ký tự) - chỉ khớp khi tin nhắn ngắn trùng chính xác
        if ($messageLength <= 5) {
            $sql = "SELECT q.*, k.keyword FROM {$this->table} q
                    INNER JOIN keywords k ON q.id = k.question_id
                    WHERE q.is_active = 1
                    AND CHAR_LENGTH(k.keyword) < 3
                    AND LOWER(TRIM(?)) = LOWER(TRIM(k.keyword))
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userMessage]);
            $result = $stmt->fetch();

            if ($result) {
                return $result;
            }
        }

        // 3b. Tìm bằng từ khóa dài (>= 3 ký tự) - khớp substring
        $sql = "SELECT q.*, k.keyword FROM {$this->table} q
                INNER JOIN keywords k ON q.id = k.question_id
                WHERE q.is_active = 1 
                AND CHAR_LENGTH(k.keyword) >= 3
                AND LOWER(?) LIKE CONCAT('%', LOWER(k.keyword), '%')
                ORDER BY CHAR_LENGTH(k.keyword) DESC
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userMessage]);
        $result = $stmt->fetch();

        if ($result) {
            return $result;
        }

        // 4. Tìm bằng LIKE - chỉ khi tin nhắn đủ dài (>= 6 ký tự) để tránh match quá rộng
        if ($messageLength >= 6) {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE is_active = 1 AND (
                        LOWER(question_text) LIKE CONCAT('%', LOWER(?), '%')
                        OR LOWER(?) LIKE CONCAT('%', LOWER(question_text), '%')
                    )
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userMessage, $userMessage]);
            $result = $stmt->fetch();

            if ($result) {
                return $result;
            }
        }

        // Không tìm thấy câu trả lời phù hợp
        return false;
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
