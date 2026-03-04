<?php
require_once __DIR__ . '/../core/BaseController.php';

class AdminController extends BaseController
{
    private $questionModel;
    private $categoryModel;
    private $chatModel;
    private $settingModel;

    public function __construct()
    {
        $this->questionModel = $this->model('QuestionModel');
        $this->categoryModel = $this->model('CategoryModel');
        $this->chatModel = $this->model('ChatModel');
        $this->settingModel = $this->model('SettingModel');
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
     * GET /api/admin/questions - Lấy danh sách câu hỏi
     */
    public function questions()
    {
        $this->requireAuth();

        if ($this->getMethod() === 'POST') {
            return $this->createQuestion();
        }

        $questions = $this->questionModel->getAllWithCategory();
        $this->json(['questions' => $questions]);
    }

    /**
     * POST /api/admin/questions - Tạo câu hỏi mới
     */
    private function createQuestion()
    {
        $adminId = $this->requireAuth();
        $input = $this->getJsonInput();

        $id = $this->questionModel->create([
            'category_id' => $input['category_id'] ?? null,
            'question_text' => sanitize($input['question_text']),
            'answer_text' => $input['answer_text'],
            'source_type' => 'manual',
            'created_by' => $adminId,
        ]);

        // Thêm từ khóa nếu có
        if (!empty($input['keywords'])) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("INSERT INTO keywords (question_id, keyword) VALUES (?, ?)");
            foreach ($input['keywords'] as $keyword) {
                $stmt->execute([$id, trim($keyword)]);
            }
        }

        $this->json(['success' => true, 'id' => $id], 201);
    }

    /**
     * PUT /api/admin/question/{id} - Cập nhật câu hỏi
     */
    public function question($id = null)
    {
        $adminId = $this->requireAuth();

        if (!$id) {
            $this->json(['error' => 'ID không hợp lệ'], 400);
        }

        if ($this->getMethod() === 'DELETE') {
            $this->questionModel->delete($id);
            $this->json(['success' => true]);
        }

        if ($this->getMethod() === 'PUT') {
            $input = $this->getJsonInput();
            $this->questionModel->update($id, [
                'category_id' => $input['category_id'] ?? null,
                'question_text' => sanitize($input['question_text']),
                'answer_text' => $input['answer_text'],
                'is_active' => $input['is_active'] ?? 1,
            ]);
            $this->json(['success' => true]);
        }

        // GET - lấy chi tiết
        $question = $this->questionModel->getById($id);
        $this->json(['question' => $question]);
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
            $this->categoryModel->update($id, [
                'name' => sanitize($input['name']),
                'description' => $input['description'] ?? '',
                'sort_order' => $input['sort_order'] ?? 0,
                'is_active' => $input['is_active'] ?? 1,
            ]);
            $this->json(['success' => true]);
        }

        $category = $this->categoryModel->getById($id);
        $this->json(['category' => $category]);
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
     */
    public function themes()
    {
        $adminId = $this->requireAuth();

        if ($this->getMethod() === 'POST') {
            $input = $this->getJsonInput();
            $db = Database::getInstance()->getConnection();
            
            // Nếu kích hoạt theme mới, tắt các theme cũ
            if (!empty($input['is_active'])) {
                $db->exec("UPDATE event_themes SET is_active = 0");
            }

            $id = $db->prepare(
                "INSERT INTO event_themes (theme_name, primary_color, secondary_color, header_bg_color, header_text_color, 
                user_bubble_color, bot_bubble_color, button_color, welcome_message, start_date, end_date, is_active, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $id->execute([
                $input['theme_name'], $input['primary_color'] ?? '#1976D2',
                $input['secondary_color'] ?? '#FFFFFF', $input['header_bg_color'] ?? '#1976D2',
                $input['header_text_color'] ?? '#FFFFFF', $input['user_bubble_color'] ?? '#E3F2FD',
                $input['bot_bubble_color'] ?? '#F5F5F5', $input['button_color'] ?? '#1976D2',
                $input['welcome_message'] ?? '', $input['start_date'] ?? null,
                $input['end_date'] ?? null, $input['is_active'] ?? 0, $adminId,
            ]);
            $this->json(['success' => true, 'id' => $db->lastInsertId()], 201);
        }

        $themes = $this->settingModel->getAllThemes();
        $this->json(['themes' => $themes]);
    }

    // ==================== UPLOAD DATASET ====================

    /**
     * POST /api/admin/upload - Upload file Word/PDF
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
        $allowedTypes = [
            'application/pdf' => 'pdf',
            'application/msword' => 'word',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'word',
        ];

        if (!isset($allowedTypes[$file['type']])) {
            $this->json(['error' => 'Chỉ chấp nhận file Word (.doc, .docx) hoặc PDF'], 400);
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

        // Lưu vào database
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "INSERT INTO datasets (file_name, file_path, file_type, file_size, uploaded_by) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$file['name'], $filePath, $allowedTypes[$file['type']], $file['size'], $adminId]);

        $this->json([
            'success' => true,
            'id' => $db->lastInsertId(),
            'message' => 'File đã được tải lên. Hệ thống sẽ xử lý và import câu hỏi.',
        ], 201);
    }

    /**
     * GET /api/admin/unanswered - Câu hỏi chưa trả lời
     */
    public function unanswered()
    {
        $this->requireAuth();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "SELECT * FROM unanswered_questions ORDER BY frequency DESC, created_at DESC"
        );
        $stmt->execute();
        $this->json(['unanswered' => $stmt->fetchAll()]);
    }
}
