<?php
/**
 * Base Model - Kết nối Database
 */
class BaseModel
{
    protected $db;
    protected $table;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Lấy tất cả bản ghi
     */
    public function getAll($conditions = '', $params = [], $orderBy = 'id DESC')
    {
        $sql = "SELECT * FROM {$this->table}";
        if ($conditions) {
            $sql .= " WHERE $conditions";
        }
        $sql .= " ORDER BY $orderBy";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Lấy bản ghi theo ID
     */
    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Tạo bản ghi mới
     */
    public function create($data)
    {
        $keys = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$this->table} ($keys) VALUES ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        return $this->db->lastInsertId();
    }

    /**
     * Cập nhật bản ghi
     */
    public function update($id, $data)
    {
        $set = implode(' = ?, ', array_keys($data)) . ' = ?';
        $sql = "UPDATE {$this->table} SET $set WHERE id = ?";
        $values = array_values($data);
        $values[] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Xóa bản ghi
     */
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Đếm bản ghi
     */
    public function count($conditions = '', $params = [])
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        if ($conditions) {
            $sql .= " WHERE $conditions";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch()['total'];
    }
}
