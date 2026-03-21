<?php
require_once __DIR__ . '/../core/BaseModel.php';
require_once __DIR__ . '/../helpers/ContextAnalyzer.php';

class QuestionModel extends BaseModel
{
    protected $table = 'questions';

    /**
     * Lấy tất cả câu hỏi kèm danh mục
     */
    public function getAllWithCategory()
    {
        try {
            $sql = "SELECT q.*, c.name as category_name
                    FROM {$this->table} q 
                    LEFT JOIN categories c ON q.category_id = c.id 
                    ORDER BY q.created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Thêm thông tin người phê duyệt nếu có
            if (!empty($results)) {
                foreach ($results as $key => $row) {
                    $results[$key]['approved_by_name'] = null;
                    
                    if (!empty($row['approved_by'])) {
                        try {
                            $stmtAdmin = $this->db->prepare("SELECT full_name FROM admins WHERE id = ?");
                            $stmtAdmin->execute([$row['approved_by']]);
                            $admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
                            if ($admin) {
                                $results[$key]['approved_by_name'] = $admin['full_name'];
                            }
                        } catch (Exception $e) {
                            // Bỏ qua lỗi khi lấy tên admin
                            error_log("Error fetching admin name: " . $e->getMessage());
                        }
                    }
                }
            }
            
            return $results;
        } catch (Exception $e) {
            error_log("Error in getAllWithCategory: " . $e->getMessage());
            throw $e;
        }
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

        // 1. Tìm chính xác (exact match) - so sánh không phân biệt hoa thường - chỉ lấy câu đã duyệt
        $sql = "SELECT * FROM {$this->table} 
                WHERE is_active = 1 
                AND approval_status = 'approved'
                AND LOWER(TRIM(question_text)) = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$lowerMessage]);
        $result = $stmt->fetch();

        if ($result) {
            return $result;
        }

        // 2. Tìm bằng FULLTEXT (hiểu ngữ cảnh tốt nhất) - chỉ lấy câu đã duyệt
        if ($messageLength >= 3) {
            $sql = "SELECT q.*, MATCH(question_text) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
                    FROM {$this->table} q 
                    WHERE q.is_active = 1 
                    AND q.approval_status = 'approved'
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

        // 3. KHÔNG dùng LIKE cho câu hỏi vắn tắt (< 15 ký tự hoặc < 3 từ) - chỉ lấy câu đã duyệt
        // Vì sẽ khớp quá nhiều kết quả không chính xác
        if ($messageLength >= 15 && $wordCount >= 3) {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE is_active = 1 
                    AND approval_status = 'approved'
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
        require_once __DIR__ . '/../helpers/ContextAnalyzer.php';

        $messageLength = mb_strlen(trim($userMessage));
        $normalizedMessage = $this->normalizeMessage($userMessage);

        if ($messageLength < 3) {
            return [];
        }

        $results = [];

        // BƯỚC 1: TÌM CANDIDATES TRONG DB - chỉ lấy câu đã duyệt
        // Sử dụng FULLTEXT search để lấy candidates
        $sql = "SELECT q.*, 
                MATCH(question_text) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
                FROM {$this->table} q 
                WHERE q.is_active = 1 
                AND q.approval_status = 'approved'
                AND MATCH(question_text) AGAINST(? IN NATURAL LANGUAGE MODE)
                HAVING relevance > 0
                ORDER BY relevance DESC 
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$normalizedMessage, $normalizedMessage, $limit * 10]);
        $candidates = $stmt->fetchAll();

        // Nếu không đủ candidates, tìm thêm bằng từ khóa
        if (count($candidates) < $limit * 5) {
            $userKeywords = $this->extractKeywords(mb_strtolower($normalizedMessage));

            if (!empty($userKeywords)) {
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
                            WHERE is_active = 1 
                            AND approval_status = 'approved'
                            AND (" . implode(' OR ', $conditions) . ")
                            LIMIT ?";
                    $params[] = $limit * 10;
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute($params);
                    $likeResults = $stmt->fetchAll();

                    // Merge (tránh trùng)
                    $existingIds = array_column($candidates, 'id');
                    foreach ($likeResults as $item) {
                        if (!in_array($item['id'], $existingIds)) {
                            $candidates[] = $item;
                        }
                    }
                }
            }
        }

        // BƯỚC 2: CHẤM ĐIỂM BẰNG ContextAnalyzer (Semantic Similarity)
        if (!empty($candidates)) {
            foreach ($candidates as &$candidate) {
                // Sử dụng ContextAnalyzer để tính điểm tương đồng ngữ nghĩa
                $similarityResult = ContextAnalyzer::calculateAdvancedSimilarity(
                    $userMessage,
                    $candidate['question_text'],
                    $candidate['answer_text'] ?? ''
                );

                $candidate['similarity_score'] = $similarityResult['score'];
                $candidate['debug'] = [
                    'base_score' => round($similarityResult['base_score'], 3),
                    'boosts' => $similarityResult['boosts'],
                    'penalties' => $similarityResult['penalties'],
                    'total_boost' => round($similarityResult['details']['total_boost'], 3),
                    'total_penalty' => round($similarityResult['details']['total_penalty'], 3),
                ];
            }

            // Sắp xếp theo điểm giảm dần
            usort($candidates, function($a, $b) {
                return $b['similarity_score'] <=> $a['similarity_score'];
            });

            // Lọc kết quả có điểm >= 0.50 (50%)
            // CHỈ TRẢ LỜI KHI CHẮC CHẮN - Tránh trả lời sai khi ngữ cảnh không khớp
            $results = array_filter($candidates, function($r) {
                return $r['similarity_score'] >= 0.50;
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
     * Lấy câu hỏi theo danh mục - chỉ lấy câu đã duyệt
     */
    public function getByCategory($categoryId)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE is_active = 1 
                AND approval_status = 'approved'
                AND category_id = ? 
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

    /**
     * Cập nhật trạng thái phê duyệt câu hỏi
     */
    public function updateApprovalStatus($id, $status, $adminId)
    {
        $sql = "UPDATE {$this->table} 
                SET approval_status = ?, 
                    approved_by = ?, 
                    approved_at = NOW() 
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$status, $adminId, $id]);
    }

    /**
     * Lấy số lượng câu hỏi theo trạng thái phê duyệt
     */
    public function countByApprovalStatus()
    {
        $sql = "SELECT approval_status, COUNT(*) as count 
                FROM {$this->table} 
                GROUP BY approval_status";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        $counts = [
            'pending' => 0,
            'approved' => 0
        ];
        
        foreach ($results as $row) {
            $counts[$row['approval_status']] = (int)$row['count'];
        }
        
        return $counts;
    }
}
