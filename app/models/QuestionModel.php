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
        // 1. Tìm chính xác bằng FULLTEXT
        $sql = "SELECT q.*, MATCH(question_text) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
                FROM {$this->table} q 
                WHERE q.is_active = 1 
                AND MATCH(question_text) AGAINST(? IN NATURAL LANGUAGE MODE)
                ORDER BY relevance DESC 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userMessage, $userMessage]);
        $result = $stmt->fetch();

        if ($result && $result['relevance'] > 0) {
            return $result;
        }

        // 2. Tìm bằng từ khóa
        $sql = "SELECT q.* FROM {$this->table} q
                INNER JOIN keywords k ON q.id = k.question_id
                WHERE q.is_active = 1 AND LOWER(?) LIKE CONCAT('%', LOWER(k.keyword), '%')
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userMessage]);
        $result = $stmt->fetch();

        if ($result) {
            return $result;
        }

        // 3. Tìm bằng LIKE
        $sql = "SELECT * FROM {$this->table} 
                WHERE is_active = 1 AND (
                    LOWER(question_text) LIKE CONCAT('%', LOWER(?), '%')
                    OR LOWER(?) LIKE CONCAT('%', LOWER(question_text), '%')
                )
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userMessage, $userMessage]);
        return $stmt->fetch();
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
