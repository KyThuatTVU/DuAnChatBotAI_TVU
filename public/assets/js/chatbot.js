/**
 * CELRAS TVU Chatbot - Frontend JS
 * Xử lý giao diện chat phía người dùng
 */

const API_BASE = '/DuAnChatbotThuVien/public/index.php?url=api';
let sessionToken = localStorage.getItem('chat_session') || '';

/**
 * Khởi tạo chatbot
 */
async function initChatbot() {
    // Cập nhật lời chào theo thời gian
    updateGreeting();

    // Tải cài đặt & câu hỏi gợi ý từ server
    try {
        const res = await fetch(`${API_BASE}/chat`);
        if (res.ok) {
            const data = await res.json();

            // Áp dụng settings
            if (data.settings) {
                applySettings(data.settings);
            }

            // Hiển thị câu hỏi gợi ý
            if (data.suggestions && data.suggestions.length > 0) {
                renderSuggestions(data.suggestions);
            }
        }
    } catch (e) {
        console.warn('Could not load chatbot settings:', e);
    }

    // Focus vào input
    document.getElementById('chatInput')?.focus();
}

/**
 * Cập nhật lời chào theo thời gian trong ngày
 */
function updateGreeting() {
    const hour = new Date().getHours();
    let greeting = 'buổi sáng';
    if (hour >= 12 && hour < 18) greeting = 'buổi chiều';
    else if (hour >= 18) greeting = 'buổi tối';

    const el = document.getElementById('greetingText');
    if (el) {
        el.textContent = `CELRAS TVU chúc bạn ${greeting} nhiều niềm vui! 👋`;
    }
}

/**
 * Áp dụng cài đặt giao diện từ server
 */
function applySettings(settings) {
    if (!settings.enabled) {
        document.getElementById('chatArea').innerHTML = `
            <div class="text-center py-20">
                <p class="text-gray-500 text-lg">Chatbot hiện đang tạm ngưng hoạt động.</p>
                <p class="text-gray-400 text-sm mt-2">Vui lòng quay lại sau.</p>
            </div>`;
        return;
    }

    // Áp dụng CSS variables
    const root = document.documentElement;
    if (settings.primary_color) root.style.setProperty('--primary', settings.primary_color);
    if (settings.header_bg_color) root.style.setProperty('--primary-dark', settings.header_bg_color);
    if (settings.user_bubble_color) root.style.setProperty('--primary-light', settings.user_bubble_color);
}

/**
 * Render câu hỏi gợi ý
 */
function renderSuggestions(suggestions) {
    const container = document.getElementById('suggestionsContainer');
    if (!container) return;

    container.innerHTML = suggestions.map(s =>
        `<button class="suggestion-chip" onclick="sendSuggestion(this)">${escapeHtml(s.question_text)}</button>`
    ).join('');
}

/**
 * Gửi tin nhắn
 */
async function sendMessage() {
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    if (!message) return;

    // Ẩn welcome block & suggestions
    const welcome = document.getElementById('welcomeBlock');
    if (welcome) welcome.style.display = 'none';
    const suggestions = document.getElementById('suggestionsContainer');
    if (suggestions) suggestions.style.display = 'none';

    // Hiển thị tin nhắn người dùng
    appendMessage('user', message);
    input.value = '';
    updateCharCount();
    autoResize(input);
    document.getElementById('sendBtn').disabled = true;

    // Hiển thị typing indicator
    showTypingIndicator();

    try {
        const res = await fetch(`${API_BASE}/chat/send`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                message: message,
                session_token: sessionToken,
            }),
        });

        const data = await res.json();
        hideTypingIndicator();

        if (data.success) {
            sessionToken = data.session_token;
            localStorage.setItem('chat_session', sessionToken);
            appendMessage('bot', data.reply);
        } else {
            appendMessage('bot', data.error || 'Đã có lỗi xảy ra. Vui lòng thử lại.');
        }
    } catch (e) {
        hideTypingIndicator();
        appendMessage('bot', 'Không thể kết nối đến server. Vui lòng kiểm tra kết nối mạng.');
    }
}

/**
 * Gửi câu hỏi gợi ý
 */
function sendSuggestion(btn) {
    const input = document.getElementById('chatInput');
    input.value = btn.textContent;
    updateCharCount();
    sendMessage();
}

/**
 * Thêm tin nhắn vào giao diện chat
 */
function appendMessage(sender, text) {
    const container = document.getElementById('chatMessages');
    const avatarUrl = sender === 'bot'
        ? "https://ui-avatars.com/api/?name=CELRAS&background=0369a1&color=fff&rounded=true&size=36"
        : "https://ui-avatars.com/api/?name=User&background=64748b&color=fff&rounded=true&size=36";

    const msgHtml = `
        <div class="message ${sender}">
            <img src="${avatarUrl}" alt="${sender}" class="avatar">
            <div class="bubble">${escapeHtml(text)}</div>
        </div>`;
    container.insertAdjacentHTML('beforeend', msgHtml);
    
    // Scroll to bottom 
    container.scrollIntoView({ behavior: 'smooth', block: 'end' });
}

/**
 * Typing indicator
 */
function showTypingIndicator() {
    const container = document.getElementById('chatMessages');
    const html = `
        <div class="message bot" id="typingIndicator">
            <img src="https://ui-avatars.com/api/?name=CELRAS&background=0369a1&color=fff&rounded=true&size=36" alt="bot" class="avatar">
            <div class="bubble">
                <div class="typing-indicator">
                    <span></span><span></span><span></span>
                </div>
            </div>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
    container.scrollIntoView({ behavior: 'smooth', block: 'end' });
}

function hideTypingIndicator() {
    document.getElementById('typingIndicator')?.remove();
}

/**
 * Tạo cuộc trò chuyện mới
 */
async function startNewChat() {
    // Clear messages
    document.getElementById('chatMessages').innerHTML = '';
    
    // Show welcome & suggestions
    const welcome = document.getElementById('welcomeBlock');
    if (welcome) welcome.style.display = 'flex';
    const suggestions = document.getElementById('suggestionsContainer');
    if (suggestions) suggestions.style.display = 'flex';

    // Clear session
    sessionToken = '';
    localStorage.removeItem('chat_session');

    // Create new session
    try {
        const res = await fetch(`${API_BASE}/chat/newChat`, { method: 'POST' });
        const data = await res.json();
        if (data.success) {
            sessionToken = data.session_token;
            localStorage.setItem('chat_session', sessionToken);
        }
    } catch (e) {
        console.warn('Could not create new session');
    }
}

/**
 * Xử lý phím Enter gửi tin nhắn
 */
function handleKeyDown(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage();
    }
}

/**
 * Cập nhật đếm ký tự
 */
function updateCharCount() {
    const input = document.getElementById('chatInput');
    const count = document.getElementById('charCount');
    const sendBtn = document.getElementById('sendBtn');
    const len = input.value.length;
    
    count.textContent = `${len} / 3000`;
    sendBtn.disabled = len === 0;

    if (len > 2900) {
        count.style.color = '#ef4444';
    } else {
        count.style.color = '';
    }
}

/**
 * Auto resize textarea
 */
function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 100) + 'px';
}

/**
 * Escape HTML để tránh XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Logout
 */
async function logout() {
    try {
        await fetch(`${API_BASE}/auth/logout`);
    } catch (e) {}
    window.location.href = '/DuAnChatbotThuVien/public/pages/chatbot.html';
}
