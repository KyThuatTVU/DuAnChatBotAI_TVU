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

    $input        = $this->getJsonInput();
    $message      = trim($input['message'] ?? '');
    $sessionToken = $input['session_token'] ?? '';
    $lang         = $input['lang'] ?? 'vi';

    if (empty($message)) {
        $this->json([
            'error' => $lang === 'en'
                ? 'Message cannot be empty'
                : 'Tin nhắn không được để trống'
        ], 400);
    }

    if (mb_strlen($message) > 3000) {
        $this->json([
            'error' => $lang === 'en'
                ? 'Message must not exceed 3000 characters'
                : 'Tin nhắn không được vượt quá 3000 ký tự'
        ], 400);
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

    // ===== 1. Tầng lọc TOXIC =====
    if ($this->isToxic($message)) {
        $botReply = $lang === 'en'
            ? "I'd like to keep our conversation respectful. Please avoid using offensive language. You can ask me anything related to the CELRAS TVU Library, its services, books, or study resources."
            : "Mình mong muốn giữ cuộc trò chuyện lịch sự và tôn trọng nhé. Vui lòng tránh sử dụng những từ ngữ không phù hợp. Bạn có thể hỏi mình về thư viện CELRAS TVU, sách, dịch vụ hoặc tài nguyên học tập.";

        $this->chatModel->saveMessage($session['id'], 'bot', $botReply);

        $this->json([
            'success'           => true,
            'reply'             => $botReply,
            'forms'             => [],
            'related_questions' => [],
            'session_token'     => $session['session_token'],
            'matched'           => false,
        ]);
    }

    $settings = $this->settingModel->getPublicSettings();

    // ===== 2. Tầng FORM (biểu mẫu) =====
    $matchedForms = $this->formModel->findMatchingForms($message);
    if (!empty($matchedForms)) {
        $botReply = $lang === 'en'
            ? 'Here are the forms/documents related to your request. Please click the link to download or fill in the information:'
            : 'Dưới đây là biểu mẫu / giấy tờ liên quan đến yêu cầu của bạn. Vui lòng nhấn vào link để tải về hoặc điền thông tin:';

        $this->chatModel->saveMessage($session['id'], 'bot', $botReply);

        $this->json([
            'success'           => true,
            'reply'             => $botReply,
            'forms'             => $matchedForms,
            'related_questions' => [],
            'session_token'     => $session['session_token'],
            'matched'           => true,
        ]);
    }

    // ===== 3. Tầng Q&A trong DB – scoring 3 mức =====
    $relatedQuestions = $this->questionModel->findRelatedQuestions($message, 8);
    $top              = $relatedQuestions[0] ?? null;
    $topScore         = isset($top['similarity_score']) ? (float) $top['similarity_score'] : 0.0;

    // Ngưỡng điểm (điều chỉnh cho thuật toán mới)
    $HIGH_THRESHOLD = 0.70; // >= 70% → trả lời trực tiếp (TĂNG từ 60%)
    $EXACT_THRESHOLD = 0.85; // >= 85% → coi như exact match, trả lời luôn
    $LOW_THRESHOLD  = 0.50; // >= 50% → hiển thị list (TĂNG từ 20%)

    // Kiểm tra xem câu hỏi có vắn tắt/chung chung không
    $isVague = $this->isVagueQuestion($message);

    // 3.1. Nếu có EXACT MATCH (điểm rất cao) → Trả lời trực tiếp, bất kể vắn hay dài
    if ($top && $topScore >= $EXACT_THRESHOLD) {
        if ($lang === 'en' && !empty($top['answer_text_en'])) {
            $botReply = $top['answer_text_en'];
        } else {
            $botReply = $top['answer_text'];
        }

        $botReply = $this->stripEmbeddedQuestion($botReply, $message);

        $this->chatModel->saveMessage(
            $session['id'],
            'bot',
            $botReply,
            $top['id'],
            $topScore
        );

        $this->json([
            'success'           => true,
            'reply'             => $botReply,
            'forms'             => [],
            'related_questions' => [],
            'session_token'     => $session['session_token'],
            'matched'           => true,
        ]);
    }

    // 3.2. Nếu câu hỏi VẮNG TẮT và CÓ related questions → CHỈ hiển thị list
    if ($isVague && $topScore >= $LOW_THRESHOLD && !empty($relatedQuestions)) {
        $botReply = $lang === 'en'
            ? "Your question is quite general. Here are some related questions that might help:"
            : "Câu hỏi của bạn khá chung chung. Dưới đây là một số câu hỏi liên quan có thể giúp bạn:";

        $this->chatModel->saveMessage($session['id'], 'bot', $botReply);

        $this->json([
            'success'           => true,
            'reply'             => $botReply,
            'forms'             => [],
            'related_questions' => $relatedQuestions,
            'session_token'     => $session['session_token'],
            'matched'           => false,
        ]);
    }

    // 3.3. Nếu câu hỏi VẮNG TẮT nhưng KHÔNG có related questions → Yêu cầu hỏi cụ thể hơn
    if ($isVague && ($topScore < $LOW_THRESHOLD || empty($relatedQuestions))) {
        $botReply = $lang === 'en'
            ? "Your question is too general. Could you please be more specific? For example:\n• What information are you looking for?\n• Which service do you need help with?\n\nYou can also browse the categories on the left or contact us directly."
            : "Câu hỏi của bạn quá chung chung. Bạn có thể hỏi cụ thể hơn được không? Ví dụ:\n• Bạn đang tìm thông tin gì?\n• Bạn cần hỗ trợ về dịch vụ nào?\n\nBạn cũng có thể xem các danh mục bên trái hoặc liên hệ trực tiếp với chúng mình nhé.";

        $this->chatModel->saveMessage($session['id'], 'bot', $botReply);

        $this->json([
            'success'           => true,
            'reply'             => $botReply,
            'forms'             => [],
            'related_questions' => [],
            'session_token'     => $session['session_token'],
            'matched'           => false,
        ]);
    }

    // 3.4. Câu hỏi CỤ THỂ và đủ giống 1 câu trong DB → trả lời trực tiếp
    if (!$isVague && $top && $topScore >= $HIGH_THRESHOLD) {
        if ($lang === 'en' && !empty($top['answer_text_en'])) {
            $botReply = $top['answer_text_en'];
        } else {
            $botReply = $top['answer_text'];
        }

        $botReply = $this->stripEmbeddedQuestion($botReply, $message);

        $this->chatModel->saveMessage(
            $session['id'],
            'bot',
            $botReply,
            $top['id'],
            $topScore
        );

        $this->json([
            'success'           => true,
            'reply'             => $botReply,
            'forms'             => [],
            'related_questions' => [],
            'session_token'     => $session['session_token'],
            'matched'           => true,
        ]);
    }

    // 3.5. Có liên quan nhưng chưa đủ chắc chắn → chỉ gợi ý list câu hỏi
    if ($topScore >= $LOW_THRESHOLD && !empty($relatedQuestions)) {
        $botReply = $lang === 'en'
            ? "I couldn't find an exact answer. Here are some related questions you might be interested in:"
            : "Mình không tìm thấy câu trả lời chính xác. Dưới đây là một số câu hỏi liên quan bạn có thể quan tâm:";

        $this->chatModel->saveMessage($session['id'], 'bot', $botReply);

        $this->json([
            'success'           => true,
            'reply'             => $botReply,
            'forms'             => [],
            'related_questions' => $relatedQuestions,
            'session_token'     => $session['session_token'],
            'matched'           => false,
        ]);
    }

    // ===== 4. Không câu nào trong DB đủ điểm → thử Gemini, rồi fallback =====
    $geminiReply = $this->generateWithGemini($message, $lang);

    if ($geminiReply !== null) {
        $botReply = $geminiReply;
        $this->chatModel->saveMessage($session['id'], 'bot', $botReply);
    } else {
        if ($lang === 'en') {
            $botReply = "Sorry, I couldn't find an answer to your question. 😊\n\nYou can try:\n📌 Rephrasing your question\n📌 Browsing the categories on the left\n📌 Contacting us directly:\n📧 Email: trungtamhoclieu@tvu.edu.vn\n📞 Phone: 0294 3855 246 (ext. 142)\n\nWe're happy to help!";
        } else {
            $botReply = $settings['no_answer_message'];
        }
        $this->chatModel->saveMessage($session['id'], 'bot', $botReply);
        $this->chatModel->saveUnanswered($session['id'], $message);
    }

    $this->json([
        'success'           => true,
        'reply'             => $botReply,
        'forms'             => [],
        'related_questions' => [],
        'session_token'     => $session['session_token'],
        'matched'           => false,
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
     * Kiểm tra xem câu hỏi có nằm trong "miền kiến thức của hệ thống" hay không.
     *
     * Theo yêu cầu:
     * - Không đoán chủ đề trước, không hard-code từ khóa thư viện hay từ khóa "ngoài thư viện".
     * - Thay vào đó, dùng chính dữ liệu Q&A trong DB (thuật toán TF-IDF + Levenshtein +
     *   N-gram trong QuestionModel::findRelatedQuestions) để xem câu hỏi người dùng
     *   có đủ giống với bất kỳ câu hỏi nào trong hệ thống hay không.
     * - Nếu không có câu nào đủ giống → coi là "ngoài hệ thống" và chỉ trả lời message chung.
     */
    private function isLibraryRelated(string $message): bool
    {
        $messageLower = mb_strtolower(trim($message));

        // 1. Lọc nhanh các câu chứa từ tục tĩu/spam rõ ràng
        $badWords = [
            'đm', 'dm', 'vl', 'vcl', 'cc', 'lồn', 'cặc', 'đéo', 'đ.m',
            'fuck', 'shit', 'ngu', 'ngốc', 'đần', 'khùng', 'điên', 'loz'
        ];
        foreach ($badWords as $bad) {
            if ($bad !== '' && mb_strpos($messageLower, $bad) !== false) {
                return false;
            }
        }

        // 2. Dùng thuật toán tìm câu hỏi liên quan trên toàn bộ DB.
        $candidates = $this->questionModel->findRelatedQuestions($message, 3);
        if (empty($candidates)) {
            return false;
        }

        // 3. Nếu có ít nhất một câu hỏi trong DB có similarity_score đủ cao
        //    (dựa trên TF-IDF + Levenshtein + N-gram) thì coi là "nằm trong hệ thống".
        foreach ($candidates as $row) {
            if (isset($row['similarity_score']) && (float) $row['similarity_score'] >= 0.4) {
                return true;
            }
        }

        // Không câu nào trong DB đủ gần → coi là ngoài hệ thống
        return false;
    }

    /**
     * Kiểm tra xem câu hỏi có vắn tắt/chung chung không
     * Câu hỏi vắn tắt: chỉ 1-2 từ, không có động từ, không có từ nghi vấn
     */
    private function isVagueQuestion(string $message): bool
    {
        require_once __DIR__ . '/../helpers/ContextAnalyzer.php';
        
        $messageLower = mb_strtolower(trim($message));
        $messageLength = mb_strlen($messageLower);
        
        // 1. Câu hỏi quá ngắn (< 8 ký tự) hoặc chỉ 1-2 từ
        if ($messageLength < 8) {
            return true;
        }
        
        $words = preg_split('/\s+/u', $messageLower);
        $wordCount = count($words);
        
        if ($wordCount <= 2) {
            return true;
        }
        
        // 2. Phân tích ngữ cảnh bằng ContextAnalyzer
        $context = ContextAnalyzer::analyze($message);
        $importantWords = $context['important_words'];
        $importantWordCount = count($importantWords);
        
        // 3. Tính "độ cụ thể" của câu hỏi dựa trên ngữ nghĩa
        $specificityScore = 0;
        
        // 3.1. Số lượng từ quan trọng (không phải stop words)
        // Càng nhiều từ quan trọng → càng cụ thể
        if ($importantWordCount >= 4) {
            $specificityScore += 3; // Rất cụ thể
        } elseif ($importantWordCount >= 3) {
            $specificityScore += 2; // Khá cụ thể
        } elseif ($importantWordCount >= 2) {
            $specificityScore += 1; // Hơi cụ thể
        }
        // Nếu < 2 từ quan trọng → không cộng điểm
        
        // 3.2. Độ dài trung bình của từ quan trọng
        // Từ dài thường mang nhiều thông tin hơn (ví dụ: "đăng ký" vs "có")
        if (!empty($importantWords)) {
            $avgLength = 0;
            foreach ($importantWords as $word) {
                $avgLength += mb_strlen($word);
            }
            $avgLength = $avgLength / $importantWordCount;
            
            if ($avgLength >= 4) {
                $specificityScore += 2; // Từ dài → cụ thể
            } elseif ($avgLength >= 3) {
                $specificityScore += 1;
            }
        }
        
        // 3.3. Có chứa số (thời gian, số lượng, tầng...) → rất cụ thể
        if (preg_match('/\d+/', $messageLower)) {
            $specificityScore += 2;
        }
        
        // 3.4. Độ dài câu hỏi
        // Câu dài thường chứa nhiều thông tin hơn
        if ($messageLength >= 30) {
            $specificityScore += 2;
        } elseif ($messageLength >= 20) {
            $specificityScore += 1;
        }
        
        // 3.5. Tỷ lệ từ quan trọng / tổng số từ
        // Tỷ lệ cao → câu súc tích, có nhiều thông tin
        $importantRatio = $importantWordCount / $wordCount;
        if ($importantRatio >= 0.6) {
            $specificityScore += 2;
        } elseif ($importantRatio >= 0.4) {
            $specificityScore += 1;
        }
        
        // 3.6. Có n-gram dài (cụm từ 3-4 từ) → câu có cấu trúc rõ ràng
        $ngrams = $context['ngrams'];
        $hasLongNgram = false;
        foreach ($ngrams as $ngram) {
            $ngramWords = explode(' ', $ngram);
            if (count($ngramWords) >= 3) {
                $hasLongNgram = true;
                break;
            }
        }
        if ($hasLongNgram) {
            $specificityScore += 1;
        }
        
        // 4. Phán đoán dựa trên điểm số
        // Điểm >= 6: Câu CỤ THỂ (không chung chung)
        // Điểm < 6: Câu CHUNG CHUNG
        if ($specificityScore >= 6) {
            return false; // KHÔNG chung chung
        }
        
        // 5. Trường hợp đặc biệt: Câu quá ngắn và ít từ quan trọng
        if ($wordCount <= 3 && $importantWordCount <= 1) {
            return true; // Chung chung
        }
        
        // 6. Mặc định: Nếu có >= 3 từ quan trọng → coi là cụ thể
        if ($importantWordCount >= 3) {
            return false; // KHÔNG chung chung
        }
        
        // Còn lại: Chung chung
        return true;
    }

    /**
     * Kiểm tra tin nhắn có độc hại/toxic (chửi bới, xúc phạm) hay không.
     * Dùng blacklist + Regex ranh giới Unicode để tránh khớp nhầm từ con trong từ dài.
     */
    private function isToxic(string $message): bool
    {
        $text = mb_strtolower(trim($message));
        if ($text === '') return false;

        // Các cụm/toxic phổ biến (có thể mở rộng dần)
        $toxicPhrases = [
            'ông nội mày',
            'ông nội nhà mày',
            'đồ ngu',
            'đồ điên',
            'mày ngu',
            'mày điên',
            'thằng ngu',
            'con ngu',
            'đm',
            'đmm',
            'dm',
            'vcl',
            'vl',
            'cặc',
            'lồn',
            'đéo',
            'fuck',
            'shit',
        ];

        // Ranh giới theo chữ cái Unicode: không cho dính vào từ dài
        $escaped = array_map('preg_quote', $toxicPhrases);
        $pattern = '/(?<!\p{L})(' . implode('|', $escaped) . ')(?!\p{L})/iu';

        return preg_match($pattern, $text) === 1;
    }

    /**
     * Gọi Gemini (qua GEMINI_API_KEY) để tạo câu trả lời khi DB không có kết quả.
     * Trả về string nếu thành công, hoặc null nếu lỗi / hết key / không cấu hình.
     */
    private function generateWithGemini(string $message, string $lang = 'vi'): ?string
    {
        $apiKey = getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? '');
        if (!$apiKey) {
            return null;
        }

        // Endpoint Gemini generative language API (HTTP v1beta)
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . urlencode($apiKey);

        // Prompt hướng mô hình trả lời trong phạm vi thư viện
        $systemInstruction = $lang === 'en'
            ? "You are a virtual assistant for the CELRAS TVU Library. Only answer questions related to the library, its services, opening hours, borrowing/returning books, facilities, and study resources at Tra Vinh University. If the question is clearly unrelated to the library, politely say that you can only answer library-related questions."
            : "Bạn là trợ lý ảo cho Trung tâm Học liệu CELRAS TVU. Chỉ trả lời các câu hỏi liên quan đến thư viện, dịch vụ thư viện, giờ mở cửa, mượn/trả sách, cơ sở vật chất và tài nguyên học tập tại Trường Đại học Trà Vinh. Nếu câu hỏi rõ ràng không liên quan đến thư viện, hãy lịch sự thông báo rằng bạn chỉ có thể trả lời các câu hỏi liên quan đến thư viện.";

        $body = [
            'system_instruction' => [
                'parts' => [
                    ['text' => $systemInstruction],
                ],
            ],
            'contents' => [
                [
                    'role'  => 'user',
                    'parts' => [
                        ['text' => $message],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.3,
                'maxOutputTokens' => 512,
            ],
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            curl_close($ch);
            return null;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            // Có thể là hết quota / key invalid → fallback
            return null;
        }

        $data = json_decode($response, true);
        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return null;
        }

        return trim($data['candidates'][0]['content']['parts'][0]['text']);
    }
}
