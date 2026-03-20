<?php
require_once __DIR__ . '/../core/BaseController.php';

class AdminController extends BaseController
{
    private $questionModel;
    private $categoryModel;
    private $chatModel;
    private $settingModel;
    private $formModel;
    private $adminModel;

    public function __construct()
    {
        $this->questionModel = $this->model('QuestionModel');
        $this->categoryModel = $this->model('CategoryModel');
        $this->chatModel     = $this->model('ChatModel');
        $this->settingModel  = $this->model('SettingModel');
        $this->formModel     = $this->model('FormModel');
        $this->adminModel    = $this->model('AdminModel');
    }

    /**
     * Chỉ admin/super_admin mới được quản lý tài khoản
     */
    private function requireAccountManager()
    {
        $this->requireAuth();
        $role = $_SESSION['admin_role'] ?? '';
        if (!in_array($role, ['admin', 'super_admin'], true)) {
            $this->json(['error' => 'Forbidden'], 403);
        }
        return $_SESSION['admin_id'];
    }

    /**
     * GET /api/admin/dashboard - Thống kê dashboard
     */
    public function dashboard()
    {
        $this->requireAuth();

        $stats = $this->chatModel->getStats();
        $stats['total_questions'] = $this->questionModel->count('is_active = 1');
        $stats['total_categories'] = $this->categoryModel->count('is_active = 1');

        $this->json(['stats' => $stats]);
    }

    // ==================== QUESTIONS ====================

    /**
     * Chuẩn hóa text để so sánh trùng lặp:
     * - Bỏ dấu tiếng Việt
     * - Bỏ ký tự đặc biệt, chỉ giữ chữ + số
     * - Chuyển lowercase
     * - Gộp khoảng trắng
     */
    private function normalizeForCompare(string $text): string
    {
        // Bảng chuyển đổi dấu tiếng Việt
        $vietnamese = [
            'à','á','ạ','ả','ã','â','ầ','ấ','ậ','ẩ','ẫ','ă','ằ','ắ','ặ','ẳ','ẵ',
            'è','é','ẹ','ẻ','ẽ','ê','ề','ế','ệ','ể','ễ',
            'ì','í','ị','ỉ','ĩ',
            'ò','ó','ọ','ỏ','õ','ô','ồ','ố','ộ','ổ','ỗ','ơ','ờ','ớ','ợ','ở','ỡ',
            'ù','ú','ụ','ủ','ũ','ư','ừ','ứ','ự','ử','ữ',
            'ỳ','ý','ỵ','ỷ','ỹ',
            'đ',
            'À','Á','Ạ','Ả','Ã','Â','Ầ','Ấ','Ậ','Ẩ','Ẫ','Ă','Ằ','Ắ','Ặ','Ẳ','Ẵ',
            'È','É','Ẹ','Ẻ','Ẽ','Ê','Ề','Ế','Ệ','Ể','Ễ',
            'Ì','Í','Ị','Ỉ','Ĩ',
            'Ò','Ó','Ọ','Ỏ','Õ','Ô','Ồ','Ố','Ộ','Ổ','Ỗ','Ơ','Ờ','Ớ','Ợ','Ở','Ỡ',
            'Ù','Ú','Ụ','Ủ','Ũ','Ư','Ừ','Ứ','Ự','Ử','Ữ',
            'Ỳ','Ý','Ỵ','Ỷ','Ỹ',
            'Đ',
        ];
        $ascii = [
            'a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
            'e','e','e','e','e','e','e','e','e','e','e',
            'i','i','i','i','i',
            'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
            'u','u','u','u','u','u','u','u','u','u','u',
            'y','y','y','y','y',
            'd',
            'a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
            'e','e','e','e','e','e','e','e','e','e','e',
            'i','i','i','i','i',
            'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
            'u','u','u','u','u','u','u','u','u','u','u',
            'y','y','y','y','y',
            'd',
        ];

        $text = str_replace($vietnamese, $ascii, $text);
        $text = mb_strtolower($text);
        // Chỉ giữ chữ cái, số và khoảng trắng
        $text = preg_replace('/[^a-z0-9\s]/u', '', $text);
        // Gộp khoảng trắng
        $text = preg_replace('/\s+/', ' ', trim($text));
        return $text;
    }

    /**
     * Kiểm tra trùng lặp câu hỏi + câu trả lời với DB hiện có
     * Trả về mảng câu hỏi trùng nếu có
     */
    private function findDuplicates(array $qaPairs): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, question_text, answer_text FROM questions WHERE is_active = 1");
        $stmt->execute();
        $existingQuestions = $stmt->fetchAll();

        // Chuẩn hóa tất cả câu hỏi+trả lời hiện có
        $existingNormalized = [];
        foreach ($existingQuestions as $eq) {
            $existingNormalized[] = [
                'id' => $eq['id'],
                'question_text' => $eq['question_text'],
                'answer_text' => $eq['answer_text'],
                'norm_q' => $this->normalizeForCompare($eq['question_text']),
                'norm_a' => $this->normalizeForCompare($eq['answer_text']),
            ];
        }

        $duplicates = [];
        $newItems = [];

        foreach ($qaPairs as $index => $qa) {
            $normQ = $this->normalizeForCompare($qa['question']);
            $normA = $this->normalizeForCompare($qa['answer']);
            $isDuplicate = false;

            foreach ($existingNormalized as $eq) {
                // So sánh cả câu hỏi VÀ câu trả lời
                $qSimilar = ($normQ === $eq['norm_q']);
                $aSimilar = ($normA === $eq['norm_a']);

                // Hoặc dùng similar_text nếu gần giống (>= 85%)
                if (!$qSimilar && !empty($normQ) && !empty($eq['norm_q'])) {
                    similar_text($normQ, $eq['norm_q'], $qPercent);
                    $qSimilar = ($qPercent >= 85);
                }
                if (!$aSimilar && !empty($normA) && !empty($eq['norm_a'])) {
                    similar_text($normA, $eq['norm_a'], $aPercent);
                    $aSimilar = ($aPercent >= 85);
                }

                // Trùng nếu câu hỏi HOẶC câu trả lời giống nhau
                if ($qSimilar || $aSimilar) {
                    $isDuplicate = true;
                    $duplicates[] = [
                        'index' => $index + 1,
                        'new_question' => $qa['question'],
                        'new_answer' => mb_substr($qa['answer'], 0, 100) . (mb_strlen($qa['answer']) > 100 ? '...' : ''),
                        'existing_id' => $eq['id'],
                        'existing_question' => $eq['question_text'],
                        'match_type' => $qSimilar ? 'question' : 'answer',
                    ];
                    break;
                }
            }

            if (!$isDuplicate) {
                $newItems[] = $qa;
            }
        }

        return [
            'duplicates' => $duplicates,
            'new_items' => $newItems,
        ];
    }

    /**
     * Kiểm tra 1 câu hỏi trùng (dùng khi thêm thủ công)
     */
    private function checkSingleDuplicate(string $questionText, string $answerText, ?int $excludeId = null): ?array
    {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT id, question_text, answer_text FROM questions WHERE is_active = 1";
        if ($excludeId) {
            $sql .= " AND id != " . intval($excludeId);
        }
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $existingQuestions = $stmt->fetchAll();

        $normQ = $this->normalizeForCompare($questionText);
        $normA = $this->normalizeForCompare($answerText);

        foreach ($existingQuestions as $eq) {
            $eqNormQ = $this->normalizeForCompare($eq['question_text']);
            $eqNormA = $this->normalizeForCompare($eq['answer_text']);

            $qSimilar = ($normQ === $eqNormQ);
            $aSimilar = ($normA === $eqNormA);

            if (!$qSimilar && !empty($normQ) && !empty($eqNormQ)) {
                similar_text($normQ, $eqNormQ, $qPercent);
                $qSimilar = ($qPercent >= 85);
            }
            if (!$aSimilar && !empty($normA) && !empty($eqNormA)) {
                similar_text($normA, $eqNormA, $aPercent);
                $aSimilar = ($aPercent >= 85);
            }

            if ($qSimilar || $aSimilar) {
                return [
                    'existing_id' => $eq['id'],
                    'existing_question' => $eq['question_text'],
                    'match_type' => $qSimilar ? 'question' : 'answer',
                ];
            }
        }

        return null;
    }

    /**
     * GET /api/admin/questions - Lấy danh sách câu hỏi
     */
    public function questions()
    {
        $this->requireAuth();

        if ($this->getMethod() === 'POST') {
            return $this->createQuestion();
        }

        try {
            $questions = $this->questionModel->getAllWithCategory();
            $this->json(['questions' => $questions]);
        } catch (\Exception $e) {
            $this->json(['error' => 'Lỗi khi tải danh sách câu hỏi: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/admin/questions - Tạo câu hỏi mới
     */
    private function createQuestion()
    {
        $adminId = $this->requireAuth();
        $input = $this->getJsonInput();
        $forceAdd = $input['force_add'] ?? false;

        // Kiểm tra trùng lặp (trừ khi user xác nhận thêm)
        if (!$forceAdd) {
            $duplicate = $this->checkSingleDuplicate(
                $input['question_text'],
                $input['answer_text']
            );
            if ($duplicate) {
                $matchLabel = $duplicate['match_type'] === 'question' ? 'câu hỏi' : 'câu trả lời';
                $this->json([
                    'success' => false,
                    'duplicate' => true,
                    'error' => "Phát hiện trùng lặp {$matchLabel} với câu hỏi đã có (ID #{$duplicate['existing_id']}): \"{$duplicate['existing_question']}\"",
                    'existing_id' => $duplicate['existing_id'],
                    'existing_question' => $duplicate['existing_question'],
                    'match_type' => $duplicate['match_type'],
                ], 409);
            }
        }

        $id = $this->questionModel->create([
            'category_id' => $input['category_id'] ?? null,
            'question_text' => sanitize($input['question_text']),
            'answer_text' => $input['answer_text'],
            'answer_text_en' => $input['answer_text_en'] ?? null,
            'source_type' => 'manual',
            'created_by' => $adminId,
        ]);

        $db = Database::getInstance()->getConnection();

        // Thêm từ khóa thủ công nếu có
        if (!empty($input['keywords'])) {
            $stmt = $db->prepare("INSERT INTO keywords (question_id, keyword, is_auto, language) VALUES (?, ?, 0, 'vi')");
            foreach ($input['keywords'] as $keyword) {
                $kw = trim($keyword);
                if ($kw !== '') {
                    $stmt->execute([$id, $kw]);
                }
            }
        }

        // Tự động tạo từ khóa từ câu hỏi
        $autoKeywords = $this->generateAutoKeywords($input['question_text']);
        $this->saveAutoKeywords($db, $id, $autoKeywords);

        $this->json([
            'success' => true, 
            'id' => $id,
            'auto_keywords' => $autoKeywords,
        ], 201);
    }

    /**
     * PUT /api/admin/question/{id} - Cập nhật câu hỏi
     */
    public function question($id = null)
    {
        $adminId = $this->requireAuth();

        if (!$id) {
            $this->json(['error' => 'ID không hợp lệ'], 400);
            return;
        }

        if ($this->getMethod() === 'DELETE') {
            try {
                $this->questionModel->delete($id);
                $this->json(['success' => true]);
            } catch (\Exception $e) {
                $this->json(['error' => 'Lỗi khi xóa câu hỏi: ' . $e->getMessage()], 500);
            }
            return;
        }

        if ($this->getMethod() === 'PUT') {
            try {
                $input = $this->getJsonInput();
                $this->questionModel->update($id, [
                    'category_id' => $input['category_id'] ?? null,
                    'question_text' => sanitize($input['question_text']),
                    'answer_text' => $input['answer_text'],
                    'answer_text_en' => $input['answer_text_en'] ?? null,
                    'is_active' => $input['is_active'] ?? 1,
                ]);

                $db = Database::getInstance()->getConnection();
                
                // Xóa tất cả từ khóa cũ (cả thủ công và tự động)
                $db->prepare("DELETE FROM keywords WHERE question_id = ?")->execute([$id]);
                
                // Thêm từ khóa thủ công mới
                if (!empty($input['keywords'])) {
                    $stmt = $db->prepare("INSERT INTO keywords (question_id, keyword, is_auto, language) VALUES (?, ?, 0, 'vi')");
                    foreach ($input['keywords'] as $keyword) {
                        $kw = trim($keyword);
                        if ($kw !== '') {
                            $stmt->execute([$id, $kw]);
                        }
                    }
                }

                // Tự động tạo lại từ khóa từ câu hỏi mới
                $autoKeywords = $this->generateAutoKeywords($input['question_text']);
                $this->saveAutoKeywords($db, $id, $autoKeywords);

                $this->json([
                    'success' => true,
                    'auto_keywords' => $autoKeywords,
                ]);
            } catch (\Exception $e) {
                $this->json(['error' => 'Lỗi khi cập nhật câu hỏi: ' . $e->getMessage()], 500);
            }
            return;
        }

        // GET - lấy chi tiết
        try {
            $question = $this->questionModel->getById($id);
            if (!$question) {
                $this->json(['error' => 'Không tìm thấy câu hỏi với ID: ' . $id], 404);
                return;
            }
            $this->json(['question' => $question]);
        } catch (\Exception $e) {
            $this->json(['error' => 'Lỗi khi tải câu hỏi: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/admin/questions/delete-multiple - Xóa nhiều câu hỏi
     */
    public function deleteMultipleQuestions()
    {
        $this->requireAuth();

        if ($this->getMethod() !== 'POST') {
            $this->json(['error' => 'Method not allowed'], 405);
        }

        $input = $this->getJsonInput();
        $ids = $input['ids'] ?? [];

        if (empty($ids) || !is_array($ids)) {
            $this->json(['error' => 'Danh sách ID không hợp lệ'], 400);
        }

        // Validate IDs
        $ids = array_filter($ids, function($id) {
            return is_numeric($id) && $id > 0;
        });

        if (empty($ids)) {
            $this->json(['error' => 'Không có ID hợp lệ'], 400);
        }

        $db = Database::getInstance()->getConnection();
        
        try {
            // Xóa từ khóa liên quan trước
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("DELETE FROM keywords WHERE question_id IN ($placeholders)");
            $stmt->execute($ids);

            // Xóa câu hỏi
            $result = $this->questionModel->deleteMultiple($ids);
            
            if ($result) {
                $this->json([
                    'success' => true,
                    'message' => 'Đã xóa ' . count($ids) . ' câu hỏi',
                    'deleted_count' => count($ids)
                ]);
            } else {
                $this->json(['error' => 'Không thể xóa câu hỏi'], 500);
            }
        } catch (\Exception $e) {
            $this->json(['error' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    // ==================== CATEGORIES ====================

    /**
     * /api/admin/categories
     */
    public function categories()
    {
        $this->requireAuth();

        if ($this->getMethod() === 'POST') {
            $input = $this->getJsonInput();
            $id = $this->categoryModel->create([
                'name' => sanitize($input['name']),
                'description' => $input['description'] ?? '',
                'sort_order' => $input['sort_order'] ?? 0,
                'created_by' => $_SESSION['admin_id'],
            ]);
            $this->json(['success' => true, 'id' => $id], 201);
        }

        $categories = $this->categoryModel->getAllWithCount();
        $this->json(['categories' => $categories]);
    }

    /**
     * /api/admin/category/{id}
     */
    public function category($id = null)
    {
        $this->requireAuth();

        if (!$id) {
            $this->json(['error' => 'ID không hợp lệ'], 400);
        }

        if ($this->getMethod() === 'DELETE') {
            $this->categoryModel->delete($id);
            $this->json(['success' => true]);
        }

        if ($this->getMethod() === 'PUT') {
            $input = $this->getJsonInput();
            
            // Nếu chỉ cập nhật is_active (toggle status)
            if (isset($input['is_active']) && count($input) === 1) {
                $this->categoryModel->update($id, [
                    'is_active' => $input['is_active']
                ]);
                $this->json(['success' => true]);
                return;
            }
            
            // Cập nhật đầy đủ thông tin
            $this->categoryModel->update($id, [
                'name' => sanitize($input['name']),
                'description' => $input['description'] ?? '',
                'sort_order' => $input['sort_order'] ?? 0,
                'is_active' => $input['is_active'] ?? 1,
            ]);
            $this->json(['success' => true]);
            return;
        }

        $category = $this->categoryModel->getById($id);
        $this->json(['category' => $category]);
    }

    /**
     * POST /api/admin/deleteMultipleCategories - Xóa nhiều danh mục
     */
    public function deleteMultipleCategories()
    {
        $this->requireAuth();

        if ($this->getMethod() !== 'POST') {
            $this->json(['error' => 'Method not allowed'], 405);
        }

        $input = $this->getJsonInput();
        $ids = $input['ids'] ?? [];

        if (empty($ids) || !is_array($ids)) {
            $this->json(['error' => 'Danh sách ID không hợp lệ'], 400);
        }

        $ids = array_filter($ids, function($id) {
            return is_numeric($id) && $id > 0;
        });

        if (empty($ids)) {
            $this->json(['error' => 'Không có ID hợp lệ'], 400);
        }

        $db = Database::getInstance()->getConnection();
        
        try {
            // Gỡ liên kết với câu hỏi (set category_id = NULL)
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("UPDATE questions SET category_id = NULL WHERE category_id IN ($placeholders)");
            $stmt->execute($ids);

            // Xóa danh mục
            $result = $this->categoryModel->deleteMultiple($ids);
            
            if ($result) {
                $this->json([
                    'success' => true,
                    'message' => 'Đã xóa ' . count($ids) . ' danh mục',
                    'deleted_count' => count($ids)
                ]);
            } else {
                $this->json(['error' => 'Không thể xóa danh mục'], 500);
            }
        } catch (\Exception $e) {
            $this->json(['error' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    // ==================== SETTINGS ====================

    /**
     * /api/admin/settings
     */
    public function settings()
    {
        $adminId = $this->requireAuth();

        if ($this->getMethod() === 'PUT' || $this->getMethod() === 'POST') {
            $input = $this->getJsonInput();
            foreach ($input as $key => $value) {
                $this->settingModel->set($key, $value, $adminId);
            }
            $this->json(['success' => true]);
        }

        $settings = $this->settingModel->getAllSettings();
        $this->json(['settings' => $settings]);
    }

    /**
     * /api/admin/themes
     * /api/admin/themes/{id}/activate (PUT)
     * /api/admin/themes/{id} (DELETE)
     */
    public function themes($id = null, $action = null)
    {
        $adminId = $this->requireAuth();
        $db = Database::getInstance()->getConnection();

        // PUT /api/admin/themes/{id}/activate - Bật chủ đề
        if ($this->getMethod() === 'PUT' && $id && $action === 'activate') {
            // Tắt tất cả theme
            $db->exec("UPDATE event_themes SET is_active = 0");
            // Bật theme được chọn
            $stmt = $db->prepare("UPDATE event_themes SET is_active = 1 WHERE id = ?");
            $stmt->execute([$id]);
            $this->json(['success' => true, 'message' => 'Đã bật chủ đề']);
            return;
        }

        // DELETE /api/admin/themes/{id} - Xóa chủ đề
        if ($this->getMethod() === 'DELETE' && $id) {
            // Không cho xóa theme mặc định
            $stmt = $db->prepare("SELECT theme_key FROM event_themes WHERE id = ?");
            $stmt->execute([$id]);
            $theme = $stmt->fetch();
            if ($theme && $theme['theme_key'] === 'mac-dinh') {
                $this->json(['success' => false, 'message' => 'Không thể xóa chủ đề mặc định'], 400);
                return;
            }
            $stmt = $db->prepare("DELETE FROM event_themes WHERE id = ?");
            $stmt->execute([$id]);
            $this->json(['success' => true, 'message' => 'Đã xóa chủ đề']);
            return;
        }

        // POST - Thêm theme mới
        if ($this->getMethod() === 'POST') {
            $input = $this->getJsonInput();
            
            // Nếu kích hoạt theme mới, tắt các theme cũ
            if (!empty($input['is_active'])) {
                $db->exec("UPDATE event_themes SET is_active = 0");
            }

            $stmt = $db->prepare(
                "INSERT INTO event_themes (theme_name, theme_key, primary_color, secondary_color, header_bg_color, header_text_color, 
                user_bubble_color, bot_bubble_color, button_color, welcome_message, start_date, end_date, is_active, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $themeKey = $input['theme_key'] ?? $this->generateThemeKey($input['theme_name']);
            $stmt->execute([
                $input['theme_name'], $themeKey, $input['primary_color'] ?? '#1976D2',
                $input['secondary_color'] ?? '#FFFFFF', $input['header_bg_color'] ?? '#1976D2',
                $input['header_text_color'] ?? '#FFFFFF', $input['user_bubble_color'] ?? '#E3F2FD',
                $input['bot_bubble_color'] ?? '#F5F5F5', $input['button_color'] ?? '#1976D2',
                $input['welcome_message'] ?? '', $input['start_date'] ?? null,
                $input['end_date'] ?? null, $input['is_active'] ?? 0, $adminId,
            ]);
            $this->json(['success' => true, 'id' => $db->lastInsertId()], 201);
            return;
        }

        // GET - Lấy danh sách themes
        $themes = $this->settingModel->getAllThemes();
        $this->json(['themes' => $themes]);
    }

    /**
     * Tạo theme_key từ tên theme
     */
    private function generateThemeKey($name)
    {
        $key = mb_strtolower($name);
        $key = preg_replace('/[^a-z0-9\s-]/u', '', $key);
        $key = preg_replace('/\s+/', '-', trim($key));
        return $key ?: 'custom-' . time();
    }

    // ==================== UPLOAD DATASET ====================

    /**
     * POST /api/admin/upload - Upload file Word và tự động tạo Q&A
     */
    public function upload()
    {
        $adminId = $this->requireAuth();

        if ($this->getMethod() !== 'POST') {
            $this->json(['error' => 'Method not allowed'], 405);
        }

        if (!isset($_FILES['file'])) {
            $this->json(['error' => 'Không tìm thấy file'], 400);
        }

        $file = $_FILES['file'];

        // Kiểm tra extension (đáng tin hơn MIME type do trình duyệt gửi)
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['doc', 'docx'];

        if (!in_array($ext, $allowedExtensions)) {
            $this->json(['error' => 'Chỉ chấp nhận file Word (.doc, .docx)'], 400);
        }

        // Kiểm tra MIME type bổ sung (cho phép cả application/octet-stream vì một số trình duyệt gửi sai)
        $allowedMimes = [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/octet-stream',
            'application/zip',
        ];
        if (!empty($file['type']) && !in_array($file['type'], $allowedMimes)) {
            $this->json(['error' => 'File không đúng định dạng Word (.doc, .docx). MIME: ' . $file['type']], 400);
        }

        if ($file['size'] > MAX_FILE_SIZE) {
            $this->json(['error' => 'File vượt quá 10MB'], 400);
        }

        // Tạo thư mục upload
        $uploadDir = UPLOAD_DIR . date('Y/m/');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = time() . '_' . basename($file['name']);
        $filePath = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            $this->json(['error' => 'Lỗi khi lưu file'], 500);
        }

        $fileType = 'word';

        // Kiểm tra ZipArchive cho .docx (trước khi insert vào DB)
        if ($ext === 'docx' && !class_exists('ZipArchive')) {
            $this->json(['error' => 'Server chưa bật extension ZIP. Vui lòng bật extension=zip trong php.ini và restart Apache.'], 500);
        }

        // Lưu dataset vào database (status = processing)
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "INSERT INTO datasets (file_name, file_path, file_type, file_size, status, uploaded_by) VALUES (?, ?, ?, ?, 'processing', ?)"
        );
        $stmt->execute([$file['name'], $filePath, $fileType, $file['size'], $adminId]);
        $datasetId = $db->lastInsertId();

        try {
            // Trích xuất text từ Word
            $text = $this->extractTextFromWord($filePath);

            if (empty(trim($text))) {
                $this->updateDatasetStatus($db, $datasetId, 'failed', 'Không thể đọc nội dung file.');
                $errorHint = ($ext === 'doc')
                    ? 'Không thể đọc nội dung file .doc. Hãy thử lưu lại thành .docx rồi upload lại.'
                    : 'Không thể đọc nội dung file. Vui lòng kiểm tra file Word có chứa text.';
                $this->json([
                    'success' => false,
                    'error' => $errorHint,
                ], 400);
            }

            // Tự động tạo Q&A từ nội dung văn bản
            $qaPairs = $this->autoGenerateQA($text);

            if (empty($qaPairs)) {
                $this->updateDatasetStatus($db, $datasetId, 'failed', 'Không tạo được câu hỏi từ nội dung file.');
                $this->json([
                    'success' => false,
                    'error' => 'Không tạo được câu hỏi nào từ nội dung file. File có thể quá ngắn hoặc không có đủ nội dung.',
                ], 400);
            }

            // Lưu Q&A vào database (tự kiểm tra trùng lặp)
            $importResult = $this->importQAPairs($db, $qaPairs, $datasetId, $adminId, $fileType);
            $importCount = $importResult['imported'];
            $duplicateCount = $importResult['skipped'];
            $duplicateList = $importResult['duplicates'];

            // Cập nhật dataset status
            $this->updateDatasetStatus($db, $datasetId, 'completed', null, $importCount);

            // Tạo message kết quả
            $message = "Đã tự động tạo {$importCount} câu hỏi từ file.";
            if ($duplicateCount > 0) {
                $message .= " Bỏ qua {$duplicateCount} câu hỏi trùng lặp.";
            }
            $message .= " Bạn có thể chỉnh sửa tại trang Quản lý câu hỏi.";

            $this->json([
                'success' => true,
                'id' => $datasetId,
                'total_questions' => $importCount,
                'questions' => $qaPairs,
                'duplicates' => $duplicateList,
                'duplicate_count' => $duplicateCount,
                'message' => $message,
            ], 201);

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            $this->updateDatasetStatus($db, $datasetId, 'failed', $errorMsg);
            $this->json([
                'success' => false,
                'error' => 'Lỗi khi xử lý file: ' . $errorMsg,
            ], 500);
        }
    }

    // ==================== TEXT EXTRACTION ====================

    /**
     * Trích xuất text từ file Word (.docx / .doc)
     */
    private function extractTextFromWord(string $filePath): string
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($ext === 'docx') {
            return $this->extractTextFromDocx($filePath);
        }

        // Fallback: .doc (binary OLE2) — trích xuất text
        return $this->extractTextFromDoc($filePath);
    }

    /**
     * Đọc nội dung .doc (OLE2 binary format)
     * Sử dụng nhiều phương pháp để tối ưu kết quả
     */
    private function extractTextFromDoc(string $filePath): string
    {
        $content = file_get_contents($filePath);
        if ($content === false || strlen($content) < 512) return '';

        // Phương pháp 1: Trích xuất text từ OLE2 compound document
        $text = $this->extractOLE2Text($content);

        // Phương pháp 2: Nếu OLE2 ko đủ, thử UTF-16LE decode
        if (mb_strlen(trim($text)) < 30 && function_exists('mb_convert_encoding')) {
            $utf16 = @mb_convert_encoding($content, 'UTF-8', 'UTF-16LE');
            if ($utf16) {
                $text2 = '';
                // Tìm các chuỗi text có ý nghĩa (>= 4 ký tự liên tiếp)
                if (preg_match_all('/[\x20-\x7E\x{00C0}-\x{1EF9}]{4,}/u', $utf16, $matches)) {
                    $text2 = implode("\n", $matches[0]);
                }
                if (mb_strlen($text2) > mb_strlen($text)) {
                    $text = $text2;
                }
            }
        }

        // Phương pháp 3: Fallback - tìm chuỗi ASCII/UTF-8 trong binary
        if (mb_strlen(trim($text)) < 30) {
            $text3 = '';
            if (preg_match_all('/[\x20-\x7E\x{00C0}-\x{1EF9}]{6,}/u', $content, $matches)) {
                // Lọc bỏ các chuỗi binary/metadata
                $filtered = array_filter($matches[0], function($s) {
                    // Bỏ các chuỗi chỉ có ký tự đặc biệt hoặc quá nhiều ký tự lạ
                    $alphaRatio = preg_match_all('/[\p{L}\d]/u', $s) / max(mb_strlen($s), 1);
                    return $alphaRatio > 0.5 && mb_strlen($s) >= 6;
                });
                $text3 = implode("\n", $filtered);
            }
            if (mb_strlen($text3) > mb_strlen($text)) {
                $text = $text3;
            }
        }

        return $text;
    }

    /**
     * Trích xuất text từ OLE2 compound binary format
     * .doc file lưu text trong streams có thể đọc được
     */
    private function extractOLE2Text(string $data): string
    {
        // Xác nhận OLE2 magic bytes: D0 CF 11 E0
        if (substr($data, 0, 4) !== "\xD0\xCF\x11\xE0") {
            return '';
        }

        $text = '';

        // Tìm text content: .doc lưu text dạng ANSI hoặc Unicode
        // Đọc từ byte offset 0x200 trở đi (sau header) để tìm text stream
        $len = strlen($data);
        $pos = 0x200; // Skip OLE2 header
        $chunk = '';

        while ($pos < $len) {
            $byte = ord($data[$pos]);

            // Readable ASCII/Latin characters
            if (($byte >= 0x20 && $byte <= 0x7E) || $byte === 0x0A || $byte === 0x0D || $byte === 0x09) {
                $chunk .= chr($byte);
            }
            // Vietnamese UTF-8 chars (2-3 byte sequences)
            elseif ($byte >= 0xC0 && $byte <= 0xEF && $pos + 1 < $len) {
                $seqLen = ($byte >= 0xE0) ? 3 : 2;
                if ($pos + $seqLen <= $len) {
                    $seq = substr($data, $pos, $seqLen);
                    $decoded = @mb_convert_encoding($seq, 'UTF-8', 'UTF-8');
                    if ($decoded && mb_strlen($decoded) === 1) {
                        $chunk .= $decoded;
                        $pos += $seqLen - 1;
                    } else {
                        if (mb_strlen(trim($chunk)) >= 5) {
                            $text .= trim($chunk) . "\n";
                        }
                        $chunk = '';
                    }
                }
            } else {
                // Non-text byte: flush current chunk if meaningful
                if (mb_strlen(trim($chunk)) >= 5) {
                    $text .= trim($chunk) . "\n";
                }
                $chunk = '';
            }
            $pos++;
        }

        // Flush remaining
        if (mb_strlen(trim($chunk)) >= 5) {
            $text .= trim($chunk) . "\n";
        }

        // Làm sạch: bỏ dòng trùng lặp và dòng metadata
        $lines = explode("\n", $text);
        $cleanLines = [];
        $seen = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            // Bỏ các dòng metadata thường gặp trong .doc
            if (preg_match('/^(MSWord|Normal|Microsoft|Default|Heading|Title|Calibri|Times|Arial|\{)/i', $line)) continue;
            if (preg_match('/^[\x00-\x1F\x7F-\x9F]+$/', $line)) continue;
            if (mb_strlen($line) < 3) continue;
            $hash = md5($line);
            if (isset($seen[$hash])) continue;
            $seen[$hash] = true;
            $cleanLines[] = $line;
        }

        return implode("\n", $cleanLines);
    }

    /**
     * Đọc nội dung .docx (XML-based) - giữ cấu trúc đoạn
     */
    private function extractTextFromDocx(string $filePath): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new \Exception('Không thể mở file .docx');
        }

        $content = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($content === false) {
            throw new \Exception('File .docx không hợp lệ');
        }

        // Parse XML để giữ cấu trúc paragraph
        $xml = simplexml_load_string($content);
        if ($xml === false) {
            // Fallback: strip tags
            $text = strip_tags($content);
            return html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        }

        $namespaces = $xml->getNamespaces(true);
        $wns = $namespaces['w'] ?? 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

        $text = '';
        $body = $xml->children($wns)->body;
        if (!$body) {
            $text = strip_tags($content);
            return html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        }

        foreach ($body->children($wns) as $element) {
            if ($element->getName() === 'p') {
                $paraText = $this->extractDocxParagraph($element, $wns);
                if (!empty($paraText)) {
                    $text .= $paraText . "\n";
                }
            } elseif ($element->getName() === 'tbl') {
                // Xử lý bảng
                foreach ($element->children($wns) as $row) {
                    if ($row->getName() === 'tr') {
                        $cells = [];
                        foreach ($row->children($wns) as $cell) {
                            if ($cell->getName() === 'tc') {
                                $cellText = '';
                                foreach ($cell->children($wns) as $p) {
                                    if ($p->getName() === 'p') {
                                        $cellText .= $this->extractDocxParagraph($p, $wns) . ' ';
                                    }
                                }
                                $cells[] = trim($cellText);
                            }
                        }
                        if (!empty(array_filter($cells))) {
                            $text .= implode(' | ', $cells) . "\n";
                        }
                    }
                }
            }
        }

        return html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Trích xuất text từ 1 paragraph element trong docx XML
     */
    private function extractDocxParagraph($pElement, string $wns): string
    {
        $runs = '';
        foreach ($pElement->children($wns) as $child) {
            if ($child->getName() === 'r') {
                $t = $child->children($wns)->t;
                if ($t !== null) {
                    $runs .= (string) $t;
                }
            } elseif ($child->getName() === 'hyperlink') {
                foreach ($child->children($wns) as $hRun) {
                    if ($hRun->getName() === 'r') {
                        $t = $hRun->children($wns)->t;
                        if ($t !== null) {
                            $runs .= (string) $t;
                        }
                    }
                }
            }
        }
        return trim($runs);
    }

    // ==================== AUTO GENERATE Q&A ====================

    /**
     * Tự động tạo Q&A từ văn bản tiếng Việt
     * Phân tích cấu trúc: Chương, Điều, Mục, heading, paragraph
     */
    private function autoGenerateQA(string $text): array
    {
        $pairs = [];

        // Normalize
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        // === Bước 1: Thử parse Q&A có sẵn trong file (Q:/A: format) ===
        $existingQA = $this->parseExistingQA($text);
        if (!empty($existingQA)) {
            return $existingQA;
        }

        // === Bước 2: Phát hiện cấu trúc văn bản pháp luật (Điều, Chương, Mục) ===
        $legalQA = $this->parseLegalStructure($text);
        if (!empty($legalQA)) {
            return $legalQA;
        }

        // === Bước 3: Phát hiện heading + nội dung (I., II., 1., 2.) ===
        $headingQA = $this->parseHeadingStructure($text);
        if (!empty($headingQA)) {
            return $headingQA;
        }

        // === Bước 4: Fallback - chia đoạn và tạo Q&A từ paragraph ===
        $paragraphQA = $this->parseParagraphStructure($text);
        return $paragraphQA;
    }

    /**
     * Parse Q&A có sẵn trong file (Q:/A:, Hỏi:/Đáp:)
     */
    private function parseExistingQA(string $text): array
    {
        $pairs = [];
        $qPattern = '(?:Q|Hỏi|Câu\s*hỏi|Question)\s*[:：]\s*';
        $aPattern = '(?:A|Đáp|Trả\s*lời|Đáp\s*án|Answer)\s*[:：]\s*';
        $fullPattern = '/(' . $qPattern . ')(.*?)(' . $aPattern . ')(.*?)(?=(?:' . $qPattern . ')|\z)/isu';

        if (preg_match_all($fullPattern, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $question = trim($match[2]);
                $answer = trim($match[4]);
                if (mb_strlen($question) >= 3 && mb_strlen($answer) >= 3) {
                    $pairs[] = ['question' => $question, 'answer' => $answer];
                }
            }
        }
        return $pairs;
    }

    /**
     * Parse cấu trúc văn bản pháp luật: Chương/Điều/Khoản
     * VD: "Điều 5. Quy định giờ mở cửa" → Q: "Quy định giờ mở cửa như thế nào?"
     */
    private function parseLegalStructure(string $text): array
    {
        $pairs = [];

        // Pattern: "Điều X. Tiêu đề" hoặc "Điều X: Tiêu đề"
        $pattern = '/^\s*(Điều\s+\d+[.:]?)\s*(.+?)$/mu';

        if (!preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        // Nếu có ít nhất 2 Điều, coi như là văn bản pháp luật
        if (count($matches[0]) < 2) {
            return [];
        }

        for ($i = 0; $i < count($matches[0]); $i++) {
            $dieuLabel = trim($matches[1][$i][0]); // "Điều 5."
            $dieuTitle = trim($matches[2][$i][0]); // "Quy định giờ mở cửa"
            $startPos = $matches[0][$i][1] + strlen($matches[0][$i][0]);

            // Lấy nội dung đến Điều tiếp theo hoặc hết file
            if ($i + 1 < count($matches[0])) {
                $endPos = $matches[0][$i + 1][1];
            } else {
                $endPos = strlen($text);
            }

            $content = trim(substr($text, $startPos, $endPos - $startPos));

            // Bỏ nếu nội dung quá ngắn
            if (mb_strlen($content) < 10) continue;

            // Tạo câu hỏi tự nhiên từ tiêu đề Điều
            $question = $this->generateQuestionFromTitle($dieuTitle, $dieuLabel);
            $answer = $content;

            $pairs[] = [
                'question' => $question,
                'answer' => $answer,
            ];
        }

        return $pairs;
    }

    /**
     * Parse cấu trúc heading: I., II., 1., 2., hoặc các heading dạng chữ in hoa
     */
    private function parseHeadingStructure(string $text): array
    {
        $pairs = [];

        // Pattern heading dạng số La Mã hoặc số: "I. Giới thiệu", "1. Nội quy"
        $headingPattern = '/^\s*((?:[IVXLC]+|\d+)[.):]?)\s+(.{5,80})$/mu';

        if (!preg_match_all($headingPattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        if (count($matches[0]) < 2) {
            return [];
        }

        for ($i = 0; $i < count($matches[0]); $i++) {
            $headingNum = trim($matches[1][$i][0]);
            $headingTitle = trim($matches[2][$i][0]);
            $startPos = $matches[0][$i][1] + strlen($matches[0][$i][0]);

            if ($i + 1 < count($matches[0])) {
                $endPos = $matches[0][$i + 1][1];
            } else {
                $endPos = strlen($text);
            }

            $content = trim(substr($text, $startPos, $endPos - $startPos));
            if (mb_strlen($content) < 15) continue;

            // Tạo câu hỏi
            $question = $this->generateQuestionFromTitle($headingTitle);
            $answer = $content;

            $pairs[] = [
                'question' => $question,
                'answer' => $answer,
            ];
        }

        return $pairs;
    }

    /**
     * Fallback: chia paragraph và tạo Q&A
     */
    private function parseParagraphStructure(string $text): array
    {
        $pairs = [];
        $paragraphs = preg_split('/\n\n+/', $text);

        foreach ($paragraphs as $para) {
            $para = trim($para);
            if (mb_strlen($para) < 30) continue;

            // Bỏ đoạn chỉ có số/ký tự đặc biệt
            $cleanCheck = preg_replace('/[\d\s\W]/u', '', $para);
            if (mb_strlen($cleanCheck) < 10) continue;

            // Lấy dòng đầu tiên làm cơ sở tạo câu hỏi
            $lines = explode("\n", $para);
            $firstLine = trim($lines[0]);

            // Nếu dòng đầu ngắn (heading), tạo Q từ nó
            if (mb_strlen($firstLine) <= 100) {
                $question = $this->generateQuestionFromTitle($firstLine);
            } else {
                // Cắt câu đầu tiên
                $dotPos = mb_strpos($firstLine, '.');
                if ($dotPos !== false && $dotPos < 120) {
                    $sentence = mb_substr($firstLine, 0, $dotPos + 1);
                } else {
                    $sentence = mb_substr($firstLine, 0, 80);
                }
                $question = $this->generateQuestionFromTitle($sentence);
            }

            $pairs[] = [
                'question' => $question,
                'answer' => $para,
            ];
        }

        return $pairs;
    }

    /**
     * Tạo câu hỏi tự nhiên từ tiêu đề/heading tiếng Việt
     * VD: "Quy định giờ mở cửa" → "Quy định giờ mở cửa như thế nào?"
     * VD: "Điều kiện mượn sách" → "Điều kiện mượn sách là gì?"
     */
    private function generateQuestionFromTitle(string $title, string $prefix = ''): string
    {
        // Loại bỏ số đầu, dấu chấm, dấu hai chấm
        $clean = preg_replace('/^[\d\s.):]+/', '', $title);
        $clean = trim($clean, ' .:;-');

        if (empty($clean)) {
            $clean = $title;
        }

        // Nếu đã là câu hỏi
        if (preg_match('/[?？]$/', $clean)) {
            return $clean;
        }

        $lower = mb_strtolower($clean);

        // Pattern matching cho các loại nội dung khác nhau
        $patterns = [
            // Quy định, quy chế, quy trình
            '/^(quy\s*(?:định|chế|trình))/' => '%s như thế nào?',
            // Thủ tục, hướng dẫn, cách
            '/^(thủ\s*tục|hướng\s*dẫn|cách)/' => '%s như thế nào?',
            // Trách nhiệm, nghĩa vụ
            '/^(trách\s*nhiệm|nghĩa\s*vụ)/' => '%s là gì?',
            // Quyền, quyền lợi
            '/^(quyền)/' => '%s gồm những gì?',
            // Điều kiện, yêu cầu, tiêu chuẩn
            '/^(điều\s*kiện|yêu\s*cầu|tiêu\s*chuẩn)/' => '%s là gì?',
            // Mục đích, mục tiêu
            '/^(mục\s*đích|mục\s*tiêu)/' => '%s là gì?',
            // Phạm vi, đối tượng
            '/^(phạm\s*vi|đối\s*tượng)/' => '%s là gì?',
            // Thời gian, giờ, lịch
            '/^(thời\s*gian|giờ|lịch)/' => '%s như thế nào?',
            // Xử lý, xử phạt
            '/^(xử\s*(?:lý|phạt))/' => '%s như thế nào?',
            // Nội quy, nội dung
            '/^(nội\s*quy)/' => '%s bao gồm những gì?',
            // Tổ chức, cơ cấu
            '/^(tổ\s*chức|cơ\s*cấu)/' => '%s như thế nào?',
        ];

        foreach ($patterns as $regex => $template) {
            if (preg_match($regex, $lower)) {
                return sprintf($template, $clean);
            }
        }

        // Nếu tiêu đề chứa động từ → hỏi "như thế nào"
        $verbPatterns = '/^(cần|phải|nên|được|có thể|không được|để)/u';
        if (preg_match($verbPatterns, $lower)) {
            return $clean . '?';
        }

        // Default: thêm "là gì?" hoặc "như thế nào?"
        if (mb_strlen($clean) <= 50) {
            return $clean . ' là gì?';
        }
        return $clean . '?';
    }

    /**
     * Import danh sách Q&A vào bảng questions (chỉ các item mới, bỏ qua trùng)
     */
    private function importQAPairs(\PDO $db, array $qaPairs, int $datasetId, int $adminId, string $sourceType): array
    {
        // Kiểm tra trùng lặp trước khi import
        $checkResult = $this->findDuplicates($qaPairs);
        $newItems = $checkResult['new_items'];
        $duplicates = $checkResult['duplicates'];

        $stmt = $db->prepare(
            "INSERT INTO questions (question_text, answer_text, source_type, dataset_id, created_by, is_active) 
             VALUES (?, ?, ?, ?, ?, 1)"
        );

        $count = 0;
        foreach ($newItems as $qa) {
            try {
                $stmt->execute([
                    $qa['question'],
                    $qa['answer'],
                    $sourceType,
                    $datasetId,
                    $adminId,
                ]);
                $questionId = $db->lastInsertId();
                
                // Tự động tạo từ khóa cho câu hỏi vừa import
                $autoKeywords = $this->generateAutoKeywords($qa['question']);
                $this->saveAutoKeywords($db, $questionId, $autoKeywords);
                
                $count++;
            } catch (\Exception $e) {
                continue;
            }
        }

        return [
            'imported' => $count,
            'duplicates' => $duplicates,
            'skipped' => count($duplicates),
        ];
    }

    /**
     * Tạo từ khóa tự động từ câu hỏi
     */
    private function generateAutoKeywords(string $questionText): array
    {
        require_once __DIR__ . '/../helpers/KeywordGenerator.php';
        return KeywordGenerator::generate($questionText);
    }

    /**
     * Lưu từ khóa tự động vào database
     */
    private function saveAutoKeywords(\PDO $db, int $questionId, array $autoKeywords): void
    {
        $stmt = $db->prepare("INSERT INTO keywords (question_id, keyword, is_auto, language, weight) VALUES (?, ?, 1, ?, ?)");
        
        // Lưu từ khóa tiếng Việt
        foreach ($autoKeywords['vi'] as $item) {
            try {
                $keyword = is_array($item) ? $item['keyword'] : $item;
                $weight = is_array($item) && isset($item['weight']) ? $item['weight'] : 5.0;
                $stmt->execute([$questionId, $keyword, 'vi', $weight]);
            } catch (\Exception $e) {
                // Bỏ qua nếu trùng
                continue;
            }
        }
        
        // Lưu từ khóa tiếng Anh
        foreach ($autoKeywords['en'] as $item) {
            try {
                $keyword = is_array($item) ? $item['keyword'] : $item;
                $weight = is_array($item) && isset($item['weight']) ? $item['weight'] : 5.0;
                $stmt->execute([$questionId, $keyword, 'en', $weight]);
            } catch (\Exception $e) {
                // Bỏ qua nếu trùng
                continue;
            }
        }
    }

    /**
     * Cập nhật trạng thái dataset
     */
    private function updateDatasetStatus(\PDO $db, int $datasetId, string $status, ?string $errorMessage = null, int $totalQuestions = 0): void
    {
        $stmt = $db->prepare(
            "UPDATE datasets SET status = ?, error_message = ?, total_questions = ?, updated_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$status, $errorMessage, $totalQuestions, $datasetId]);
    }

    /**
     * GET /api/admin/datasets - Lấy lịch sử upload
     */
    public function datasets()
    {
        $this->requireAuth();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "SELECT d.*, a.full_name as uploaded_by_name 
             FROM datasets d 
             LEFT JOIN admins a ON d.uploaded_by = a.id 
             ORDER BY d.created_at DESC"
        );
        $stmt->execute();
        $this->json(['datasets' => $stmt->fetchAll()]);
    }

    /**
     * GET /api/admin/unanswered - Câu hỏi chưa trả lời
     */
    public function unanswered()
    {
        $this->requireAuth();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "SELECT * FROM unanswered_questions ORDER BY is_resolved ASC, frequency DESC, created_at DESC"
        );
        $stmt->execute();
        $this->json(['unanswered' => $stmt->fetchAll()]);
    }

    /**
     * PUT /api/admin/resolveUnanswered/{id} - Đánh dấu đã xử lý
     */
    public function resolveUnanswered($id = null)
    {
        $adminId = $this->requireAuth();
        if (!$id) {
            $this->json(['error' => 'ID không hợp lệ'], 400);
        }
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "UPDATE unanswered_questions SET is_resolved = 1, resolved_by = ?, resolved_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$adminId, $id]);
        $this->json(['success' => true]);
    }

    /**
     * DELETE /api/admin/deleteUnanswered/{id} - Xóa câu hỏi chưa trả lời
     */
    public function deleteUnanswered($id = null)
    {
        $this->requireAuth();
        if (!$id) {
            $this->json(['error' => 'ID không hợp lệ'], 400);
        }
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM unanswered_questions WHERE id = ?");
        $stmt->execute([$id]);
        $this->json(['success' => true]);
    }

    /**
     * POST /api/admin/deleteMultipleUnanswered - Xóa nhiều câu hỏi chưa trả lời
     */
    public function deleteMultipleUnanswered()
    {
        $this->requireAuth();

        if ($this->getMethod() !== 'POST') {
            $this->json(['error' => 'Method not allowed'], 405);
        }

        $input = $this->getJsonInput();
        $ids = $input['ids'] ?? [];

        if (empty($ids) || !is_array($ids)) {
            $this->json(['error' => 'Danh sách ID không hợp lệ'], 400);
        }

        $ids = array_filter($ids, function($id) {
            return is_numeric($id) && $id > 0;
        });

        if (empty($ids)) {
            $this->json(['error' => 'Không có ID hợp lệ'], 400);
        }

        $db = Database::getInstance()->getConnection();
        
        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("DELETE FROM unanswered_questions WHERE id IN ($placeholders)");
            $result = $stmt->execute($ids);
            
            if ($result) {
                $this->json([
                    'success' => true,
                    'message' => 'Đã xóa ' . count($ids) . ' câu hỏi chưa trả lời',
                    'deleted_count' => count($ids)
                ]);
            } else {
                $this->json(['error' => 'Không thể xóa'], 500);
            }
        } catch (\Exception $e) {
            $this->json(['error' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    // ==================== FORMS (BIỂU MẪU) ====================

    /**
     * GET  /api/admin/forms        – Lấy danh sách biểu mẫu
     * POST /api/admin/forms        – Tạo biểu mẫu mới
     */
    public function forms()
    {
        $adminId = $this->requireAuth();

        if ($this->getMethod() === 'POST') {
            $input = $this->getJsonInput();
            if (empty($input['name']) || empty($input['url'])) {
                $this->json(['error' => 'Tên và URL là bắt buộc'], 400);
            }
            $id = $this->formModel->create([
                'name'        => sanitize($input['name']),
                'description' => sanitize($input['description'] ?? ''),
                'url'         => trim($input['url']),
                'keywords'    => sanitize($input['keywords'] ?? ''),
                'is_active'   => $input['is_active'] ?? 1,
                'created_by'  => $adminId,
            ]);
            $this->json(['success' => true, 'id' => $id], 201);
        }

        $forms = $this->formModel->getAll();
        $this->json(['forms' => $forms]);
    }

    /**
     * GET    /api/admin/form/{id}  – Chi tiết
     * PUT    /api/admin/form/{id}  – Cập nhật
     * DELETE /api/admin/form/{id}  – Xóa
     */
    public function form($id = null)
    {
        $this->requireAuth();
        if (!$id) {
            $this->json(['error' => 'ID không hợp lệ'], 400);
        }

        if ($this->getMethod() === 'DELETE') {
            $this->formModel->delete($id);
            $this->json(['success' => true]);
        }

        if ($this->getMethod() === 'PUT') {
            $input = $this->getJsonInput();
            if (empty($input['name']) || empty($input['url'])) {
                $this->json(['error' => 'Tên và URL là bắt buộc'], 400);
            }
            $this->formModel->update($id, [
                'name'        => sanitize($input['name']),
                'description' => sanitize($input['description'] ?? ''),
                'url'         => trim($input['url']),
                'keywords'    => sanitize($input['keywords'] ?? ''),
                'is_active'   => $input['is_active'] ?? 1,
            ]);
            $this->json(['success' => true]);
        }

        $form = $this->formModel->getById($id);
        $this->json(['form' => $form]);
    }

    /**
     * POST /api/admin/deleteMultipleForms - Xóa nhiều biểu mẫu
     */
    public function deleteMultipleForms()
    {
        $this->requireAuth();

        if ($this->getMethod() !== 'POST') {
            $this->json(['error' => 'Method not allowed'], 405);
        }

        $input = $this->getJsonInput();
        $ids = $input['ids'] ?? [];

        if (empty($ids) || !is_array($ids)) {
            $this->json(['error' => 'Danh sách ID không hợp lệ'], 400);
        }

        $ids = array_filter($ids, function($id) {
            return is_numeric($id) && $id > 0;
        });

        if (empty($ids)) {
            $this->json(['error' => 'Không có ID hợp lệ'], 400);
        }

        try {
            $result = $this->formModel->deleteMultiple($ids);
            
            if ($result) {
                $this->json([
                    'success' => true,
                    'message' => 'Đã xóa ' . count($ids) . ' biểu mẫu',
                    'deleted_count' => count($ids)
                ]);
            } else {
                $this->json(['error' => 'Không thể xóa biểu mẫu'], 500);
            }
        } catch (\Exception $e) {
            $this->json(['error' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    // ==================== KEYWORDS ====================

    /**
     * GET /api/admin/keywords/{questionId} - Lấy tất cả từ khóa của câu hỏi
     */
    public function keywords($questionId = null)
    {
        $this->requireAuth();
        
        if (!$questionId) {
            $this->json(['error' => 'Question ID không hợp lệ'], 400);
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "SELECT id, keyword, is_auto, language, created_at 
             FROM keywords 
             WHERE question_id = ? 
             ORDER BY is_auto ASC, language ASC, keyword ASC"
        );
        $stmt->execute([$questionId]);
        $keywords = $stmt->fetchAll();

        // Nhóm theo loại
        $result = [
            'manual' => [],
            'auto_vi' => [],
            'auto_en' => [],
        ];

        foreach ($keywords as $kw) {
            if ($kw['is_auto'] == 0) {
                $result['manual'][] = $kw;
            } elseif ($kw['language'] === 'vi') {
                $result['auto_vi'][] = $kw;
            } elseif ($kw['language'] === 'en') {
                $result['auto_en'][] = $kw;
            }
        }

        $this->json(['keywords' => $result]);
    }

    /**
     * POST /api/admin/regenerateKeywords/{questionId} - Tạo lại từ khóa tự động
     */
    public function regenerateKeywords($questionId = null)
    {
        $this->requireAuth();
        
        if (!$questionId) {
            $this->json(['error' => 'Question ID không hợp lệ'], 400);
        }

        $db = Database::getInstance()->getConnection();
        
        // Lấy câu hỏi
        $stmt = $db->prepare("SELECT question_text FROM questions WHERE id = ?");
        $stmt->execute([$questionId]);
        $question = $stmt->fetch();
        
        if (!$question) {
            $this->json(['error' => 'Câu hỏi không tồn tại'], 404);
        }

        // Xóa từ khóa tự động cũ
        $stmt = $db->prepare("DELETE FROM keywords WHERE question_id = ? AND is_auto = 1");
        $stmt->execute([$questionId]);

        // Tạo từ khóa mới
        $autoKeywords = $this->generateAutoKeywords($question['question_text']);
        $this->saveAutoKeywords($db, $questionId, $autoKeywords);

        $this->json([
            'success' => true,
            'auto_keywords' => $autoKeywords,
            'message' => 'Đã tạo lại từ khóa tự động',
        ]);
    }

    /**
     * GET /api/admin/dictionary - Lấy từ điển dịch Vi-En
     */
    public function dictionary()
    {
        $this->requireAuth();

        if ($this->getMethod() === 'POST') {
            return $this->addToDictionary();
        }

        require_once __DIR__ . '/../helpers/KeywordGenerator.php';
        $dict = KeywordGenerator::getDictionary();
        
        $this->json(['dictionary' => $dict]);
    }

    /**
     * POST /api/admin/dictionary - Thêm từ vào từ điển
     */
    private function addToDictionary()
    {
        $input = $this->getJsonInput();
        
        if (empty($input['vi']) || empty($input['en'])) {
            $this->json(['error' => 'Cần cung cấp cả từ tiếng Việt và tiếng Anh'], 400);
        }

        require_once __DIR__ . '/../helpers/KeywordGenerator.php';
        KeywordGenerator::addToDictionary(
            mb_strtolower(trim($input['vi'])),
            mb_strtolower(trim($input['en']))
        );

        $this->json([
            'success' => true,
            'message' => 'Đã thêm từ vào từ điển',
        ]);
    }

    // ==================== ADMIN ACCOUNTS ====================

    /**
     * GET /api/admin/admins - Danh sách tài khoản
     * POST /api/admin/admins - Thêm tài khoản mới (role editor)
     */
    public function admins()
    {
        $this->requireAccountManager();

        if ($this->getMethod() === 'POST') {
            return $this->createAdminAccount();
        }

        $admins = $this->adminModel->getAllSafe();
        $this->json(['admins' => $admins]);
    }

    /**
     * PUT /api/admin/admin/{id} - Cập nhật tài khoản
     * DELETE /api/admin/admin/{id} - Xóa tài khoản
     */
    public function admin($id = null)
    {
        $this->requireAccountManager();

        $adminId = intval($id);
        if (!$adminId) {
            $this->json(['error' => 'ID không hợp lệ'], 400);
        }

        if ($this->getMethod() === 'PUT') {
            return $this->updateAdminAccount($adminId);
        }

        if ($this->getMethod() === 'DELETE') {
            return $this->deleteAdminAccount($adminId);
        }

        $this->json(['error' => 'Method not allowed'], 405);
    }

    private function createAdminAccount()
    {
        $input = $this->getJsonInput();
        $email = trim($input['email'] ?? '');
        $fullName = trim($input['full_name'] ?? '');
        $password = $input['password'] ?? '';

        if (empty($email) || empty($fullName) || empty($password)) {
            $this->json(['error' => 'Vui lòng nhập đầy đủ email, họ tên và mật khẩu'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['error' => 'Email không hợp lệ'], 400);
        }

        if (strlen($password) < 6) {
            $this->json(['error' => 'Mật khẩu phải có ít nhất 6 ký tự'], 400);
        }

        if ($this->adminModel->findByEmail($email)) {
            $this->json(['error' => 'Email đã tồn tại trong hệ thống'], 409);
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $newId = $this->adminModel->create([
            'google_id' => null,
            'email' => $email,
            'password' => $hashed,
            'full_name' => sanitize($fullName),
            'avatar_url' => null,
            'role' => 'editor',
            'is_active' => 1,
            'last_login' => null,
        ]);

        $this->json(['success' => true, 'id' => $newId]);
    }

    private function updateAdminAccount(int $adminId)
    {
        $input = $this->getJsonInput();
        $currentId = $_SESSION['admin_id'] ?? 0;

        if ($adminId === $currentId && isset($input['is_active']) && !$input['is_active']) {
            $this->json(['error' => 'Không thể vô hiệu hóa chính mình'], 400);
        }

        $update = [];
        if (isset($input['full_name'])) {
            $name = trim($input['full_name']);
            if ($name !== '') {
                $update['full_name'] = sanitize($name);
            }
        }
        if (isset($input['is_active'])) {
            $update['is_active'] = $input['is_active'] ? 1 : 0;
        }

        if (!empty($input['password'])) {
            if (strlen($input['password']) < 6) {
                $this->json(['error' => 'Mật khẩu phải có ít nhất 6 ký tự'], 400);
            }
            $update['password'] = password_hash($input['password'], PASSWORD_DEFAULT);
        }

        if (empty($update)) {
            $this->json(['error' => 'Không có dữ liệu cần cập nhật'], 400);
        }

        $this->adminModel->update($adminId, $update);
        $this->json(['success' => true]);
    }

    private function deleteAdminAccount(int $adminId)
    {
        $currentId = $_SESSION['admin_id'] ?? 0;
        if ($adminId === $currentId) {
            $this->json(['error' => 'Không thể xóa chính mình'], 400);
        }

        $this->adminModel->delete($adminId);
        $this->json(['success' => true]);
    }

    /**
     * POST /api/admin/adminResetLink/{id} - Tạo link đặt lại mật khẩu cho tài khoản
     */
    public function adminResetLink($id = null)
    {
        $this->requireAccountManager();

        if ($this->getMethod() !== 'POST') {
            $this->json(['error' => 'Method not allowed'], 405);
        }

        $adminId = intval($id);
        if (!$adminId) {
            $this->json(['error' => 'ID không hợp lệ'], 400);
        }

        $admin = $this->adminModel->getById($adminId);
        if (!$admin) {
            $this->json(['error' => 'Tài khoản không tồn tại'], 404);
        }

        $result = $this->adminModel->createResetToken($admin['email']);
        if (!$result) {
            $this->json(['error' => 'Không thể tạo link đặt lại mật khẩu'], 500);
        }

        $resetLink = FRONTEND_URL . '/login.html?reset_token=' . $result['token'];
        $this->json(['success' => true, 'reset_link' => $resetLink]);
    }

    // ==================== EXPORT EXCEL ====================

    /**
     * GET /api/admin/exportQuestions - Xuất toàn bộ dữ liệu câu hỏi ra Excel
     * Sử dụng XML format (Excel 2003) - không cần thư viện bên ngoài
     */
    public function exportQuestions()
    {
        $this->requireAuth();

        try {
            $db = Database::getInstance()->getConnection();
            
            // Lấy toàn bộ dữ liệu câu hỏi kèm danh mục và từ khóa
            $sql = "SELECT 
                        q.id,
                        q.question_text,
                        q.answer_text,
                        q.answer_text_en,
                        c.name as category_name,
                        q.source_type,
                        q.is_active,
                        q.created_at,
                        q.updated_at,
                        GROUP_CONCAT(DISTINCT CASE WHEN k.is_auto = 0 THEN k.keyword END SEPARATOR ', ') as manual_keywords,
                        GROUP_CONCAT(DISTINCT CASE WHEN k.is_auto = 1 AND k.language = 'vi' THEN k.keyword END SEPARATOR ', ') as auto_keywords_vi,
                        GROUP_CONCAT(DISTINCT CASE WHEN k.is_auto = 1 AND k.language = 'en' THEN k.keyword END SEPARATOR ', ') as auto_keywords_en
                    FROM questions q
                    LEFT JOIN categories c ON q.category_id = c.id
                    LEFT JOIN keywords k ON q.id = k.question_id
                    GROUP BY q.id
                    ORDER BY q.created_at DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $questions = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($questions)) {
                $this->json(['error' => 'Không có dữ liệu để xuất'], 404);
            }

            // Tạo tên file
            $fileName = 'DuLieuCauHoi_' . date('Y-m-d_His') . '.xls';

            // Tạo nội dung Excel XML (Excel 2003 format)
            $excelContent = $this->generateExcelXML($questions);

            // Gửi file về client
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Cache-Control: max-age=0');
            
            echo "\xEF\xBB\xBF"; // UTF-8 BOM
            echo $excelContent;
            exit;

        } catch (\Exception $e) {
            $this->json([
                'error' => 'Lỗi khi xuất file Excel: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tạo nội dung Excel XML format (Excel 2003)
     * Không cần thư viện bên ngoài
     */
    private function generateExcelXML($questions)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n";
        $xml .= ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n";
        $xml .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
        
        // Styles
        $xml .= '<Styles>' . "\n";
        
        // Header style
        $xml .= '<Style ss:ID="Header">' . "\n";
        $xml .= '<Font ss:Bold="1" ss:Size="12" ss:Color="#FFFFFF"/>' . "\n";
        $xml .= '<Interior ss:Color="#1976D2" ss:Pattern="Solid"/>' . "\n";
        $xml .= '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>' . "\n";
        $xml .= '<Borders>' . "\n";
        $xml .= '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
        $xml .= '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
        $xml .= '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
        $xml .= '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
        $xml .= '</Borders>' . "\n";
        $xml .= '</Style>' . "\n";
        
        // Data style
        $xml .= '<Style ss:ID="Data">' . "\n";
        $xml .= '<Alignment ss:Vertical="Top" ss:WrapText="1"/>' . "\n";
        $xml .= '<Borders>' . "\n";
        $xml .= '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>' . "\n";
        $xml .= '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>' . "\n";
        $xml .= '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>' . "\n";
        $xml .= '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>' . "\n";
        $xml .= '</Borders>' . "\n";
        $xml .= '</Style>' . "\n";
        
        // Center style
        $xml .= '<Style ss:ID="Center">' . "\n";
        $xml .= '<Alignment ss:Horizontal="Center" ss:Vertical="Top"/>' . "\n";
        $xml .= '<Borders>' . "\n";
        $xml .= '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>' . "\n";
        $xml .= '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>' . "\n";
        $xml .= '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>' . "\n";
        $xml .= '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>' . "\n";
        $xml .= '</Borders>' . "\n";
        $xml .= '</Style>' . "\n";
        
        $xml .= '</Styles>' . "\n";
        
        // Worksheet
        $xml .= '<Worksheet ss:Name="Câu hỏi">' . "\n";
        $xml .= '<Table>' . "\n";
        
        // Column widths
        $xml .= '<Column ss:Width="50"/>' . "\n";  // ID
        $xml .= '<Column ss:Width="300"/>' . "\n"; // Câu hỏi
        $xml .= '<Column ss:Width="400"/>' . "\n"; // Câu trả lời VI
        $xml .= '<Column ss:Width="400"/>' . "\n"; // Câu trả lời EN
        $xml .= '<Column ss:Width="150"/>' . "\n"; // Danh mục
        $xml .= '<Column ss:Width="200"/>' . "\n"; // Từ khóa thủ công
        $xml .= '<Column ss:Width="200"/>' . "\n"; // Từ khóa auto VI
        $xml .= '<Column ss:Width="200"/>' . "\n"; // Từ khóa auto EN
        $xml .= '<Column ss:Width="100"/>' . "\n"; // Nguồn
        $xml .= '<Column ss:Width="100"/>' . "\n"; // Trạng thái
        $xml .= '<Column ss:Width="150"/>' . "\n"; // Ngày tạo
        $xml .= '<Column ss:Width="150"/>' . "\n"; // Ngày cập nhật
        
        // Header row
        $xml .= '<Row ss:Height="30">' . "\n";
        $headers = ['ID', 'Câu hỏi', 'Câu trả lời (Tiếng Việt)', 'Câu trả lời (Tiếng Anh)', 
                    'Danh mục', 'Từ khóa thủ công', 'Từ khóa tự động (VI)', 'Từ khóa tự động (EN)',
                    'Nguồn', 'Trạng thái', 'Ngày tạo', 'Ngày cập nhật'];
        
        foreach ($headers as $header) {
            $xml .= '<Cell ss:StyleID="Header"><Data ss:Type="String">' . htmlspecialchars($header, ENT_XML1, 'UTF-8') . '</Data></Cell>' . "\n";
        }
        $xml .= '</Row>' . "\n";
        
        // Data rows
        $sourceMap = [
            'manual' => 'Thủ công',
            'word' => 'Word',
            'pdf' => 'PDF'
        ];
        
        foreach ($questions as $q) {
            $xml .= '<Row>' . "\n";
            
            // ID
            $xml .= '<Cell ss:StyleID="Center"><Data ss:Type="Number">' . $q['id'] . '</Data></Cell>' . "\n";
            
            // Câu hỏi
            $xml .= '<Cell ss:StyleID="Data"><Data ss:Type="String">' . htmlspecialchars($q['question_text'], ENT_XML1, 'UTF-8') . '</Data></Cell>' . "\n";
            
            // Câu trả lời VI (loại bỏ HTML)
            $answerText = strip_tags($q['answer_text']);
            $xml .= '<Cell ss:StyleID="Data"><Data ss:Type="String">' . htmlspecialchars($answerText, ENT_XML1, 'UTF-8') . '</Data></Cell>' . "\n";
            
            // Câu trả lời EN (loại bỏ HTML)
            $answerTextEn = strip_tags($q['answer_text_en'] ?? '');
            $xml .= '<Cell ss:StyleID="Data"><Data ss:Type="String">' . htmlspecialchars($answerTextEn, ENT_XML1, 'UTF-8') . '</Data></Cell>' . "\n";
            
            // Danh mục
            $xml .= '<Cell ss:StyleID="Data"><Data ss:Type="String">' . htmlspecialchars($q['category_name'] ?? 'Chưa phân loại', ENT_XML1, 'UTF-8') . '</Data></Cell>' . "\n";
            
            // Từ khóa thủ công
            $xml .= '<Cell ss:StyleID="Data"><Data ss:Type="String">' . htmlspecialchars($q['manual_keywords'] ?? '', ENT_XML1, 'UTF-8') . '</Data></Cell>' . "\n";
            
            // Từ khóa tự động VI
            $xml .= '<Cell ss:StyleID="Data"><Data ss:Type="String">' . htmlspecialchars($q['auto_keywords_vi'] ?? '', ENT_XML1, 'UTF-8') . '</Data></Cell>' . "\n";
            
            // Từ khóa tự động EN
            $xml .= '<Cell ss:StyleID="Data"><Data ss:Type="String">' . htmlspecialchars($q['auto_keywords_en'] ?? '', ENT_XML1, 'UTF-8') . '</Data></Cell>' . "\n";
            
            // Nguồn
            $source = $sourceMap[$q['source_type']] ?? $q['source_type'];
            $xml .= '<Cell ss:StyleID="Center"><Data ss:Type="String">' . htmlspecialchars($source, ENT_XML1, 'UTF-8') . '</Data></Cell>' . "\n";
            
            // Trạng thái
            $status = $q['is_active'] ? 'Hoạt động' : 'Tắt';
            $xml .= '<Cell ss:StyleID="Center"><Data ss:Type="String">' . htmlspecialchars($status, ENT_XML1, 'UTF-8') . '</Data></Cell>' . "\n";
            
            // Ngày tạo
            $xml .= '<Cell ss:StyleID="Data"><Data ss:Type="String">' . htmlspecialchars($q['created_at'], ENT_XML1, 'UTF-8') . '</Data></Cell>' . "\n";
            
            // Ngày cập nhật
            $xml .= '<Cell ss:StyleID="Data"><Data ss:Type="String">' . htmlspecialchars($q['updated_at'], ENT_XML1, 'UTF-8') . '</Data></Cell>' . "\n";
            
            $xml .= '</Row>' . "\n";
        }
        
        $xml .= '</Table>' . "\n";
        $xml .= '</Worksheet>' . "\n";
        $xml .= '</Workbook>';
        
        return $xml;
    }
}
