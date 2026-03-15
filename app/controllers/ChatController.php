<?php
require_once __DIR__ . '/../core/BaseController.php';

class ChatController extends BaseController
{
    private $questionModel;
    private $chatModel;
    private $settingModel;
    private $formModel;
    private $categoryModel;

    public function __construct()
    {
        $this->questionModel = $this->model('QuestionModel');
        $this->chatModel     = $this->model('ChatModel');
        $this->settingModel  = $this->model('SettingModel');
        $this->formModel     = $this->model('FormModel');
        $this->categoryModel = $this->model('CategoryModel');
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
        $lang = $input['lang'] ?? 'vi';

        if (empty($message)) {
            $this->json(['error' => $lang === 'en' ? 'Message cannot be empty' : 'Tin nhắn không được để trống'], 400);
        }

        if (mb_strlen($message) > 3000) {
            $this->json(['error' => $lang === 'en' ? 'Message must not exceed 3000 characters' : 'Tin nhắn không được vượt quá 3000 ký tự'], 400);
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

        // Kiểm tra xem câu hỏi có liên quan đến thư viện không
        $isRelevant = $this->isLibraryRelated($message);

        // Tìm câu trả lời
        $answer = $this->questionModel->findAnswer($message);
        $settings = $this->settingModel->getPublicSettings();

        // Tìm biểu mẫu liên quan
        $matchedForms = $this->formModel->findMatchingForms($message);

        // Biến để lưu danh sách câu hỏi gợi ý (nếu câu hỏi vắng tắt)
        $relatedQuestions = [];

        if ($answer) {
            // Use English answer if lang=en and answer_text_en is available
            if ($lang === 'en' && !empty($answer['answer_text_en'])) {
                $botReply = $answer['answer_text_en'];
            } else {
                $botReply = $answer['answer_text'];
            }

            // Loại bỏ câu hỏi bị lồng ở đầu câu trả lời (nếu có)
            $botReply = $this->stripEmbeddedQuestion($botReply, $message);

            $this->chatModel->saveMessage($session['id'], 'bot', $botReply, $answer['id'], 1.0);
        } elseif (!empty($matchedForms)) {
            $botReply = $lang === 'en'
                ? 'Here are the forms/documents related to your request. Please click the link to download or fill in the information:'
                : 'Dưới đây là biểu mẫu / giấy tờ liên quan đến yêu cầu của bạn. Vui lòng nhấn vào link để tải về hoặc điền thông tin:';
            $this->chatModel->saveMessage($session['id'], 'bot', $botReply);
        } else {
            // Kiểm tra độ liên quan trước khi tìm related questions
            if (!$isRelevant) {
                // Câu hỏi không liên quan đến thư viện
                $botReply = $lang === 'en'
                    ? "Hi there! 😊 I'm the CELRAS TVU Library chatbot, so I can only help with questions about the library.\n\nI can assist you with:\n📚 Library services and hours\n📖 Borrowing and returning books\n🏢 Library facilities and locations\n📋 Forms and procedures\n\nFeel free to ask me anything about the library!"
                    : "Xin chào bạn! 😊 Mình là chatbot của Trung tâm Học liệu CELRAS TVU, nên mình chỉ có thể hỗ trợ các câu hỏi liên quan đến thư viện thôi nhé.\n\nMình có thể giúp bạn về:\n📚 Dịch vụ và giờ mở cửa thư viện\n📖 Mượn và trả sách\n🏢 Cơ sở vật chất và vị trí các phòng\n📋 Biểu mẫu và thủ tục\n\nHãy hỏi mình bất cứ điều gì về thư viện nhé!";
                $this->chatModel->saveMessage($session['id'], 'bot', $botReply);
                $this->chatModel->saveUnanswered($session['id'], $message);
            } else {
                // Kiểm tra xem câu hỏi có vắn tắt/chung chung không
                $isVague = $this->isVagueQuestion($message);
                
                // Tìm câu hỏi liên quan với thuật toán cải tiến
                $relatedQuestions = $this->questionModel->findRelatedQuestions($message, $isVague ? 8 : 5);

                if (!empty($relatedQuestions)) {
                    // Có câu hỏi liên quan → đưa ra để người dùng chọn
                    if ($isVague) {
                        $botReply = $lang === 'en'
                            ? "Your question is quite general. Here are some related questions that might help:"
                            : "Câu hỏi của bạn khá chung chung. Dưới đây là một số câu hỏi liên quan có thể giúp bạn:";
                    } else {
                        $botReply = $lang === 'en'
                            ? "I couldn't find an exact answer. Here are some related questions you might be interested in:"
                            : "Mình không tìm thấy câu trả lời chính xác. Dưới đây là một số câu hỏi liên quan bạn có thể quan tâm:";
                    }
                    $this->chatModel->saveMessage($session['id'], 'bot', $botReply);
                } else {
                    // Không có câu hỏi liên quan → thông báo không tìm thấy
                    if ($lang === 'en') {
                        $botReply = "Sorry, I couldn't find an answer to your question. 😊\n\nYou can try:\n📌 Rephrasing your question\n📌 Browsing the categories on the left\n📌 Contacting us directly:\n📧 Email: trungtamhoclieu@tvu.edu.vn\n📞 Phone: 0294 3855 246 (ext. 142)\n\nWe're happy to help!";
                    } else {
                        $botReply = $settings['no_answer_message'];
                    }
                    $this->chatModel->saveMessage($session['id'], 'bot', $botReply);
                    $this->chatModel->saveUnanswered($session['id'], $message);
                }
            }
        }

        $this->json([
            'success'          => true,
            'reply'            => $botReply,
            'forms'            => $matchedForms,
            'related_questions' => $relatedQuestions,
            'session_token'    => $session['session_token'],
            'matched'          => ($answer || !empty($matchedForms)) ? true : false,
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
     * GET /api/chat/categories - Lấy danh sách danh mục đang hoạt động kèm số câu hỏi
     */
    public function categories()
    {
        $categories = $this->categoryModel->getActiveWithCount();
        $this->json([
            'success'    => true,
            'categories' => $categories,
        ]);
    }

    /**
     * GET /api/chat/categoryQuestions/{id} - Lấy câu hỏi theo danh mục
     */
    public function categoryQuestions($categoryId = 0)
    {
        $categoryId = (int) $categoryId;
        if ($categoryId <= 0) {
            $this->json(['error' => 'ID danh mục không hợp lệ'], 400);
        }

        $category = $this->categoryModel->getById($categoryId);
        if (!$category || !$category['is_active']) {
            $this->json(['error' => 'Danh mục không tồn tại'], 404);
        }

        $questions = $this->questionModel->getByCategory($categoryId);
        $this->json([
            'success'   => true,
            'category'  => $category,
            'questions' => $questions,
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

    /**
     * Loại bỏ câu hỏi bị lồng ở đầu câu trả lời
     * VD: "18. Phòng Tài liệu Nội sinh nằm ở đâu?\nPhòng Tài liệu..." → "Phòng Tài liệu..."
     */
    private function stripEmbeddedQuestion(string $answer, string $question): string
    {
        $lines = explode("\n", $answer);
        if (count($lines) < 2) return $answer;

        $firstLine = trim($lines[0]);

        // Loại bỏ số thứ tự đầu dòng để so sánh
        $cleanFirst = preg_replace('/^\s*\d+[\s.):;\-]+\s*/', '', $firstLine);
        $cleanQuestion = preg_replace('/^\s*\d+[\s.):;\-]+\s*/', '', trim($question));

        // Bỏ dấu ? cuối để so sánh
        $cleanFirst = rtrim($cleanFirst, '? ');
        $cleanQuestion = rtrim($cleanQuestion, '? ');

        // Nếu dòng đầu chứa câu hỏi → xóa dòng đầu
        if (mb_strtolower($cleanFirst) === mb_strtolower($cleanQuestion) ||
            mb_strpos(mb_strtolower($firstLine), mb_strtolower($cleanQuestion)) !== false) {
            array_shift($lines);
            $result = trim(implode("\n", $lines));
            return !empty($result) ? $result : $answer;
        }

        return $answer;
    }

    /**
     * Kiểm tra xem câu hỏi có liên quan đến thư viện không
     * Dựa trên các từ khóa chủ đề của thư viện
     */
    private function isLibraryRelated(string $message): bool
    {
        $messageLower = mb_strtolower($message);

        // Danh sách từ khóa liên quan đến thư viện
        $libraryKeywords = [
            // Từ khóa chính
            'thư viện', 'library', 'học liệu', 'celras', 'tvu',
            
            // Dịch vụ
            'sách', 'book', 'mượn', 'trả', 'borrow', 'return', 'gia hạn', 'renew',
            'đọc', 'read', 'tài liệu', 'document', 'giáo trình', 'luận văn', 'thesis',
            
            // Địa điểm
            'phòng', 'room', 'tầng', 'floor', 'khu', 'area', 'vị trí', 'location',
            'đâu', 'where', 'nằm', 'ở đâu',
            
            // Thời gian
            'giờ', 'hour', 'mở cửa', 'đóng cửa', 'open', 'close', 'thời gian',
            
            // Thủ tục
            'đăng ký', 'register', 'thẻ', 'card', 'biểu mẫu', 'form', 'hồ sơ',
            'thủ tục', 'procedure', 'quy định', 'regulation', 'quy trình',
            
            // Cơ sở vật chất
            'máy tính', 'computer', 'wifi', 'internet', 'in ấn', 'print',
            'photocopy', 'scan', 'thiết bị', 'equipment',
            
            // Dịch vụ khác
            'hỗ trợ', 'support', 'tư vấn', 'consult', 'hướng dẫn', 'guide',
            'tìm kiếm', 'search', 'tra cứu', 'lookup',
            
            // Người dùng
            'sinh viên', 'student', 'giảng viên', 'teacher', 'lecturer',
            
            // Các phòng cụ thể
            'nội sinh', 'ngoại sinh', 'tự học', 'nghiên cứu', 'đọc báo',
            'multimedia', 'kho', 'lưu trữ',
        ];

        // Kiểm tra xem có từ khóa nào xuất hiện trong câu hỏi không
        foreach ($libraryKeywords as $keyword) {
            if (mb_strpos($messageLower, $keyword) !== false) {
                return true;
            }
        }

        // Nếu không có từ khóa nào → không liên quan
        return false;
    }

    /**
     * Kiểm tra xem câu hỏi có vắn tắt/chung chung không
     * Câu hỏi vắn tắt: chỉ 1-2 từ, không có động từ, không có từ nghi vấn
     */
    private function isVagueQuestion(string $message): bool
    {
        $messageLower = mb_strtolower(trim($message));
        $messageLength = mb_strlen($messageLower);
        
        // 1. Câu hỏi quá ngắn (< 10 ký tự)
        if ($messageLength < 10) {
            return true;
        }
        
        // 2. Chỉ có 1-2 từ
        $words = preg_split('/\s+/u', $messageLower);
        if (count($words) <= 2) {
            return true;
        }
        
        // 3. Không có từ nghi vấn và không có động từ
        $questionWords = ['gì', 'nào', 'đâu', 'sao', 'thế nào', 'như thế nào', 'bao giờ', 'khi nào', 
                          'ai', 'what', 'where', 'when', 'who', 'how', 'why', 'which'];
        $verbs = ['là', 'có', 'được', 'nằm', 'ở', 'mở', 'đóng', 'mượn', 'trả', 'đăng ký', 
                  'tìm', 'tra cứu', 'làm', 'thực hiện'];
        
        $hasQuestionWord = false;
        $hasVerb = false;
        
        foreach ($questionWords as $qw) {
            if (mb_strpos($messageLower, $qw) !== false) {
                $hasQuestionWord = true;
                break;
            }
        }
        
        foreach ($verbs as $verb) {
            if (mb_strpos($messageLower, $verb) !== false) {
                $hasVerb = true;
                break;
            }
        }
        
        // Nếu không có từ nghi vấn và không có động từ → câu hỏi vắn tắt
        if (!$hasQuestionWord && !$hasVerb) {
            return true;
        }
        
        return false;
    }
}
