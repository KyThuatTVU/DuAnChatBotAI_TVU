<?php
/**
 * Entry Point - Chatbot Thư Viện
 * CELRAS TVU
 */

// Autoload
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../app/core/',
        __DIR__ . '/../app/controllers/',
        __DIR__ . '/../app/models/',
        __DIR__ . '/../app/config/',
    ];
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Composer autoload (cho smalot/pdfparser)
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// Load config
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/helpers/functions.php';

// Start session
session_start();

// CORS headers for API
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Nếu không phải API request → redirect sang trang chatbot
if (!isset($_GET['url']) || strpos($_GET['url'], 'api') !== 0) {
    header('Location: ' . BASE_URL . '/pages/index.html');
    exit;
}

// Initialize App (Router)
$app = new App();
