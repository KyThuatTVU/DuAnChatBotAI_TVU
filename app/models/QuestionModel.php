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
     * Sử dụng thuật toán kết hợp: Keywords với trọng số + TF-IDF + Context Matching
     */
    /**
     * Tìm các câu hỏi liên quan (dùng khi không tìm thấy exact match)
     * Thuật toán đơn giản: Tìm trong DB dựa trên từ khóa và ngữ cảnh
     */
    public function findRelatedQuestions($userMessage, $limit = 5)
    {
        $messageLength = mb_strlen(trim($userMessage));
        $normalizedMessage = $this->normalizeMessage($userMessage);
        $lowerMessage = mb_strtolower($normalizedMessage);
        
        // Tách từ khóa từ câu hỏi người dùng
        $userKeywords = $this->extractKeywords($lowerMessage);
        
        if (empty($userKeywords)) {
            return [];
        }
        
        $results = [];

        // BƯỚC 1: TÌM TRONG DB BẰNG FULLTEXT (Tìm theo ngữ nghĩa)
        if ($messageLength >= 3) {
            $sql = "SELECT q.*, 
                    MATCH(question_text) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
                    FROM {$this->table} q 
                    WHERE q.is_active = 1 
                    AND MATCH(question_text) AGAINST(? IN NATURAL LANGUAGE MODE)
                    HAVING relevance > 0
                    ORDER BY relevance DESC 
                    LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$normalizedMessage, $normalizedMessage, $limit * 5]);
            $results = $stmt->fetchAll();
        }

        // BƯỚC 2: Nếu không đủ, TÌM BẰNG TỪ KHÓA (LIKE)
        if (count($results) < $limit * 3) {
            $conditions = [];
            $params = [];
            
            // Tìm câu hỏi có chứa BẤT KỲ từ khóa nào
            foreach ($userKeywords as $keyword) {
                if (mb_strlen($keyword) >= 2) {
                    $conditions[] = "LOWER(question_text) LIKE ?";
                    $params[] = "%{$keyword}%";
                }
            }
            
            if (!empty($conditions)) {
                $sql = "SELECT * FROM {$this->table} 
                        WHERE is_active = 1 
                        AND (" . implode(' OR ', $conditions) . ")
                        LIMIT ?";
                $params[] = $limit * 5;
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                $likeResults = $stmt->fetchAll();
                
                // Merge (tránh trùng)
                $existingIds = array_column($results, 'id');
                foreach ($likeResults as $item) {
                    if (!in_array($item['id'], $existingIds)) {
                        $results[] = $item;
                    }
                }
            }
        }

        // BƯỚC 3: CHẤM ĐIỂM DựA trên TỪ KHÓA + NGỮ CẢNH
        if (!empty($results)) {
            foreach ($results as &$result) {
                $questionText = mb_strtolower($result['question_text']);
                $questionKeywords = $this->extractKeywords($questionText);
                
                $score = 0;
                
                // 1. Điểm từ khóa khớp (60% - QUAN TRỌNG NHẤT)
                $matchedCount = 0;
                $totalUserKeywords = count($userKeywords);
                
                foreach ($userKeywords as $userWord) {
                    foreach ($questionKeywords as $qWord) {
                        // Khớp chính xác
                        if ($userWord === $qWord) {
                            $matchedCount += 1.0;
                            break;
                        }
                        // Khớp một phần
                        elseif (mb_strpos($qWord, $userWord) !== false || mb_strpos($userWord, $qWord) !== false) {
                            $matchedCount += 0.5;
                            break;
                        }
                    }
                }
                
                $keywordScore = $totalUserKeywords > 0 ? $matchedCount / $totalUserKeywords : 0;
                $score += $keywordScore * 0.60;
                
                // 2. Điểm ngữ cảnh (20% - XỬ LÝ TRÙNG TỪ KHÓA)
                // Kiểm tra nhiều yếu tố ngữ cảnh
                $contextScore = 0;
                $contextFactors = 0;
                
                // 2.1. Kiểm tra SỐ (1, 2, 3...)
                preg_match_all('/\d+/', $lowerMessage, $userNumbers);
                preg_match_all('/\d+/', $questionText, $qNumbers);
                
                if (!empty($userNumbers[0]) || !empty($qNumbers[0])) {
                    $contextFactors++;
                    if (!empty($userNumbers[0]) && !empty($qNumbers[0])) {
                        $commonNumbers = array_intersect($userNumbers[0], $qNumbers[0]);
                        if (!empty($commonNumbers)) {
                            $contextScore += 1.0; // Số khớp hoàn toàn
                        } else {
                            $contextScore -= 0.5; // Số KHÔNG khớp → Trừ điểm
                        }
                    } elseif (empty($userNumbers[0]) && empty($qNumbers[0])) {
                        $contextScore += 0.3; // Cả 2 đều không có số → Trung lập
                    }
                }
                
                // 2.2. Kiểm tra TỪ CHỈ VỊ TRÍ (tầng, phòng, khu, tòa...)
                $locationWords = ['tầng', 'phòng', 'khu', 'tòa', 'nhà', 'block', 'toà', 
                                  'floor', 'room', 'area', 'building', 'zone', 'quầy', 'bàn'];
                
                $userLocations = [];
                $qLocations = [];
                
                foreach ($locationWords as $loc) {
                    if (mb_strpos($lowerMessage, $loc) !== false) {
                        $userLocations[] = $loc;
                    }
                    if (mb_strpos($questionText, $loc) !== false) {
                        $qLocations[] = $loc;
                    }
                }
                
                if (!empty($userLocations) || !empty($qLocations)) {
                    $contextFactors++;
                    $commonLocations = array_intersect($userLocations, $qLocations);
                    if (!empty($commonLocations)) {
                        $contextScore += 1.0; // Vị trí khớp
                    } elseif (!empty($userLocations) && !empty($qLocations)) {
                        $contextScore += 0.3; // Có vị trí nhưng khác nhau
                    } elseif (empty($userLocations) && empty($qLocations)) {
                        $contextScore += 0.5; // Cả 2 đều không có vị trí
                    }
                }
                
                // 2.3. Kiểm tra THỜI GIAN (giờ, ngày, tháng, năm...)
                $timeWords = ['giờ', 'ngày', 'tháng', 'năm', 'tuần', 'thứ', 'chủ nhật',
                              'sáng', 'chiều', 'tối', 'đêm', 'hour', 'day', 'month', 'year'];
                
                $userHasTime = false;
                $qHasTime = false;
                
                foreach ($timeWords as $time) {
                    if (mb_strpos($lowerMessage, $time) !== false) {
                        $userHasTime = true;
                    }
                    if (mb_strpos($questionText, $time) !== false) {
                        $qHasTime = true;
                    }
                }
                
                if ($userHasTime || $qHasTime) {
                    $contextFactors++;
                    if ($userHasTime && $qHasTime) {
                        $contextScore += 1.0; // Cả 2 đều về thời gian
                    } elseif (!$userHasTime && !$qHasTime) {
                        $contextScore += 0.5; // Cả 2 đều không về thời gian
                    }
                }
                
                // 2.4. Kiểm tra HÀNH ĐỘNG (mượn, trả, đăng ký, gia hạn...)
                $actionWords = ['mượn', 'trả', 'đăng ký', 'gia hạn', 'tìm', 'tra cứu', 
                                'đặt', 'yêu cầu', 'borrow', 'return', 'register', 'renew', 'search'];
                
                $userActions = [];
                $qActions = [];
                
                foreach ($actionWords as $action) {
                    if (mb_strpos($lowerMessage, $action) !== false) {
                        $userActions[] = $action;
                    }
                    if (mb_strpos($questionText, $action) !== false) {
                        $qActions[] = $action;
                    }
                }
                
                if (!empty($userActions) || !empty($qActions)) {
                    $contextFactors++;
                    $commonActions = array_intersect($userActions, $qActions);
                    if (!empty($commonActions)) {
                        $contextScore += 1.0; // Hành động khớp
                    } elseif (!empty($userActions) && !empty($qActions)) {
                        $contextScore += 0.2; // Có hành động nhưng khác nhau
                    } elseif (empty($userActions) && empty($qActions)) {
                        $contextScore += 0.5; // Cả 2 đều không có hành động
                    }
                }
                
                // 2.5. Kiểm tra ĐỐI TƯỢNG (sinh viên, giảng viên, cán bộ...)
                $targetWords = ['sinh viên', 'giảng viên', 'cán bộ', 'học sinh', 'giáo viên',
                                'student', 'teacher', 'lecturer', 'staff'];
                
                $userHasTarget = false;
                $qHasTarget = false;
                
                foreach ($targetWords as $target) {
                    if (mb_strpos($lowerMessage, $target) !== false) {
                        $userHasTarget = true;
                    }
                    if (mb_strpos($questionText, $target) !== false) {
                        $qHasTarget = true;
                    }
                }
                
                if ($userHasTarget || $qHasTarget) {
                    $contextFactors++;
                    if ($userHasTarget && $qHasTarget) {
                        $contextScore += 1.0; // Cả 2 đều về đối tượng
                    } elseif (!$userHasTarget && !$qHasTarget) {
                        $contextScore += 0.5; // Cả 2 đều không về đối tượng
                    }
                }
                
                // Tính điểm ngữ cảnh trung bình
                if ($contextFactors > 0) {
                    $contextScore = $contextScore / $contextFactors;
                } else {
                    $contextScore = 0.5; // Không có yếu tố ngữ cảnh → Trung lập
                }
                
                $contextScore = max(0, min($contextScore, 1.0)); // Giới hạn 0-1
                $score += $contextScore * 0.20;
                
                // 3. Điểm FULLTEXT relevance (10%)
                if (isset($result['relevance']) && $result['relevance'] > 0) {
                    $fulltextScore = min($result['relevance'] / 2, 1.0);
                    $score += $fulltextScore * 0.10;
                }
                
                // 4. Điểm độ dài tương đương (10%)
                $userLen = mb_strlen($lowerMessage);
                $qLen = mb_strlen($questionText);
                $lenDiff = abs($userLen - $qLen);
                $avgLen = ($userLen + $qLen) / 2;
                
                if ($avgLen > 0) {
                    $lenScore = 1 - min($lenDiff / $avgLen, 1);
                    $score += $lenScore * 0.10;
                }
                
                $result['similarity_score'] = $score;
                
                // Debug
                $result['debug'] = [
                    'keyword_score' => round($keywordScore, 3),
                    'context_score' => round($contextScore, 3),
                    'matched_keywords' => $matchedCount . '/' . $totalUserKeywords,
                ];
            }
            
            // Sắp xếp theo điểm giảm dần
            usort($results, function($a, $b) {
                return $b['similarity_score'] <=> $a['similarity_score'];
            });
            
            // Lọc kết quả có điểm >= 0.25 (25%)
            $results = array_filter($results, function($r) {
                return $r['similarity_score'] >= 0.25;
            });
            
            $results = array_slice($results, 0, $limit);
        }

        return $results;
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
