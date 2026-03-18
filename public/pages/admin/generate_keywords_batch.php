<?php
/**
 * Script tự động tạo từ khóa có trọng số cho tất cả câu hỏi
 * Chạy một lần để cập nhật dữ liệu hiện có
 */

// Load config
require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/config/Database.php';
require_once __DIR__ . '/../../../app/helpers/KeywordGenerator.php';

// Kết nối database
$db = Database::getInstance()->getConnection();

// Lấy tất cả câu hỏi active
$sql = "SELECT id, question_text FROM questions WHERE is_active = 1";
$stmt = $db->prepare($sql);
$stmt->execute();
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalQuestions = count($questions);
$processedCount = 0;
$errorCount = 0;

echo "=== BẮT ĐẦU TẠO TỪ KHÓA TỰ ĐỘNG ===\n";
echo "Tổng số câu hỏi: {$totalQuestions}\n\n";

foreach ($questions as $question) {
    $questionId = $question['id'];
    $questionText = $question['question_text'];
    
    try {
        // Xóa từ khóa tự động cũ (nếu có)
        $deleteSql = "DELETE FROM keywords WHERE question_id = ? AND is_auto = 1";
        $deleteStmt = $db->prepare($deleteSql);
        $deleteStmt->execute([$questionId]);
        
        // Tạo từ khóa mới
        $generated = KeywordGenerator::generate($questionText);
        
        // Lưu từ khóa tiếng Việt
        $insertSql = "INSERT INTO keywords (question_id, keyword, weight, is_auto, language) 
                      VALUES (?, ?, ?, 1, 'vi')";
        $insertStmt = $db->prepare($insertSql);
        
        foreach ($generated['vi'] as $item) {
            $insertStmt->execute([$questionId, $item['keyword'], $item['weight']]);
        }
        
        // Lưu từ khóa tiếng Anh
        if (!empty($generated['en'])) {
            $insertSqlEn = "INSERT INTO keywords (question_id, keyword, weight, is_auto, language) 
                            VALUES (?, ?, ?, 1, 'en')";
            $insertStmtEn = $db->prepare($insertSqlEn);
            
            foreach ($generated['en'] as $item) {
                $insertStmtEn->execute([$questionId, $item['keyword'], $item['weight']]);
            }
        }
        
        $processedCount++;
        
        // Hiển thị tiến trình
        if ($processedCount % 10 == 0) {
            $percent = round(($processedCount / $totalQuestions) * 100, 1);
            echo "Đã xử lý: {$processedCount}/{$totalQuestions} ({$percent}%)\n";
        }
        
    } catch (Exception $e) {
        $errorCount++;
        echo "LỖI tại câu hỏi ID {$questionId}: " . $e->getMessage() . "\n";
    }
}

echo "\n=== HOÀN THÀNH ===\n";
echo "Thành công: {$processedCount}/{$totalQuestions}\n";
echo "Lỗi: {$errorCount}\n";

// Cập nhật IDF cache
echo "\n=== CẬP NHẬT IDF CACHE ===\n";
try {
    $db->exec("CALL update_idf_cache()");
    echo "IDF cache đã được cập nhật!\n";
} catch (Exception $e) {
    echo "Lỗi cập nhật IDF cache: " . $e->getMessage() . "\n";
    echo "Bạn có thể chạy thủ công: CALL update_idf_cache();\n";
}

echo "\n✅ HOÀN TẤT!\n";
