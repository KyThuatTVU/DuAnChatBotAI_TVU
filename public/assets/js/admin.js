/**
 * CELRAS TVU - Admin JS
 * Xử lý logic trang quản trị
 */

const ADMIN_API = '/DuAnChatbotThuVien/public/index.php?url=api';

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
    const fallbackAvatar = `https://ui-avatars.com/api/?name=${encodeURIComponent(admin.name)}&background=0369a1&color=fff&rounded=true`;
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
            <td class="text-gray-500">${i + 1}</td>
            <td>
                <div class="font-medium text-sm">${escapeHtml(q.question_text.substring(0, 80))}${q.question_text.length > 80 ? '...' : ''}</div>
                <div class="text-xs text-gray-400 mt-1">${escapeHtml(q.answer_text.substring(0, 60))}...</div>
            </td>
            <td><span class="badge badge-info">${q.category_name || 'Chưa phân loại'}</span></td>
            <td><span class="badge ${q.source_type === 'manual' ? 'badge-success' : 'badge-warning'}">${q.source_type === 'manual' ? 'Nhập tay' : q.source_type.toUpperCase()}</span></td>
            <td>${q.is_active ? '<span class="badge badge-success">Hoạt động</span>' : '<span class="badge badge-danger">Tắt</span>'}</td>
            <td>
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

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Thêm câu hỏi mới';
    document.getElementById('questionId').value = '';
    document.getElementById('questionText').value = '';
    document.getElementById('answerText').value = '';
    document.getElementById('keywordsInput').value = '';
    document.getElementById('questionModal').classList.add('active');
}

function closeModal() {
    document.getElementById('questionModal').classList.remove('active');
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
            document.getElementById('questionModal').classList.add('active');
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
            closeModal();
            loadQuestions();
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

async function loadCategories() {
    try {
        const res = await fetch(`${ADMIN_API}/admin/categories`);
        const data = await res.json();
        const categories = data.categories || [];
        renderCategories(categories);
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
                    <button onclick="editCategory(${c.id}, ${escapeAttr(c.name)}, ${escapeAttr(c.description || '')}, ${c.sort_order})" class="text-sky-600 hover:text-sky-800 p-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button onclick="deleteCategory(${c.id})" class="text-red-500 hover:text-red-700 p-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
            <p class="text-sm text-gray-500 mb-3">${escapeHtml(c.description || 'Không có mô tả')}</p>
            <div class="flex items-center gap-2">
                <span class="badge badge-info">${c.question_count || 0} câu hỏi</span>
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

function openCategoryModal() {
    document.getElementById('catModalTitle').textContent = 'Thêm danh mục mới';
    document.getElementById('catId').value = '';
    document.getElementById('catName').value = '';
    document.getElementById('catDescription').value = '';
    document.getElementById('catOrder').value = '0';
    document.getElementById('categoryModal').classList.add('active');
}

function closeCategoryModal() {
    document.getElementById('categoryModal').classList.remove('active');
}

function editCategory(id, name, desc, order) {
    document.getElementById('catModalTitle').textContent = 'Sửa danh mục';
    document.getElementById('catId').value = id;
    document.getElementById('catName').value = name;
    document.getElementById('catDescription').value = desc;
    document.getElementById('catOrder').value = order;
    document.getElementById('categoryModal').classList.add('active');
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
    } catch (e) {}
}

function renderThemes(themes) {
    const container = document.getElementById('themesList');
    if (!container) return;

    if (!themes.length) {
        container.innerHTML = '<p class="text-gray-400 text-sm">Chưa có chủ đề</p>';
        return;
    }

    container.innerHTML = themes.map(t => `
        <div class="flex items-center gap-3 p-3 rounded-lg border ${t.is_active ? 'border-sky-300 bg-sky-50' : 'border-gray-200'} transition">
            <div class="w-8 h-8 rounded-full border-2 border-white shadow" style="background: ${t.primary_color}"></div>
            <div class="flex-1">
                <p class="font-medium text-sm">${escapeHtml(t.theme_name)}</p>
                <p class="text-xs text-gray-500">${t.start_date ? t.start_date + ' - ' + t.end_date : 'Không giới hạn'}</p>
            </div>
            ${t.is_active ? '<span class="badge badge-success">Đang dùng</span>' : '<span class="badge badge-warning">Tắt</span>'}
        </div>
    `).join('');
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
                                <p class="text-sm text-green-600 mt-1">Bạn có thể chỉnh sửa câu hỏi và câu trả lời tại <a href="questions.html?source=word" class="underline font-semibold">Quản lý câu hỏi</a></p>
                            </div>
                        </div>
                    </div>
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
            <td class="font-medium text-sm">${escapeHtml(d.file_name)}</td>
            <td class="text-sm text-gray-500">${sizeDisplay}</td>
            <td class="font-semibold">${d.total_questions || 0} câu</td>
            <td>${statusBadges[d.status] || d.status}${d.error_message ? '<br><span class="text-xs text-red-500">' + escapeHtml(d.error_message) + '</span>' : ''}</td>
            <td class="text-sm text-gray-500">${dateDisplay}</td>
            <td>${d.status === 'completed' && d.total_questions > 0 ? '<a href="questions.html?source=word" class="text-sky-600 hover:text-sky-800 text-sm font-medium">Xem & Sửa</a>' : ''}</td>
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
            <td>${i + 1}</td>
            <td class="font-medium">${safeQuestion}</td>
            <td><span class="badge badge-warning">${item.frequency} lần</span></td>
            <td>${item.is_resolved ? '<span class="badge badge-success">Đã xử lý</span>' : '<span class="badge badge-danger">Chưa xử lý</span>'}</td>
            <td class="text-sm text-gray-500">${dateDisplay}</td>
            <td>
                <div class="flex items-center gap-2">
                    <button data-question-id="${item.id}" data-question-text="${safeQuestion}" onclick="createAnswerForUnanswered(this)" class="text-sky-600 hover:text-sky-800 text-sm font-medium">
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
    // Decode HTML entities back to raw text for URL encoding
    const div = document.createElement('div');
    div.innerHTML = questionText;
    const rawText = div.textContent;
    window.location.href = `questions.html?autoAdd=${encodeURIComponent(rawText)}`;
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

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Escape cho attribute onclick - trả về chuỗi JSON an toàn
 */
function escapeAttr(text) {
    return JSON.stringify(text || '');
}
