<?php

class TrashModel extends BaseModel {
    
    protected $table = 'trash_questions'; // Default table
    
    public function __construct() {
        error_log("TrashModel: Constructor called");
        try {
            parent::__construct();
            error_log("TrashModel: Parent constructor successful, db connection: " . ($this->db ? 'OK' : 'NULL'));
        } catch (Exception $e) {
            error_log("TrashModel constructor error: " . $e->getMessage());
            error_log("TrashModel constructor stack trace: " . $e->getTraceAsString());
            // Không throw exception để không làm crash toàn bộ app
        }
    }
    
    /**
     * Chuyển câu hỏi vào thùng rác
     */
    public function moveQuestionToTrash($questionId, $deletedBy) {
        try {
            error_log("moveQuestionToTrash called: questionId=$questionId, deletedBy=$deletedBy");
            
            // Lấy thông tin câu hỏi
            $stmt = $this->db->prepare("SELECT * FROM questions WHERE id = ?");
            $stmt->execute([$questionId]);
            $question = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$question) {
                error_log("moveQuestionToTrash: Question not found with id=$questionId");
                return false;
            }
            
            error_log("moveQuestionToTrash: Found question: " . $question['question_text']);
            
            // Tính thời gian hết hạn (24 giờ)
            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // Chuyển vào thùng rác - KHÔNG lưu keywords vì chúng ở bảng riêng
            $stmt = $this->db->prepare("
                INSERT INTO trash_questions (
                    original_question_id, question_text, answer_text, answer_text_en,
                    category_id, source, approval_status, created_at, updated_at, updated_by,
                    deleted_by, expires_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $question['id'],
                $question['question_text'],
                $question['answer_text'],
                $question['answer_text_en'] ?? null,
                $question['category_id'],
                $question['source_type'] ?? 'manual',
                $question['approval_status'] ?? 'approved',
                $question['created_at'],
                $question['updated_at'],
                $question['updated_by'] ?? null,
                $deletedBy,
                $expiresAt
            ]);
            
            error_log("moveQuestionToTrash: Insert result=" . ($result ? 'true' : 'false'));
            
            if ($result) {
                // Xóa keywords liên quan
                $stmt = $this->db->prepare("DELETE FROM keywords WHERE question_id = ?");
                $stmt->execute([$questionId]);
                
                // Xóa câu hỏi khỏi bảng chính
                $stmt = $this->db->prepare("DELETE FROM questions WHERE id = ?");
                $deleteResult = $stmt->execute([$questionId]);
                error_log("moveQuestionToTrash: Delete from questions result=" . ($deleteResult ? 'true' : 'false'));
                return $deleteResult;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error moving question to trash: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Chuyển danh mục vào thùng rác
     */
    public function moveCategoryToTrash($categoryId, $deletedBy) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt->execute([$categoryId]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$category) {
                return false;
            }
            
            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            $stmt = $this->db->prepare("
                INSERT INTO trash_categories (
                    original_category_id, name, description, icon, display_order,
                    is_active, created_at, updated_at, deleted_by, expires_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $category['id'],
                $category['name'],
                $category['description'],
                $category['icon'],
                $category['display_order'],
                $category['is_active'],
                $category['created_at'],
                $category['updated_at'],
                $deletedBy,
                $expiresAt
            ]);
            
            if ($result) {
                $stmt = $this->db->prepare("DELETE FROM categories WHERE id = ?");
                return $stmt->execute([$categoryId]);
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error moving category to trash: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Chuyển biểu mẫu vào thùng rác
     */
    public function moveFormToTrash($formId, $deletedBy) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM forms WHERE id = ?");
            $stmt->execute([$formId]);
            $form = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$form) {
                return false;
            }
            
            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            $stmt = $this->db->prepare("
                INSERT INTO trash_forms (
                    original_form_id, title, description, file_path, file_size,
                    download_count, is_active, created_at, updated_at,
                    deleted_by, expires_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $form['id'],
                $form['title'],
                $form['description'],
                $form['file_path'],
                $form['file_size'],
                $form['download_count'],
                $form['is_active'],
                $form['created_at'],
                $form['updated_at'],
                $deletedBy,
                $expiresAt
            ]);
            
            if ($result) {
                $stmt = $this->db->prepare("DELETE FROM forms WHERE id = ?");
                return $stmt->execute([$formId]);
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error moving form to trash: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lấy danh sách câu hỏi trong thùng rác
     */
    public function getTrashQuestions() {
        try {
            // Kiểm tra bảng có tồn tại không
            $stmt = $this->db->query("SHOW TABLES LIKE 'trash_questions'");
            if ($stmt->rowCount() === 0) {
                return [];
            }
            
            $stmt = $this->db->query("
                SELECT 
                    tq.*,
                    c.name as category_name,
                    a.full_name as deleted_by_name,
                    TIMESTAMPDIFF(HOUR, NOW(), tq.expires_at) as hours_remaining
                FROM trash_questions tq
                LEFT JOIN categories c ON tq.category_id = c.id
                LEFT JOIN admins a ON tq.deleted_by = a.id
                WHERE tq.expires_at > NOW()
                ORDER BY tq.deleted_at DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting trash questions: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Lấy danh sách danh mục trong thùng rác
     */
    public function getTrashCategories() {
        try {
            // Kiểm tra bảng có tồn tại không
            $stmt = $this->db->query("SHOW TABLES LIKE 'trash_categories'");
            if ($stmt->rowCount() === 0) {
                return [];
            }
            
            $stmt = $this->db->query("
                SELECT 
                    tc.*,
                    a.full_name as deleted_by_name,
                    TIMESTAMPDIFF(HOUR, NOW(), tc.expires_at) as hours_remaining
                FROM trash_categories tc
                LEFT JOIN admins a ON tc.deleted_by = a.id
                WHERE tc.expires_at > NOW()
                ORDER BY tc.deleted_at DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting trash categories: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Lấy danh sách biểu mẫu trong thùng rác
     */
    public function getTrashForms() {
        try {
            // Kiểm tra bảng có tồn tại không
            $stmt = $this->db->query("SHOW TABLES LIKE 'trash_forms'");
            if ($stmt->rowCount() === 0) {
                return [];
            }
            
            $stmt = $this->db->query("
                SELECT 
                    tf.*,
                    a.full_name as deleted_by_name,
                    TIMESTAMPDIFF(HOUR, NOW(), tf.expires_at) as hours_remaining
                FROM trash_forms tf
                LEFT JOIN admins a ON tf.deleted_by = a.id
                WHERE tf.expires_at > NOW()
                ORDER BY tf.deleted_at DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting trash forms: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Khôi phục câu hỏi từ thùng rác
     */
    public function restoreQuestion($trashId) {
        try {
            // Lấy thông tin từ thùng rác
            $stmt = $this->db->prepare("SELECT * FROM trash_questions WHERE id = ? AND expires_at > NOW()");
            $stmt->execute([$trashId]);
            $trash = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$trash) {
                return false;
            }
            
            // Khôi phục vào bảng chính
            $stmt = $this->db->prepare("
                INSERT INTO questions (
                    question_text, answer_text, answer_text_en, category_id,
                    source_type, approval_status, created_at, updated_at, updated_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $trash['question_text'],
                $trash['answer_text'],
                $trash['answer_text_en'],
                $trash['category_id'],
                $trash['source'] ?? 'manual',
                $trash['approval_status'] ?? 'approved',
                $trash['created_at'],
                $trash['updated_at'],
                $trash['updated_by']
            ]);
            
            if ($result) {
                // Xóa khỏi thùng rác
                $stmt = $this->db->prepare("DELETE FROM trash_questions WHERE id = ?");
                return $stmt->execute([$trashId]);
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error restoring question: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Khôi phục danh mục từ thùng rác
     */
    public function restoreCategory($trashId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM trash_categories WHERE id = ? AND expires_at > NOW()");
            $stmt->execute([$trashId]);
            $trash = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$trash) {
                return false;
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO categories (
                    name, description, icon, display_order, is_active,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $trash['name'],
                $trash['description'],
                $trash['icon'],
                $trash['display_order'],
                $trash['is_active'],
                $trash['created_at'],
                $trash['updated_at']
            ]);
            
            if ($result) {
                $stmt = $this->db->prepare("DELETE FROM trash_categories WHERE id = ?");
                return $stmt->execute([$trashId]);
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error restoring category: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Khôi phục biểu mẫu từ thùng rác
     */
    public function restoreForm($trashId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM trash_forms WHERE id = ? AND expires_at > NOW()");
            $stmt->execute([$trashId]);
            $trash = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$trash) {
                return false;
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO forms (
                    title, description, file_path, file_size, download_count,
                    is_active, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $trash['title'],
                $trash['description'],
                $trash['file_path'],
                $trash['file_size'],
                $trash['download_count'],
                $trash['is_active'],
                $trash['created_at'],
                $trash['updated_at']
            ]);
            
            if ($result) {
                $stmt = $this->db->prepare("DELETE FROM trash_forms WHERE id = ?");
                return $stmt->execute([$trashId]);
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error restoring form: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Xóa vĩnh viễn khỏi thùng rác
     */
    public function permanentDeleteQuestion($trashId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM trash_questions WHERE id = ?");
            return $stmt->execute([$trashId]);
        } catch (Exception $e) {
            error_log("Error permanent delete question: " . $e->getMessage());
            return false;
        }
    }
    
    public function permanentDeleteCategory($trashId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM trash_categories WHERE id = ?");
            return $stmt->execute([$trashId]);
        } catch (Exception $e) {
            error_log("Error permanent delete category: " . $e->getMessage());
            return false;
        }
    }
    
    public function permanentDeleteForm($trashId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM trash_forms WHERE id = ?");
            return $stmt->execute([$trashId]);
        } catch (Exception $e) {
            error_log("Error permanent delete form: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Đếm số lượng items trong thùng rác
     */
    public function getTrashCount() {
        try {
            $stmt = $this->db->query("
                SELECT 
                    (SELECT COUNT(*) FROM trash_questions WHERE expires_at > NOW()) as questions,
                    (SELECT COUNT(*) FROM trash_categories WHERE expires_at > NOW()) as categories,
                    (SELECT COUNT(*) FROM trash_forms WHERE expires_at > NOW()) as forms
            ");
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting trash count: " . $e->getMessage());
            return ['questions' => 0, 'categories' => 0, 'forms' => 0];
        }
    }
}
