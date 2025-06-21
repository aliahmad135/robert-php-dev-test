<?php

/**
 * TranslationUnit Class
 * 
 * Manages translation units for the Robert CAT tool.
 * Implements CRUD operations with version control and history tracking.
 */
class TranslationUnit
{
    private $pdo;
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    /**
     * Add a new translation unit
     * 
     * @param string $sourceText The source text to translate
     * @param string $sourceLanguage Source language code (e.g., 'en', 'es')
     * @param string $targetLanguage Target language code (e.g., 'en', 'es')
     * @param string $context Optional context for the translation
     * @param string $segmentType Type of segment ('sentence', 'paragraph', 'phrase')
     * @param int $projectId Optional project ID
     * @return int|false The ID of the created translation unit or false on failure
     */
    public function addTranslationUnit(
        string $sourceText,
        string $sourceLanguage,
        string $targetLanguage,
        string $context = '',
        string $segmentType = 'sentence',
        int $projectId = null
    ) {
        try {
            // Calculate word count
            $wordCount = str_word_count($sourceText);
            
            $sql = "INSERT INTO translation_units 
                    (project_id, source_text, source_language, target_language, context, segment_type, word_count) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $projectId,
                $sourceText,
                $sourceLanguage,
                $targetLanguage,
                $context,
                $segmentType,
                $wordCount
            ]);
            
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error adding translation unit: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Retrieve a translation unit by ID
     * 
     * @param int $id The translation unit ID
     * @return array|false The translation unit data or false if not found
     */
    public function getTranslationUnit(int $id)
    {
        try {
            $sql = "SELECT tu.*, 
                           t.id as translation_id,
                           t.translated_text,
                           t.translation_method,
                           t.confidence_score,
                           t.is_approved,
                           t.created_at as translation_created_at
                    FROM translation_units tu
                    LEFT JOIN translations t ON tu.id = t.translation_unit_id
                    WHERE tu.id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                // Get translation history if translation exists
                if ($result['translation_id']) {
                    $result['history'] = $this->getTranslationHistory($result['translation_id']);
                }
                
                return $result;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error retrieving translation unit: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update a translation unit and keep history
     * 
     * @param int $translationUnitId The translation unit ID
     * @param string $translatedText The new translated text
     * @param int $translatorId Optional translator ID
     * @param string $translationMethod Translation method ('human', 'machine', 'hybrid')
     * @param float $confidenceScore Confidence score (0.00 to 1.00)
     * @param string $changeReason Reason for the change
     * @return bool Success status
     */
    public function updateTranslationUnit(
        int $translationUnitId,
        string $translatedText,
        int $translatorId = null,
        string $translationMethod = 'human',
        float $confidenceScore = 1.0,
        string $changeReason = 'Translation updated'
    ) {
        try {
            $this->pdo->beginTransaction();
            
            // Check if translation already exists
            $existingTranslation = $this->getExistingTranslation($translationUnitId);
            
            if ($existingTranslation) {
                // Store current translation in history before updating
                $this->storeTranslationHistory(
                    $existingTranslation['id'],
                    $existingTranslation['translated_text'],
                    $existingTranslation['translator_id'],
                    $changeReason
                );
                
                // Update existing translation
                $sql = "UPDATE translations 
                        SET translated_text = ?, translator_id = ?, translation_method = ?, 
                            confidence_score = ?, updated_at = CURRENT_TIMESTAMP
                        WHERE translation_unit_id = ?";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    $translatedText,
                    $translatorId,
                    $translationMethod,
                    $confidenceScore,
                    $translationUnitId
                ]);
            } else {
                // Create new translation
                $sql = "INSERT INTO translations 
                        (translation_unit_id, translated_text, translator_id, translation_method, confidence_score) 
                        VALUES (?, ?, ?, ?, ?)";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    $translationUnitId,
                    $translatedText,
                    $translatorId,
                    $translationMethod,
                    $confidenceScore
                ]);
                
                $translationId = $this->pdo->lastInsertId();
                
                // Store initial translation in history
                $this->storeTranslationHistory(
                    $translationId,
                    $translatedText,
                    $translatorId,
                    'Initial translation'
                );
            }
            
            // Update translation unit status
            $this->updateTranslationUnitStatus($translationUnitId, 'completed');
            
            $this->pdo->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error updating translation unit: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all translation units with optional filtering
     * 
     * @param array $filters Optional filters (status, source_language, target_language, project_id)
     * @param int $limit Limit number of results
     * @param int $offset Offset for pagination
     * @return array Array of translation units
     */
    public function getAllTranslationUnits(array $filters = [], int $limit = 10, int $offset = 0)
    {
        try {
            $sql = "SELECT tu.*, 
                           t.id as translation_id,
                           t.translated_text,
                           t.translation_method,
                           t.confidence_score,
                           t.is_approved
                    FROM translation_units tu
                    LEFT JOIN translations t ON tu.id = t.translation_unit_id";
            
            $whereConditions = [];
            $params = [];
            
            // Apply filters
            if (!empty($filters['status'])) {
                $whereConditions[] = "tu.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['source_language'])) {
                $whereConditions[] = "tu.source_language = ?";
                $params[] = $filters['source_language'];
            }
            
            if (!empty($filters['target_language'])) {
                $whereConditions[] = "tu.target_language = ?";
                $params[] = $filters['target_language'];
            }
            
            if (!empty($filters['project_id'])) {
                $whereConditions[] = "tu.project_id = ?";
                $params[] = $filters['project_id'];
            }
            
            if (!empty($whereConditions)) {
                $sql .= " WHERE " . implode(" AND ", $whereConditions);
            }
            
            $sql .= " ORDER BY tu.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error retrieving translation units: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Delete a translation unit
     * 
     * @param int $id The translation unit ID
     * @return bool Success status
     */
    public function deleteTranslationUnit(int $id)
    {
        try {
            $sql = "DELETE FROM translation_units WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error deleting translation unit: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get translation history for a specific translation
     * 
     * @param int $translationId The translation ID
     * @return array Array of history records
     */
    private function getTranslationHistory(int $translationId)
    {
        try {
            $sql = "SELECT * FROM translation_history 
                    WHERE translation_id = ? 
                    ORDER BY version_number DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$translationId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error retrieving translation history: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Store translation in history
     * 
     * @param int $translationId The translation ID
     * @param string $translatedText The translated text
     * @param int $translatorId The translator ID
     * @param string $changeReason Reason for the change
     * @return bool Success status
     */
    private function storeTranslationHistory(int $translationId, string $translatedText, int $translatorId = null, string $changeReason = '')
    {
        try {
            // Get next version number
            $versionSql = "SELECT COALESCE(MAX(version_number), 0) + 1 as next_version 
                          FROM translation_history 
                          WHERE translation_id = ?";
            $versionStmt = $this->pdo->prepare($versionSql);
            $versionStmt->execute([$translationId]);
            $versionResult = $versionStmt->fetch(PDO::FETCH_ASSOC);
            $nextVersion = $versionResult['next_version'];
            
            // Insert history record
            $sql = "INSERT INTO translation_history 
                    (translation_id, translated_text, translator_id, change_reason, version_number) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $translationId,
                $translatedText,
                $translatorId,
                $changeReason,
                $nextVersion
            ]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Error storing translation history: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get existing translation for a translation unit
     * 
     * @param int $translationUnitId The translation unit ID
     * @return array|false The existing translation or false if not found
     */
    private function getExistingTranslation(int $translationUnitId)
    {
        try {
            $sql = "SELECT * FROM translations WHERE translation_unit_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$translationUnitId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error retrieving existing translation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update translation unit status
     * 
     * @param int $translationUnitId The translation unit ID
     * @param string $status The new status
     * @return bool Success status
     */
    private function updateTranslationUnitStatus(int $translationUnitId, string $status)
    {
        try {
            $sql = "UPDATE translation_units SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$status, $translationUnitId]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Error updating translation unit status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Search translation units by source text
     * 
     * @param string $searchTerm The search term
     * @param int $limit Limit number of results
     * @return array Array of matching translation units
     */
    public function searchTranslationUnits(string $searchTerm, int $limit = 10)
    {
        try {
            $sql = "SELECT tu.*, 
                           t.id as translation_id,
                           t.translated_text,
                           t.translation_method,
                           t.confidence_score
                    FROM translation_units tu
                    LEFT JOIN translations t ON tu.id = t.translation_unit_id
                    WHERE MATCH(tu.source_text) AGAINST(? IN BOOLEAN MODE)
                    ORDER BY tu.created_at DESC
                    LIMIT ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$searchTerm, $limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error searching translation units: " . $e->getMessage());
            return [];
        }
    }
}
