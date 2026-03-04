/**
 * CELRAS TVU - Auth JS
 * Xử lý đăng nhập Google
 */

const AUTH_API = '/DuAnChatbotThuVien/public/index.php?url=api';

/**
 * Đăng nhập bằng Google
 */
function loginWithGoogle() {
    window.location.href = `${AUTH_API}/auth/google`;
}

/**
 * Kiểm tra và redirect nếu đã đăng nhập
 */
async function checkAuthAndRedirect() {
    try {
        const res = await fetch(`${AUTH_API}/auth/check`);
        const data = await res.json();
        if (data.authenticated) {
            window.location.href = '/DuAnChatbotThuVien/public/pages/admin/dashboard.html';
        }
    } catch (e) {
        // Not authenticated, stay on login page
    }
}

/**
 * Kiểm tra đăng nhập (cho trang admin)
 */
async function checkAuth() {
    try {
        const res = await fetch(`${AUTH_API}/auth/check`);
        const data = await res.json();
        if (!data.authenticated) {
            window.location.href = '/DuAnChatbotThuVien/public/pages/login.html';
            return null;
        }
        return data.admin;
    } catch (e) {
        window.location.href = '/DuAnChatbotThuVien/public/pages/login.html';
        return null;
    }
}

/**
 * Đăng xuất
 */
async function logout() {
    try {
        await fetch(`${AUTH_API}/auth/logout`);
    } catch (e) {}
    window.location.href = '/DuAnChatbotThuVien/public/pages/login.html';
}
