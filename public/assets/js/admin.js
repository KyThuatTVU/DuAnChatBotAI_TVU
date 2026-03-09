/**
 * CELRAS TVU - Admin JS
 * Xử lý logic trang quản trị
 */

const ADMIN_API = '/DuAnChatbotThuVien/public/index.php?url=api';

// ==================== FORM DRAFT MANAGER ====================
// Tự động lưu trạng thái form vào sessionStorage khi user đang nhập liệu
// Khôi phục khi quay lại trang để tránh mất dữ liệu khi chuyển trang

const FormDraftManager = {
    PREFIX: 'celras_draft_',
    _listeners: {},
    _dirty: false,
    _activeForm: null,

    /**
     * Lưu draft cho một form
     * @param {string} formKey - Tên định danh form (vd: 'question', 'category', 'form')
     * @param {Object} data - Dữ liệu cần lưu
     */
    saveDraft(formKey, data) {
        try {
            const key = this.PREFIX + formKey;
            data._timestamp = Date.now();
            data._page = window.location.pathname;
            localStorage.setItem(key, JSON.stringify(data));
            this._dirty = true;
            this._activeForm = formKey;
        } catch (e) {
            console.warn('FormDraftManager: Không thể lưu draft', e);
        }
    },

    /**
     * Lấy draft đã lưu
     * @param {string} formKey
     * @returns {Object|null}
     */
    getDraft(formKey) {
        try {
            const key = this.PREFIX + formKey;
            const raw = localStorage.getItem(key);
            if (!raw) return null;
            const data = JSON.parse(raw);
            // Bỏ qua draft cũ hơn 2 giờ
            if (data._timestamp && (Date.now() - data._timestamp) > 2 * 60 * 60 * 1000) {
                this.clearDraft(formKey);
                return null;
            }
            return data;
        } catch (e) {
            return null;
        }
    },

    /**
     * Xóa draft
     * @param {string} formKey
     */
    clearDraft(formKey) {
        localStorage.removeItem(this.PREFIX + formKey);
        if (this._activeForm === formKey) {
            this._dirty = false;
            this._activeForm = null;
        }
    },

    /**
     * Kiểm tra có draft chưa lưu không
     * @param {string} formKey
     * @returns {boolean}
     */
    hasDraft(formKey) {
        return !!this.getDraft(formKey);
    },

    /**
     * Theo dõi thay đổi trên các field của form và tự động lưu draft
     * @param {string} formKey - Tên form
     * @param {string[]} fieldIds - Danh sách ID các input/textarea/select
     * @param {Object} [options] - { debounce: ms }
     */
    watchFields(formKey, fieldIds, options = {}) {
        const debounceMs = options.debounce || 500;
        let timer = null;

        const saveCurrentState = () => {
            const data = {};
            let hasValue = false;
            fieldIds.forEach(id => {
                const el = document.getElementById(id);
                if (!el) return;
                if (el.type === 'checkbox') {
                    data[id] = el.checked;
                } else {
                    data[id] = el.value;
                }
                if (el.value && el.value.trim()) hasValue = true;
            });
            // Chỉ lưu nếu form có ít nhất 1 field có giá trị
            if (hasValue) {
                this.saveDraft(formKey, data);
            }
        };

        const debouncedSave = () => {
            if (timer) clearTimeout(timer);
            timer = setTimeout(saveCurrentState, debounceMs);
        };

        // Gắn listener vào từng field
        fieldIds.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('input', debouncedSave);
            el.addEventListener('change', debouncedSave);
        });

        // Lưu reference để có thể cleanup
        this._listeners[formKey] = { fieldIds, timer };
    },

    /**
     * Khôi phục draft vào form
     * @param {string} formKey
     * @param {string[]} fieldIds
     * @returns {boolean} - true nếu có draft được khôi phục
     */
    restoreDraft(formKey, fieldIds) {
        const data = this.getDraft(formKey);
        if (!data) return false;

        let restored = false;
        fieldIds.forEach(id => {
            if (data[id] === undefined) return;
            const el = document.getElementById(id);
            if (!el) return;
            if (el.type === 'checkbox') {
                el.checked = !!data[id];
            } else {
                el.value = data[id];
            }
            restored = true;
        });

        return restored;
    },

    /**
     * Hiển thị thông báo có draft cần khôi phục
     * @param {string} formKey
     * @param {Function} onRestore - Callback khi user chọn khôi phục
     * @param {Function} onDiscard - Callback khi user chọn bỏ qua
     */
    showDraftNotification(formKey, onRestore, onDiscard) {
        const draft = this.getDraft(formKey);
        if (!draft) return;

        const timeAgo = this._formatTimeAgo(draft._timestamp);

        // Tạo toast notification
        const toast = document.createElement('div');
        toast.id = 'draftToast_' + formKey;
        toast.style.cssText = `
            position: fixed; bottom: 24px; right: 24px; z-index: 9999;
            background: linear-gradient(145deg, #ffffff, #f8fafc);
            border: 1px solid #e2e8f0; border-left: 4px solid #0ea5e9;
            border-radius: 16px; padding: 16px 20px; max-width: 380px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.12), 0 4px 12px rgba(0,0,0,0.06);
            animation: draftSlideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            font-family: 'Inter', sans-serif;
        `;
        toast.innerHTML = `
            <div style="display:flex;align-items:flex-start;gap:12px">
                <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#dbeafe,#bfdbfe);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <svg style="width:20px;height:20px;color:#2563eb" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </div>
                <div style="flex:1;min-width:0">
                    <p style="font-weight:700;font-size:14px;color:#1e293b;margin:0 0 4px">📝 Có bản nháp chưa lưu</p>
                    <p style="font-size:12px;color:#64748b;margin:0 0 12px">${timeAgo}</p>
                    <div style="display:flex;gap:8px">
                        <button id="draftRestore_${formKey}" style="padding:7px 16px;border-radius:10px;font-size:12px;font-weight:600;border:none;cursor:pointer;background:linear-gradient(135deg,#0ea5e9,#2563eb);color:#fff;box-shadow:0 2px 8px rgba(14,165,233,0.3);transition:all .2s">Khôi phục</button>
                        <button id="draftDiscard_${formKey}" style="padding:7px 16px;border-radius:10px;font-size:12px;font-weight:600;border:1.5px solid #e2e8f0;cursor:pointer;background:#fff;color:#64748b;transition:all .2s">Bỏ qua</button>
                    </div>
                </div>
                <button id="draftCloseToast_${formKey}" style="position:absolute;top:8px;right:10px;background:none;border:none;cursor:pointer;color:#94a3b8;font-size:18px;line-height:1;padding:4px">&times;</button>
            </div>
        `;

        // Thêm animation styles
        if (!document.getElementById('draftToastStyles')) {
            const style = document.createElement('style');
            style.id = 'draftToastStyles';
            style.textContent = `
                @keyframes draftSlideIn {
                    from { transform: translateX(100%) translateY(20px); opacity: 0; }
                    to { transform: translateX(0) translateY(0); opacity: 1; }
                }
                @keyframes draftSlideOut {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(120%); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }

        document.body.appendChild(toast);

        const removeToast = () => {
            toast.style.animation = 'draftSlideOut 0.3s ease-in forwards';
            setTimeout(() => toast.remove(), 300);
        };

        document.getElementById(`draftRestore_${formKey}`).addEventListener('click', () => {
            removeToast();
            if (onRestore) onRestore();
        });

        document.getElementById(`draftDiscard_${formKey}`).addEventListener('click', () => {
            this.clearDraft(formKey);
            removeToast();
            if (onDiscard) onDiscard();
        });

        document.getElementById(`draftCloseToast_${formKey}`).addEventListener('click', removeToast);

        // Tự ẩn sau 15 giây
        setTimeout(() => {
            if (document.getElementById(`draftToast_${formKey}`)) removeToast();
        }, 15000);
    },

    /**
     * Format thời gian tương đối
     */
    _formatTimeAgo(timestamp) {
        if (!timestamp) return '';
        const diff = Math.floor((Date.now() - timestamp) / 1000);
        if (diff < 60) return 'Vừa xong';
        if (diff < 3600) return `${Math.floor(diff / 60)} phút trước`;
        if (diff < 86400) return `${Math.floor(diff / 3600)} giờ trước`;
        return new Date(timestamp).toLocaleString('vi-VN');
    },

    /**
     * Bật cảnh báo beforeunload khi có dữ liệu chưa lưu
     */
    enableBeforeUnloadWarning() {
        window.addEventListener('beforeunload', (e) => {
            if (this._dirty && this._activeForm) {
                e.preventDefault();
                e.returnValue = 'Bạn có dữ liệu chưa lưu. Bạn có chắc muốn rời trang?';
                return e.returnValue;
            }
        });
    },

    /**
     * Đánh dấu form đã lưu thành công (xóa dirty flag)
     */
    markSaved(formKey) {
        this.clearDraft(formKey);
        this._dirty = false;
        this._activeForm = null;
    }
};

// Bật cảnh báo khi rời trang có dữ liệu chưa lưu
FormDraftManager.enableBeforeUnloadWarning();

// ==================== PAGE STATE MANAGER ====================
// Lưu trạng thái trang (search/filter) vào localStorage để khôi phục sau load lại

const PageStateManager = {
    PREFIX: 'celras_pagestate_',
    TTL: 24 * 60 * 60 * 1000, // 24 giờ

    /**
     * Khôi phục state đã lưu vào DOM elements và bắt đầu theo dõi thay đổi
     * @param {string} page - Tên trang (vd: 'questions', 'forms')
     * @param {string[]} selectors - CSS selectors của các input/select cần theo dõi
     * @param {Function} [onRestore] - Callback gọi sau khi khôi phục để re-apply filter
     */
    restoreAndWatch(page, selectors, onRestore) {
        let restored = false;
        try {
            const raw = localStorage.getItem(this.PREFIX + page);
            if (raw) {
                const state = JSON.parse(raw);
                if (!state._ts || (Date.now() - state._ts) < this.TTL) {
                    selectors.forEach(sel => {
                        if (state[sel] === undefined) return;
                        const el = document.querySelector(sel);
                        if (el) { el.value = state[sel]; restored = true; }
                    });
                }
            }
        } catch(e) {}

        if (restored && onRestore) { try { onRestore(); } catch(e) {} }

        // Theo dõi thay đổi → tự động lưu
        const save = () => {
            const state = { _ts: Date.now() };
            selectors.forEach(sel => {
                const el = document.querySelector(sel);
                if (el) state[sel] = el.value;
            });
            try { localStorage.setItem(this.PREFIX + page, JSON.stringify(state)); } catch(e) {}
        };

        selectors.forEach(sel => {
            const el = document.querySelector(sel);
            if (!el || el._pswatched) return;
            el._pswatched = true;
            el.addEventListener('input', save);
            el.addEventListener('change', save);
        });
    }
};

// ==================== SPA NAVIGATION (AJAX) ====================
// Chuyển trang admin bằng AJAX, không reload – giữ nguyên trạng thái

const AdminSPA = {
    currentPage: null,
    contentWrapper: null,   // .min-h-screen
    isNavigating: false,
    initialized: false,

    // Tên hiển thị trên mobile topbar
    PAGE_TITLES: {
        dashboard: 'Dashboard',
        questions: 'Quản lý câu hỏi',
        categories: 'Danh mục',
        datasets: 'Tải dữ liệu',
        forms: 'Biểu mẫu / Giấy tờ',
        settings: 'Cài đặt giao diện',
        unanswered: 'Chưa trả lời',
    },

    // Hàm khởi tạo dữ liệu cho từng trang
    PAGE_INIT: {
        dashboard:  () => loadDashboardStats(),
        questions:  async () => {
            await loadCategoriesForSelect();
            await loadQuestions();
            PageStateManager.restoreAndWatch('questions', ['#searchInput', '#filterCategory', '#filterSource'], () => { try { filterQuestions(); } catch(e){} });
            const _pq = sessionStorage.getItem('celras_pendingQuestion');
            if (_pq) {
                sessionStorage.removeItem('celras_pendingQuestion');
                try { openAddModal(); const _t = document.getElementById('questionText'); if (_t) _t.value = _pq; } catch(e) {}
            } else {
                setTimeout(() => { try { checkQuestionDraft(); } catch(e){} }, 800);
            }
        },
        categories: async () => { await loadCategories(); setTimeout(() => { try { checkCategoryDraft(); } catch(e){} }, 800); },
        datasets:   async () => { await loadDatasets(); restoreUploadSession(); },
        forms:      async () => {
            await loadForms();
            PageStateManager.restoreAndWatch('forms', ['#formSearch'], () => { try { filterForms(); } catch(e){} });
            setTimeout(() => { try { checkFormDraft(); } catch(e){} }, 800);
        },
        settings:   async () => { await loadSettings(); await loadThemes(); setTimeout(() => { try { checkSettingsDraft(); watchSettingsFields(); } catch(e){} }, 1000); },
        unanswered: () => loadUnanswered(),
    },

    /**
     * Khởi tạo SPA — chỉ gọi 1 lần
     */
    init() {
        if (this.initialized) return;

        this.contentWrapper = document.querySelector('.min-h-screen');
        if (!this.contentWrapper) return;

        this.currentPage = this._pageName(window.location.pathname);

        // Gắn event cho sidebar links
        this._bindNavLinks();

        // Xử lý nút Back / Forward trình duyệt
        window.addEventListener('popstate', (e) => {
            if (e.state && e.state.adminPage) {
                this.loadPage(e.state.adminPage, false);
            }
        });

        // Ghi state hiện tại
        history.replaceState({ adminPage: this.currentPage }, '', window.location.href);

        this.initialized = true;
    },

    _pageName(path) {
        const m = path.match(/admin\/(\w+)\.html/);
        return m ? m[1] : 'dashboard';
    },

    /**
     * Gắn click listener cho tất cả sidebar nav-items
     */
    _bindNavLinks() {
        document.querySelectorAll('#adminSidebar .nav-item[data-page]').forEach(link => {
            // Đánh dấu đã bind để không bind lại
            if (link._spaBound) return;
            link._spaBound = true;

            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = link.getAttribute('data-page');
                if (!page) return;
                if (page === this.currentPage && !this.isNavigating) return;
                this.loadPage(page, true);
                closeSidebarOnMobile();
            });
        });

        // Bắt tất cả link trong content area trỏ đến trang admin
        this._bindContentLinks();
    },

    /**
     * Sử dụng event delegation để bắt mọi link trong .admin-content
     * trỏ đến trang admin → chuyển SPA thay vì reload
     */
    _bindContentLinks() {
        const wrapper = document.querySelector('.min-h-screen') || document.querySelector('.admin-layout');
        if (!wrapper || wrapper._spaContentBound) return;
        wrapper._spaContentBound = true;

        wrapper.addEventListener('click', (e) => {
            // Tìm thẻ <a> gần nhất
            const link = e.target.closest('a[href]');
            if (!link) return;
            // Bỏ qua sidebar links (đã xử lý riêng)
            if (link.closest('#adminSidebar')) return;
            // Bỏ qua links có target="_blank"
            if (link.getAttribute('target') === '_blank') return;
            // Bỏ qua links href="#" hoặc javascript:
            const href = link.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;

            // Kiểm tra link có trỏ đến trang admin không
            const match = href.match(/(?:^|\/)(\w+)\.html(?:\?.*)?$/);
            if (!match) return;
            const pageName = match[1];
            if (!this.PAGE_INIT[pageName] && pageName !== 'dashboard') return;

            // Chặn reload và dùng SPA
            e.preventDefault();
            this.loadPage(pageName, true);
        });
    },

    /**
     * Tải trang mới bằng AJAX — không reload
     */
    async loadPage(pageName, pushState = true) {
        if (this.isNavigating) return;
        this.isNavigating = true;

        const contentEl = document.querySelector('.admin-content');
        if (!contentEl) { this.isNavigating = false; return; }

        // Fade-out hiện tại
        contentEl.style.transition = 'opacity 0.12s ease, transform 0.12s ease';
        contentEl.style.opacity = '0.3';
        contentEl.style.transform = 'translateY(6px)';

        try {
            const fullUrl = '/DuAnChatbotThuVien/public/pages/admin/' + pageName + '.html';
            const res = await fetch(fullUrl, { cache: 'no-cache' });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const html = await res.text();

            // Parse
            const doc = new DOMParser().parseFromString(html, 'text/html');

            // 1) Nội dung chính
            const newContent = doc.querySelector('.admin-content');
            if (!newContent) throw new Error('.admin-content not found');

            // 2) Modals (nằm ngoài .min-h-screen)
            const newModals = doc.querySelectorAll('body > .modal-overlay');

            // 3) Styles từ <head>
            const newStyles = Array.from(doc.querySelectorAll('head > style'))
                .map(s => s.textContent).join('\n');

            // 4) Inline scripts (function definitions, event wiring)
            const newScripts = doc.querySelectorAll('body > script:not([src])');

            // --- Cleanup DOM cũ ---
            // Xóa modals cũ
            document.querySelectorAll('body > .modal-overlay').forEach(m => m.remove());
            // Xóa styles cũ của SPA
            document.querySelectorAll('style[data-spa]').forEach(s => s.remove());
            // Xóa scripts cũ của SPA
            document.querySelectorAll('script[data-spa]').forEach(s => s.remove());
            // Xóa draft toast nếu đang hiện
            document.querySelectorAll('[id^="draftToast_"]').forEach(t => t.remove());

            // --- Inject mới ---
            // Styles
            if (newStyles.trim()) {
                const styleEl = document.createElement('style');
                styleEl.setAttribute('data-spa', pageName);
                styleEl.textContent = newStyles;
                document.head.appendChild(styleEl);
            }

            // Swap nội dung
            contentEl.innerHTML = newContent.innerHTML;

            // Inject modals
            newModals.forEach(modal => {
                const clone = document.importNode(modal, true);
                clone.setAttribute('data-spa', pageName);
                document.body.appendChild(clone);
            });

            // Inject inline scripts (chạy trong global scope)
            newScripts.forEach(scriptNode => {
                const s = document.createElement('script');
                s.setAttribute('data-spa', pageName);
                s.textContent = scriptNode.textContent;
                document.body.appendChild(s);
            });

            // Fade-in
            await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)));
            contentEl.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
            contentEl.style.opacity = '1';
            contentEl.style.transform = 'translateY(0)';

            // URL
            if (pushState) {
                history.pushState({ adminPage: pageName }, '', fullUrl);
            }

            // Active nav
            this._setActiveNav(pageName);

            // Title
            const titleEl = doc.querySelector('title');
            if (titleEl) document.title = titleEl.textContent;

            // Mobile topbar text
            const span = document.querySelector('.mobile-topbar .font-bold, .mobile-topbar span');
            if (span && this.PAGE_TITLES[pageName]) {
                span.textContent = this.PAGE_TITLES[pageName];
            }

            this.currentPage = pageName;

            // Gọi loadAdminPage (auth + admin info vào header mới)
            await loadAdminPage(pageName);

            // Gọi init cho trang (load dữ liệu)
            const initFn = this.PAGE_INIT[pageName];
            if (initFn) await initFn();

        } catch (err) {
            console.error('[AdminSPA] Error:', err);
            // Fallback → chuyển trang bình thường
            window.location.href = '/DuAnChatbotThuVien/public/pages/admin/' + pageName + '.html';
        } finally {
            this.isNavigating = false;
        }
    },

    _setActiveNav(pageName) {
        document.querySelectorAll('#adminSidebar .nav-item[data-page]').forEach(link => {
            link.classList.toggle('active', link.getAttribute('data-page') === pageName);
        });
    }
};

// ==================== MOBILE SIDEBAR TOGGLE ====================

/**
 * Bật/tắt sidebar trên mobile
 */
function toggleSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (!sidebar) return;
    
    const isOpen = sidebar.classList.contains('open');
    if (isOpen) {
        sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    } else {
        sidebar.classList.add('open');
        if (overlay) overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * Đóng sidebar khi click vào nav link trên mobile
 */
function closeSidebarOnMobile() {
    if (window.innerWidth <= 768) {
        const sidebar = document.getElementById('adminSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (sidebar) sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Khi resize, nếu về desktop thì reset sidebar
window.addEventListener('resize', () => {
    if (window.innerWidth > 768) {
        const sidebar = document.getElementById('adminSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (sidebar) sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
});

/**
 * Load trang admin - check auth và điền thông tin admin
 * (Sidebar đã được nhúng trực tiếp vào HTML, không cần fetch)
 */
async function loadAdminPage(pageName) {
    // Load auth.js nếu chưa có
    if (typeof checkAuth !== 'function') {
        await new Promise((resolve, reject) => {
            const s = document.createElement('script');
            s.src = '/DuAnChatbotThuVien/public/assets/js/auth.js';
            s.onload = resolve;
            s.onerror = reject;
            document.head.appendChild(s);
        });
    }

    // Check auth (redirect về login nếu chưa đăng nhập)
    const admin = await checkAuth();
    if (!admin) return;

    // Điền thông tin admin vào header
    const fallbackAvatar = '/DuAnChatbotThuVien/public/assets/images/US.jpg';
    const avatarSrc = admin.avatar || fallbackAvatar;

    const avatarEl = document.getElementById('dashAdminAvatar');
    if (avatarEl) avatarEl.src = avatarSrc;
    const nameEl = document.getElementById('dashAdminName');
    if (nameEl) nameEl.textContent = admin.name;

    // Điền thông tin admin vào sidebar
    const sidebarAvatar = document.getElementById('sidebarAdminAvatar');
    if (sidebarAvatar) sidebarAvatar.src = avatarSrc;
    const sidebarName = document.getElementById('sidebarAdminName');
    if (sidebarName) sidebarName.textContent = admin.name;

    // Khởi tạo SPA navigation (chỉ chạy 1 lần)
    if (typeof AdminSPA !== 'undefined' && !AdminSPA.initialized) {
        AdminSPA.init();
    }
}

// ==================== DASHBOARD ====================

async function loadDashboardStats() {
    try {
        const res = await fetch(`${ADMIN_API}/admin/dashboard`);
        const data = await res.json();
        if (data.stats) {
            document.getElementById('statQuestions').textContent = data.stats.total_questions || 0;
            document.getElementById('statCategories').textContent = data.stats.total_categories || 0;
            document.getElementById('statSessions').textContent = data.stats.total_sessions || 0;
            document.getElementById('statUnanswered').textContent = data.stats.unanswered_count || 0;
            document.getElementById('statMessages').textContent = data.stats.total_messages || 0;
        }
    } catch (e) {
        console.error('Failed to load dashboard stats:', e);
    }
}

// ==================== QUESTIONS ====================

let allQuestions = [];

async function loadQuestions() {
    try {
        const res = await fetch(`${ADMIN_API}/admin/questions`);
        const data = await res.json();
        allQuestions = data.questions || [];
        renderQuestions(allQuestions);
    } catch (e) {
        console.error('Failed to load questions:', e);
    }
}

function renderQuestions(questions) {
    const tbody = document.getElementById('questionsBody');
    if (!questions.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-400">Chưa có câu hỏi nào</td></tr>';
        return;
    }

    tbody.innerHTML = questions.map((q, i) => `
        <tr>
            <td data-label="#" class="text-gray-500">${i + 1}</td>
            <td data-label="Câu hỏi">
                <div class="font-medium text-sm">${escapeHtml(q.question_text.substring(0, 80))}${q.question_text.length > 80 ? '...' : ''}</div>
                <div class="text-xs text-gray-400 mt-1">${escapeHtml(q.answer_text.substring(0, 60))}...</div>
            </td>
            <td data-label="Danh mục"><span class="badge badge-info">${q.category_name || 'Chưa phân loại'}</span></td>
            <td data-label="Nguồn"><span class="badge ${q.source_type === 'manual' ? 'badge-success' : 'badge-warning'}">${q.source_type === 'manual' ? 'Nhập tay' : q.source_type.toUpperCase()}</span></td>
            <td data-label="Trạng thái">${q.is_active ? '<span class="badge badge-success">Hoạt động</span>' : '<span class="badge badge-danger">Tắt</span>'}</td>
            <td data-label="Thao tác">
                <div class="flex items-center gap-2">
                    <button onclick="editQuestion(${q.id})" class="text-sky-600 hover:text-sky-800" title="Sửa">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button onclick="deleteQuestion(${q.id})" class="text-red-500 hover:text-red-700" title="Xóa">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function filterQuestions() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const category = document.getElementById('filterCategory').value;
    const source = document.getElementById('filterSource').value;

    let filtered = allQuestions.filter(q => {
        const matchSearch = !search || q.question_text.toLowerCase().includes(search) || q.answer_text.toLowerCase().includes(search);
        const matchCategory = !category || q.category_id == category;
        const matchSource = !source || q.source_type === source;
        return matchSearch && matchCategory && matchSource;
    });
    renderQuestions(filtered);
}

const QUESTION_DRAFT_FIELDS = ['questionId', 'questionCategory', 'questionText', 'answerText', 'answerTextEn', 'keywordsInput'];

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Thêm câu hỏi mới';
    document.getElementById('questionId').value = '';
    document.getElementById('questionText').value = '';
    document.getElementById('answerText').value = '';
    document.getElementById('answerTextEn').value = '';
    document.getElementById('keywordsInput').value = '';
    document.getElementById('questionModal').classList.add('active');
    // Bắt đầu theo dõi thay đổi để lưu draft
    FormDraftManager.watchFields('question', QUESTION_DRAFT_FIELDS);
}

function closeModal() {
    document.getElementById('questionModal').classList.remove('active');
    // Xóa draft khi đóng modal (user chủ động đóng)
    FormDraftManager.clearDraft('question');
}

async function editQuestion(id) {
    try {
        const res = await fetch(`${ADMIN_API}/admin/question/${id}`);
        const data = await res.json();
        if (data.question) {
            const q = data.question;
            document.getElementById('modalTitle').textContent = 'Sửa câu hỏi';
            document.getElementById('questionId').value = q.id;
            document.getElementById('questionCategory').value = q.category_id || '';
            document.getElementById('questionText').value = q.question_text;
            document.getElementById('answerText').value = q.answer_text;
            document.getElementById('answerTextEn').value = q.answer_text_en || '';
            document.getElementById('questionModal').classList.add('active');
            // Bắt đầu theo dõi thay đổi để lưu draft
            FormDraftManager.watchFields('question', QUESTION_DRAFT_FIELDS);
        }
    } catch (e) {
        alert('Lỗi khi tải câu hỏi');
    }
}

async function saveQuestion(event) {
    event.preventDefault();
    const id = document.getElementById('questionId').value;
    const payload = {
        category_id: document.getElementById('questionCategory').value || null,
        question_text: document.getElementById('questionText').value,
        answer_text: document.getElementById('answerText').value,
        answer_text_en: document.getElementById('answerTextEn').value,
        keywords: document.getElementById('keywordsInput').value.split(',').map(k => k.trim()).filter(k => k),
    };

    try {
        const url = id ? `${ADMIN_API}/admin/question/${id}` : `${ADMIN_API}/admin/questions`;
        const method = id ? 'PUT' : 'POST';
        const res = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (data.success) {
            FormDraftManager.markSaved('question');
            closeModal();
            loadQuestions();
        } else if (data.duplicate) {
            // Phát hiện trùng lặp - hỏi user có muốn thêm hay không
            const matchLabel = data.match_type === 'question' ? 'câu hỏi' : 'câu trả lời';
            const confirmMsg = `⚠️ Phát hiện trùng lặp ${matchLabel}!\n\nCâu hỏi đã có (ID #${data.existing_id}):\n"${data.existing_question}"\n\nBạn có muốn thêm dù trùng không?`;
            if (confirm(confirmMsg)) {
                // User xác nhận → gửi lại với force_add
                payload.force_add = true;
                const res2 = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });
                const data2 = await res2.json();
                if (data2.success) {
                    FormDraftManager.markSaved('question');
                    closeModal();
                    loadQuestions();
                } else {
                    alert(data2.error || 'Lỗi khi lưu');
                }
            }
        } else {
            alert(data.error || 'Lỗi khi lưu');
        }
    } catch (e) {
        alert('Lỗi kết nối server');
    }
}

async function deleteQuestion(id) {
    if (!confirm('Bạn có chắc muốn xóa câu hỏi này?')) return;
    try {
        await fetch(`${ADMIN_API}/admin/question/${id}`, { method: 'DELETE' });
        loadQuestions();
    } catch (e) {
        alert('Lỗi khi xóa');
    }
}

// ==================== CATEGORIES ====================

let _categoriesData = [];

async function loadCategories() {
    try {
        const res = await fetch(`${ADMIN_API}/admin/categories`);
        const data = await res.json();
        _categoriesData = data.categories || [];
        renderCategories(_categoriesData);
    } catch (e) {
        console.error('Failed to load categories:', e);
    }
}

function renderCategories(categories) {
    const grid = document.getElementById('categoriesGrid');
    if (!categories.length) {
        grid.innerHTML = '<div class="text-center py-8 text-gray-400 col-span-3">Chưa có danh mục</div>';
        return;
    }

    grid.innerHTML = categories.map(c => `
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
            <div class="flex items-start justify-between mb-3">
                <h3 class="font-semibold text-gray-800">${escapeHtml(c.name)}</h3>
                <div class="flex gap-1">
                    <button onclick="editCategory(${c.id})" class="text-sky-600 hover:text-sky-800 p-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button onclick="deleteCategory(${c.id})" class="text-red-500 hover:text-red-700 p-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
            <p class="text-sm text-gray-500 mb-3">${escapeHtml(c.description || 'Không có mô tả')}</p>
            <div class="flex items-center gap-2">
                <a href="/DuAnChatbotThuVien/public/pages/admin/questions.html?category=${c.id}" class="badge badge-info hover:opacity-80 cursor-pointer no-underline" title="Xem danh sách câu hỏi">${c.question_count || 0} câu hỏi</a>
                <span class="badge ${c.is_active ? 'badge-success' : 'badge-danger'}">${c.is_active ? 'Hoạt động' : 'Tắt'}</span>
            </div>
        </div>
    `).join('');
}

async function loadCategoriesForSelect() {
    try {
        const res = await fetch(`${ADMIN_API}/admin/categories`);
        const data = await res.json();
        const categories = data.categories || [];

        // Populate question form select
        const sel = document.getElementById('questionCategory');
        if (sel) {
            const options = categories.map(c => `<option value="${c.id}">${escapeHtml(c.name)}</option>`);
            sel.innerHTML = '<option value="">-- Chọn danh mục --</option>' + options.join('');
        }

        // Populate filter select
        const filterSel = document.getElementById('filterCategory');
        if (filterSel) {
            const options = categories.map(c => `<option value="${c.id}">${escapeHtml(c.name)}</option>`);
            filterSel.innerHTML = '<option value="">Tất cả danh mục</option>' + options.join('');
        }
    } catch (e) {}
}

const CATEGORY_DRAFT_FIELDS = ['catId', 'catName', 'catDescription', 'catOrder'];

function openCategoryModal() {
    document.getElementById('catModalTitle').textContent = 'Thêm danh mục mới';
    document.getElementById('catId').value = '';
    document.getElementById('catName').value = '';
    document.getElementById('catDescription').value = '';
    document.getElementById('catOrder').value = '0';
    document.getElementById('categoryModal').classList.add('active');
    FormDraftManager.watchFields('category', CATEGORY_DRAFT_FIELDS);
}

function closeCategoryModal() {
    document.getElementById('categoryModal').classList.remove('active');
    FormDraftManager.clearDraft('category');
}

function editCategory(id) {
    const c = _categoriesData.find(cat => cat.id == id);
    if (!c) return;
    document.getElementById('catModalTitle').textContent = 'Sửa danh mục';
    document.getElementById('catId').value = c.id;
    document.getElementById('catName').value = c.name;
    document.getElementById('catDescription').value = c.description || '';
    document.getElementById('catOrder').value = c.sort_order || 0;
    document.getElementById('categoryModal').classList.add('active');
    FormDraftManager.watchFields('category', CATEGORY_DRAFT_FIELDS);
}

async function saveCategory(event) {
    event.preventDefault();
    const id = document.getElementById('catId').value;
    const payload = {
        name: document.getElementById('catName').value,
        description: document.getElementById('catDescription').value,
        sort_order: parseInt(document.getElementById('catOrder').value) || 0,
    };

    try {
        const url = id ? `${ADMIN_API}/admin/category/${id}` : `${ADMIN_API}/admin/categories`;
        const method = id ? 'PUT' : 'POST';
        const res = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (data.success) {
            FormDraftManager.markSaved('category');
            closeCategoryModal();
            loadCategories();
        }
    } catch (e) {
        alert('Lỗi kết nối server');
    }
}

async function deleteCategory(id) {
    if (!confirm('Xóa danh mục sẽ gỡ liên kết với các câu hỏi. Tiếp tục?')) return;
    try {
        await fetch(`${ADMIN_API}/admin/category/${id}`, { method: 'DELETE' });
        loadCategories();
    } catch (e) {
        alert('Lỗi khi xóa');
    }
}

// ==================== SETTINGS ====================

async function loadSettings() {
    try {
        const res = await fetch(`${ADMIN_API}/admin/settings`);
        const data = await res.json();
        const s = data.settings || {};

        // Populate fields
        if (s.chatbot_enabled) document.getElementById('settingEnabled').checked = s.chatbot_enabled.value === 'true';
        if (s.chatbot_title) document.getElementById('settingTitle').value = s.chatbot_title.value;
        if (s.welcome_message) document.getElementById('settingWelcome').value = s.welcome_message.value;
        if (s.no_answer_message) document.getElementById('settingNoAnswer').value = s.no_answer_message.value;
        if (s.primary_color) {
            document.getElementById('colorPrimary').value = s.primary_color.value;
            document.getElementById('colorPrimaryText').value = s.primary_color.value;
        }
        if (s.header_bg_color) {
            document.getElementById('colorHeaderBg').value = s.header_bg_color.value;
            document.getElementById('colorHeaderBgText').value = s.header_bg_color.value;
        }
        if (s.header_text_color) {
            document.getElementById('colorHeaderText').value = s.header_text_color.value;
            document.getElementById('colorHeaderTextVal').value = s.header_text_color.value;
        }
        if (s.user_bubble_color) {
            document.getElementById('colorUserBubble').value = s.user_bubble_color.value;
            document.getElementById('colorUserBubbleText').value = s.user_bubble_color.value;
        }
        if (s.bot_bubble_color) {
            document.getElementById('colorBotBubble').value = s.bot_bubble_color.value;
            document.getElementById('colorBotBubbleText').value = s.bot_bubble_color.value;
        }
        if (s.button_color) {
            document.getElementById('colorButton').value = s.button_color.value;
            document.getElementById('colorButtonText').value = s.button_color.value;
        }
    } catch (e) {
        console.error('Failed to load settings:', e);
    }
}

async function saveSettings() {
    const payload = {
        chatbot_enabled: document.getElementById('settingEnabled').checked ? 'true' : 'false',
        chatbot_title: document.getElementById('settingTitle').value,
        welcome_message: document.getElementById('settingWelcome').value,
        no_answer_message: document.getElementById('settingNoAnswer').value,
        primary_color: document.getElementById('colorPrimary').value,
        header_bg_color: document.getElementById('colorHeaderBg').value,
        header_text_color: document.getElementById('colorHeaderText').value,
        user_bubble_color: document.getElementById('colorUserBubble').value,
        bot_bubble_color: document.getElementById('colorBotBubble').value,
        button_color: document.getElementById('colorButton').value,
    };

    try {
        const res = await fetch(`${ADMIN_API}/admin/settings`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (data.success) {
            FormDraftManager.markSaved('settings');
            alert('Đã lưu cài đặt thành công!');
        }
    } catch (e) {
        alert('Lỗi kết nối server');
    }
}

// ==================== THEMES ====================

async function loadThemes() {
    try {
        const res = await fetch(`${ADMIN_API}/admin/themes`);
        const data = await res.json();
        const themes = data.themes || [];
        renderThemes(themes);
    } catch (e) {
        const container = document.getElementById('themesList');
        if (container) container.innerHTML = '<p class="text-red-400 text-sm">Không thể tải chủ đề</p>';
    }
}

function renderThemes(themes) {
    const container = document.getElementById('themesList');
    if (!container) return;

    if (!themes.length) {
        container.innerHTML = '<p class="text-gray-400 text-sm text-center py-4">Chưa có chủ đề nào</p>';
        return;
    }

    // Emoji map for theme keys
    const themeEmojis = {
        'mac-dinh': '🏠', 'tet': '🧧', 'trung-thu': '🌕', 'halloween': '🎃',
        'giang-sinh': '🎄', '8-3': '🌸', '20-10': '🌹', '20-11': '📚',
        '30-4': '🇻🇳', '1-5': '👷', '2-9': '🎆'
    };

    container.innerHTML = themes.map(t => {
        const isActive = parseInt(t.is_active) === 1;
        const isDefault = t.theme_key === 'mac-dinh';
        const emoji = themeEmojis[t.theme_key] || '🎨';
        const safeName = escapeHtml(t.theme_name);

        return `
        <div class="theme-card ${isActive ? 'active' : ''}">
            <div class="theme-orb" style="background:linear-gradient(135deg, ${escapeHtml(t.primary_color)}, ${escapeHtml(t.secondary_color || t.primary_color)})">
                <span style="position:relative;z-index:1">${emoji}</span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-bold text-sm truncate text-gray-800">${safeName}</p>
                <p class="text-[11px] text-gray-400 mt-0.5">${t.start_date ? t.start_date + ' → ' + (t.end_date || '∞') : 'Không giới hạn thời gian'}</p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                ${isActive
                    ? '<span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-gradient-to-r from-sky-100 to-blue-100 text-sky-700 border border-sky-200" style="box-shadow:0 2px 8px rgba(14,165,233,.15)"><span class="w-1.5 h-1.5 bg-sky-500 rounded-full animate-pulse"></span>Đang dùng</span>'
                    : '<button onclick="activateTheme(' + t.id + ')" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-semibold bg-gray-50 text-gray-400 hover:bg-emerald-50 hover:text-emerald-600 border border-gray-200 hover:border-emerald-200 transition-all cursor-pointer" title="Bật chủ đề này"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>Bật</button>'
                }
                ${!isDefault ? '<button onclick="deleteTheme(' + t.id + ', ' + escapeAttr(t.theme_name) + ')" class="w-8 h-8 flex items-center justify-center rounded-xl text-gray-300 hover:text-red-500 hover:bg-red-50 transition-all" title="Xóa chủ đề"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>' : ''}
            </div>
        </div>`;
    }).join('');
}

async function activateTheme(themeId) {
    if (!confirm('Bạn muốn bật chủ đề này? Chủ đề đang dùng sẽ bị tắt.')) return;
    try {
        const res = await fetch(`${ADMIN_API}/admin/themes/${themeId}/activate`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
        });
        const data = await res.json();
        if (data.success) {
            loadThemes();
        } else {
            alert(data.message || 'Lỗi khi bật chủ đề');
        }
    } catch (e) {
        alert('Lỗi kết nối server');
    }
}

async function deleteTheme(themeId, themeName) {
    if (!confirm(`Bạn chắc chắn muốn xóa chủ đề "${themeName}"?`)) return;
    try {
        const res = await fetch(`${ADMIN_API}/admin/themes/${themeId}`, {
            method: 'DELETE',
        });
        const data = await res.json();
        if (data.success) {
            loadThemes();
        } else {
            alert(data.message || 'Lỗi khi xóa chủ đề');
        }
    } catch (e) {
        alert('Lỗi kết nối server');
    }
}

function openThemeModal() {
    const modal = document.getElementById('themeModal');
    if (!modal) return;
    // Reset form
    document.getElementById('themeName').value = '';
    document.getElementById('themePrimaryColor').value = '#0369a1';
    document.getElementById('themeSecondaryColor').value = '#ffffff';
    document.getElementById('themeHeaderBg').value = '#0369a1';
    document.getElementById('themeHeaderText').value = '#ffffff';
    document.getElementById('themeUserBubble').value = '#e3f2fd';
    document.getElementById('themeBotBubble').value = '#f5f5f5';
    document.getElementById('themeButtonColor').value = '#0369a1';
    document.getElementById('themeWelcome').value = '';
    document.getElementById('themeStartDate').value = '';
    document.getElementById('themeEndDate').value = '';
    document.getElementById('themeActive').checked = false;
    modal.classList.add('active');
}

function closeThemeModal() {
    const modal = document.getElementById('themeModal');
    if (modal) modal.classList.remove('active');
}

async function saveTheme(e) {
    e.preventDefault();
    const name = document.getElementById('themeName').value.trim();
    if (!name) {
        alert('Vui lòng nhập tên chủ đề');
        return;
    }

    const body = {
        theme_name: name,
        primary_color: document.getElementById('themePrimaryColor').value,
        secondary_color: document.getElementById('themeSecondaryColor').value,
        header_bg_color: document.getElementById('themeHeaderBg').value,
        header_text_color: document.getElementById('themeHeaderText').value,
        user_bubble_color: document.getElementById('themeUserBubble').value,
        bot_bubble_color: document.getElementById('themeBotBubble').value,
        button_color: document.getElementById('themeButtonColor').value,
        welcome_message: document.getElementById('themeWelcome').value.trim(),
        start_date: document.getElementById('themeStartDate').value || null,
        end_date: document.getElementById('themeEndDate').value || null,
        is_active: document.getElementById('themeActive').checked ? 1 : 0
    };

    try {
        const res = await fetch(`${ADMIN_API}/admin/themes`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const data = await res.json();
        if (data.success) {
            closeThemeModal();
            loadThemes();
            alert('Lưu chủ đề thành công!');
        } else {
            alert(data.message || 'Lỗi khi lưu chủ đề');
        }
    } catch (err) {
        alert('Lỗi kết nối server');
    }
}

// ==================== DATASETS (Upload) ====================

function handleDrop(event) {
    event.preventDefault();
    const dropZone = document.getElementById('dropZone');
    dropZone.classList.remove('border-sky-400', 'bg-sky-50');
    
    const files = event.dataTransfer.files;
    if (files.length > 0) {
        uploadFile(files[0]);
    }
}

function handleFileSelect(event) {
    const files = event.target.files;
    if (files.length > 0) {
        uploadFile(files[0]);
    }
}

async function uploadFile(file) {
    const validTypes = [
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/octet-stream',
        '',
    ];
    const validExts = ['.doc', '.docx'];
    const fileName = file.name.toLowerCase();
    const ext = fileName.substring(fileName.lastIndexOf('.'));

    // Kiểm tra extension trước (đáng tin hơn MIME type)
    if (!validExts.includes(ext)) {
        alert('Chỉ chấp nhận file Word (.doc, .docx)');
        return;
    }

    // Nếu có MIME type, kiểm tra thêm
    if (file.type && !validTypes.includes(file.type)) {
        alert('File không đúng định dạng Word. Vui lòng chọn file .doc hoặc .docx');
        return;
    }

    if (file.size > 10 * 1024 * 1024) {
        alert('File vượt quá 10MB');
        return;
    }

    // Clear stale upload session when starting a new upload
    sessionStorage.removeItem('celras_upload_session');

    // Hide previous result & preview
    const resultDiv = document.getElementById('uploadResult');
    const previewSection = document.getElementById('qaPreviewSection');
    if (resultDiv) resultDiv.classList.add('hidden');
    if (previewSection) previewSection.classList.add('hidden');

    // Show progress
    document.getElementById('uploadProgress').classList.remove('hidden');
    document.getElementById('uploadFileName').textContent = `Đang phân tích: ${file.name}`;
    document.getElementById('uploadBar').style.width = '30%';

    const formData = new FormData();
    formData.append('file', file);

    try {
        document.getElementById('uploadBar').style.width = '60%';
        const res = await fetch(`${ADMIN_API}/admin/upload`, {
            method: 'POST',
            body: formData,
        });
        const data = await res.json();
        
        document.getElementById('uploadBar').style.width = '100%';
        
        if (data.success) {
            document.getElementById('uploadFileName').textContent = `Hoàn thành!`;

            // Build duplicate warning HTML
            let duplicateHtml = '';
            if (data.duplicate_count > 0 && data.duplicates && data.duplicates.length > 0) {
                const dupRows = data.duplicates.map(d => {
                    const matchLabel = d.match_type === 'question' ? 'Trùng câu hỏi' : 'Trùng câu trả lời';
                    return `<div class="flex items-start gap-2 py-2 border-b border-amber-200 last:border-0">
                        <span class="bg-amber-200 text-amber-800 text-xs font-bold px-2 py-0.5 rounded-full flex-shrink-0">#${d.index}</span>
                        <div class="text-xs">
                            <p class="font-medium text-amber-900">${escapeHtml(d.new_question)}</p>
                            <p class="text-amber-600 mt-0.5">${matchLabel} với ID #${d.existing_id}: "${escapeHtml(d.existing_question)}"</p>
                        </div>
                    </div>`;
                }).join('');

                duplicateHtml = `
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mt-3">
                        <div class="flex items-start gap-3 mb-2">
                            <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                            <div class="w-full">
                                <p class="font-semibold text-amber-800 text-sm">⚠️ Bỏ qua ${data.duplicate_count} câu hỏi trùng lặp</p>
                                <div class="mt-2 max-h-48 overflow-y-auto">${dupRows}</div>
                            </div>
                        </div>
                    </div>`;
            }

            // Show success result
            if (resultDiv) {
                resultDiv.classList.remove('hidden');
                resultDiv.innerHTML = `
                    <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-green-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="font-semibold text-green-800">${escapeHtml(data.message)}</p>
                                <p class="text-sm text-green-600 mt-1">Bạn có thể chỉnh sửa câu hỏi và câu trả lời tại <a href="#" onclick="event.preventDefault();AdminSPA.loadPage('questions')" class="underline font-semibold">Quản lý câu hỏi</a></p>
                            </div>
                        </div>
                    </div>
                    ${duplicateHtml}
                `;
            }

            // Show Q&A preview
            if (data.questions && data.questions.length > 0 && previewSection) {
                previewSection.classList.remove('hidden');
                const listDiv = document.getElementById('qaPreviewList');
                listDiv.innerHTML = data.questions.map((qa, i) => `
                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="flex items-start gap-2 mb-2">
                            <span class="bg-sky-100 text-sky-700 text-xs font-bold px-2 py-0.5 rounded-full flex-shrink-0">Q${i+1}</span>
                            <p class="text-sm font-medium text-gray-800">${escapeHtml(qa.question)}</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="bg-green-100 text-green-700 text-xs font-bold px-2 py-0.5 rounded-full flex-shrink-0">A</span>
                            <p class="text-sm text-gray-600 whitespace-pre-line">${escapeHtml(qa.answer.substring(0, 200))}${qa.answer.length > 200 ? '...' : ''}</p>
                        </div>
                    </div>
                `).join('');
            }

            setTimeout(() => {
                document.getElementById('uploadProgress').classList.add('hidden');
                document.getElementById('uploadBar').style.width = '0%';
                loadDatasets();
            }, 1500);

            // Save upload session so result is restored on F5
            try {
                sessionStorage.setItem('celras_upload_session', JSON.stringify({
                    message: data.message,
                    questions: data.questions || [],
                    duplicate_count: data.duplicate_count || 0,
                    duplicates: data.duplicates || [],
                    ts: Date.now()
                }));
            } catch(e) { /* quota exceeded — ignore */ }
        } else {
            document.getElementById('uploadFileName').textContent = `Lỗi xử lý`;
            if (resultDiv) {
                resultDiv.classList.remove('hidden');
                resultDiv.innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-red-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm text-red-700">${escapeHtml(data.error)}</p>
                        </div>
                    </div>
                `;
            }
            setTimeout(() => {
                document.getElementById('uploadProgress').classList.add('hidden');
                document.getElementById('uploadBar').style.width = '0%';
            }, 2000);
        }
        document.getElementById('fileInput').value = '';
    } catch (e) {
        document.getElementById('uploadFileName').textContent = 'Lỗi kết nối server';
        setTimeout(() => {
            document.getElementById('uploadProgress').classList.add('hidden');
            document.getElementById('uploadBar').style.width = '0%';
        }, 3000);
    }
}

// ==================== DATASETS HISTORY ====================

/**
 * Restore the last upload result from sessionStorage so F5 doesn't lose it.
 * Session expires after 30 minutes of inactivity.
 */
function restoreUploadSession() {
    try {
        const raw = sessionStorage.getItem('celras_upload_session');
        if (!raw) return;
        const saved = JSON.parse(raw);
        // Expire after 30 min
        if (!saved.ts || Date.now() - saved.ts > 30 * 60 * 1000) {
            sessionStorage.removeItem('celras_upload_session');
            return;
        }

        const resultDiv = document.getElementById('uploadResult');
        const previewSection = document.getElementById('qaPreviewSection');
        if (!resultDiv) return;

        // Rebuild duplicate warning HTML
        let duplicateHtml = '';
        if (saved.duplicate_count > 0 && saved.duplicates && saved.duplicates.length > 0) {
            const dupRows = saved.duplicates.map(d => {
                const matchLabel = d.match_type === 'question' ? 'Trùng câu hỏi' : 'Trùng câu trả lời';
                return `<div class="flex items-start gap-2 py-2 border-b border-amber-200 last:border-0">
                    <span class="bg-amber-200 text-amber-800 text-xs font-bold px-2 py-0.5 rounded-full flex-shrink-0">#${d.index}</span>
                    <div class="text-xs">
                        <p class="font-medium text-amber-900">${escapeHtml(d.new_question)}</p>
                        <p class="text-amber-600 mt-0.5">${matchLabel} với ID #${d.existing_id}: "${escapeHtml(d.existing_question)}"</p>
                    </div>
                </div>`;
            }).join('');
            duplicateHtml = `
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mt-3">
                    <div class="flex items-start gap-3 mb-2">
                        <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                        <div class="w-full">
                            <p class="font-semibold text-amber-800 text-sm">⚠️ Bỏ qua ${saved.duplicate_count} câu hỏi trùng lặp</p>
                            <div class="mt-2 max-h-48 overflow-y-auto">${dupRows}</div>
                        </div>
                    </div>
                </div>`;
        }

        // Restore success panel with a "restored" note
        resultDiv.classList.remove('hidden');
        resultDiv.innerHTML = `
            <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-green-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="font-semibold text-green-800">${escapeHtml(saved.message)}</p>
                        <p class="text-sm text-green-600 mt-1">Bạn có thể chỉnh sửa câu hỏi và câu trả lời tại <a href="#" onclick="event.preventDefault();AdminSPA.loadPage('questions')" class="underline font-semibold">Quản lý câu hỏi</a></p>
                        <p class="text-xs text-gray-400 mt-1">(Kết quả được khôi phục sau khi tải lại trang)</p>
                    </div>
                </div>
            </div>
            ${duplicateHtml}
        `;

        // Restore Q&A preview
        if (saved.questions && saved.questions.length > 0 && previewSection) {
            previewSection.classList.remove('hidden');
            const listDiv = document.getElementById('qaPreviewList');
            if (listDiv) {
                listDiv.innerHTML = saved.questions.map((qa, i) => `
                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="flex items-start gap-2 mb-2">
                            <span class="bg-sky-100 text-sky-700 text-xs font-bold px-2 py-0.5 rounded-full flex-shrink-0">Q${i+1}</span>
                            <p class="text-sm font-medium text-gray-800">${escapeHtml(qa.question)}</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="bg-green-100 text-green-700 text-xs font-bold px-2 py-0.5 rounded-full flex-shrink-0">A</span>
                            <p class="text-sm text-gray-600 whitespace-pre-line">${escapeHtml(qa.answer.substring(0, 200))}${qa.answer.length > 200 ? '...' : ''}</p>
                        </div>
                    </div>
                `).join('');
            }
        }
    } catch(e) {
        console.warn('restoreUploadSession error:', e);
    }
}

async function loadDatasets() {
    try {
        const res = await fetch(`${ADMIN_API}/admin/datasets`);
        const data = await res.json();
        const datasets = data.datasets || [];
        renderDatasets(datasets);
    } catch (e) {
        console.error('Failed to load datasets:', e);
    }
}

function renderDatasets(datasets) {
    const tbody = document.getElementById('datasetsBody');
    if (!tbody) return;

    if (!datasets.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-gray-400">Chưa có dữ liệu</td></tr>';
        return;
    }

    const statusBadges = {
        'pending': '<span class="badge badge-warning">Chờ xử lý</span>',
        'processing': '<span class="badge badge-info">Đang xử lý</span>',
        'completed': '<span class="badge badge-success">Hoàn thành</span>',
        'failed': '<span class="badge badge-danger">Thất bại</span>',
    };

    tbody.innerHTML = datasets.map(d => {
        const sizeKB = (d.file_size / 1024).toFixed(1);
        const sizeDisplay = sizeKB > 1024 ? (sizeKB / 1024).toFixed(1) + ' MB' : sizeKB + ' KB';
        const dateDisplay = d.created_at ? new Date(d.created_at).toLocaleString('vi-VN') : '';

        return `<tr>
            <td data-label="Tên file" class="font-medium text-sm">${escapeHtml(d.file_name)}</td>
            <td data-label="Kích thước" class="text-sm text-gray-500">${sizeDisplay}</td>
            <td data-label="Số câu hỏi" class="font-semibold">${d.total_questions || 0} câu</td>
            <td data-label="Trạng thái">${statusBadges[d.status] || d.status}${d.error_message ? '<br><span class="text-xs text-red-500">' + escapeHtml(d.error_message) + '</span>' : ''}</td>
            <td data-label="Ngày tải" class="text-sm text-gray-500">${dateDisplay}</td>
            <td data-label="Thao tác">${d.status === 'completed' && d.total_questions > 0 ? '<a href="#" onclick="event.preventDefault();AdminSPA.loadPage(\'questions\')" class="text-sky-600 hover:text-sky-800 text-sm font-medium">Xem & Sửa</a>' : ''}</td>
        </tr>`;
    }).join('');
}

// ==================== UNANSWERED ====================

async function loadUnanswered() {
    try {
        const res = await fetch(`${ADMIN_API}/admin/unanswered`);
        const data = await res.json();
        const items = data.unanswered || [];
        renderUnanswered(items);
    } catch (e) {
        console.error('Failed to load unanswered:', e);
    }
}

function renderUnanswered(items) {
    const tbody = document.getElementById('unansweredBody');
    if (!items.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-400">Không có câu hỏi chưa trả lời 🎉</td></tr>';
        return;
    }

    tbody.innerHTML = items.map((item, i) => {
        const safeQuestion = escapeHtml(item.question_text);
        const dateDisplay = item.created_at ? new Date(item.created_at).toLocaleString('vi-VN') : '';
        return `
        <tr>
            <td data-label="#">${i + 1}</td>
            <td data-label="Câu hỏi" class="font-medium">${safeQuestion}</td>
            <td data-label="Số lần hỏi"><span class="badge badge-warning">${item.frequency} lần</span></td>
            <td data-label="Trạng thái">${item.is_resolved ? '<span class="badge badge-success">Đã xử lý</span>' : '<span class="badge badge-danger">Chưa xử lý</span>'}</td>
            <td data-label="Ngày" class="text-sm text-gray-500">${dateDisplay}</td>
            <td data-label="Thao tác">
                <div class="flex items-center gap-3 flex-wrap">
                    <button data-question-id="${item.id}" data-question-text="${safeQuestion}" onclick="createAnswerForUnanswered(this)" class="text-sky-600 hover:text-sky-800 text-sm font-medium whitespace-nowrap">
                        + Tạo trả lời
                    </button>
                    ${!item.is_resolved ? `<button data-id="${item.id}" onclick="resolveUnanswered(this)" class="text-green-600 hover:text-green-800 text-sm font-medium" title="Đánh dấu đã xử lý">✓</button>` : ''}
                    <button data-id="${item.id}" onclick="deleteUnanswered(this)" class="text-red-500 hover:text-red-700 text-sm font-medium" title="Xóa">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

function createAnswerForUnanswered(btn) {
    const questionText = btn.getAttribute('data-question-text');
    const div = document.createElement('div');
    div.innerHTML = questionText;
    const rawText = div.textContent;
    // Lưu tạm vào sessionStorage để trang questions đọc lại sau khi SPA load xong
    sessionStorage.setItem('celras_pendingQuestion', rawText);
    if (typeof AdminSPA !== 'undefined' && AdminSPA.initialized) {
        AdminSPA.loadPage('questions', true);
    } else {
        window.location.href = 'questions.html';
    }
}

async function resolveUnanswered(btn) {
    const id = btn.getAttribute('data-id');
    if (!confirm('Đánh dấu câu hỏi này đã được xử lý?')) return;
    try {
        const res = await fetch(`${ADMIN_API}/admin/resolveUnanswered/${id}`, { method: 'PUT' });
        const data = await res.json();
        if (data.success) {
            loadUnanswered();
        } else {
            alert(data.error || 'Lỗi khi cập nhật');
        }
    } catch (e) {
        alert('Lỗi kết nối server');
    }
}

async function deleteUnanswered(btn) {
    const id = btn.getAttribute('data-id');
    if (!confirm('Xóa câu hỏi chưa trả lời này?')) return;
    try {
        const res = await fetch(`${ADMIN_API}/admin/deleteUnanswered/${id}`, { method: 'DELETE' });
        const data = await res.json();
        if (data.success) {
            loadUnanswered();
        } else {
            alert(data.error || 'Lỗi khi xóa');
        }
    } catch (e) {
        alert('Lỗi kết nối server');
    }
}

// ==================== HELPERS ====================

// ==================== FORMS (BIỂU MẪU / GIẤY TỜ) ====================

let allForms = [];

async function loadForms() {
    try {
        const res = await fetch(`${ADMIN_API}/admin/forms`);
        const data = await res.json();
        allForms = data.forms || [];
        renderForms(allForms);
    } catch (e) {
        console.error('Failed to load forms:', e);
    }
}

function renderForms(forms) {
    const tbody = document.getElementById('formsBody');
    if (!tbody) return;
    if (!forms.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-10 text-gray-400">Chưa có biểu mẫu nào. Nhấn "+ Thêm biểu mẫu" để bắt đầu.</td></tr>';
        return;
    }
    tbody.innerHTML = forms.map((f, i) => `
        <tr>
            <td data-label="#" class="text-gray-500 text-center">${i + 1}</td>
            <td data-label="Biểu mẫu">
                <div class="font-medium text-sm">${escapeHtml(f.name)}</div>
                <div class="text-xs text-gray-400 mt-0.5">${escapeHtml((f.description || '').substring(0, 70))}${(f.description || '').length > 70 ? '…' : ''}</div>
            </td>
            <td data-label="Link">
                <a href="${escapeHtml(f.url)}" target="_blank" rel="noopener"
                   class="text-sky-600 hover:underline text-xs break-all">${escapeHtml(f.url.substring(0, 50))}${f.url.length > 50 ? '…' : ''}</a>
            </td>
            <td data-label="Từ khóa" class="text-xs text-gray-500">${escapeHtml(f.keywords || '—')}</td>
            <td data-label="Trạng thái" class="text-center">${f.is_active
                ? '<span class="badge badge-success">Hoạt động</span>'
                : '<span class="badge badge-danger">Tắt</span>'}</td>
            <td data-label="Thao tác">
                <div class="flex items-center gap-2 justify-end">
                    <button onclick="editForm(${f.id})" class="text-sky-600 hover:text-sky-800" title="Sửa">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button onclick="deleteForm(${f.id})" class="text-red-500 hover:text-red-700" title="Xóa">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function filterForms() {
    const q = document.getElementById('formSearch').value.toLowerCase();
    const filtered = allForms.filter(f =>
        f.name.toLowerCase().includes(q) ||
        (f.description || '').toLowerCase().includes(q) ||
        (f.keywords || '').toLowerCase().includes(q)
    );
    renderForms(filtered);
}

const FORM_DRAFT_FIELDS = ['formId', 'formName', 'formDesc', 'formUrl', 'formKeywords', 'formActive'];

function openFormModal() {
    document.getElementById('formModalTitle').textContent = 'Thêm biểu mẫu mới';
    document.getElementById('formId').value = '';
    document.getElementById('formName').value = '';
    document.getElementById('formDesc').value = '';
    document.getElementById('formUrl').value = '';
    document.getElementById('formKeywords').value = '';
    document.getElementById('formActive').checked = true;
    document.getElementById('formModal').classList.add('active');
    FormDraftManager.watchFields('form', FORM_DRAFT_FIELDS);
}

function closeFormModal() {
    document.getElementById('formModal').classList.remove('active');
    FormDraftManager.clearDraft('form');
}

async function editForm(id) {
    const form = allForms.find(f => f.id === id);
    if (!form) return;
    document.getElementById('formModalTitle').textContent = 'Sửa biểu mẫu';
    document.getElementById('formId').value = form.id;
    document.getElementById('formName').value = form.name;
    document.getElementById('formDesc').value = form.description || '';
    document.getElementById('formUrl').value = form.url;
    document.getElementById('formKeywords').value = form.keywords || '';
    document.getElementById('formActive').checked = form.is_active == 1;
    document.getElementById('formModal').classList.add('active');
    FormDraftManager.watchFields('form', FORM_DRAFT_FIELDS);
}

async function saveForm(event) {
    event.preventDefault();
    const id = document.getElementById('formId').value;
    const payload = {
        name:        document.getElementById('formName').value.trim(),
        description: document.getElementById('formDesc').value.trim(),
        url:         document.getElementById('formUrl').value.trim(),
        keywords:    document.getElementById('formKeywords').value.trim(),
        is_active:   document.getElementById('formActive').checked ? 1 : 0,
    };
    if (!payload.name || !payload.url) {
        alert('Tên biểu mẫu và URL là bắt buộc');
        return;
    }
    try {
        const url    = id ? `${ADMIN_API}/admin/form/${id}` : `${ADMIN_API}/admin/forms`;
        const method = id ? 'PUT' : 'POST';
        const res = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (data.success) {
            FormDraftManager.markSaved('form');
            closeFormModal();
            loadForms();
        } else {
            alert(data.error || 'Lỗi khi lưu');
        }
    } catch (e) {
        alert('Lỗi kết nối server');
    }
}

async function deleteForm(id) {
    if (!confirm('Xóa biểu mẫu này?')) return;
    try {
        const res = await fetch(`${ADMIN_API}/admin/form/${id}`, { method: 'DELETE' });
        const data = await res.json();
        if (data.success) loadForms();
        else alert(data.error || 'Lỗi khi xóa');
    } catch (e) {
        alert('Lỗi kết nối server');
    }
}

// ==================== HELPERS ====================

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Đảm bảo các hàm forms có thể truy cập từ toàn cục
window.loadForms      = loadForms;
window.renderForms    = renderForms;
window.filterForms    = filterForms;
window.openFormModal  = openFormModal;
window.closeFormModal = closeFormModal;
window.editForm       = editForm;
window.saveForm       = saveForm;
window.deleteForm     = deleteForm;

// ==================== CATEGORIES (window) ====================
window.loadCategories      = loadCategories;
window.renderCategories    = renderCategories;
window.openCategoryModal   = openCategoryModal;
window.closeCategoryModal  = closeCategoryModal;
window.editCategory        = editCategory;
window.saveCategory        = saveCategory;
window.deleteCategory      = deleteCategory;

/**
 * Escape cho attribute onclick - trả về chuỗi JSON an toàn
 */
function escapeAttr(text) {
    return JSON.stringify(text || '');
}

// ==================== DRAFT RESTORE PER PAGE ====================

/**
 * Kiểm tra và khôi phục draft cho trang questions
 */
function checkQuestionDraft() {
    if (!FormDraftManager.hasDraft('question')) return;
    FormDraftManager.showDraftNotification('question',
        () => {
            // Khôi phục: mở modal và điền dữ liệu
            const draft = FormDraftManager.getDraft('question');
            document.getElementById('modalTitle').textContent = draft.questionId ? 'Sửa câu hỏi' : 'Thêm câu hỏi mới';
            document.getElementById('questionModal').classList.add('active');
            FormDraftManager.restoreDraft('question', QUESTION_DRAFT_FIELDS);
            FormDraftManager.watchFields('question', QUESTION_DRAFT_FIELDS);
        },
        () => { /* Bỏ qua - đã xóa trong showDraftNotification */ }
    );
}

/**
 * Kiểm tra và khôi phục draft cho trang categories
 */
function checkCategoryDraft() {
    if (!FormDraftManager.hasDraft('category')) return;
    FormDraftManager.showDraftNotification('category',
        () => {
            const draft = FormDraftManager.getDraft('category');
            document.getElementById('catModalTitle').textContent = draft.catId ? 'Sửa danh mục' : 'Thêm danh mục mới';
            document.getElementById('categoryModal').classList.add('active');
            FormDraftManager.restoreDraft('category', CATEGORY_DRAFT_FIELDS);
            FormDraftManager.watchFields('category', CATEGORY_DRAFT_FIELDS);
        },
        () => {}
    );
}

/**
 * Kiểm tra và khôi phục draft cho trang forms
 */
function checkFormDraft() {
    if (!FormDraftManager.hasDraft('form')) return;
    FormDraftManager.showDraftNotification('form',
        () => {
            const draft = FormDraftManager.getDraft('form');
            document.getElementById('formModalTitle').textContent = draft.formId ? 'Sửa biểu mẫu' : 'Thêm biểu mẫu mới';
            document.getElementById('formModal').classList.add('active');
            FormDraftManager.restoreDraft('form', FORM_DRAFT_FIELDS);
            FormDraftManager.watchFields('form', FORM_DRAFT_FIELDS);
        },
        () => {}
    );
}

/**
 * Kiểm tra và khôi phục draft cho trang settings
 */
const SETTINGS_DRAFT_FIELDS = ['settingEnabled', 'settingTitle', 'settingWelcome', 'settingNoAnswer',
    'colorPrimary', 'colorHeaderBg', 'colorHeaderText', 'colorUserBubble', 'colorBotBubble', 'colorButton'];

function watchSettingsFields() {
    // Bắt đầu theo dõi các field settings sau khi load xong
    setTimeout(() => {
        FormDraftManager.watchFields('settings', SETTINGS_DRAFT_FIELDS, { debounce: 1000 });
    }, 1500);
}

function checkSettingsDraft() {
    if (!FormDraftManager.hasDraft('settings')) return;
    FormDraftManager.showDraftNotification('settings',
        () => {
            FormDraftManager.restoreDraft('settings', SETTINGS_DRAFT_FIELDS);
            // Sync color text inputs
            ['Primary', 'HeaderBg', 'HeaderText', 'UserBubble', 'BotBubble', 'Button'].forEach(name => {
                if (typeof syncColor === 'function') {
                    try { syncColor(name); } catch(e) {}
                }
            });
            FormDraftManager.watchFields('settings', SETTINGS_DRAFT_FIELDS, { debounce: 1000 });
        },
        () => {}
    );
}

// Export draft functions
window.FormDraftManager     = FormDraftManager;
window.AdminSPA             = AdminSPA;
window.checkQuestionDraft   = checkQuestionDraft;
window.checkCategoryDraft   = checkCategoryDraft;
window.checkFormDraft       = checkFormDraft;
window.checkSettingsDraft   = checkSettingsDraft;
window.watchSettingsFields  = watchSettingsFields;
