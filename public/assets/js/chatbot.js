/**
 * CELRAS TVU Chatbot - Frontend JS
 * Xử lý giao diện chat phía người dùng
 */

const API_BASE = '/DuAnChatbotThuVien/public/index.php?url=api';
let sessionToken = localStorage.getItem('chat_session') || '';
let activeCategoryId = null; // Danh mục đang mở

// ==================== CHAT HISTORY (SESSION) ====================
// Lưu lịch sử trò chuyện vào sessionStorage để khôi phục khi load lại trang.
// sessionStorage tự xóa khi đóng tab — phù hợp với "phiên làm việc" hiện tại.

const ChatHistory = {
    KEY: 'celras_chat_history',
    TOKEN_KEY: 'celras_chat_session_bak',

    /** Thêm một tin nhắn vào lịch sử và lưu vào sessionStorage */
    push(sender, text, forms = []) {
        const history = this._load();
        history.push({ sender, text, forms, ts: Date.now() });
        try {
            sessionStorage.setItem(this.KEY, JSON.stringify(history));
            // Luôn đồng bộ session token theo lịch sử
            if (sessionToken) sessionStorage.setItem(this.TOKEN_KEY, sessionToken);
        } catch(e) {
            // sessionStorage đầy (hiếm) — xóa tin cũ nhất rồi thử lại
            if (history.length > 1) {
                history.shift();
                try { sessionStorage.setItem(this.KEY, JSON.stringify(history)); } catch(_) {}
            }
        }
    },

    /** Khôi phục lịch sử vào DOM, trả về số tin nhắn đã khôi phục */
    restore() {
        const history = this._load();
        if (!history.length) return 0;

        // Khôi phục session token từ backup nếu localStorage đã mất
        if (!sessionToken) {
            const bak = sessionStorage.getItem(this.TOKEN_KEY);
            if (bak) {
                sessionToken = bak;
                localStorage.setItem('chat_session', bak);
            }
        }

        // Ẩn welcome block & suggestions
        const welcome = document.getElementById('welcomeBlock');
        if (welcome) welcome.style.display = 'none';
        const suggestions = document.getElementById('suggestionsContainer');
        if (suggestions) suggestions.style.display = 'none';

        // Render lại từng tin nhắn (không gọi push để tránh ghi đè)
        history.forEach(msg => _renderMessage(msg.sender, msg.text, msg.forms || []));

        // Scroll xuống cuối
        const container = document.getElementById('chatMessages');
        if (container) requestAnimationFrame(() => { container.scrollTop = container.scrollHeight; });

        return history.length;
    },

    /** Xóa toàn bộ lịch sử (gọi khi bắt đầu cuộc trò chuyện mới) */
    clear() {
        sessionStorage.removeItem(this.KEY);
        sessionStorage.removeItem(this.TOKEN_KEY);
    },

    _load() {
        try {
            const raw = sessionStorage.getItem(this.KEY);
            return raw ? JSON.parse(raw) : [];
        } catch(e) { return []; }
    },

    /** Số tin nhắn đang được lưu */
    get count() { return this._load().length; }
};

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

            // Áp dụng theme sự kiện
            if (data.theme && data.theme.theme_key && data.theme.theme_key !== 'mac-dinh') {
                applyEventTheme(data.theme);
            }

            // Hiển thị câu hỏi gợi ý
            if (data.suggestions && data.suggestions.length > 0) {
                renderSuggestions(data.suggestions);
            }
        }
    } catch (e) {
        console.warn('Could not load chatbot settings:', e);
    }

    // Tải danh mục câu hỏi
    loadCategories();

    // Focus vào input
    document.getElementById('chatInput')?.focus();

    // Khôi phục lịch sử trò chuyện từ phiên làm việc hiện tại
    ChatHistory.restore();

    // Cập nhật trạng thái nút micro theo khả năng hỗ trợ
    setupVoiceButtonSupport();
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
                <p class="text-gray-500 text-lg">${typeof t === 'function' ? t('chatbot_disabled') : 'Chatbot hiện đang tạm ngưng hoạt động.'}</p>
                <p class="text-gray-400 text-sm mt-2">${typeof t === 'function' ? t('come_back_later') : 'Vui lòng quay lại sau.'}</p>
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
 * Áp dụng chủ đề sự kiện (theme 3D)
 */
function applyEventTheme(theme) {
    if (!theme || !theme.theme_key || theme.theme_key === 'mac-dinh') return;

    const themeClass = 'theme-' + theme.theme_key;

    // Xóa các theme class cũ (nếu có)
    document.body.className = document.body.className
        .replace(/\btheme-[\w-]+\b/g, '')
        .trim();

    // Thêm theme class
    document.body.classList.add(themeClass);

    // Thêm decorations (emoji bay)
    const decorations = theme.decorations;
    if (decorations && Array.isArray(decorations) && decorations.length > 0) {
        // Xóa decorations cũ nếu có
        const oldDeco = document.getElementById('themeDecorations');
        if (oldDeco) oldDeco.remove();

        const decoContainer = document.createElement('div');
        decoContainer.id = 'themeDecorations';
        decoContainer.className = 'theme-decorations';
        decoContainer.setAttribute('aria-hidden', 'true');

        decorations.forEach(emoji => {
            const span = document.createElement('span');
            span.className = 'deco';
            span.textContent = emoji;
            decoContainer.appendChild(span);
        });

        document.body.appendChild(decoContainer);
    }

    // Thêm banner sự kiện
    const bannerText = theme.banner_text;
    if (bannerText) {
        const chatMessages = document.querySelector('#chatMessages .max-w-3xl');
        if (chatMessages) {
            // Xóa banner cũ
            const oldBanner = document.getElementById('themeBanner');
            if (oldBanner) oldBanner.remove();

            const banner = document.createElement('div');
            banner.id = 'themeBanner';
            banner.className = 'theme-banner';
            banner.textContent = bannerText;
            chatMessages.insertBefore(banner, chatMessages.firstChild);
        }
    }

    // Override welcome message nếu theme có
    if (theme.welcome_message) {
        const welcomeTitle = document.querySelector('#welcomeBlock h2 [data-i18n="welcome_title"]');
        // Không override title, chỉ log
    }
}

/**
 * Render câu hỏi gợi ý
 */
function renderSuggestions(suggestions) {
    const container = document.getElementById('suggestionsContainer');
    if (!container) return;

    container.innerHTML = suggestions.map(s =>
        `<button class="suggestion-chip" onclick="sendSuggestion(this)">${escapeHtml(stripLeadingNumber(s.question_text))}</button>`
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
    const typingStart = Date.now();
    const MIN_TYPING_MS = 1500; // Hiệu ứng typing tối thiểu 1.5 giây

    try {
        const lang = (typeof currentLang !== 'undefined') ? currentLang : 'vi';
        const res = await fetch(`${API_BASE}/chat/send`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                message: message,
                session_token: sessionToken,
                lang: lang,
            }),
        });

        const data = await res.json();

        // Đợi đủ thời gian tối thiểu để typing indicator hiển thị tự nhiên
        const elapsed = Date.now() - typingStart;
        if (elapsed < MIN_TYPING_MS) {
            await new Promise(r => setTimeout(r, MIN_TYPING_MS - elapsed));
        }
        hideTypingIndicator();

        if (data.success) {
            sessionToken = data.session_token;
            localStorage.setItem('chat_session', sessionToken);
            console.log('Chat response forms:', data.forms);
            
            // Hiển thị câu trả lời
            appendMessage('bot', data.reply, data.forms || []);
            
            // Nếu có câu hỏi liên quan, hiển thị dạng gợi ý để người dùng chọn
            if (data.related_questions && data.related_questions.length > 0) {
                appendRelatedQuestions(data.related_questions);
            }
        } else {
            appendMessage('bot', data.error || (typeof t === 'function' ? t('error_occurred') : 'Đã có lỗi xảy ra. Vui lòng thử lại.'));
        }
    } catch (e) {
        const elapsed = Date.now() - typingStart;
        if (elapsed < MIN_TYPING_MS) {
            await new Promise(r => setTimeout(r, MIN_TYPING_MS - elapsed));
        }
        hideTypingIndicator();
        appendMessage('bot', typeof t === 'function' ? t('cannot_connect') : 'Không thể kết nối đến server. Vui lòng kiểm tra kết nối mạng.');
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
 * Thêm tin nhắn vào giao diện chat và lưu vào lịch sử phiên làm việc
 * @param {string} sender 'user' | 'bot'
 * @param {string} text   Nội dung tin nhắn
 * @param {Array}  forms  Danh sách biểu mẫu kèm (chỉ dùng cho bot)
 */
function appendMessage(sender, text, forms = []) {
    // Lưu vào lịch sử trước khi render (để khôi phục khi load lại)
    ChatHistory.push(sender, text, forms);
    _renderMessage(sender, text, forms);
}

/**
 * Chỉ render tin nhắn lên DOM (không lưu lịch sử — dùng khi khôi phục)
 * @param {string} sender
 * @param {string} text
 * @param {Array}  forms
 */
function _renderMessage(sender, text, forms = []) {
    const container = document.getElementById('chatMessages');
    const avatarUrl = sender === 'bot'
        ? "/DuAnChatbotThuVien/public/assets/images/logo1.png"
        : "/DuAnChatbotThuVien/public/assets/images/US.jpg";

    // Render nội dung text
    let safeText;
    if (sender === 'bot') {
        // Bot message: kiểm tra xem có phải HTML từ Quill không
        const hasHtmlTags = /<\/?[a-z][\s\S]*>/i.test(text);
        
        if (hasHtmlTags) {
            // Có HTML tags → kiểm tra xem có formatting thực sự không
            const hasFormatting = /<(strong|em|u|b|i|ul|ol|h1|h2|h3|blockquote|code)/.test(text);
            
            if (hasFormatting) {
                // Có formatting thực sự → giữ HTML
                safeText = text;
            } else {
                // Chỉ có <p> đơn giản → lấy text thuần
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = text;
                safeText = tempDiv.textContent.replace(/\n/g, '<br>');
            }
        } else {
            // Text thuần → giữ xuống dòng
            safeText = escapeHtml(text).replace(/\n/g, '<br>');
        }
    } else {
        // User message → luôn escape
        safeText = escapeHtml(text).replace(/\n/g, '<br>');
    }

    // Render form links nếu có
    let formsHtml = '';
    if (sender === 'bot' && forms && forms.length > 0) {
        formsHtml = `
            <div class="form-links">
                ${forms.map(f => `
                <a href="${escapeHtml(f.url)}" target="_blank" rel="noopener noreferrer" class="form-link">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586
                                 a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>${escapeHtml(f.name)}</span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                         style="width:14px;height:14px;flex-shrink:0;opacity:0.6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </a>`).join('')}
            </div>`;
    }

    const msgHtml = `
        <div class="message ${sender}">
            <img src="${avatarUrl}" alt="${sender}" class="avatar">
            <div class="bubble">${safeText}${formsHtml}</div>
        </div>`;
    container.insertAdjacentHTML('beforeend', msgHtml);

    // Scroll to bottom
    requestAnimationFrame(() => {
        container.scrollTop = container.scrollHeight;
    });
}

/**
 * Typing indicator
 */
function showTypingIndicator() {
    const container = document.getElementById('chatMessages');
    const typingLabel = (typeof t === 'function') ? t('typing') : 'Đang trả lời';
    const html = `
        <div class="message bot" id="typingIndicator">
            <img src="/DuAnChatbotThuVien/public/assets/images/logo1.png" alt="bot" class="avatar">
            <div class="bubble">
                <div class="typing-wrapper">
                    <div class="typing-indicator">
                        <span></span><span></span><span></span>
                    </div>
                    <span class="typing-text">${typingLabel}...</span>
                </div>
            </div>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
    requestAnimationFrame(() => {
        container.scrollTop = container.scrollHeight;
    });
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

    // Reset danh mục sidebar
    closeCategoryQuestions();

    // Xóa lịch sử phiên làm việc
    ChatHistory.clear();

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
 * Loại bỏ số thứ tự đầu câu hỏi
 * VD: "1. Thư viện mở cửa..." → "Thư viện mở cửa..."
 *     "2) Cách mượn sách" → "Cách mượn sách"
 *     "10: Quy định" → "Quy định"
 */
function stripLeadingNumber(text) {
    if (!text) return text;
    return text.replace(/^\s*\d+[\s.):;-]+\s*/, '');
}

// ===== DANH MỤC CÂU HỎI (SIDEBAR) =====

/** SVG icon paths for categories (outline style) */
const categorySVGs = [
    '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
    '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>',
    '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>',
    '<path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/>',
    '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/>',
    '<path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/>',
];

/** Color palette for category icons (light ocean blue) */
const categoryColors = [
    { bg: 'rgba(14,165,233,0.1)', color: '#0284c7' },
    { bg: 'rgba(16,185,129,0.1)', color: '#059669' },
    { bg: 'rgba(245,158,11,0.1)', color: '#d97706' },
    { bg: 'rgba(236,72,153,0.1)', color: '#db2777' },
    { bg: 'rgba(139,92,246,0.1)', color: '#7c3aed' },
    { bg: 'rgba(6,182,212,0.1)', color: '#0891b2' },
];

/**
 * Tải danh mục từ server
 */
async function loadCategories() {
    try {
        const res = await fetch(`${API_BASE}/chat/categories`);
        if (res.ok) {
            const data = await res.json();
            if (data.success && data.categories && data.categories.length > 0) {
                renderCategories(data.categories);
                // Chỉ tự mở sidebar trên desktop (> 768px)
                const sidebar = document.getElementById('categorySidebar');
                if (sidebar && window.innerWidth > 768) {
                    sidebar.classList.remove('sidebar-hidden');
                }
            }
        }
    } catch (e) {
        console.warn('Could not load categories:', e);
    }
}

/**
 * Toggle sidebar
 */
function toggleSidebar() {
    const sidebar = document.getElementById('categorySidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (!sidebar) return;

    const isHidden = sidebar.classList.contains('sidebar-hidden');

    if (isHidden) {
        // Mở sidebar
        sidebar.classList.remove('sidebar-hidden');
        if (overlay) overlay.classList.add('active');
    } else {
        // Đóng sidebar
        sidebar.classList.add('sidebar-hidden');
        if (overlay) overlay.classList.remove('active');
    }
}

/**
 * Render danh mục dạng danh sách dọc trong sidebar
 */
function renderCategories(categories) {
    const container = document.getElementById('categoryList');
    if (!container) return;

    container.innerHTML = categories.map((cat, idx) => {
        const svgPath = categorySVGs[idx % categorySVGs.length];
        const palette = categoryColors[idx % categoryColors.length];
        const count = cat.question_count || 0;
        return `
            <div>
                <div class="category-item" onclick="openCategory(${cat.id}, this)" data-cat-id="${cat.id}">
                    <div class="cat-icon-wrap" style="background:${palette.bg};color:${palette.color}">
                        <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">${svgPath}</svg>
                    </div>
                    <div class="cat-info">
                        <div class="cat-name" data-name-vi="${escapeHtml(cat.name)}">${typeof translateCatName === 'function' ? translateCatName(cat.name) : escapeHtml(cat.name)}</div>
                        <div class="cat-count" data-count="${count}">${count} ${typeof t === 'function' ? t('questions') : 'câu hỏi'}</div>
                    </div>
                    <svg class="cat-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
                <div class="category-questions" id="catQuestions_${cat.id}"></div>
            </div>`;
    }).join('');
}

/**
 * Mở danh mục → tải câu hỏi inline bên dưới
 */
async function openCategory(categoryId, itemEl) {
    const questionsDiv = document.getElementById(`catQuestions_${categoryId}`);

    // Nếu bấm lại danh mục đang mở → đóng
    if (activeCategoryId === categoryId) {
        closeCategoryQuestions();
        return;
    }

    // Đóng danh mục cũ
    closeCategoryQuestions();
    activeCategoryId = categoryId;

    // Highlight item
    if (itemEl) itemEl.classList.add('active');

    // Hiện panel câu hỏi
    if (questionsDiv) {
        questionsDiv.classList.add('open');
        questionsDiv.innerHTML = `
            <div class="flex items-center justify-center py-4 text-gray-400 text-xs gap-2">
                <svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                ${typeof t === 'function' ? t('loading') : 'Đang tải...'}
            </div>`;
    }

    try {
        const res = await fetch(`${API_BASE}/chat/categoryQuestions/${categoryId}`);
        const data = await res.json();

        if (data.success && questionsDiv) {
            if (data.questions.length === 0) {
                questionsDiv.innerHTML = `
                    <div class="text-center py-3 text-gray-400 text-xs">
                        ${typeof t === 'function' ? t('no_questions') : 'Chưa có câu hỏi nào.'}
                    </div>`;
            } else {
                questionsDiv.innerHTML = data.questions.map(q =>
                    `<div class="cat-question-item" onclick="askCategoryQuestion(this)" data-question="${escapeHtml(stripLeadingNumber(q.question_text))}" data-question-vi="${escapeHtml(stripLeadingNumber(q.question_text))}"${q.question_text_en ? ` data-question-en="${escapeHtml(stripLeadingNumber(q.question_text_en))}"` : ''}>
                        <span class="q-dot"></span>
                        <span class="q-text">${escapeHtml(stripLeadingNumber(q.question_text))}</span>
                    </div>`
                ).join('');
            }
        } else if (questionsDiv) {
            questionsDiv.innerHTML = `
                <div class="text-center py-3 text-red-400 text-xs">
                    ${data.error || (typeof t === 'function' ? t('cannot_load') : 'Không thể tải.')}
                </div>`;
        }
    } catch (e) {
        if (questionsDiv) {
            questionsDiv.innerHTML = `
                <div class="text-center py-3 text-red-400 text-xs">
                    ${typeof t === 'function' ? t('connection_error') : 'Lỗi kết nối.'}
                </div>`;
        }
    }
}

/**
 * Đóng panel câu hỏi danh mục
 */
function closeCategoryQuestions() {
    activeCategoryId = null;
    document.querySelectorAll('.category-item').forEach(c => c.classList.remove('active'));
    document.querySelectorAll('.category-questions').forEach(c => {
        c.classList.remove('open');
        c.innerHTML = '';
    });
}

/**
 * Gửi câu hỏi từ danh mục vào chatbot
 */
function askCategoryQuestion(el) {
    const question = el.getAttribute('data-question');
    if (!question) return;

    // Trên mobile: đóng sidebar sau khi chọn câu hỏi
    if (window.innerWidth <= 768) {
        toggleSidebar();
    }

    const input = document.getElementById('chatInput');
    input.value = question;
    updateCharCount();
    sendMessage();
}

/**
 * Logout
 */
async function logout() {
    try {
        await fetch(`${API_BASE}/auth/logout`);
    } catch (e) {}
    window.location.href = '/DuAnChatbotThuVien/public/pages/index.html';
}

// ===== VOICE INPUT (SPEECH RECOGNITION) =====

let recognition = null;
let isListening = false;

function getSpeechRecognitionClass() {
    return window.SpeechRecognition || window.webkitSpeechRecognition || null;
}

function isSecureSpeechContext() {
    if (window.isSecureContext) return true;
    const host = window.location.hostname;
    return host === 'localhost' || host === '127.0.0.1';
}

function setVoiceButtonDisabled(disabled, title) {
    const voiceBtn = document.getElementById('voiceBtn');
    if (!voiceBtn) return;

    voiceBtn.disabled = !!disabled;
    voiceBtn.setAttribute('aria-disabled', disabled ? 'true' : 'false');

    if (title) voiceBtn.title = title;
    if (disabled) {
        voiceBtn.style.opacity = '0.55';
        voiceBtn.style.cursor = 'not-allowed';
    } else {
        voiceBtn.style.opacity = '';
        voiceBtn.style.cursor = '';
    }
}

function setupVoiceButtonSupport() {
    const SpeechRecognition = getSpeechRecognitionClass();
    if (!SpeechRecognition) {
        const msg = typeof t === 'function' ? t('voice_not_supported') : 'Trình duyệt không hỗ trợ nhận diện giọng nói';
        setVoiceButtonDisabled(true, msg);
        return;
    }
    if (!isSecureSpeechContext()) {
        const msg = typeof t === 'function' ? t('voice_requires_https') : 'Tìm kiếm giọng nói chỉ hoạt động trên HTTPS hoặc localhost.';
        setVoiceButtonDisabled(true, msg);
        return;
    }
    setVoiceButtonDisabled(false);
}

/**
 * Khởi tạo Speech Recognition API
 */
function initSpeechRecognition(SpeechRecognitionClass) {
    const SpeechRecognition = SpeechRecognitionClass || getSpeechRecognitionClass();
    if (!SpeechRecognition) return null;

    recognition = new SpeechRecognition();
    
    // Cấu hình
    recognition.continuous = false; // Dừng sau khi nhận được kết quả
    recognition.interimResults = true; // Hiển thị kết quả tạm thời
    
    // Tự động chọn ngôn ngữ dựa trên currentLang
    const lang = (typeof currentLang !== 'undefined' && currentLang === 'en') ? 'en-US' : 'vi-VN';
    recognition.lang = lang;

    // Xử lý kết quả
    recognition.onresult = (event) => {
        let transcript = '';
        let isFinal = false;

        for (let i = event.resultIndex; i < event.results.length; i++) {
            transcript += event.results[i][0].transcript;
            if (event.results[i].isFinal) {
                isFinal = true;
            }
        }

        // Cập nhật input với kết quả
        const input = document.getElementById('chatInput');
        if (input) {
            input.value = transcript;
            updateCharCount();
            autoResize(input);
        }

        // Nếu là kết quả cuối cùng, tự động gửi tin nhắn
        if (isFinal && transcript.trim()) {
            stopVoiceInput();
            // Delay nhỏ để người dùng thấy text trước khi gửi
            setTimeout(() => {
                sendMessage();
            }, 300);
        }
    };

    // Xử lý lỗi
    recognition.onerror = (event) => {
        console.error('Speech recognition error:', event.error);
        stopVoiceInput();
        
        let errorMsg = typeof t === 'function' ? t('voice_error') : 'Lỗi nhận diện giọng nói';
        
        if (event.error === 'no-speech') {
            errorMsg = typeof t === 'function' ? t('voice_no_speech') : 'Không nhận được giọng nói. Vui lòng thử lại.';
        } else if (event.error === 'not-allowed' || event.error === 'service-not-allowed') {
            if (!isSecureSpeechContext()) {
                errorMsg = typeof t === 'function' ? t('voice_requires_https') : 'Tìm kiếm giọng nói chỉ hoạt động trên HTTPS hoặc localhost.';
            } else {
                errorMsg = typeof t === 'function'
                    ? t('voice_permission_denied')
                    : 'Quyền truy cập microphone bị từ chối. Vui lòng cho phép quyền truy cập.';
            }
        } else if (event.error === 'audio-capture') {
            errorMsg = typeof t === 'function'
                ? t('voice_audio_capture')
                : 'Không tìm thấy microphone. Vui lòng kiểm tra thiết bị.';
        } else if (event.error === 'network') {
            errorMsg = typeof t === 'function'
                ? t('voice_network_error')
                : 'Lỗi mạng khi nhận diện giọng nói. Vui lòng thử lại.';
        }
        
        // Hiển thị thông báo lỗi
        showVoiceError(errorMsg);
    };

    // Khi kết thúc
    recognition.onend = () => {
        stopVoiceInput();
    };

    return recognition;
}

/**
 * Bật/tắt nhận diện giọng nói
 */
function toggleVoiceInput() {
    if (isListening) {
        stopVoiceInput();
    } else {
        startVoiceInput();
    }
}

/**
 * Bắt đầu nhận diện giọng nói
 */
function startVoiceInput() {
    const SpeechRecognition = getSpeechRecognitionClass();
    if (!SpeechRecognition) {
        const msg = typeof t === 'function' ? t('voice_not_supported') : 'Trình duyệt không hỗ trợ nhận diện giọng nói';
        showVoiceError(msg);
        setVoiceButtonDisabled(true, msg);
        return;
    }
    if (!isSecureSpeechContext()) {
        const msg = typeof t === 'function' ? t('voice_requires_https') : 'Tìm kiếm giọng nói chỉ hoạt động trên HTTPS hoặc localhost.';
        showVoiceError(msg);
        setVoiceButtonDisabled(true, msg);
        return;
    }

    // Khởi tạo recognition nếu chưa có
    if (!recognition) {
        recognition = initSpeechRecognition(SpeechRecognition);
    }

    if (!recognition) return;

    // Cập nhật ngôn ngữ theo currentLang
    const lang = (typeof currentLang !== 'undefined' && currentLang === 'en') ? 'en-US' : 'vi-VN';
    recognition.lang = lang;

    try {
        recognition.start();
        isListening = true;
        
        // Cập nhật UI
        const voiceBtn = document.getElementById('voiceBtn');
        if (voiceBtn) {
            voiceBtn.classList.add('listening');
            voiceBtn.title = typeof t === 'function' ? t('voice_listening') : 'Đang nghe...';
        }

        // Clear input để sẵn sàng nhận giọng nói mới
        const input = document.getElementById('chatInput');
        if (input) {
            input.value = '';
            updateCharCount();
        }
    } catch (e) {
        console.error('Failed to start speech recognition:', e);
        stopVoiceInput();
    }
}

/**
 * Dừng nhận diện giọng nói
 */
function stopVoiceInput() {
    if (recognition && isListening) {
        try {
            recognition.stop();
        } catch (e) {
            console.warn('Error stopping recognition:', e);
        }
    }
    
    isListening = false;
    
    // Cập nhật UI
    const voiceBtn = document.getElementById('voiceBtn');
    if (voiceBtn) {
        voiceBtn.classList.remove('listening');
        voiceBtn.title = typeof t === 'function' ? t('voice_search') : 'Tìm kiếm bằng giọng nói';
        voiceBtn.style.background = 'linear-gradient(145deg, #f0f9ff, #e0f2fe)';
    }
}

/**
 * Hiển thị thông báo lỗi giọng nói
 */
function showVoiceError(message) {
    // Tạo toast notification
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed; bottom: 100px; left: 50%; transform: translateX(-50%);
        background: linear-gradient(145deg, #fee2e2, #fecaca);
        border: 1px solid #fca5a5; border-left: 4px solid #ef4444;
        border-radius: 12px; padding: 12px 20px; max-width: 400px;
        box-shadow: 0 10px 25px rgba(239, 68, 68, 0.2);
        animation: voiceErrorSlideIn 0.3s ease-out;
        font-family: 'Inter', sans-serif;
        z-index: 9999;
        color: #991b1b;
        font-size: 14px;
        font-weight: 500;
    `;
    toast.textContent = message;

    // Animation
    if (!document.getElementById('voiceErrorStyles')) {
        const style = document.createElement('style');
        style.id = 'voiceErrorStyles';
        style.textContent = `
            @keyframes voiceErrorSlideIn {
                from { opacity: 0; transform: translateX(-50%) translateY(20px); }
                to { opacity: 1; transform: translateX(-50%) translateY(0); }
            }
            @keyframes voiceErrorSlideOut {
                from { opacity: 1; transform: translateX(-50%) translateY(0); }
                to { opacity: 0; transform: translateX(-50%) translateY(20px); }
            }
        `;
        document.head.appendChild(style);
    }

    document.body.appendChild(toast);

    // Tự động ẩn sau 3 giây
    setTimeout(() => {
        toast.style.animation = 'voiceErrorSlideOut 0.3s ease-in forwards';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * Hiển thị danh sách câu hỏi liên quan dạng nút bấm
 */
function appendRelatedQuestions(questions) {
    const container = document.getElementById('chatMessages');
    const avatarUrl = "/DuAnChatbotThuVien/public/assets/images/logo1.png";

    const questionsHtml = questions.map(q => {
        const questionText = stripLeadingNumber(q.question_text);
        return `<button class="related-question-btn" onclick="askRelatedQuestion(this)" data-question="${escapeHtml(questionText)}">
            ${escapeHtml(questionText)}
        </button>`;
    }).join('');

    const msgHtml = `
        <div class="message bot">
            <img src="${avatarUrl}" alt="bot" class="avatar">
            <div class="bubble">
                <div class="related-questions-container">
                    ${questionsHtml}
                </div>
            </div>
        </div>`;
    
    container.insertAdjacentHTML('beforeend', msgHtml);

    // Scroll to bottom
    requestAnimationFrame(() => {
        container.scrollTop = container.scrollHeight;
    });
}

/**
 * Gửi câu hỏi liên quan được chọn
 */
function askRelatedQuestion(btn) {
    const question = btn.getAttribute('data-question');
    if (!question) return;

    const input = document.getElementById('chatInput');
    input.value = question;
    updateCharCount();
    sendMessage();
}
