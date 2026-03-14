<?php
require_once __DIR__ . '/../core/BaseModel.php';

class FormModel extends BaseModel
{
    protected $table = 'forms';

    /**
     * Tìm biểu mẫu phù hợp với tin nhắn người dùng
     * So khớp keyword trong trường `keywords` (comma-separated)
     */
    public function findMatchingForms(string $userMessage): array
    {
        $len = mb_strlen(trim($userMessage));
        if ($len < 2) return [];

        // 1. Tìm qua keyword – so khớp từng từ khóa (>= 3 ký tự)
        $sql = "SELECT * FROM {$this->table}
                WHERE is_active = 1
                  AND keywords IS NOT NULL
                  AND keywords != ''
                ORDER BY id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $all = $stmt->fetchAll();

        $matched = [];
        $msgLower = mb_strtolower($userMessage);

        foreach ($all as $form) {
            $keywords = array_filter(
                array_map('trim', explode(',', $form['keywords']))
            );
            foreach ($keywords as $kw) {
                if (mb_strlen($kw) >= 2 && mb_strpos($msgLower, mb_strtolower($kw)) !== false) {
                    $matched[] = $form;
                    break; // Mỗi form chỉ thêm 1 lần
                }
            }
        }

        // 2. Nếu chưa khớp, thử LIKE trên name / description (>= 4 ký tự)
        if (empty($matched) && $len >= 4) {
            $sql = "SELECT * FROM {$this->table}
                    WHERE is_active = 1
                      AND (LOWER(name) LIKE LOWER(?)
                           OR LOWER(description) LIKE LOWER(?))
                    LIMIT 5";
            $param = "%{$userMessage}%";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$param, $param]);
            $matched = $stmt->fetchAll();
        }

        return $matched;
    }

    /**
     * Lấy tất cả biểu mẫu (JOIN với admins để lấy tên người tạo)
     */
    public function getAll($conditions = '', $params = [], $orderBy = 'f.created_at DESC'): array
    {
        $sql = "SELECT f.*, a.full_name as created_by_name
                FROM {$this->table} f
                LEFT JOIN admins a ON f.created_by = a.id";
        if ($conditions) $sql .= " WHERE {$conditions}";
        $sql .= " ORDER BY {$orderBy}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Xóa nhiều biểu mẫu cùng lúc
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
}
