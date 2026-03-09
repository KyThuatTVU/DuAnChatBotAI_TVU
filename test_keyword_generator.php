<?php
/**
 * Test Script - Keyword Generator
 * Kiểm tra chức năng tự động tạo từ khóa
 */

require_once __DIR__ . '/app/helpers/KeywordGenerator.php';

echo "=== TEST KEYWORD GENERATOR ===\n\n";

// Test cases
$testCases = [
    [
        'question' => 'Làm thế nào để mượn sách tại thư viện?',
        'description' => 'Câu hỏi cơ bản về mượn sách',
    ],
    [
        'question' => 'Quy trình đăng ký gia hạn thẻ thư viện như thế nào?',
        'description' => 'Câu hỏi về quy trình',
    ],
    [
        'question' => 'Thư viện mở cửa lúc mấy giờ?',
        'description' => 'Câu hỏi về giờ mở cửa',
    ],
    [
        'question' => 'Làm sao để tra cứu tài liệu luận văn trên hệ thống?',
        'description' => 'Câu hỏi về tra cứu',
    ],
    [
        'question' => 'Sinh viên có được sử dụng wifi miễn phí không?',
        'description' => 'Câu hỏi về dịch vụ',
    ],
    [
        'question' => 'Phí phạt khi trả sách trễ hạn là bao nhiêu?',
        'description' => 'Câu hỏi về phí phạt',
    ],
    [
        'question' => 'How to borrow books from library?',
        'description' => 'Câu hỏi tiếng Anh (test)',
    ],
];

foreach ($testCases as $index => $test) {
    echo "--- Test Case " . ($index + 1) . " ---\n";
    echo "Mô tả: {$test['description']}\n";
    echo "Câu hỏi: \"{$test['question']}\"\n\n";
    
    $keywords = KeywordGenerator::generate($test['question']);
    
    echo "Từ khóa tiếng Việt (" . count($keywords['vi']) . "):\n";
    foreach ($keywords['vi'] as $kw) {
        echo "  - {$kw}\n";
    }
    
    echo "\nTừ khóa tiếng Anh (" . count($keywords['en']) . "):\n";
    if (empty($keywords['en'])) {
        echo "  (Không có)\n";
    } else {
        foreach ($keywords['en'] as $kw) {
            echo "  - {$kw}\n";
        }
    }
    
    echo "\n" . str_repeat("=", 60) . "\n\n";
}

// Test từ điển
echo "=== TEST TỪ ĐIỂN ===\n\n";
$dict = KeywordGenerator::getDictionary();
echo "Tổng số từ trong từ điển: " . count($dict) . "\n\n";

echo "Một số từ trong từ điển:\n";
$count = 0;
foreach ($dict as $vi => $en) {
    echo "  {$vi} → {$en}\n";
    $count++;
    if ($count >= 10) break;
}

echo "\n=== TEST THÊM TỪ VÀO TỪ ĐIỂN ===\n\n";
KeywordGenerator::addToDictionary('gia hạn', 'renew');
KeywordGenerator::addToDictionary('đặt trước', 'reserve');

echo "Đã thêm: gia hạn → renew\n";
echo "Đã thêm: đặt trước → reserve\n\n";

// Test lại với từ mới
$testNew = 'Làm sao để gia hạn và đặt trước sách?';
echo "Test với câu hỏi mới: \"{$testNew}\"\n\n";
$keywordsNew = KeywordGenerator::generate($testNew);

echo "Từ khóa tiếng Việt:\n";
foreach ($keywordsNew['vi'] as $kw) {
    echo "  - {$kw}\n";
}

echo "\nTừ khóa tiếng Anh:\n";
foreach ($keywordsNew['en'] as $kw) {
    echo "  - {$kw}\n";
}

echo "\n=== TEST HOÀN TẤT ===\n";
