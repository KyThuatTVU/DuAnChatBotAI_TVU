<?php
/**
 * Trang kiểm tra kết nối Database
 */
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/Database.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiểm tra kết nối Database</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 40px 50px;
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        .icon { font-size: 60px; margin-bottom: 16px; }
        .success .icon { color: #22c55e; }
        .error .icon   { color: #ef4444; }
        h1 { font-size: 22px; margin-bottom: 12px; }
        .success h1 { color: #16a34a; }
        .error h1   { color: #dc2626; }
        .info {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            margin-top: 20px;
            text-align: left;
            font-size: 14px;
            color: #475569;
        }
        .info p { margin: 6px 0; }
        .info strong { color: #1e293b; }
        .error-msg {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 16px;
            margin-top: 20px;
            color: #991b1b;
            font-size: 14px;
        }
    </style>
</head>
<body>
<?php
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Thử truy vấn đơn giản
    $stmt = $conn->query("SELECT VERSION() AS version");
    $row = $stmt->fetch();
    $mysqlVersion = $row['version'] ?? 'N/A';

    // Lấy tên database hiện tại
    $stmt2 = $conn->query("SELECT DATABASE() AS db_name");
    $row2 = $stmt2->fetch();
    $dbName = $row2['db_name'] ?? 'N/A';
?>
    <div class="card success">
        <div class="icon">&#10004;</div>
        <h1>Kết nối Database thành công!</h1>
        <p style="color:#64748b;">Ứng dụng đã kết nối tới cơ sở dữ liệu.</p>
        <div class="info">
            <p><strong>Host:</strong> <?= htmlspecialchars(DB_HOST) ?></p>
            <p><strong>Database:</strong> <?= htmlspecialchars($dbName) ?></p>
            <p><strong>User:</strong> <?= htmlspecialchars(DB_USER) ?></p>
            <p><strong>MySQL Version:</strong> <?= htmlspecialchars($mysqlVersion) ?></p>
            <p><strong>PHP Version:</strong> <?= phpversion() ?></p>
            <p><strong>Thời gian:</strong> <?= date('d/m/Y H:i:s') ?></p>
        </div>
    </div>
<?php
} catch (Exception $e) {
?>
    <div class="card error">
        <div class="icon">&#10008;</div>
        <h1>Kết nối Database thất bại!</h1>
        <div class="error-msg">
            <strong>Lỗi:</strong> <?= htmlspecialchars($e->getMessage()) ?>
        </div>
    </div>
<?php } ?>
</body>
</html>
