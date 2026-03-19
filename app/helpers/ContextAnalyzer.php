<?php
/**
 * ContextAnalyzer - Hệ thống phân tích ngữ cảnh thông minh
 * Sử dụng TF-IDF và Semantic Similarity để hiểu ngữ cảnh
 * Không dựa vào pattern cứng nhắc
 */
class ContextAnalyzer
{
    // Cache để tối ưu performance
    private static $idfCache = [];
    private static $stopWords = null;
    
    /**
     * Phân tích ngữ cảnh đầy đủ của câu hỏi
     * @param string $userMessage Câu hỏi người dùng
     * @return array
     */
    public static function analyze(string $userMessage): array
    {
        $normalized = mb_strtolower(trim($userMessage));
        
        return [
            'normalized' => $normalized,
            'tokens' => self::tokenize($normalized),
            'important_words' => self::extractImportantWords($normalized),
            'ngrams' => self::generateNGrams($normalized),
        ];
    }

    /**
     * Tokenize văn bản thành các từ
     */
    private static function tokenize(string $text): array
    {
        // Loại bỏ dấu câu
        $text = preg_replace('/[?!.,;:()"\[\]{}]+/u', ' ', $text);
        
        // Tách từ
        $words = preg_split('/\s+/u', $text);
        
        // Loại bỏ từ rỗng
        return array_filter($words, function($w) {
            return mb_strlen(trim($w)) > 0;
        });
    }
    
    /**
     * Lấy danh sách stop words
     */
    private static function getStopWords(): array
    {
        if (self::$stopWords !== null) {
            return self::$stopWords;
        }
        
        self::$stopWords = [
            // Tiếng Việt
            'là', 'của', 'và', 'có', 'được', 'trong', 'ở', 'tại', 'với', 'cho',
            'để', 'từ', 'đến', 'về', 'như', 'khi', 'nào', 'đâu', 'sao', 'gì',
            'thế', 'không', 'các', 'những', 'một', 'này', 'đó', 'thì',
            'hay', 'hoặc', 'nhưng', 'mà', 'nếu', 'vì', 'do', 'bởi', 'theo',
            'đã', 'sẽ', 'đang', 'vẫn', 'còn', 'cũng', 'rằng',
            'tôi', 'mình', 'bạn', 'em', 'anh', 'chị',
            'xin', 'vui', 'lòng', 'ơi', 'nhé', 'nha', 'ạ',
            // Tiếng Anh
            'the', 'a', 'an', 'is', 'are', 'was', 'were', 'be', 'been',
            'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would',
            'can', 'could', 'may', 'might', 'must', 'shall', 'should',
            'i', 'you', 'he', 'she', 'it', 'we', 'they',
            'in', 'on', 'at', 'to', 'for', 'of', 'with', 'from', 'by',
            'and', 'or', 'but', 'if', 'what', 'which', 'who', 'when', 'where',
        ];
        
        return self::$stopWords;
    }
    
    /**
     * Trích xuất từ quan trọng (loại bỏ stop words)
     */
    private static function extractImportantWords(string $text): array
    {
        $tokens = self::tokenize($text);
        $stopWords = self::getStopWords();
        
        $important = array_filter($tokens, function($word) use ($stopWords) {
            return mb_strlen($word) >= 2 && !in_array($word, $stopWords);
        });
        
        return array_values($important);
    }
    
    /**
     * Tạo N-grams (cụm từ 2-4 từ)
     */
    private static function generateNGrams(string $text, int $maxN = 4): array
    {
        $tokens = self::tokenize($text);
        $ngrams = [];
        $tokenCount = count($tokens);
        
        // Unigrams (từ đơn) - đã có trong tokens
        
        // Bigrams (2 từ)
        if ($tokenCount >= 2) {
            for ($i = 0; $i < $tokenCount - 1; $i++) {
                $ngrams[] = $tokens[$i] . ' ' . $tokens[$i + 1];
            }
        }
        
        // Trigrams (3 từ)
        if ($maxN >= 3 && $tokenCount >= 3) {
            for ($i = 0; $i < $tokenCount - 2; $i++) {
                $ngrams[] = $tokens[$i] . ' ' . $tokens[$i + 1] . ' ' . $tokens[$i + 2];
            }
        }
        
        // 4-grams
        if ($maxN >= 4 && $tokenCount >= 4) {
            for ($i = 0; $i < $tokenCount - 3; $i++) {
                $ngrams[] = $tokens[$i] . ' ' . $tokens[$i + 1] . ' ' . $tokens[$i + 2] . ' ' . $tokens[$i + 3];
            }
        }
        
        return $ngrams;
    }

    /**
     * Tính TF (Term Frequency) cho một document
     */
    private static function calculateTF(array $terms, array $allTerms): array
    {
        $tf = [];
        $totalTerms = count($allTerms);
        
        if ($totalTerms == 0) {
            return $tf;
        }
        
        foreach ($terms as $term) {
            $count = 0;
            foreach ($allTerms as $t) {
                if ($t === $term) {
                    $count++;
                }
            }
            $tf[$term] = $count / $totalTerms;
        }
        
        return $tf;
    }
    
    /**
     * Tính Cosine Similarity giữa 2 vectors
     */
    private static function cosineSimilarity(array $vec1, array $vec2): float
    {
        // Lấy tất cả các keys
        $allKeys = array_unique(array_merge(array_keys($vec1), array_keys($vec2)));
        
        if (empty($allKeys)) {
            return 0.0;
        }
        
        $dotProduct = 0.0;
        $magnitude1 = 0.0;
        $magnitude2 = 0.0;
        
        foreach ($allKeys as $key) {
            $val1 = $vec1[$key] ?? 0.0;
            $val2 = $vec2[$key] ?? 0.0;
            
            $dotProduct += $val1 * $val2;
            $magnitude1 += $val1 * $val1;
            $magnitude2 += $val2 * $val2;
        }
        
        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);
        
        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0.0;
        }
        
        return $dotProduct / ($magnitude1 * $magnitude2);
    }
    
    /**
     * Tính Jaccard Similarity giữa 2 sets
     */
    private static function jaccardSimilarity(array $set1, array $set2): float
    {
        if (empty($set1) && empty($set2)) {
            return 0.0;
        }
        
        $intersection = array_intersect($set1, $set2);
        $union = array_unique(array_merge($set1, $set2));
        
        if (empty($union)) {
            return 0.0;
        }
        
        return count($intersection) / count($union);
    }
    
    /**
     * Tính độ tương đồng giữa 2 từ (synonym detection)
     * Sử dụng Levenshtein distance và common patterns
     */
    private static function wordSimilarity(string $word1, string $word2): float
    {
        // Exact match
        if ($word1 === $word2) {
            return 1.0;
        }
        
        // Substring match
        if (mb_strpos($word1, $word2) !== false || mb_strpos($word2, $word1) !== false) {
            return 0.8;
        }
        
        // Levenshtein distance (cho từ tương tự)
        $maxLen = max(mb_strlen($word1), mb_strlen($word2));
        if ($maxLen <= 3) {
            return 0.0; // Từ quá ngắn, không so sánh
        }
        
        $distance = levenshtein($word1, $word2);
        $similarity = 1 - ($distance / $maxLen);
        
        // Chỉ chấp nhận nếu similarity >= 0.7 (70%)
        return $similarity >= 0.7 ? $similarity : 0.0;
    }
    
    /**
     * Tính điểm tương đồng ngữ nghĩa nâng cao
     * Bao gồm cả synonym detection
     */
    private static function semanticWordMatch(array $userWords, array $dbWords): float
    {
        if (empty($userWords) || empty($dbWords)) {
            return 0.0;
        }
        
        $totalScore = 0;
        $matchedCount = 0;
        
        foreach ($userWords as $userWord) {
            $bestMatch = 0;
            
            foreach ($dbWords as $dbWord) {
                $similarity = self::wordSimilarity($userWord, $dbWord);
                if ($similarity > $bestMatch) {
                    $bestMatch = $similarity;
                }
            }
            
            if ($bestMatch > 0) {
                $totalScore += $bestMatch;
                $matchedCount++;
            }
        }
        
        return count($userWords) > 0 ? $totalScore / count($userWords) : 0.0;
    }
    
    /**
     * Tính điểm semantic similarity giữa 2 câu hỏi
     * Sử dụng nhiều phương pháp kết hợp
     * 
     * @param string $userQuestion Câu hỏi người dùng
     * @param string $dbQuestion Câu hỏi trong DB
     * @param string $dbAnswer Câu trả lời trong DB (để tăng context)
     * @return float Điểm từ 0-1
     */
    public static function calculateSemanticSimilarity(
        string $userQuestion, 
        string $dbQuestion, 
        string $dbAnswer = ''
    ): float {
        // Phân tích cả 2 câu hỏi
        $userContext = self::analyze($userQuestion);
        $dbContext = self::analyze($dbQuestion);
        
        $scores = [];
        
        // 1. EXACT MATCH (100%)
        if (mb_strtolower(trim($userQuestion)) === mb_strtolower(trim($dbQuestion))) {
            return 1.0;
        }
        
        // 2. N-GRAM OVERLAP (30%)
        // Tính độ tương đồng của các cụm từ
        $userNgrams = array_merge($userContext['tokens'], $userContext['ngrams']);
        $dbNgrams = array_merge($dbContext['tokens'], $dbContext['ngrams']);
        
        $ngramScore = self::jaccardSimilarity($userNgrams, $dbNgrams);
        $scores['ngram'] = $ngramScore * 0.30;
        
        // 3. IMPORTANT WORDS OVERLAP với SYNONYM DETECTION (25%)
        // Không chỉ so sánh exact match, mà còn tìm từ tương tự
        $importantScore = self::semanticWordMatch(
            $userContext['important_words'], 
            $dbContext['important_words']
        );
        $scores['important'] = $importantScore * 0.25;
        
        // 4. COSINE SIMILARITY với TF (20%)
        // Tính TF cho cả 2 câu
        $userTF = self::calculateTF(
            $userContext['important_words'], 
            $userContext['tokens']
        );
        $dbTF = self::calculateTF(
            $dbContext['important_words'], 
            $dbContext['tokens']
        );
        
        $cosineScore = self::cosineSimilarity($userTF, $dbTF);
        $scores['cosine'] = $cosineScore * 0.20;
        
        // 5. ANSWER CONTEXT với SEMANTIC MATCHING (15%)
        // Kiểm tra xem từ khóa của user có xuất hiện trong answer không
        // Sử dụng semantic matching thay vì exact match
        if (!empty($dbAnswer)) {
            $answerContext = self::analyze($dbAnswer);
            $answerScore = self::semanticWordMatch(
                $userContext['important_words'],
                $answerContext['important_words']
            );
            $scores['answer'] = $answerScore * 0.15;
        } else {
            $scores['answer'] = 0;
        }
        
        // 6. LENGTH SIMILARITY (10%)
        // Câu hỏi có độ dài tương đương thường liên quan
        $userLen = count($userContext['tokens']);
        $dbLen = count($dbContext['tokens']);
        
        if ($userLen > 0 && $dbLen > 0) {
            $lenDiff = abs($userLen - $dbLen);
            $avgLen = ($userLen + $dbLen) / 2;
            $lenScore = 1 - min($lenDiff / $avgLen, 1);
            $scores['length'] = $lenScore * 0.10;
        } else {
            $scores['length'] = 0;
        }
        
        // Tổng điểm
        $totalScore = array_sum($scores);
        
        return min($totalScore, 1.0);
    }
    
    /**
     * Tính điểm tương đồng với boost cho các yếu tố đặc biệt
     * 
     * @param string $userQuestion
     * @param string $dbQuestion
     * @param string $dbAnswer
     * @return array ['score' => float, 'details' => array]
     */
    public static function calculateAdvancedSimilarity(
        string $userQuestion,
        string $dbQuestion,
        string $dbAnswer = ''
    ): array {
        $baseScore = self::calculateSemanticSimilarity($userQuestion, $dbQuestion, $dbAnswer);
        
        $userNorm = mb_strtolower(trim($userQuestion));
        $dbNorm = mb_strtolower(trim($dbQuestion));
        
        $boosts = [];
        $penalties = [];
        
        // BOOST 1: Số khớp chính xác (+15%)
        preg_match_all('/\d+/', $userNorm, $userNumbers);
        preg_match_all('/\d+/', $dbNorm, $dbNumbers);
        
        if (!empty($userNumbers[0]) && !empty($dbNumbers[0])) {
            $commonNumbers = array_intersect($userNumbers[0], $dbNumbers[0]);
            if (!empty($commonNumbers)) {
                $boosts['exact_numbers'] = 0.15;
            } else {
                // Số khác nhau → penalty
                $penalties['different_numbers'] = -0.20;
            }
        }
        
        // BOOST 2: Cụm từ quan trọng khớp - TỰ ĐỘNG phát hiện (+10%)
        // Tìm các bigram và trigram quan trọng (không phải stop words)
        $userContext = self::analyze($userQuestion);
        $dbContext = self::analyze($dbQuestion);
        
        // Lấy các n-gram quan trọng (2-3 từ, không có stop words)
        $userImportantNgrams = [];
        $dbImportantNgrams = [];
        
        // Tạo bigrams từ important words
        $userWords = $userContext['important_words'];
        $dbWords = $dbContext['important_words'];
        
        for ($i = 0; $i < count($userWords) - 1; $i++) {
            $userImportantNgrams[] = $userWords[$i] . ' ' . $userWords[$i + 1];
        }
        
        for ($i = 0; $i < count($dbWords) - 1; $i++) {
            $dbImportantNgrams[] = $dbWords[$i] . ' ' . $dbWords[$i + 1];
        }
        
        // Kiểm tra có bigram nào khớp không
        $commonNgrams = array_intersect($userImportantNgrams, $dbImportantNgrams);
        if (!empty($commonNgrams)) {
            // Boost dựa trên số lượng n-gram khớp
            $ngramBoost = min(count($commonNgrams) * 0.05, 0.15); // Tối đa +15%
            $boosts['important_ngrams'] = $ngramBoost;
        }
        
        // BOOST 3: Từ phủ định khớp (+5%)
        $negativeWords = ['không', 'chưa', 'chẳng', 'không có', 'not', 'no', 'never'];
        $userHasNegative = false;
        $dbHasNegative = false;
        
        foreach ($negativeWords as $neg) {
            if (mb_strpos($userNorm, $neg) !== false) $userHasNegative = true;
            if (mb_strpos($dbNorm, $neg) !== false) $dbHasNegative = true;
        }
        
        if ($userHasNegative === $dbHasNegative) {
            $boosts['negative_match'] = 0.05;
        } else {
            // Một câu có phủ định, một câu không → penalty nhẹ
            $penalties['negative_mismatch'] = -0.05;
        }
        
        // PENALTY 1: Câu quá ngắn so với câu dài (-10%)
        $userLen = mb_strlen($userNorm);
        $dbLen = mb_strlen($dbNorm);
        
        if (($userLen < 10 && $dbLen > 30) || ($dbLen < 10 && $userLen > 30)) {
            $penalties['length_mismatch'] = -0.10;
        }
        
        // PENALTY 2: Từ khóa chính KHÁC NHAU (-40%) - QUAN TRỌNG!
        // Phát hiện từ khóa chính (danh từ quan trọng) không khớp
        $userContext = self::analyze($userQuestion);
        $dbContext = self::analyze($dbQuestion);
        
        $userMainWords = $userContext['important_words'];
        $dbMainWords = $dbContext['important_words'];
        
        // Loại bỏ các từ quá phổ biến (động từ, từ chung chung)
        $commonWords = [
            // Động từ
            'mượn', 'trả', 'đăng', 'ký', 'gia', 'hạn', 'tìm', 'tra', 'cứu', 
            'đặt', 'yêu', 'cầu', 'borrow', 'return', 'register', 'renew', 'search',
            'mở', 'đóng', 'bán', 'mua', 'có', 'được', 'làm', 'open', 'close', 'sell', 'buy',
            // Từ chung chung (xuất hiện trong hầu hết câu hỏi về thư viện)
            'thư', 'viện', 'library', 'celras', 'tvu',
            // Từ hỏi
            'nào', 'đâu', 'sao', 'thế', 'nào', 'where', 'what', 'how', 'when', 'why'
        ];
        
        $userMainNouns = array_filter($userMainWords, function($w) use ($commonWords) {
            return mb_strlen($w) >= 3 && !in_array($w, $commonWords);
        });
        
        $dbMainNouns = array_filter($dbMainWords, function($w) use ($commonWords) {
            return mb_strlen($w) >= 3 && !in_array($w, $commonWords);
        });
        
        // Kiểm tra xem có từ khóa chính nào khớp không
        $hasMainWordMatch = false;
        $mainWordMatchScore = 0;
        
        foreach ($userMainNouns as $userNoun) {
            foreach ($dbMainNouns as $dbNoun) {
                $similarity = self::wordSimilarity($userNoun, $dbNoun);
                if ($similarity >= 0.7) {
                    $hasMainWordMatch = true;
                    $mainWordMatchScore = max($mainWordMatchScore, $similarity);
                    break 2;
                }
            }
        }
        
        // Nếu KHÔNG có từ khóa chính nào khớp → Penalty lớn
        if (!$hasMainWordMatch && !empty($userMainNouns) && !empty($dbMainNouns)) {
            $penalties['main_word_mismatch'] = -0.40; // Tăng penalty từ -30% lên -40%
        }
        
        // Tính điểm cuối
        $totalBoost = array_sum($boosts);
        $totalPenalty = array_sum($penalties);
        
        $finalScore = $baseScore + $totalBoost + $totalPenalty;
        $finalScore = max(0, min($finalScore, 1.0));
        
        return [
            'score' => $finalScore,
            'base_score' => $baseScore,
            'boosts' => $boosts,
            'penalties' => $penalties,
            'details' => [
                'total_boost' => $totalBoost,
                'total_penalty' => $totalPenalty,
            ]
        ];
    }
}
