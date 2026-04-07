<?php
/**
 * App Core - Router chính
 * Xử lý URL: /api/controller/method/params
 */
class App
{
    protected $controller = 'ChatController';
    protected $method = 'index';
    protected $params = [];

    public function __construct()
    {
        $url = $this->parseUrl();

        // Chỉ xử lý khi có /api/ prefix
        if (!$url || $url[0] !== 'api') {
            return;
        }

        // Bỏ 'api' prefix
        array_shift($url);

        if (empty($url)) {
            return;
        }

        // Controller
        $controllerName = ucfirst($url[0]) . 'Controller';
        $controllerFile = __DIR__ . '/../controllers/' . $controllerName . '.php';

        if (file_exists($controllerFile)) {
            $this->controller = $controllerName;
            unset($url[0]);
        } else {
            // Controller không tồn tại → 404
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Route not found'], JSON_UNESCAPED_UNICODE);
            return;
        }

        require_once $controllerFile;
        $this->controller = new $this->controller;

        // Method
        if (isset($url[1])) {
            if (method_exists($this->controller, $url[1])) {
                $this->method = $url[1];
                unset($url[1]);
            }
        }

        // Params
        $this->params = $url ? array_values($url) : [];

        // Call controller method
        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    protected function parseUrl()
    {
        if (isset($_GET['url'])) {
            // Loại bỏ query string nếu có
            $url = $_GET['url'];
            $url = strtok($url, '?'); // Lấy phần trước dấu ?
            return explode('/', filter_var(rtrim($url, '/'), FILTER_SANITIZE_URL));
        }
        return null;
    }
}
