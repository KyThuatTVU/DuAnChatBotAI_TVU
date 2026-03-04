<?php
require_once __DIR__ . '/../core/BaseModel.php';

class ChatModel extends BaseModel
{
    protected $table = 'chat_sessions';

    /**
     * Tạo phiên chat mới
     */
    public function createSession($ip, $userAgent)
    {
        return $this->create([
            'session_token' => generateToken(),
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Lưu tin nhắn
     */
    public function saveMessage($sessionId, $senderType, $messageText, $matchedQuestionId = null, $confidence = null)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO chat_messages (session_id, sender_type, message_text, matched_question_id, confidence_score) 
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$sessionId, $senderType, $messageText, $matchedQuestionId, $confidence]);

        // Cập nhật tổng tin nhắn
        $this->db->prepare("UPDATE {$this->table} SET total_messages = total_messages + 1 WHERE id = ?")->execute([$sessionId]);

        return $this->db->lastInsertId();
    }

    /**
     * Lấy lịch sử chat
     */
    public function getMessages($sessionId)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM chat_messages WHERE session_id = ? ORDER BY created_at ASC"
        );
        $stmt->execute([$sessionId]);
        return $stmt->fetchAll();
    }

    /**
     * Lưu câu hỏi chưa trả lời được
     */
    public function saveUnanswered($sessionId, $questionText)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO unanswered_questions (session_id, question_text) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE frequency = frequency + 1"
        );
        $stmt->execute([$sessionId, $questionText]);
    }

    /**
     * Tìm session theo token
     */
    public function findByToken($token)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE session_token = ?");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    /**
     * Thống kê chat
     */
    public function getStats()
    {
        $totalSessions = $this->count();
        
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM chat_messages");
        $stmt->execute();
        $totalMessages = $stmt->fetch()['total'];

        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM unanswered_questions WHERE is_resolved = 0");
        $stmt->execute();
        $unanswered = $stmt->fetch()['total'];

        return [
            'total_sessions' => $totalSessions,
            'total_messages' => $totalMessages,
            'unanswered_count' => $unanswered,
        ];
    }
}
