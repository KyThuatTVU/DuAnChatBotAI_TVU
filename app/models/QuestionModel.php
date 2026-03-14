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
     * Tìm câu trả lời phù hợp nhất
     * Phiên bản đơn giản: chỉ dùng FULLTEXT search
     */
    public function findAnswer($userMessage)
    {
        $messageLength = mb_strlen(trim($userMessage));

        // 0. Tin nhắn rỗng → không tìm
        if ($messageLength < 1) {
            return false;
        }

        // 0.5 Kiểm tra spam
        if ($this->isSpam($userMessage)) {
            return false;
        }

        // 1. Tìm chính xác (exact match)
        $sql = "SELECT * FROM {$this->table} 
                WHERE is_active = 1 AND LOWER(TRIM(question_text)) = LOWER(TRIM(?))
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userMessage]);
        $result = $stmt->fetch();

        if ($result) {
            return $result;
        }

        // 2. Tìm bằng FULLTEXT (hiểu ngữ cảnh tốt nhất)
        if ($messageLength >= 5) {
            $sql = "SELECT q.*, MATCH(question_text) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
                    FROM {$this->table} q 
                    WHERE q.is_active = 1 
                    AND MATCH(question_text) AGAINST(? IN NATURAL LANGUAGE MODE)
                    HAVING relevance > 0.5
                    ORDER BY relevance DESC 
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userMessage, $userMessage]);
            $result = $stmt->fetch();

            if ($result && $result['relevance'] > 1.0) {
                return $result;
            }
        }

        // Không tìm thấy câu trả lời phù hợp
        return false;
    }

    /**
     * Kiểm tra xem tin nhắn có phải spam không
     */
    private function isSpam($message)
    {
        $message = trim($message);
        $messageLength = mb_strlen($message);
        
        // 1. Tin nhắn quá ngắn (< 2 ký tự)
        if ($messageLength < 2) {
            return true;
        }
        
        // 2. Chỉ chứa ký tự lặp lại (aaaa, ????, 1111)
        if (preg_match('/^(.)\1+$/u', $message)) {
            return true;
        }
        
        // 3. Chỉ chứa số
        if (preg_match('/^\d+$/', $message)) {
            return true;
        }
        
        // 4. Chỉ chứa ký tự đặc biệt
        if (preg_match('/^[^\w\s]+$/u', $message)) {
            return true;
        }
        
        // 5. Lặp lại cùng 1 từ nhiều lần (abc abc abc)
        $words = preg_split('/\s+/u', $message);
        if (count($words) > 2) {
            $uniqueWords = array_unique($words);
            // Nếu > 70% từ giống nhau → spam
            if (count($uniqueWords) / count($words) < 0.3) {
                return true;
            }
        }
        
        // 6. Chứa quá nhiều ký tự lặp liên tiếp (aaabbbccc)
        if (preg_match('/(.)\1{4,}/u', $message)) {
            return true;
        }
        
        return false;
    }

    /**
     * Tìm các câu hỏi liên quan (dùng khi không tìm thấy exact match)
     */
    public function findRelatedQuestions($userMessage, $limit = 5)
    {
        $messageLength = mb_strlen(trim($userMessage));
        $results = [];

        // 1. Tìm bằng FULLTEXT
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

        // 2. Nếu không có kết quả, tìm bằng LIKE
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
     * Lấy câu hỏi theo ID
     */
    public function getById($id)
    {
        $sql = "SELECT q.*, c.name as category_name 
                FROM {$this->table} q 
                LEFT JOIN categories c ON q.category_id = c.id 
                WHERE q.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Lấy câu hỏi theo danh mục
     */
    public function getByCategory($categoryId)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE is_active = 1 AND category_id = ? 
                ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll();
    }

    /**
     * Thêm câu hỏi mới
     */
    public function create($data)
    {
        $sql = "INSERT INTO {$this->table} 
                (category_id, question_text, answer_text, answer_text_en, source_type, created_by) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['category_id'],
            $data['question_text'],
            $data['answer_text'],
            $data['answer_text_en'] ?? null,
            $data['source_type'] ?? 'manual',
            $data['created_by'] ?? null
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * Cập nhật câu hỏi
     */
    public function update($id, $data)
    {
        $sql = "UPDATE {$this->table} 
                SET category_id = ?, question_text = ?, answer_text = ?, answer_text_en = ? 
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['category_id'],
            $data['question_text'],
            $data['answer_text'],
            $data['answer_text_en'] ?? null,
            $id
        ]);
    }

    /**
     * Xóa câu hỏi
     */
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Xóa nhiều câu hỏi cùng lúc
     */
    public function deleteMultiple($ids)
    {
        if (empty($ids) || !is_array($ids)) {
            return false;
        }
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "DELETE FROM {$this->table} WHERE id IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($ids);
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
