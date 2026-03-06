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
     * Tìm câu trả lời phù hợp nhất (FULLTEXT search)
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
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userMessage, $userMessage]);
            $result = $stmt->fetch();

            if ($result && $result['relevance'] > 0.5) {
                return $result;
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
