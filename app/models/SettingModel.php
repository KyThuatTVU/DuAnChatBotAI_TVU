<?php
require_once __DIR__ . '/../core/BaseModel.php';

class SettingModel extends BaseModel
{
    protected $table = 'chatbot_settings';

    /**
     * Lấy giá trị cài đặt theo key
     */
    public function get($key, $default = null)
    {
        $stmt = $this->db->prepare("SELECT setting_value FROM {$this->table} WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    }

    /**
     * Cập nhật cài đặt
     */
    public function set($key, $value, $updatedBy = null)
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET setting_value = ?, updated_by = ? WHERE setting_key = ?"
        );
        return $stmt->execute([$value, $updatedBy, $key]);
    }

    /**
     * Lấy tất cả cài đặt dạng key-value
     */
    public function getAllSettings()
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} ORDER BY id ASC");
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = [
                'value' => $row['setting_value'],
                'type' => $row['setting_type'],
                'description' => $row['description'],
            ];
        }
        return $settings;
    }

    /**
     * Lấy cài đặt cho frontend (chatbot widget)
     */
    public function getPublicSettings()
    {
        $all = $this->getAllSettings();
        return [
            'enabled' => ($all['chatbot_enabled']['value'] ?? 'true') === 'true',
            'title' => $all['chatbot_title']['value'] ?? 'Chatbot Thư Viện',
            'welcome_message' => $all['welcome_message']['value'] ?? 'Xin chào!',
            'primary_color' => $all['primary_color']['value'] ?? '#1976D2',
            'secondary_color' => $all['secondary_color']['value'] ?? '#FFFFFF',
            'header_bg_color' => $all['header_bg_color']['value'] ?? '#1976D2',
            'header_text_color' => $all['header_text_color']['value'] ?? '#FFFFFF',
            'user_bubble_color' => $all['user_bubble_color']['value'] ?? '#E3F2FD',
            'bot_bubble_color' => $all['bot_bubble_color']['value'] ?? '#F5F5F5',
            'button_color' => $all['button_color']['value'] ?? '#1976D2',
            'button_position' => json_decode($all['button_position']['value'] ?? '{}', true),
            'bot_avatar' => $all['bot_avatar']['value'] ?? '',
            'no_answer_message' => $all['no_answer_message']['value'] ?? 'Cảm ơn bạn đã đặt câu hỏi! Hiện tại mình chưa tìm thấy thông tin phù hợp cho câu hỏi này. Bạn có thể thử hỏi lại theo cách khác, hoặc liên hệ trực tiếp với thủ thư qua số (02943) 855 246 hoặc email celras@tvu.edu.vn để được hỗ trợ tận tình nhé! 😊',
            'max_suggestions' => (int)($all['max_suggestions']['value'] ?? 5),
        ];
    }

    /**
     * Lấy theme đang hoạt động
     */
    public function getActiveTheme()
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM event_themes 
             WHERE is_active = 1 
             AND (start_date IS NULL OR start_date <= CURDATE()) 
             AND (end_date IS NULL OR end_date >= CURDATE()) 
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Lấy tất cả themes
     */
    public function getAllThemes()
    {
        $stmt = $this->db->prepare("SELECT * FROM event_themes ORDER BY id ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
