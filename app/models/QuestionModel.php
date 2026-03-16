<?php
require_once __DIR__ . '/../core/BaseModel.php';

class QuestionModel extends BaseModel
{
    protected $table = 'questions';

    /**
     * Lấy tất cả câu hỏi kèm danh mục
     */
    public function getAllWithCategory()
    {
        $sql = "SELECT q.*, c.name as category_name 
                FROM {$this->table} q 
                LEFT JOIN categories c ON q.category_id = c.id 
                ORDER BY q.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Tìm câu trả lời phù hợp nhất
     * Phiên bản đơn giản: chỉ dùng FULLTEXT search
     */
    public function findAnswer($userMessage)
    {
        $messageLength = mb_strlen(trim($userMessage));

        // 0. Tin nhắn rỗng → không tìm
        if ($messageLength < 1) {
            return false;
        }

        // 0.5 Kiểm tra spam
        if ($this->isSpam($userMessage)) {
            return false;
        }

        // Chuẩn hóa tin nhắn (loại bỏ dấu câu và chuyển về chữ thường)
        $normalizedMessage = $this->normalizeMessage($userMessage);
        $lowerMessage = mb_strtolower($normalizedMessage);
        
        // Đếm số từ trong câu hỏi
        $wordCount = count(preg_split('/\s+/u', trim($lowerMessage)));

        // 1. Tìm chính xác (exact match) - so sánh không phân biệt hoa thường
        $sql = "SELECT * FROM {$this->table} 
                WHERE is_active = 1 
                AND LOWER(TRIM(question_text)) = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$lowerMessage]);
        $result = $stmt->fetch();

        if ($result) {
            return $result;
        }

        // 2. Tìm bằng FULLTEXT (hiểu ngữ cảnh tốt nhất)
        if ($messageLength >= 3) {
            $sql = "SELECT q.*, MATCH(question_text) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
                    FROM {$this->table} q 
                    WHERE q.is_active = 1 
                    AND MATCH(question_text) AGAINST(? IN NATURAL LANGUAGE MODE)
                    HAVING relevance > 0.3
                    ORDER BY relevance DESC 
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$normalizedMessage, $normalizedMessage]);
            $result = $stmt->fetch();

            // Với câu hỏi dài (>= 10 ký tự hoặc >= 3 từ), chấp nhận relevance thấp hơn
            $minRelevance = ($messageLength >= 15 || $wordCount >= 4) ? 0.5 : 1.5;
            
            if ($result && $result['relevance'] >= $minRelevance) {
                return $result;
            }
        }

        // 3. KHÔNG dùng LIKE cho câu hỏi vắn tắt (< 15 ký tự hoặc < 3 từ)
        // Vì sẽ khớp quá nhiều kết quả không chính xác
        if ($messageLength >= 15 && $wordCount >= 3) {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE is_active = 1 
                    AND LOWER(question_text) LIKE CONCAT('%', ?, '%')
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$lowerMessage]);
            $result = $stmt->fetch();

            if ($result) {
                return $result;
            }
        }

        // Không tìm thấy câu trả lời phù hợp
        return false;
    }

    /**
     * Chuẩn hóa tin nhắn: loại bỏ dấu câu cuối, khoảng trắng thừa
     */
    private function normalizeMessage($message)
    {
        $message = trim($message);
        // Loại bỏ dấu câu ở cuối (?, !, ., ,, ;, :)
        $message = preg_replace('/[?!.,;:]+$/', '', $message);
        // Loại bỏ khoảng trắng thừa
        $message = preg_replace('/\s+/', ' ', $message);
        return trim($message);
    }

    /**
     * Kiểm tra xem tin nhắn có phải spam không
     */
    private function isSpam($message)
    {
        $message = trim($message);
        $messageLength = mb_strlen($message);
        
        // 1. Tin nhắn quá ngắn (< 2 ký tự)
        if ($messageLength < 2) {
            return true;
        }
        
        // 2. Chỉ chứa ký tự lặp lại (aaaa, ????, 1111)
        if (preg_match('/^(.)\1+$/u', $message)) {
            return true;
        }
        
        // 3. Chỉ chứa số
        if (preg_match('/^\d+$/', $message)) {
            return true;
        }
        
        // 4. Chỉ chứa ký tự đặc biệt
        if (preg_match('/^[^\w\s]+$/u', $message)) {
            return true;
        }
        
        // 5. Lặp lại cùng 1 từ nhiều lần (abc abc abc)
        $words = preg_split('/\s+/u', $message);
        if (count($words) > 2) {
            $uniqueWords = array_unique($words);
            // Nếu > 70% từ giống nhau → spam
            if (count($uniqueWords) / count($words) < 0.3) {
                return true;
            }
        }
        
        // 6. Chứa quá nhiều ký tự lặp liên tiếp (aaabbbccc)
        if (preg_match('/(.)\1{4,}/u', $message)) {
            return true;
        }
        
        return false;
    }

    /**
     * Tìm các câu hỏi liên quan (dùng khi không tìm thấy exact match)
     * Sử dụng thuật toán kết hợp: FULLTEXT + TF-IDF + Levenshtein Distance
     */
    public function findRelatedQuestions($userMessage, $limit = 5)
    {
        $messageLength = mb_strlen(trim($userMessage));
        $normalizedMessage = $this->normalizeMessage($userMessage);
        $lowerMessage = mb_strtolower($normalizedMessage);
        
        // Tách từ khóa từ câu hỏi người dùng
        $userKeywords = $this->extractKeywords($lowerMessage);
        
        // Kiểm tra ngữ cảnh: câu hỏi có liên quan đến thư viện không?
        $isLibraryContext = $this->checkLibraryContext($lowerMessage, $userKeywords);
        
        $results = [];

        // 1. Tìm bằng FULLTEXT (nhanh nhất, dùng index)
        if ($messageLength >= 3) {
            $sql = "SELECT q.*, MATCH(question_text) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
                    FROM {$this->table} q 
                    WHERE q.is_active = 1 
                    AND MATCH(question_text) AGAINST(? IN NATURAL LANGUAGE MODE)
                    HAVING relevance > 0.1
                    ORDER BY relevance DESC 
                    LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$normalizedMessage, $normalizedMessage, $limit * 3]);
            $results = $stmt->fetchAll();
        }

        // 2. Nếu không có kết quả, tìm bằng LIKE với từng từ khóa (chỉ khi có ngữ cảnh thư viện)
        if (empty($results) && !empty($userKeywords) && $isLibraryContext) {
            $conditions = [];
            $params = [];
            
            foreach ($userKeywords as $keyword) {
                if (mb_strlen($keyword) >= 2) {
                    $conditions[] = "LOWER(question_text) LIKE ?";
                    $params[] = "%{$keyword}%";
                }
            }
            
            if (!empty($conditions)) {
                $sql = "SELECT * FROM {$this->table} 
                        WHERE is_active = 1 AND (" . implode(' OR ', $conditions) . ")
                        LIMIT ?";
                $params[] = $limit * 3;
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                $results = $stmt->fetchAll();
            }
        }

        // 3. Tính điểm tương đồng cho từng kết quả (TF-IDF + Levenshtein + Context)
        if (!empty($results)) {
            foreach ($results as &$result) {
                $questionKeywords = $this->extractKeywords(mb_strtolower($result['question_text']));
                
                // Tính điểm dựa trên số từ khóa trùng khớp (TF-IDF đơn giản)
                $matchScore = $this->calculateKeywordMatchScore($userKeywords, $questionKeywords);
                
                // Tính khoảng cách Levenshtein (chuẩn hóa 0-1)
                $levenshteinScore = $this->calculateLevenshteinScore($lowerMessage, mb_strtolower($result['question_text']));
                
                // Tính điểm ngữ cảnh (semantic similarity)
                $contextScore = $this->calculateContextScore($lowerMessage, mb_strtolower($result['question_text']));
                
                // Điểm tổng hợp (50% keyword + 20% Levenshtein + 30% context)
                $result['similarity_score'] = ($matchScore * 0.5) + ($levenshteinScore * 0.2) + ($contextScore * 0.3);
                
                // Nếu có relevance từ FULLTEXT, kết hợp thêm
                if (isset($result['relevance'])) {
                    $result['similarity_score'] = ($result['similarity_score'] * 0.7) + ($result['relevance'] * 0.3);
                }
                
                // Penalty nếu câu hỏi người dùng không có ngữ cảnh thư viện
                if (!$isLibraryContext) {
                    $result['similarity_score'] *= 0.3; // Giảm 70% điểm
                }
            }
            
            // Sắp xếp theo điểm tương đồng giảm dần
            usort($results, function($a, $b) {
                return $b['similarity_score'] <=> $a['similarity_score'];
            });
            
            // Lọc kết quả có điểm >= 0.2 và giới hạn số lượng
            $results = array_filter($results, function($r) {
                return $r['similarity_score'] >= 0.2;
            });
            
            $results = array_slice($results, 0, $limit);
        }

        return $results;
    }
    
    /**
     * Kiểm tra ngữ cảnh: câu hỏi có liên quan đến thư viện không?
     */
    private function checkLibraryContext($message, $keywords)
    {
        // Từ khóa liên quan đến thư viện
        $libraryKeywords = [
            'thư viện', 'library', 'sách', 'book', 'mượn', 'borrow', 'trả', 'return',
            'đọc', 'read', 'tài liệu', 'document', 'học liệu', 'celras', 'tvu',
            'tra vinh', 'đại học', 'university', 'sinh viên', 'student',
            'giảng viên', 'teacher', 'giáo viên', 'phòng', 'room', 'tầng', 'floor',
            'giờ mở cửa', 'opening hours', 'thẻ', 'card', 'đăng ký', 'register',
            'luận văn', 'thesis', 'nghiên cứu', 'research', 'tạp chí', 'journal',
            'cơ sở dữ liệu', 'database', 'tra cứu', 'search', 'wifi', 'máy tính', 'computer',
            'photocopy', 'in ấn', 'print', 'scan', 'dịch vụ', 'service'
        ];
        
        // Kiểm tra xem có từ khóa thư viện nào trong câu hỏi không
        foreach ($libraryKeywords as $libKeyword) {
            if (mb_strpos($message, $libKeyword) !== false) {
                return true;
            }
        }
        
        // Kiểm tra xem có ít nhất 2 từ khóa trùng với từ khóa thư viện không
        $matchCount = 0;
        foreach ($keywords as $keyword) {
            foreach ($libraryKeywords as $libKeyword) {
                if ($keyword === $libKeyword || mb_strpos($libKeyword, $keyword) !== false) {
                    $matchCount++;
                    break;
                }
            }
        }
        
        return $matchCount >= 2;
    }
    
    /**
     * Tính điểm ngữ cảnh (semantic similarity) dựa trên cấu trúc câu
     */
    private function calculateContextScore($userMessage, $questionText)
    {
        $score = 0.0;
        
        // 1. Kiểm tra cấu trúc câu hỏi (có từ nghi vấn không?)
        $questionWords = ['gì', 'nào', 'đâu', 'sao', 'thế nào', 'như thế nào', 'bao giờ', 'khi nào', 
                          'ai', 'what', 'where', 'when', 'who', 'how', 'why', 'which'];
        
        $userHasQuestion = false;
        $dbHasQuestion = false;
        
        foreach ($questionWords as $qw) {
            if (mb_strpos($userMessage, $qw) !== false) $userHasQuestion = true;
            if (mb_strpos($questionText, $qw) !== false) $dbHasQuestion = true;
        }
        
        // Cả hai đều là câu hỏi → +0.3
        if ($userHasQuestion && $dbHasQuestion) {
            $score += 0.3;
        }
        
        // 2. Kiểm tra động từ chính (hành động)
        $actionVerbs = [
            'mượn', 'trả', 'đăng ký', 'tìm', 'tra cứu', 'đọc', 'xem', 'tải', 'download',
            'borrow', 'return', 'register', 'search', 'find', 'read', 'view', 'download',
            'in', 'print', 'photocopy', 'scan', 'gia hạn', 'renew', 'đặt', 'reserve'
        ];
        
        $userActions = [];
        $dbActions = [];
        
        foreach ($actionVerbs as $verb) {
            if (mb_strpos($userMessage, $verb) !== false) $userActions[] = $verb;
            if (mb_strpos($questionText, $verb) !== false) $dbActions[] = $verb;
        }
        
        // Có động từ chung → +0.4
        $commonActions = array_intersect($userActions, $dbActions);
        if (!empty($commonActions)) {
            $score += 0.4;
        }
        
        // 3. Kiểm tra danh từ chính (đối tượng)
        $mainNouns = [
            'sách', 'book', 'tài liệu', 'document', 'luận văn', 'thesis',
            'thẻ', 'card', 'phòng', 'room', 'tầng', 'floor', 'giờ', 'hour',
            'dịch vụ', 'service', 'wifi', 'máy tính', 'computer', 'database'
        ];
        
        $userNouns = [];
        $dbNouns = [];
        
        foreach ($mainNouns as $noun) {
            if (mb_strpos($userMessage, $noun) !== false) $userNouns[] = $noun;
            if (mb_strpos($questionText, $noun) !== false) $dbNouns[] = $noun;
        }
        
        // Có danh từ chung → +0.3
        $commonNouns = array_intersect($userNouns, $dbNouns);
        if (!empty($commonNouns)) {
            $score += 0.3;
        }
        
        return min($score, 1.0); // Giới hạn tối đa 1.0
    }

    /**
     * Lấy tất cả câu hỏi đang hoạt động (để Gemini phân tích)
     */
    public function getAllActiveQuestions()
    {
        $sql = "SELECT id, question_text, answer_text, answer_text_en, category_id 
                FROM {$this->table} 
                WHERE is_active = 1 
                ORDER BY priority DESC, id ASC
                LIMIT 100"; // Giới hạn 100 câu phổ biến nhất
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Trích xuất từ khóa từ câu hỏi (loại bỏ stop words) - Public wrapper
     */
    public function extractKeywordsPublic($text)
    {
        $normalized = $this->normalizeMessage($text);
        $lower = mb_strtolower($normalized);
        return $this->extractKeywords($lower);
    }

    /**
     * Trích xuất từ khóa từ câu hỏi (loại bỏ stop words)
     */
    private function extractKeywords($text)
    {
        // Danh sách stop words tiếng Việt
        $stopWords = [
            'là', 'của', 'và', 'có', 'được', 'trong', 'ở', 'tại', 'với', 'cho',
            'để', 'từ', 'đến', 'về', 'như', 'khi', 'nào', 'đâu', 'sao', 'gì',
            'thế', 'nào', 'không', 'các', 'những', 'một', 'này', 'đó', 'thì',
            'hay', 'hoặc', 'nhưng', 'mà', 'nếu', 'vì', 'do', 'bởi', 'theo',
            'the', 'a', 'an', 'is', 'are', 'was', 'were', 'in', 'on', 'at',
            'to', 'for', 'of', 'and', 'or', 'but', 'if', 'what', 'where', 'when'
        ];
        
        // Tách từ
        $words = preg_split('/\s+/u', $text);
        
        // Loại bỏ stop words và từ quá ngắn
        $keywords = array_filter($words, function($word) use ($stopWords) {
            return mb_strlen($word) >= 2 && !in_array($word, $stopWords);
        });
        
        return array_values($keywords);
    }

    /**
     * Tính điểm khớp từ khóa (TF-IDF đơn giản)
     * Trả về giá trị 0-1
     */
    private function calculateKeywordMatchScore($userKeywords, $questionKeywords)
    {
        if (empty($userKeywords) || empty($questionKeywords)) {
            return 0;
        }
        
        $matchCount = 0;
        $totalWeight = 0;
        
        foreach ($userKeywords as $userWord) {
            $totalWeight += 1;
            
            foreach ($questionKeywords as $qWord) {
                // Khớp chính xác
                if ($userWord === $qWord) {
                    $matchCount += 1;
                    break;
                }
                // Khớp một phần (substring)
                elseif (mb_strpos($qWord, $userWord) !== false || mb_strpos($userWord, $qWord) !== false) {
                    $matchCount += 0.5;
                    break;
                }
            }
        }
        
        return $totalWeight > 0 ? $matchCount / $totalWeight : 0;
    }

    /**
     * Tính điểm Levenshtein (khoảng cách chỉnh sửa)
     * Trả về giá trị 0-1 (1 = giống nhau hoàn toàn)
     */
    private function calculateLevenshteinScore($str1, $str2)
    {
        $maxLen = max(mb_strlen($str1), mb_strlen($str2));
        
        if ($maxLen === 0) {
            return 1;
        }
        
        // Giới hạn độ dài để tránh tính toán quá lâu
        if ($maxLen > 200) {
            $str1 = mb_substr($str1, 0, 200);
            $str2 = mb_substr($str2, 0, 200);
            $maxLen = 200;
        }
        
        $distance = levenshtein($str1, $str2);
        
        return 1 - ($distance / $maxLen);
    }

    /**
     * Lấy câu hỏi theo ID
     */
    public function getById($id)
    {
        $sql = "SELECT q.*, c.name as category_name 
                FROM {$this->table} q 
                LEFT JOIN categories c ON q.category_id = c.id 
                WHERE q.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Lấy câu hỏi theo danh mục
     */
    public function getByCategory($categoryId)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE is_active = 1 AND category_id = ? 
                ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll();
    }

    /**
     * Thêm câu hỏi mới
     */
    public function create($data)
    {
        $sql = "INSERT INTO {$this->table} 
                (category_id, question_text, answer_text, answer_text_en, source_type, created_by) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['category_id'],
            $data['question_text'],
            $data['answer_text'],
            $data['answer_text_en'] ?? null,
            $data['source_type'] ?? 'manual',
            $data['created_by'] ?? null
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * Cập nhật câu hỏi
     */
    public function update($id, $data)
    {
        $sql = "UPDATE {$this->table} 
                SET category_id = ?, question_text = ?, answer_text = ?, answer_text_en = ? 
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['category_id'],
            $data['question_text'],
            $data['answer_text'],
            $data['answer_text_en'] ?? null,
            $id
        ]);
    }

    /**
     * Xóa câu hỏi
     */
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Xóa nhiều câu hỏi cùng lúc
     */
    public function deleteMultiple($ids)
    {
        if (empty($ids) || !is_array($ids)) {
            return false;
        }
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "DELETE FROM {$this->table} WHERE id IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($ids);
    }

    /**
     * Lấy câu hỏi gợi ý
     */
    public function getSuggestions($limit = 5)
    {
        $sql = "SELECT sq.*, q.answer_text 
                FROM suggested_questions sq 
                LEFT JOIN questions q ON sq.linked_question_id = q.id 
                WHERE sq.is_active = 1 
                ORDER BY sq.sort_order ASC 
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
