<?php
// Fix Vietnamese encoding for event themes
$host = 'localhost';
$db = 'chatbot_thuvien';
$user = 'root';
$pass = 'TVU@842004';

$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// Delete all existing themes except default
$pdo->exec("DELETE FROM event_themes WHERE id > 1");

// Update default theme name
$pdo->exec("UPDATE event_themes SET theme_key='mac-dinh', decorations=NULL, banner_text=NULL, theme_name=N'" . html_entity_decode('M&#7863;c &#273;&#7883;nh', ENT_HTML5, 'UTF-8') . "' WHERE id=1");

// Actually let's just use raw UTF-8 strings via prepared statements
$stmt = $pdo->prepare("UPDATE event_themes SET theme_name = ?, theme_key = 'mac-dinh', decorations = NULL, banner_text = NULL WHERE id = 1");
$stmt->execute(["M\xE1\xBA\xB7c \xC4\x91\xE1\xBB\x8Bnh"]);

$themes = [
    [
        "T\xE1\xBA\xBFt Nguy\xC3\xAAn \xC4\x90\xC3\xA1n",  // Tết Nguyên Đán
        'tet', '#dc2626', '#fbbf24', '#dc2626', '#ffffff',
        '#fbbf24', '#fff1f2', '#dc2626', "'Playfair Display', serif",
        NULL,
        '["🧧","🎆","🌸","🏮","🎊","🎋","🐉"]',
        "Ch\xC3\xBAc M\xE1\xBB\xABng N\xC4\x83m M\xE1\xBB\x9Bi! \xF0\x9F\xA7\xA7",  // Chúc Mừng Năm Mới! 🧧
        "Ch\xC3\xA0o m\xE1\xBB\xABng n\xC4\x83m m\xE1\xBB\x9Bi! Ch\xC3\xBAc b\xE1\xBA\xA1n n\xC4\x83m m\xE1\xBB\x9Bi an khang th\xE1\xBB\x8Bnh v\xC6\xB0\xE1\xBB\xA3ng! \xF0\x9F\x8C\xB8\xF0\x9F\xA7\xA7",  // Chào mừng năm mới!...
        NULL, NULL
    ],
    [
        "T\xE1\xBA\xBFt Trung Thu",  // Tết Trung Thu
        'trung-thu', '#f59e0b', '#7c3aed', '#1e3a5f', '#fbbf24',
        '#fef3c7', '#ede9fe', '#f59e0b', "'Quicksand', sans-serif",
        NULL,
        '["🌕","🏮","🥮","🐇","⭐","🎑","🎐"]',
        "Vui T\xE1\xBA\xBFt Trung Thu! \xF0\x9F\x8C\x95",  // Vui Tết Trung Thu! 🌕
        "Ch\xC3\xBAc b\xE1\xBA\xA1n T\xE1\xBA\xBFt Trung Thu vui v\xE1\xBA\xBB! T\xC3\xB4i c\xC3\xB3 th\xE1\xBB\x83 gi\xC3\xBAp g\xC3\xAC cho b\xE1\xBA\xA1n? \xF0\x9F\x8F\xAE",  // Chúc bạn Tết Trung Thu vui vẻ!...
        NULL, NULL
    ],
    [
        'Halloween',
        'halloween', '#f97316', '#6b21a8', '#1a1a2e', '#f97316',
        '#f97316', '#3b0764', '#f97316', "'Creepster', cursive",
        NULL,
        '["🎃","👻","🦇","🕸️","💀","🕯️","🧙"]',
        "Happy Halloween! \xF0\x9F\x8E\x83",
        "Chuy\xE1\xBB\x87n g\xC3\xAC \xC4\x91\xC3\xA3 \xC4\x91\xC6\xB0a b\xE1\xBA\xA1n \xC4\x91\xE1\xBA\xBFn \xC4\x91\xC3\xA2y trong \xC4\x91\xC3\xAAm Halloween n\xC3\xA0y? \xF0\x9F\x91\xBB",  // Chuyện gì đã đưa bạn đến đây trong đêm Halloween này? 👻
        NULL, NULL
    ],
    [
        "Gi\xC3\xA1ng Sinh",  // Giáng Sinh
        'giang-sinh', '#16a34a', '#dc2626', '#0c4a2f', '#ffffff',
        '#dc2626', '#dcfce7', '#16a34a', "'Mountains of Christmas', cursive",
        NULL,
        '["🎄","🎅","⛄","🎁","❄️","🔔","⭐"]',
        "Merry Christmas! \xF0\x9F\x8E\x84",
        "Gi\xC3\xA1ng Sinh vui v\xE1\xBA\xBB! T\xC3\xB4i l\xC3\xA0 tr\xE1\xBB\xA3 l\xC3\xBD th\xC6\xB0 vi\xE1\xBB\x87n, ch\xC3\xBAc b\xE1\xBA\xA1n m\xC3\xB9a l\xE1\xBB\x85 an l\xC3\xA0nh! \xE2\x9D\x84\xEF\xB8\x8F",  // Giáng Sinh vui vẻ!...
        NULL, NULL
    ],
    [
        "Qu\xE1\xBB\x91c t\xE1\xBA\xBF Ph\xE1\xBB\xA5 n\xE1\xBB\xAF 8/3",  // Quốc tế Phụ nữ 8/3
        '8-3', '#ec4899', '#f472b6', '#be185d', '#ffffff',
        '#ec4899', '#fce7f3', '#ec4899', "'Dancing Script', cursive",
        NULL,
        '["🌸","🌷","💐","🌹","💝","🎀","✨"]',
        "Ch\xC3\xBAc M\xE1\xBB\xABng 8/3! \xF0\x9F\x8C\xB8",  // Chúc Mừng 8/3! 🌸
        "Ch\xC3\xBAc m\xE1\xBB\xABng Ng\xC3\xA0y Qu\xE1\xBB\x91c t\xE1\xBA\xBF Ph\xE1\xBB\xA5 n\xE1\xBB\xAF! Ch\xC3\xBAc b\xE1\xBA\xA1n lu\xC3\xB4n h\xE1\xBA\xA1nh ph\xC3\xBAc v\xC3\xA0 \xC4\x91\xE1\xBA\xB9p r\xE1\xBA\xA1ng ng\xE1\xBB\x9Di! \xF0\x9F\x92\x90",  // Chúc mừng Ngày Quốc tế Phụ nữ!...
        NULL, NULL
    ],
    [
        "Ph\xE1\xBB\xA5 n\xE1\xBB\xAF Vi\xE1\xBB\x87t Nam 20/10",  // Phụ nữ Việt Nam 20/10
        '20-10', '#e11d48', '#fb7185', '#9f1239', '#ffffff',
        '#e11d48', '#ffe4e6', '#e11d48', "'Pacifico', cursive",
        NULL,
        '["🌹","💖","👩","🎀","💐","🌺","✨"]',
        "Ch\xC3\xBAc M\xE1\xBB\xABng 20/10! \xF0\x9F\x8C\xB9",  // Chúc Mừng 20/10! 🌹
        "Ch\xC3\xBAc m\xE1\xBB\xABng Ng\xC3\xA0y Ph\xE1\xBB\xA5 n\xE1\xBB\xAF Vi\xE1\xBB\x87t Nam! Ch\xC3\xBAc ch\xE1\xBB\x8B em lu\xC3\xB4n t\xC6\xB0\xC6\xA1i \xC4\x91\xE1\xBA\xB9p! \xF0\x9F\x92\x96",  // Chúc mừng Ngày Phụ nữ Việt Nam!...
        NULL, NULL
    ],
    [
        "Nh\xC3\xA0 gi\xC3\xA1o Vi\xE1\xBB\x87t Nam 20/11",  // Nhà giáo Việt Nam 20/11
        '20-11', '#7c3aed', '#a78bfa', '#4c1d95', '#ffffff',
        '#7c3aed', '#ede9fe', '#7c3aed', "'Libre Baskerville', serif",
        NULL,
        '["📚","🎓","✏️","🏫","🌻","💐","⭐"]',
        "Ch\xC3\xBAc M\xE1\xBB\xABng 20/11! \xF0\x9F\x93\x9A",  // Chúc Mừng 20/11! 📚
        "Ch\xC3\xBAc m\xE1\xBB\xABng Ng\xC3\xA0y Nh\xC3\xA0 gi\xC3\xA1o Vi\xE1\xBB\x87t Nam! T\xC3\xB4n vinh c\xC3\xA1c th\xE1\xBA\xA7y c\xC3\xB4! \xF0\x9F\x8E\x93",  // Chúc mừng Ngày Nhà giáo VN!...
        NULL, NULL
    ],
    [
        "Gi\xE1\xBA\xA3i ph\xC3\xB3ng mi\xE1\xBB\x81n Nam 30/4",  // Giải phóng miền Nam 30/4
        '30-4', '#dc2626', '#fbbf24', '#7f1d1d', '#fbbf24',
        '#dc2626', '#fef9c3', '#dc2626', "'Roboto Slab', serif",
        NULL,
        '["🇻🇳","⭐","🎆","🏵️","🎊","🕊️","🌟"]',
        "M\xE1\xBB\xABng 30/4! \xF0\x9F\x87\xBB\xF0\x9F\x87\xB3",  // Mừng 30/4! 🇻🇳
        "K\xE1\xBB\xB7 ni\xE1\xBB\x87m Ng\xC3\xA0y Gi\xE1\xBA\xA3i ph\xC3\xB3ng mi\xE1\xBB\x81n Nam, th\xE1\xBB\x91ng nh\xE1\xBA\xA5t \xC4\x91\xE1\xBA\xA5t n\xC6\xB0\xE1\xBB\x9Bc! \xF0\x9F\x8E\x86",  // Kỷ niệm Ngày Giải phóng miền Nam...
        NULL, NULL
    ],
    [
        "Qu\xE1\xBB\x91c t\xE1\xBA\xBF Lao \xC4\x91\xE1\xBB\x99ng 1/5",  // Quốc tế Lao động 1/5
        '1-5', '#2563eb', '#3b82f6', '#1e3a5f', '#ffffff',
        '#2563eb', '#dbeafe', '#2563eb', "'Montserrat', sans-serif",
        NULL,
        '["👷","🔧","⚙️","🏗️","💪","🌟","🎊"]',
        "Ch\xC3\xBAc M\xE1\xBB\xABng 1/5! \xF0\x9F\x91\xB7",  // Chúc Mừng 1/5! 👷
        "Ch\xC3\xBAc m\xE1\xBB\xABng Ng\xC3\xA0y Qu\xE1\xBB\x91c t\xE1\xBA\xBF Lao \xC4\x91\xE1\xBB\x99ng! Vinh danh ng\xC6\xB0\xE1\xBB\x9Di lao \xC4\x91\xE1\xBB\x99ng! \xF0\x9F\x92\xAA",  // Chúc mừng Ngày Quốc tế Lao động!...
        NULL, NULL
    ],
    [
        "Qu\xE1\xBB\x91c kh\xC3\xA1nh 2/9",  // Quốc khánh 2/9
        '2-9', '#dc2626', '#fbbf24', '#7f1d1d', '#ffffff',
        '#dc2626', '#fef9c3', '#dc2626', "'Playfair Display', serif",
        NULL,
        '["🇻🇳","🎆","⭐","🎊","🏵️","🎇","🌟"]',
        "M\xE1\xBB\xABng Qu\xE1\xBB\x91c Kh\xC3\xA1nh 2/9! \xF0\x9F\x87\xBB\xF0\x9F\x87\xB3",  // Mừng Quốc Khánh 2/9! 🇻🇳
        "Ch\xC3\xBAc m\xE1\xBB\xABng Ng\xC3\xA0y Qu\xE1\xBB\x91c kh\xC3\xA1nh n\xC6\xB0\xE1\xBB\x9Bc C\xE1\xBB\x99ng h\xC3\xB2a X\xC3\xA3 h\xE1\xBB\x99i Ch\xE1\xBB\xA7 ngh\xC4\xA9a Vi\xE1\xBB\x87t Nam! \xF0\x9F\x8E\x86",  // Chúc mừng Ngày Quốc khánh CHXHCN VN!...
        NULL, NULL
    ]
];

$sql = "INSERT INTO event_themes (theme_name, theme_key, primary_color, secondary_color, header_bg_color, header_text_color, user_bubble_color, bot_bubble_color, button_color, font_family, bot_avatar_url, decorations, banner_text, welcome_message, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);

foreach ($themes as $t) {
    $stmt->execute($t);
}

echo "Done! Inserted " . count($themes) . " themes.\n";

// Verify
$rows = $pdo->query("SELECT id, theme_name, theme_key FROM event_themes ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "ID: {$r['id']} | Name: {$r['theme_name']} | Key: {$r['theme_key']}\n";
}
