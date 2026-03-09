<?php
/**
 * Admin Tool: Tạo từ khóa tự động cho câu hỏi hiện có
 * Truy cập: http://localhost/pages/admin/generate_keywords.php
 */

// Load config và dependencies
require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/config/Database.php';
require_once __DIR__ . '/../../../app/helpers/KeywordGenerator.php';

// Xử lý AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        $db = Database::getInstance()->getConnection();
        
        if ($_POST['action'] === 'check') {
            // Kiểm tra database
            $checkColumn = $db->query("SHOW COLUMNS FROM keywords LIKE 'is_auto'");
            if ($checkColumn->rowCount() === 0) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Chưa chạy migration! Vui lòng import file database/migration_auto_keywords.sql'
                ]);
                exit;
            }
            
            // Đếm số câu hỏi
            $stmt = $db->query("SELECT COUNT(*) as total FROM questions WHERE is_active = 1");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Đếm số từ khóa hiện có
            $stmtKeywords = $db->query("SELECT COUNT(*) as total FROM keywords WHERE is_auto = 1");
            $keywords = $stmtKeywords->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'total_questions' => $result['total'],
                'existing_keywords' => $keywords['total']
            ]);
            exit;
        }
        
        if ($_POST['action'] === 'generate') {
            // Lấy tất cả câu hỏi
            $stmt = $db->query("SELECT id, question_text FROM questions WHERE is_active = 1 ORDER BY id ASC");
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($questions)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Không có câu hỏi nào trong database'
                ]);
                exit;
            }
            
            // Xóa từ khóa tự động cũ
            $deleteStmt = $db->prepare("DELETE FROM keywords WHERE is_auto = 1");
            $deleteStmt->execute();
            
            // Prepare statement
            $insertStmt = $db->prepare(
                "INSERT INTO keywords (question_id, keyword, is_auto, language, created_at) 
                 VALUES (?, ?, 1, ?, NOW())"
            );
            
            $successCount = 0;
            $totalKeywordsVi = 0;
            $totalKeywordsEn = 0;
            $results = [];
            
            foreach ($questions as $question) {
                $questionId = $question['id'];
                $questionText = $question['question_text'];
                
                try {
                    $autoKeywords = KeywordGenerator::generate($questionText);
                    
                    $countVi = 0;
                    $countEn = 0;
                    
                    // Lưu từ khóa tiếng Việt
                    foreach ($autoKeywords['vi'] as $keyword) {
                        try {
                            $insertStmt->execute([$questionId, $keyword, 'vi']);
                            $countVi++;
                            $totalKeywordsVi++;
                        } catch (PDOException $e) {
                            // Bỏ qua nếu trùng
                        }
                    }
                    
                    // Lưu từ khóa tiếng Anh
                    foreach ($autoKeywords['en'] as $keyword) {
                        try {
                            $insertStmt->execute([$questionId, $keyword, 'en']);
                            $countEn++;
                            $totalKeywordsEn++;
                        } catch (PDOException $e) {
                            // Bỏ qua nếu trùng
                        }
                    }
                    
                    $results[] = [
                        'id' => $questionId,
                        'question' => mb_substr($questionText, 0, 60) . '...',
                        'keywords_vi' => $countVi,
                        'keywords_en' => $countEn,
                        'sample_vi' => array_slice($autoKeywords['vi'], 0, 3),
                        'sample_en' => array_slice($autoKeywords['en'], 0, 3),
                    ];
                    
                    $successCount++;
                    
                } catch (Exception $e) {
                    // Bỏ qua lỗi
                }
            }
            
            echo json_encode([
                'success' => true,
                'total_questions' => count($questions),
                'success_count' => $successCount,
                'total_keywords_vi' => $totalKeywordsVi,
                'total_keywords_en' => $totalKeywordsEn,
                'results' => array_slice($results, 0, 10) // Chỉ trả về 10 kết quả đầu
            ]);
            exit;
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo Từ Khóa Tự Động - Admin Tool</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .content {
            padding: 30px;
        }
        
        .status-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        
        .status-box h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .stat-item:last-child {
            border-bottom: none;
        }
        
        .stat-label {
            color: #666;
        }
        
        .stat-value {
            font-weight: bold;
            color: #667eea;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
            display: none;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .results {
            display: none;
            margin-top: 20px;
        }
        
        .result-item {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            border-left: 3px solid #28a745;
        }
        
        .result-item h4 {
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .keywords {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 8px;
        }
        
        .keyword-tag {
            background: #667eea;
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
        }
        
        .keyword-tag.en {
            background: #28a745;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            display: none;
        }
        
        .summary h3 {
            margin-bottom: 15px;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .summary-item {
            text-align: center;
        }
        
        .summary-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .summary-label {
            font-size: 14px;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔑 Tạo Từ Khóa Tự Động</h1>
            <p>Công cụ tạo từ khóa tiếng Việt và tiếng Anh cho tất cả câu hỏi</p>
        </div>
        
        <div class="content">
            <div id="alert-container"></div>
            
            <div class="status-box" id="status-box">
                <h3>📊 Trạng Thái Hệ Thống</h3>
                <div class="stat-item">
                    <span class="stat-label">Tổng số câu hỏi:</span>
                    <span class="stat-value" id="total-questions">Đang kiểm tra...</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Từ khóa tự động hiện có:</span>
                    <span class="stat-value" id="existing-keywords">Đang kiểm tra...</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Trạng thái database:</span>
                    <span class="stat-value" id="db-status">Đang kiểm tra...</span>
                </div>
            </div>
            
            <button class="btn" id="generate-btn" onclick="generateKeywords()">
                🚀 Bắt Đầu Tạo Từ Khóa
            </button>
            
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Đang xử lý... Vui lòng đợi</p>
            </div>
            
            <div class="summary" id="summary">
                <h3>✅ Hoàn Tất!</h3>
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-number" id="summary-questions">0</div>
                        <div class="summary-label">Câu hỏi đã xử lý</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-number" id="summary-total">0</div>
                        <div class="summary-label">Tổng từ khóa</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-number" id="summary-vi">0</div>
                        <div class="summary-label">Từ khóa tiếng Việt</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-number" id="summary-en">0</div>
                        <div class="summary-label">Từ khóa tiếng Anh</div>
                    </div>
                </div>
            </div>
            
            <div class="results" id="results">
                <h3 style="margin-bottom: 15px;">📝 Kết Quả Mẫu (10 câu hỏi đầu tiên)</h3>
                <div id="results-container"></div>
            </div>
        </div>
    </div>
    
    <script>
        // Kiểm tra trạng thái khi load trang
        window.onload = function() {
            checkStatus();
        };
        
        function checkStatus() {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=check'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('total-questions').textContent = data.total_questions;
                    document.getElementById('existing-keywords').textContent = data.existing_keywords;
                    document.getElementById('db-status').textContent = '✅ Sẵn sàng';
                    document.getElementById('db-status').style.color = '#28a745';
                    
                    if (data.total_questions === 0) {
                        showAlert('warning', '⚠️ Không có câu hỏi nào trong database!');
                        document.getElementById('generate-btn').disabled = true;
                    }
                } else {
                    document.getElementById('db-status').textContent = '❌ Chưa sẵn sàng';
                    document.getElementById('db-status').style.color = '#dc3545';
                    showAlert('error', data.error);
                    document.getElementById('generate-btn').disabled = true;
                }
            })
            .catch(error => {
                showAlert('error', 'Lỗi kết nối: ' + error.message);
                document.getElementById('generate-btn').disabled = true;
            });
        }
        
        function generateKeywords() {
            if (!confirm('Bạn có chắc muốn tạo từ khóa cho tất cả câu hỏi?\n\nLưu ý: Các từ khóa tự động cũ sẽ bị xóa và tạo lại.')) {
                return;
            }
            
            document.getElementById('generate-btn').style.display = 'none';
            document.getElementById('loading').style.display = 'block';
            document.getElementById('results').style.display = 'none';
            document.getElementById('summary').style.display = 'none';
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=generate'
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loading').style.display = 'none';
                
                if (data.success) {
                    showAlert('success', `✅ Đã tạo thành công ${data.total_keywords_vi + data.total_keywords_en} từ khóa cho ${data.success_count} câu hỏi!`);
                    
                    // Hiển thị summary
                    document.getElementById('summary').style.display = 'block';
                    document.getElementById('summary-questions').textContent = data.success_count;
                    document.getElementById('summary-total').textContent = data.total_keywords_vi + data.total_keywords_en;
                    document.getElementById('summary-vi').textContent = data.total_keywords_vi;
                    document.getElementById('summary-en').textContent = data.total_keywords_en;
                    
                    // Hiển thị kết quả mẫu
                    if (data.results && data.results.length > 0) {
                        document.getElementById('results').style.display = 'block';
                        const container = document.getElementById('results-container');
                        container.innerHTML = '';
                        
                        data.results.forEach(result => {
                            const div = document.createElement('div');
                            div.className = 'result-item';
                            
                            let html = `
                                <h4>Câu hỏi #${result.id}: ${result.question}</h4>
                                <div style="color: #666; font-size: 13px; margin-top: 5px;">
                                    ${result.keywords_vi} từ khóa VI, ${result.keywords_en} từ khóa EN
                                </div>
                                <div class="keywords">
                            `;
                            
                            result.sample_vi.forEach(kw => {
                                html += `<span class="keyword-tag">🇻🇳 ${kw}</span>`;
                            });
                            
                            result.sample_en.forEach(kw => {
                                html += `<span class="keyword-tag en">🇬🇧 ${kw}</span>`;
                            });
                            
                            html += '</div>';
                            div.innerHTML = html;
                            container.appendChild(div);
                        });
                    }
                    
                    // Cập nhật trạng thái
                    checkStatus();
                    
                } else {
                    showAlert('error', '❌ Lỗi: ' + data.error);
                    document.getElementById('generate-btn').style.display = 'block';
                }
            })
            .catch(error => {
                document.getElementById('loading').style.display = 'none';
                document.getElementById('generate-btn').style.display = 'block';
                showAlert('error', 'Lỗi: ' + error.message);
            });
        }
        
        function showAlert(type, message) {
            const container = document.getElementById('alert-container');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            container.innerHTML = '';
            container.appendChild(alert);
            
            // Tự động ẩn sau 10 giây
            setTimeout(() => {
                alert.style.display = 'none';
            }, 10000);
        }
    </script>
</body>
</html>
