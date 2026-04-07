/**
 * CELRAS TVU - Auth JS
 * Xử lý đăng nhập Google
 */

// Xác định đường dẫn API dựa trên vị trí hiện tại
const AUTH_API = (() => {
    const path = window.location.pathname;
    if (path.includes('/pages/admin/')) {
        return '../../index.php?url=api';
    } else if (path.includes('/pages/')) {
        return '../index.php?url=api';
    } else {
        return 'index.php?url=api';
    }
})();

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
            window.location.href = 'admin/dashboard.html';
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
            window.location.href = '../login.html';
            return null;
        }
        return data.admin;
    } catch (e) {
        window.location.href = '../login.html';
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
    
    // Xác định đường dẫn login dựa trên vị trí hiện tại
    const path = window.location.pathname;
    if (path.includes('/pages/admin/')) {
        window.location.href = '../login.html';
    } else {
        window.location.href = 'login.html';
    }
}
