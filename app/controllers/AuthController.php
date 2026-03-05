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

        // Nâng kích thước ảnh Google từ 96px lên 400px
        $avatarUrl = $userInfo['picture'] ?? '';
        if ($avatarUrl && strpos($avatarUrl, '=s96-c') !== false) {
            $avatarUrl = str_replace('=s96-c', '=s400-c', $avatarUrl);
        }

        // Upsert admin
        $adminId = $this->adminModel->upsertFromGoogle([
            'google_id' => $userInfo['sub'],
            'email' => $userInfo['email'],
            'full_name' => $userInfo['name'],
            'avatar_url' => $avatarUrl,
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
            // Đọc lại avatar từ DB (đảm bảo luôn mới nhất)
            $avatar = $_SESSION['admin_avatar'] ?? '';
            if (empty($avatar)) {
                $admin = $this->adminModel->getById($_SESSION['admin_id']);
                if ($admin && !empty($admin['avatar_url'])) {
                    $avatar = $admin['avatar_url'];
                    $_SESSION['admin_avatar'] = $avatar;
                }
            }

            $this->json([
                'authenticated' => true,
                'admin' => [
                    'id' => $_SESSION['admin_id'],
                    'email' => $_SESSION['admin_email'],
                    'name' => $_SESSION['admin_name'],
                    'avatar' => $avatar,
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
     * POST /api/auth/login - Đăng nhập bằng email + mật khẩu
     */
    public function login()
    {
        if ($this->getMethod() !== 'POST') {
            $this->json(['error' => 'Method not allowed'], 405);
        }

        $input = $this->getJsonInput();
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';

        if (empty($email) || empty($password)) {
            $this->json(['error' => 'Vui lòng nhập email và mật khẩu'], 400);
        }

        $admin = $this->adminModel->findByEmail($email);
        if (!$admin) {
            $this->json(['error' => 'Email không tồn tại trong hệ thống'], 401);
        }

        if (empty($admin['password'])) {
            $this->json(['error' => 'Tài khoản chưa đặt mật khẩu. Vui lòng đăng nhập bằng Google rồi đặt mật khẩu trong Cài đặt.'], 401);
        }

        if (!password_verify($password, $admin['password'])) {
            $this->json(['error' => 'Mật khẩu không đúng'], 401);
        }

        if (!$admin['is_active']) {
            $this->json(['error' => 'Tài khoản đã bị vô hiệu hóa'], 403);
        }

        // Set session
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_name'] = $admin['full_name'];
        $_SESSION['admin_avatar'] = $admin['avatar_url'] ?? '';
        $_SESSION['admin_role'] = $admin['role'];

        $this->adminModel->updateLastLogin($admin['id']);

        $this->json([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'admin' => [
                'id' => $admin['id'],
                'email' => $admin['email'],
                'name' => $admin['full_name'],
                'avatar' => $admin['avatar_url'] ?? '',
                'role' => $admin['role'],
            ],
        ]);
    }

    /**
     * POST /api/auth/changePassword - Đổi mật khẩu (yêu cầu đăng nhập)
     */
    public function changePassword()
    {
        if ($this->getMethod() !== 'POST') {
            $this->json(['error' => 'Method not allowed'], 405);
        }

        $adminId = $this->requireAuth();
        $input = $this->getJsonInput();

        $currentPassword = $input['current_password'] ?? '';
        $newPassword = $input['new_password'] ?? '';
        $confirmPassword = $input['confirm_password'] ?? '';

        if (empty($newPassword) || empty($confirmPassword)) {
            $this->json(['error' => 'Vui lòng nhập mật khẩu mới'], 400);
        }

        if (strlen($newPassword) < 6) {
            $this->json(['error' => 'Mật khẩu mới phải có ít nhất 6 ký tự'], 400);
        }

        if ($newPassword !== $confirmPassword) {
            $this->json(['error' => 'Xác nhận mật khẩu không khớp'], 400);
        }

        $admin = $this->adminModel->getById($adminId);

        // Nếu đã có mật khẩu cũ → bắt nhập mật khẩu hiện tại
        if (!empty($admin['password'])) {
            if (empty($currentPassword)) {
                $this->json(['error' => 'Vui lòng nhập mật khẩu hiện tại'], 400);
            }
            if (!password_verify($currentPassword, $admin['password'])) {
                $this->json(['error' => 'Mật khẩu hiện tại không đúng'], 400);
            }
        }

        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->adminModel->updatePassword($adminId, $hashed);

        $this->json(['success' => true, 'message' => 'Đổi mật khẩu thành công']);
    }

    /**
     * POST /api/auth/forgotPassword - Quên mật khẩu (tạo token reset)
     */
    public function forgotPassword()
    {
        if ($this->getMethod() !== 'POST') {
            $this->json(['error' => 'Method not allowed'], 405);
        }

        $input = $this->getJsonInput();
        $email = trim($input['email'] ?? '');

        if (empty($email)) {
            $this->json(['error' => 'Vui lòng nhập email'], 400);
        }

        $result = $this->adminModel->createResetToken($email);

        if (!$result) {
            // Vẫn trả success để tránh lộ email có tồn tại hay không
            $this->json(['success' => true, 'message' => 'Nếu email tồn tại, link đặt lại mật khẩu sẽ được hiển thị.']);
        }

        $resetLink = FRONTEND_URL . '/login.html?reset_token=' . $result['token'];

        // Thử gửi email (nếu mail server có sẵn)
        $emailSent = @mail(
            $email,
            'Đặt lại mật khẩu - CELRAS TVU',
            "Xin chào {$result['admin']['full_name']},\n\nNhấn vào link sau để đặt lại mật khẩu (có hiệu lực 30 phút):\n{$resetLink}\n\nNếu bạn không yêu cầu đặt lại mật khẩu, hãy bỏ qua email này.",
            "From: noreply@celras-tvu.edu.vn\r\nContent-Type: text/plain; charset=UTF-8"
        );

        $this->json([
            'success' => true,
            'message' => $emailSent
                ? 'Link đặt lại mật khẩu đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư.'
                : 'Link đặt lại mật khẩu đã được tạo.',
            'reset_link' => !$emailSent ? $resetLink : null, // Chỉ hiện link nếu không gửi mail được
        ]);
    }

    /**
     * POST /api/auth/resetPassword - Đặt lại mật khẩu bằng token
     */
    public function resetPassword()
    {
        if ($this->getMethod() !== 'POST') {
            $this->json(['error' => 'Method not allowed'], 405);
        }

        $input = $this->getJsonInput();
        $token = $input['token'] ?? '';
        $newPassword = $input['new_password'] ?? '';
        $confirmPassword = $input['confirm_password'] ?? '';

        if (empty($token)) {
            $this->json(['error' => 'Token không hợp lệ'], 400);
        }

        if (empty($newPassword) || strlen($newPassword) < 6) {
            $this->json(['error' => 'Mật khẩu mới phải có ít nhất 6 ký tự'], 400);
        }

        if ($newPassword !== $confirmPassword) {
            $this->json(['error' => 'Xác nhận mật khẩu không khớp'], 400);
        }

        $admin = $this->adminModel->findByResetToken($token);
        if (!$admin) {
            $this->json(['error' => 'Token không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu lại.'], 400);
        }

        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->adminModel->updatePassword($admin['id'], $hashed);
        $this->adminModel->clearResetToken($admin['id']);

        $this->json(['success' => true, 'message' => 'Đặt lại mật khẩu thành công. Bạn có thể đăng nhập ngay.']);
    }

    /**
     * GET /api/auth/hasPassword - Kiểm tra admin hiện tại đã có mật khẩu chưa
     */
    public function hasPassword()
    {
        $adminId = $this->requireAuth();
        $admin = $this->adminModel->getById($adminId);

        $this->json([
            'has_password' => !empty($admin['password']),
        ]);
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
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POSTFIELDS => http_build_query([
                'code' => $code,
                'client_id' => GOOGLE_CLIENT_ID,
                'client_secret' => GOOGLE_CLIENT_SECRET,
                'redirect_uri' => GOOGLE_REDIRECT_URI,
                'grant_type' => 'authorization_code',
            ]),
        ]);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            error_log('Google OAuth exchangeCode cURL error: ' . curl_error($ch));
        }
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
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer $accessToken"],
        ]);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            error_log('Google OAuth getUserInfo cURL error: ' . curl_error($ch));
        }
        curl_close($ch);
        return json_decode($response, true);
    }
}
