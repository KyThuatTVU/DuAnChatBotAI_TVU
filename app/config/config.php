<?php
/**
 * Cấu hình chung - đọc từ file .env
 */

// ===== Load .env =====
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Bỏ qua comment
        if (strpos(trim($line), '#') === 0) continue;
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            // Bỏ dấu ngoặc kép nếu có
            $value = trim($value, '"\"');
            if (!empty($key)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

// Helper đọc env
function env(string $key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

// ===== Database =====
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_NAME', env('DB_NAME', 'chatbot_thuvien'));

// ===== URL =====
define('BASE_URL',     env('BASE_URL',     'http://localhost/DuAnChatbotThuVien/public'));
define('FRONTEND_URL', env('FRONTEND_URL', 'http://localhost/DuAnChatbotThuVien/public/pages'));

// ===== Google OAuth 2.0 =====
define('GOOGLE_CLIENT_ID',     env('GOOGLE_CLIENT_ID',     ''));
define('GOOGLE_CLIENT_SECRET', env('GOOGLE_CLIENT_SECRET', ''));
define('GOOGLE_REDIRECT_URI',  BASE_URL . '/index.php?url=api/auth/callback');

// ===== Upload =====
define('UPLOAD_DIR',     __DIR__ . '/../../public/uploads/');
define('MAX_FILE_SIZE',  (int) env('MAX_FILE_SIZE_MB', 10) * 1024 * 1024);

// ===== App =====
define('APP_NAME',  env('APP_NAME',  'CELRAS TVU Chatbot'));
define('APP_ENV',   env('APP_ENV',   'local'));
define('APP_DEBUG', env('APP_DEBUG', 'true') === 'true');
define('APP_VERSION', '1.0.0');

// Hiển thị lỗi chi tiết khi debug
if (APP_DEBUG) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}
