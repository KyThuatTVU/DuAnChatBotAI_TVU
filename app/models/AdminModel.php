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

        // Chỉ cho phép Google login nếu email đã được phân quyền sẵn
        $existingByEmail = $this->findByEmail($googleData['email']);
        if ($existingByEmail) {
            $this->update($existingByEmail['id'], [
                'google_id' => $googleData['google_id'],
                'full_name' => $googleData['full_name'],
                'avatar_url' => $googleData['avatar_url'],
                'last_login' => date('Y-m-d H:i:s'),
            ]);
            return $existingByEmail['id'];
        }

        return null;
    }

    /**
     * Lấy danh sách admin (ẩn thông tin nhạy cảm)
     */
    public function getAllSafe()
    {
        $sql = "SELECT id, google_id, email, full_name, avatar_url, role, is_active, last_login, created_at 
                FROM {$this->table} ORDER BY id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Cập nhật thời gian đăng nhập
     */
    public function updateLastLogin($id)
    {
        $this->update($id, ['last_login' => date('Y-m-d H:i:s')]);
    }

    /**
     * Cập nhật mật khẩu
     */
    public function updatePassword($id, $hashedPassword)
    {
        return $this->update($id, ['password' => $hashedPassword]);
    }

    /**
     * Tạo token đặt lại mật khẩu (hết hạn sau 30 phút)
     */
    public function createResetToken($email)
    {
        $admin = $this->findByEmail($email);
        if (!$admin) return null;

        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        $this->update($admin['id'], [
            'reset_token' => $token,
            'reset_token_expiry' => $expiry,
        ]);

        return ['token' => $token, 'admin' => $admin];
    }

    /**
     * Tìm admin theo reset token (còn hạn)
     */
    public function findByResetToken($token)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE reset_token = ? AND reset_token_expiry > NOW()"
        );
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    /**
     * Xóa reset token
     */
    public function clearResetToken($id)
    {
        return $this->update($id, [
            'reset_token' => null,
            'reset_token_expiry' => null,
        ]);
    }
}
