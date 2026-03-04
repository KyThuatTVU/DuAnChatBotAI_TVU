<?php
require_once __DIR__ . '/../core/BaseController.php';

class AuthController extends BaseController
{
    private $adminModel;

    public function __construct()
    {
        $this->adminModel = $this->model('AdminModel');
    }

    /**
     * GET /api/auth/google - Redirect đến Google OAuth
     */
    public function google()
    {
        $params = http_build_query([
            'client_id' => GOOGLE_CLIENT_ID,
            'redirect_uri' => GOOGLE_REDIRECT_URI,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);
        header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
        exit;
    }

    /**
     * GET /api/auth/google/callback - Google OAuth callback
     */
    public function callback()
    {
        if (!isset($_GET['code'])) {
            header('Location: ' . FRONTEND_URL . '/login.html?error=no_code');
            exit;
        }

        // Exchange code for access token
        $tokenData = $this->exchangeCode($_GET['code']);
        if (!$tokenData || !isset($tokenData['access_token'])) {
            header('Location: ' . FRONTEND_URL . '/login.html?error=token_failed');
            exit;
        }

        // Get user info from Google
        $userInfo = $this->getGoogleUserInfo($tokenData['access_token']);
        if (!$userInfo || !isset($userInfo['sub'])) {
            header('Location: ' . FRONTEND_URL . '/login.html?error=user_info_failed');
            exit;
        }

        // Upsert admin
        $adminId = $this->adminModel->upsertFromGoogle([
            'google_id' => $userInfo['sub'],
            'email' => $userInfo['email'],
            'full_name' => $userInfo['name'],
            'avatar_url' => $userInfo['picture'] ?? '',
        ]);

        // Check if admin is active
        $admin = $this->adminModel->getById($adminId);
        if (!$admin || !$admin['is_active']) {
            header('Location: ' . FRONTEND_URL . '/login.html?error=inactive');
            exit;
        }

        // Set session
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_name'] = $admin['full_name'];
        $_SESSION['admin_avatar'] = $admin['avatar_url'];
        $_SESSION['admin_role'] = $admin['role'];

        // Redirect to admin dashboard
        header('Location: ' . FRONTEND_URL . '/admin/dashboard.html');
        exit;
    }

    /**
     * GET /api/auth/check - Kiểm tra trạng thái đăng nhập
     */
    public function check()
    {
        if (isset($_SESSION['admin_id'])) {
            $this->json([
                'authenticated' => true,
                'admin' => [
                    'id' => $_SESSION['admin_id'],
                    'email' => $_SESSION['admin_email'],
                    'name' => $_SESSION['admin_name'],
                    'avatar' => $_SESSION['admin_avatar'],
                    'role' => $_SESSION['admin_role'],
                ],
            ]);
        } else {
            $this->json(['authenticated' => false]);
        }
    }

    /**
     * GET /api/auth/logout - Đăng xuất
     */
    public function logout()
    {
        session_destroy();
        $this->json(['success' => true, 'message' => 'Đã đăng xuất']);
    }

    /**
     * Exchange authorization code for tokens
     */
    private function exchangeCode($code)
    {
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'code' => $code,
                'client_id' => GOOGLE_CLIENT_ID,
                'client_secret' => GOOGLE_CLIENT_SECRET,
                'redirect_uri' => GOOGLE_REDIRECT_URI,
                'grant_type' => 'authorization_code',
            ]),
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    /**
     * Get Google user info
     */
    private function getGoogleUserInfo($accessToken)
    {
        $ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer $accessToken"],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }
}
