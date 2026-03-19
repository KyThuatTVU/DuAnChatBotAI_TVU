<?php
/**
 * Script kiểm tra kết nối database
 * Chạy: php test_db_connection.php
 */

require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/Database.php';

echo "=== KIỂM TRA KẾT NỐI DATABASE ===\n\n";

try {
    echo "1. Đang kết nối database...\n";
    echo "   Host: " . DB_HOST . "\n";
    echo "   Database: " . DB_NAME . "\n";
    echo "   User: " . DB_USER . "\n\n";
    
    $db = Database::getInstance()->getConnection();
    echo "✓ Kết nối database thành công!\n\n";
    
    echo "2. Kiểm tra bảng questions...\n";
    $stmt = $db->query("SELECT COUNT(*) as total FROM questions");
    $result = $stmt->fetch();
    echo "✓ Tổng số câu hỏi: " . $result['total'] . "\n\n";
    
    echo "3. Kiểm tra bảng categories...\n";
    $stmt = $db->query("SELECT COUNT(*) as total FROM categories");
    $result = $stmt->fetch();
    echo "✓ Tổng số danh mục: " . $result['total'] . "\n\n";
    
    echo "4. Kiểm tra bảng keywords...\n";
    $stmt = $db->query("SELECT COUNT(*) as total FROM keywords");
    $result = $stmt->fetch();
    echo "✓ Tổng số từ khóa: " . $result['total'] . "\n\n";
    
    echo "5. Lấy 1 câu hỏi mẫu...\n";
    $stmt = $db->query("SELECT q.*, c.name as category_name FROM questions q LEFT JOIN categories c ON q.category_id = c.id LIMIT 1");
    $question = $stmt->fetch();
    if ($question) {
        echo "✓ ID: " . $question['id'] . "\n";
        echo "  Câu hỏi: " . substr($question['question_text'], 0, 50) . "...\n";
        echo "  Danh mục: " . ($question['category_name'] ?? 'Chưa phân loại') . "\n\n";
    } else {
        echo "⚠ Chưa có câu hỏi nào trong database\n\n";
    }
    
    echo "=== TẤT CẢ KIỂM TRA THÀNH CÔNG ===\n";
    
} catch (Exception $e) {
    echo "✗ LỖI: " . $e->getMessage() . "\n";
    echo "\nVui lòng kiểm tra:\n";
    echo "- File .env có đúng thông tin database không?\n";
    echo "- MySQL server đang chạy không?\n";
    echo "- Database 'chatbot_thuvien' đã được tạo chưa?\n";
    echo "- User có quyền truy cập database không?\n";
    exit(1);
}
