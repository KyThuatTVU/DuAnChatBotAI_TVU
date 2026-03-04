<?php
require_once __DIR__ . '/../core/BaseController.php';

class ChatController extends BaseController
{
    private $questionModel;
    private $chatModel;
    private $settingModel;

    public function __construct()
    {
        $this->questionModel = $this->model('QuestionModel');
        $this->chatModel = $this->model('ChatModel');
        $this->settingModel = $this->model('SettingModel');
    }

    /**
     * GET /api/chat - Lấy thông tin chatbot (settings, suggestions)
     */
    public function index()
    {
        $settings = $this->settingModel->getPublicSettings();
        $suggestions = $this->questionModel->getSuggestions($settings['max_suggestions']);
        $theme = $this->settingModel->getActiveTheme();

        $this->json([
            'settings' => $settings,
            'suggestions' => $suggestions,
            'theme' => $theme,
            'greeting' => getTimeGreeting(),
        ]);
    }

    /**
     * POST /api/chat/send - Gửi tin nhắn và nhận trả lời
     */
    public function send()
    {
        if ($this->getMethod() !== 'POST') {
            $this->json(['error' => 'Method not allowed'], 405);
        }

        $input = $this->getJsonInput();
        $message = trim($input['message'] ?? '');
        $sessionToken = $input['session_token'] ?? '';

        if (empty($message)) {
            $this->json(['error' => 'Tin nhắn không được để trống'], 400);
        }

        if (mb_strlen($message) > 3000) {
            $this->json(['error' => 'Tin nhắn không được vượt quá 3000 ký tự'], 400);
        }

        // Tìm hoặc tạo session
        $session = null;
        if ($sessionToken) {
            $session = $this->chatModel->findByToken($sessionToken);
        }
        if (!$session) {
            $sessionId = $this->chatModel->createSession(
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            $session = $this->chatModel->getById($sessionId);
        }

        // Lưu tin nhắn người dùng
        $this->chatModel->saveMessage($session['id'], 'user', $message);

        // Tìm câu trả lời
        $answer = $this->questionModel->findAnswer($message);
        $settings = $this->settingModel->getPublicSettings();

        if ($answer) {
            $botReply = $answer['answer_text'];
            $this->chatModel->saveMessage($session['id'], 'bot', $botReply, $answer['id'], 1.0);
        } else {
            $botReply = $settings['no_answer_message'];
            $this->chatModel->saveMessage($session['id'], 'bot', $botReply);
            $this->chatModel->saveUnanswered($session['id'], $message);
        }

        $this->json([
            'success' => true,
            'reply' => $botReply,
            'session_token' => $session['session_token'],
            'matched' => $answer ? true : false,
        ]);
    }

    /**
     * POST /api/chat/new - Tạo cuộc trò chuyện mới
     */
    public function newChat()
    {
        $sessionId = $this->chatModel->createSession(
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );
        $session = $this->chatModel->getById($sessionId);
        $this->json([
            'success' => true,
            'session_token' => $session['session_token'],
        ]);
    }

    /**
     * GET /api/chat/history/{token} - Lấy lịch sử chat
     */
    public function history($token = '')
    {
        if (empty($token)) {
            $this->json(['error' => 'Token không hợp lệ'], 400);
        }
        $session = $this->chatModel->findByToken($token);
        if (!$session) {
            $this->json(['error' => 'Phiên chat không tồn tại'], 404);
        }
        $messages = $this->chatModel->getMessages($session['id']);
        $this->json(['messages' => $messages]);
    }
}
