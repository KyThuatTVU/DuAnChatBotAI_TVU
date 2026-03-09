<?php
/**
 * Script: Tạo từ khóa tự động cho tất cả câu hỏi hiện có
 * Chạy script này sau khi đã chạy migration database
 */

// Load config và dependencies
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/Database.php';
require_once __DIR__ . '/app/helpers/KeywordGenerator.php';

echo "=== TẠO TỪ KHÓA TỰ ĐỘNG CHO CÂU HỎI HIỆN CÓ ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Kiểm tra xem đã chạy migration chưa
    $checkColumn = $db->query("SHOW COLUMNS FROM keywords LIKE 'is_auto'");
    if ($checkColumn->rowCount() === 0) {
        die("❌ LỖI: Chưa chạy migration! Vui lòng chạy:\nmysql -u root -p chatbot_thuvien < database/migration_auto_keywords.sql\n\n");
    }
    
    echo "✅ Database đã sẵn sàng\n\n";
    
    // Lấy tất cả câu hỏi
    $stmt = $db->query("SELECT id, question_text FROM questions WHERE is_active = 1 ORDER BY id ASC");
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalQuestions = count($questions);
    echo "📊 Tìm thấy {$totalQuestions} câu hỏi cần xử lý\n\n";
    
    if ($totalQuestions === 0) {
        die("⚠️ Không có câu hỏi nào trong database!\n\n");
    }
    
    // Xác nhận trước khi chạy
    echo "⚠️ Script này sẽ:\n";
    echo "   1. Xóa tất cả từ khóa tự động cũ (is_auto = 1)\n";
    echo "   2. Tạo từ khóa mới cho {$totalQuestions} câu hỏi\n";
    echo "   3. Giữ nguyên từ khóa thủ công (is_auto = 0)\n\n";
    
    echo "Bạn có muốn tiếp tục? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    if (trim($line) !== 'y' && trim($line) !== 'Y') {
        die("❌ Đã hủy\n\n");
    }
    fclose($handle);
    
    echo "\n🚀 Bắt đầu xử lý...\n\n";
    
    // Xóa tất cả từ khóa tự động cũ
    $deleteStmt = $db->prepare("DELETE FROM keywords WHERE is_auto = 1");
    $deleteStmt->execute();
    $deletedCount = $deleteStmt->rowCount();
    echo "🗑️  Đã xóa {$deletedCount} từ khóa tự động cũ\n\n";
    
    // Prepare statement để insert từ khóa
    $insertStmt = $db->prepare(
        "INSERT INTO keywords (question_id, keyword, is_auto, language, created_at) 
         VALUES (?, ?, 1, ?, NOW())"
    );
    
    $successCount = 0;
    $errorCount = 0;
    $totalKeywordsVi = 0;
    $totalKeywordsEn = 0;
    
    // Xử lý từng câu hỏi
    foreach ($questions as $index => $question) {
        $questionId = $question['id'];
        $questionText = $question['question_text'];
        $progress = $index + 1;
        
        echo "[{$progress}/{$totalQuestions}] Xử lý câu hỏi #{$questionId}...\n";
        echo "   Câu hỏi: " . mb_substr($questionText, 0, 60) . (mb_strlen($questionText) > 60 ? '...' : '') . "\n";
        
        try {
            // Tạo từ khóa tự động
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
                    if ($e->getCode() !== '23000') { // Không phải duplicate key error
                        echo "   ⚠️ Lỗi khi lưu từ khóa VI '{$keyword}': " . $e->getMessage() . "\n";
                    }
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
                    if ($e->getCode() !== '23000') {
                        echo "   ⚠️ Lỗi khi lưu từ khóa EN '{$keyword}': " . $e->getMessage() . "\n";
                    }
                }
            }
            
            echo "   ✅ Đã tạo {$countVi} từ khóa VI, {$countEn} từ khóa EN\n";
            
            // Hiển thị một số từ khóa mẫu
            if ($countVi > 0) {
                $sampleVi = array_slice($autoKeywords['vi'], 0, 3);
                echo "   📝 Ví dụ VI: " . implode(', ', $sampleVi) . "\n";
            }
            if ($countEn > 0) {
                $sampleEn = array_slice($autoKeywords['en'], 0, 3);
                echo "   📝 Ví dụ EN: " . implode(', ', $sampleEn) . "\n";
            }
            
            $successCount++;
            
        } catch (Exception $e) {
            echo "   ❌ Lỗi: " . $e->getMessage() . "\n";
            $errorCount++;
        }
        
        echo "\n";
        
        // Nghỉ một chút để tránh quá tải
        if ($progress % 10 === 0) {
            usleep(100000); // 0.1 giây
        }
    }
    
    // Thống kê kết quả
    echo str_repeat("=", 70) . "\n";
    echo "🎉 HOÀN TẤT!\n\n";
    echo "📊 THỐNG KÊ:\n";
    echo "   • Tổng số câu hỏi: {$totalQuestions}\n";
    echo "   • Xử lý thành công: {$successCount}\n";
    echo "   • Lỗi: {$errorCount}\n";
    echo "   • Tổng từ khóa tiếng Việt: {$totalKeywordsVi}\n";
    echo "   • Tổng từ khóa tiếng Anh: {$totalKeywordsEn}\n";
    echo "   • Tổng cộng: " . ($totalKeywordsVi + $totalKeywordsEn) . " từ khóa\n\n";
    
    // Thống kê chi tiết
    echo "📈 THỐNG KÊ CHI TIẾT:\n";
    
    // Số câu hỏi có từ khóa
    $statsStmt = $db->query(
        "SELECT 
            COUNT(DISTINCT question_id) as questions_with_keywords,
            COUNT(*) as total_keywords,
            SUM(CASE WHEN language = 'vi' THEN 1 ELSE 0 END) as vi_keywords,
            SUM(CASE WHEN language = 'en' THEN 1 ELSE 0 END) as en_keywords,
            SUM(CASE WHEN is_auto = 1 THEN 1 ELSE 0 END) as auto_keywords,
            SUM(CASE WHEN is_auto = 0 THEN 1 ELSE 0 END) as manual_keywords
         FROM keywords"
    );
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   • Câu hỏi có từ khóa: {$stats['questions_with_keywords']}/{$totalQuestions}\n";
    echo "   • Từ khóa tự động: {$stats['auto_keywords']}\n";
    echo "   • Từ khóa thủ công: {$stats['manual_keywords']}\n";
    echo "   • Từ khóa tiếng Việt: {$stats['vi_keywords']}\n";
    echo "   • Từ khóa tiếng Anh: {$stats['en_keywords']}\n\n";
    
    // Top 10 từ khóa phổ biến nhất
    echo "🔥 TOP 10 TỪ KHÓA PHỔ BIẾN NHẤT:\n";
    $topStmt = $db->query(
        "SELECT keyword, language, COUNT(*) as count 
         FROM keywords 
         WHERE is_auto = 1 
         GROUP BY keyword, language 
         ORDER BY count DESC 
         LIMIT 10"
    );
    $topKeywords = $topStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($topKeywords as $i => $kw) {
        $lang = $kw['language'] === 'vi' ? '🇻🇳' : '🇬🇧';
        echo "   " . ($i + 1) . ". {$lang} {$kw['keyword']} ({$kw['count']} lần)\n";
    }
    
    echo "\n✅ Hoàn tất! Bạn có thể kiểm tra kết quả trong database.\n";
    echo "💡 Tip: Chạy test_keyword_generator.php để kiểm tra chất lượng từ khóa.\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ LỖI DATABASE: " . $e->getMessage() . "\n\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ LỖI: " . $e->getMessage() . "\n\n";
    exit(1);
}
