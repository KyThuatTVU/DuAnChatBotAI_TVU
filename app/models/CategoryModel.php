<?php
require_once __DIR__ . '/../core/BaseModel.php';

class CategoryModel extends BaseModel
{
    protected $table = 'categories';

    /**
     * Lấy danh mục có đếm số câu hỏi
     */
    public function getAllWithCount()
    {
        $sql = "SELECT c.*, COUNT(q.id) as question_count 
                FROM {$this->table} c 
                LEFT JOIN questions q ON c.id = q.category_id 
                GROUP BY c.id 
                ORDER BY c.sort_order ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Lấy danh mục đang hoạt động
     */
    public function getActive()
    {
        return $this->getAll('is_active = 1', [], 'sort_order ASC');
    }

    /**
     * Lấy danh mục đang hoạt động kèm đếm số câu hỏi
     */
    public function getActiveWithCount()
    {
        $sql = "SELECT c.*, COUNT(q.id) as question_count 
                FROM {$this->table} c 
                LEFT JOIN questions q ON c.id = q.category_id AND q.is_active = 1
                WHERE c.is_active = 1
                GROUP BY c.id 
                ORDER BY c.sort_order ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
