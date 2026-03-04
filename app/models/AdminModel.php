<?php
require_once __DIR__ . '/../core/BaseModel.php';

class AdminModel extends BaseModel
{
    protected $table = 'admins';

    /**
     * Tìm admin theo Google ID
     */
    public function findByGoogleId($googleId)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE google_id = ?");
        $stmt->execute([$googleId]);
        return $stmt->fetch();
    }

    /**
     * Tìm admin theo email
     */
    public function findByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    /**
     * Tạo hoặc cập nhật admin từ Google login
     */
    public function upsertFromGoogle($googleData)
    {
        $existing = $this->findByGoogleId($googleData['google_id']);

        if ($existing) {
            $this->update($existing['id'], [
                'full_name' => $googleData['full_name'],
                'avatar_url' => $googleData['avatar_url'],
                'last_login' => date('Y-m-d H:i:s'),
            ]);
            return $existing['id'];
        }

        return $this->create([
            'google_id' => $googleData['google_id'],
            'email' => $googleData['email'],
            'full_name' => $googleData['full_name'],
            'avatar_url' => $googleData['avatar_url'],
            'role' => 'admin',
            'is_active' => 1,
            'last_login' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Cập nhật thời gian đăng nhập
     */
    public function updateLastLogin($id)
    {
        $this->update($id, ['last_login' => date('Y-m-d H:i:s')]);
    }
}
