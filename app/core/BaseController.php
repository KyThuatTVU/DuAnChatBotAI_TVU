<?php
/**
 * Base Controller
 */
class BaseController
{
    /**
     * Trả về JSON response
     */
    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Lấy dữ liệu JSON từ request body
     */
    protected function getJsonInput()
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }

    /**
     * Kiểm tra admin đã đăng nhập
     */
    protected function requireAuth()
    {
        if (!isset($_SESSION['admin_id'])) {
            $this->json(['error' => 'Unauthorized'], 401);
        }
        return $_SESSION['admin_id'];
    }

    /**
     * Load model
     */
    protected function model($model)
    {
        $modelFile = __DIR__ . '/../models/' . $model . '.php';
        if (file_exists($modelFile)) {
            require_once $modelFile;
            return new $model();
        }
        return null;
    }

    /**
     * Lấy request method
     */
    protected function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }
}
