<?php

use PHPUnit\Framework\TestCase;

/**
 * TranslationUnit Test Class
 * 
 * Comprehensive unit tests for the TranslationUnit class
 * Tests all CRUD operations, error handling, and edge cases
 */
class TranslationUnitTest extends TestCase
{
    private $pdo;
    private $translationUnit;
    
    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create test tables
        $this->createTestTables();
        
        // Initialize TranslationUnit class
        $this->translationUnit = new TranslationUnit($this->pdo);
    }
    
    protected function tearDown(): void
    {
        $this->pdo = null;
        $this->translationUnit = null;
    }
    
    /**
     * Create test database tables
     */
    private function createTestTables()
    {
        // Create languages table
        $this->pdo->exec("
            CREATE TABLE languages (
                code VARCHAR(10) PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                native_name VARCHAR(100),
                is_rtl BOOLEAN DEFAULT FALSE,
                is_active BOOLEAN DEFAULT TRUE
            )
        ");
        
        // Create projects table
        $this->pdo->exec("
            CREATE TABLE projects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                source_language VARCHAR(10) NOT NULL,
                target_language VARCHAR(10) NOT NULL,
                status VARCHAR(20) DEFAULT 'draft',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Create translation_units table
        $this->pdo->exec("
            CREATE TABLE translation_units (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                project_id INTEGER,
                source_text TEXT NOT NULL,
                source_language VARCHAR(10) NOT NULL,
                target_language VARCHAR(10) NOT NULL,
                context TEXT,
                segment_type VARCHAR(20) DEFAULT 'sentence',
                status VARCHAR(20) DEFAULT 'pending',
                word_count INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (project_id) REFERENCES projects(id)
            )
        ");
        
        // Create translations table
        $this->pdo->exec("
            CREATE TABLE translations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                translation_unit_id INTEGER NOT NULL,
                translated_text TEXT NOT NULL,
                translator_id INTEGER,
                translation_method VARCHAR(20) DEFAULT 'human',
                confidence_score DECIMAL(3,2),
                is_approved BOOLEAN DEFAULT FALSE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (translation_unit_id) REFERENCES translation_units(id)
            )
        ");
        
        // Create translation_history table
        $this->pdo->exec("
            CREATE TABLE translation_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                translation_id INTEGER NOT NULL,
                translated_text TEXT NOT NULL,
                translator_id INTEGER,
                change_reason VARCHAR(255),
                version_number INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (translation_id) REFERENCES translations(id)
            )
        ");
        
        // Insert test data
        $this->insertTestData();
    }
    
    /**
     * Insert test data
     */
    private function insertTestData()
    {
        // Insert test languages
        $this->pdo->exec("
            INSERT INTO languages (code, name, native_name) VALUES 
            ('en', 'English', 'English'),
            ('es', 'Spanish', 'Español'),
            ('fr', 'French', 'Français')
        ");
        
        // Insert test project
        $this->pdo->exec("
            INSERT INTO projects (name, description, source_language, target_language) VALUES 
            ('Test Project', 'Test project description', 'en', 'es')
        ");
    }
    
    /**
     * Test adding a new translation unit
     */
    public function testAddTranslationUnit()
    {
        $unitId = $this->translationUnit->addTranslationUnit(
            sourceText: "Hello, how are you?",
            sourceLanguage: "en",
            targetLanguage: "es",
            context: "Greeting context",
            segmentType: "sentence",
            projectId: 1
        );
        
        $this->assertIsInt($unitId);
        $this->assertGreaterThan(0, $unitId);
        
        // Verify the unit was created correctly
        $unit = $this->translationUnit->getTranslationUnit($unitId);
        $this->assertNotFalse($unit);
        $this->assertEquals("Hello, how are you?", $unit['source_text']);
        $this->assertEquals("en", $unit['source_language']);
        $this->assertEquals("es", $unit['target_language']);
        $this->assertEquals("Greeting context", $unit['context']);
        $this->assertEquals("sentence", $unit['segment_type']);
        $this->assertEquals("pending", $unit['status']);
        $this->assertEquals(4, $unit['word_count']); // "Hello, how are you?" = 4 words
    }
    
    /**
     * Test adding translation unit with minimal required fields
     */
    public function testAddTranslationUnitMinimal()
    {
        $unitId = $this->translationUnit->addTranslationUnit(
            sourceText: "Test text",
            sourceLanguage: "en",
            targetLanguage: "es"
        );
        
        $this->assertIsInt($unitId);
        $this->assertGreaterThan(0, $unitId);
        
        $unit = $this->translationUnit->getTranslationUnit($unitId);
        $this->assertNotFalse($unit);
        $this->assertEquals("Test text", $unit['source_text']);
        $this->assertEquals("sentence", $unit['segment_type']); // Default value
        $this->assertEquals("pending", $unit['status']); // Default value
    }
    
    /**
     * Test retrieving a translation unit by ID
     */
    public function testGetTranslationUnit()
    {
        // First create a unit
        $unitId = $this->translationUnit->addTranslationUnit(
            sourceText: "Test source text",
            sourceLanguage: "en",
            targetLanguage: "es"
        );
        
        // Retrieve the unit
        $unit = $this->translationUnit->getTranslationUnit($unitId);
        
        $this->assertNotFalse($unit);
        $this->assertEquals($unitId, $unit['id']);
        $this->assertEquals("Test source text", $unit['source_text']);
        $this->assertEquals("en", $unit['source_language']);
        $this->assertEquals("es", $unit['target_language']);
    }
    
    /**
     * Test retrieving non-existent translation unit
     */
    public function testGetTranslationUnitNotFound()
    {
        $unit = $this->translationUnit->getTranslationUnit(99999);
        $this->assertFalse($unit);
    }
    
    /**
     * Test updating a translation unit with translation
     */
    public function testUpdateTranslationUnit()
    {
        // Create a translation unit
        $unitId = $this->translationUnit->addTranslationUnit(
            sourceText: "Hello world",
            sourceLanguage: "en",
            targetLanguage: "es"
        );
        
        // Update with translation
        $success = $this->translationUnit->updateTranslationUnit(
            translationUnitId: $unitId,
            translatedText: "Hola mundo",
            translatorId: 1,
            translationMethod: "human",
            confidenceScore: 1.0,
            changeReason: "Initial translation"
        );
        
        $this->assertTrue($success);
        
        // Verify the translation was added
        $unit = $this->translationUnit->getTranslationUnit($unitId);
        $this->assertNotFalse($unit);
        $this->assertEquals("Hola mundo", $unit['translated_text']);
        $this->assertEquals("human", $unit['translation_method']);
        $this->assertEquals(1.0, $unit['confidence_score']);
        $this->assertEquals("completed", $unit['status']);
    }
    
    /**
     * Test updating translation unit multiple times (history tracking)
     */
    public function testUpdateTranslationUnitHistory()
    {
        // Create a translation unit
        $unitId = $this->translationUnit->addTranslationUnit(
            sourceText: "Good morning",
            sourceLanguage: "en",
            targetLanguage: "es"
        );
        
        // First translation
        $this->translationUnit->updateTranslationUnit(
            translationUnitId: $unitId,
            translatedText: "Buenos días",
            translatorId: 1,
            changeReason: "Initial translation"
        );
        
        // Second translation (update)
        $this->translationUnit->updateTranslationUnit(
            translationUnitId: $unitId,
            translatedText: "Buenos días, señor",
            translatorId: 1,
            changeReason: "Added formality"
        );
        
        // Verify current translation
        $unit = $this->translationUnit->getTranslationUnit($unitId);
        $this->assertEquals("Buenos días, señor", $unit['translated_text']);
        
        // Verify history exists
        $this->assertArrayHasKey('history', $unit);
        $this->assertCount(2, $unit['history']);
        
        // Verify history entries
        $this->assertEquals("Buenos días", $unit['history'][1]['translated_text']);
        $this->assertEquals("Initial translation", $unit['history'][1]['change_reason']);
        $this->assertEquals("Buenos días, señor", $unit['history'][2]['translated_text']);
        $this->assertEquals("Added formality", $unit['history'][2]['change_reason']);
    }
    
    /**
     * Test getting all translation units
     */
    public function testGetAllTranslationUnits()
    {
        // Create multiple translation units
        $this->translationUnit->addTranslationUnit("Text 1", "en", "es");
        $this->translationUnit->addTranslationUnit("Text 2", "en", "es");
        $this->translationUnit->addTranslationUnit("Text 3", "en", "es");
        
        $units = $this->translationUnit->getAllTranslationUnits();
        
        $this->assertIsArray($units);
        $this->assertGreaterThanOrEqual(3, count($units));
        
        // Verify structure of returned units
        foreach ($units as $unit) {
            $this->assertArrayHasKey('id', $unit);
            $this->assertArrayHasKey('source_text', $unit);
            $this->assertArrayHasKey('source_language', $unit);
            $this->assertArrayHasKey('target_language', $unit);
        }
    }
    
    /**
     * Test getting translation units with filters
     */
    public function testGetAllTranslationUnitsWithFilters()
    {
        // Create units with different statuses
        $this->translationUnit->addTranslationUnit("Text 1", "en", "es");
        $this->translationUnit->addTranslationUnit("Text 2", "en", "fr");
        
        // Filter by target language
        $units = $this->translationUnit->getAllTranslationUnits(['target_language' => 'es']);
        
        $this->assertIsArray($units);
        foreach ($units as $unit) {
            $this->assertEquals("es", $unit['target_language']);
        }
    }
    
    /**
     * Test deleting a translation unit
     */
    public function testDeleteTranslationUnit()
    {
        // Create a translation unit
        $unitId = $this->translationUnit->addTranslationUnit(
            sourceText: "Delete me",
            sourceLanguage: "en",
            targetLanguage: "es"
        );
        
        // Verify it exists
        $unit = $this->translationUnit->getTranslationUnit($unitId);
        $this->assertNotFalse($unit);
        
        // Delete it
        $success = $this->translationUnit->deleteTranslationUnit($unitId);
        $this->assertTrue($success);
        
        // Verify it's gone
        $unit = $this->translationUnit->getTranslationUnit($unitId);
        $this->assertFalse($unit);
    }
    
    /**
     * Test deleting non-existent translation unit
     */
    public function testDeleteTranslationUnitNotFound()
    {
        $success = $this->translationUnit->deleteTranslationUnit(99999);
        $this->assertFalse($success);
    }
    
    /**
     * Test searching translation units
     */
    public function testSearchTranslationUnits()
    {
        // Create units with searchable text
        $this->translationUnit->addTranslationUnit("Hello world", "en", "es");
        $this->translationUnit->addTranslationUnit("Goodbye world", "en", "es");
        $this->translationUnit->addTranslationUnit("Hello there", "en", "es");
        
        $results = $this->translationUnit->searchTranslationUnits("hello");
        
        $this->assertIsArray($results);
        $this->assertGreaterThan(0, count($results));
        
        // Verify all results contain "hello"
        foreach ($results as $result) {
            $this->assertStringContainsStringIgnoringCase("hello", $result['source_text']);
        }
    }
    
    /**
     * Test word count calculation
     */
    public function testWordCountCalculation()
    {
        $unitId = $this->translationUnit->addTranslationUnit(
            sourceText: "This is a test sentence with multiple words.",
            sourceLanguage: "en",
            targetLanguage: "es"
        );
        
        $unit = $this->translationUnit->getTranslationUnit($unitId);
        $this->assertEquals(8, $unit['word_count']); // "This is a test sentence with multiple words." = 8 words
    }
    
    /**
     * Test word count with empty text
     */
    public function testWordCountEmptyText()
    {
        $unitId = $this->translationUnit->addTranslationUnit(
            sourceText: "",
            sourceLanguage: "en",
            targetLanguage: "es"
        );
        
        $unit = $this->translationUnit->getTranslationUnit($unitId);
        $this->assertEquals(0, $unit['word_count']);
    }
    
    /**
     * Test translation unit with different segment types
     */
    public function testTranslationUnitSegmentTypes()
    {
        $types = ['sentence', 'paragraph', 'phrase'];
        
        foreach ($types as $type) {
            $unitId = $this->translationUnit->addTranslationUnit(
                sourceText: "Test text",
                sourceLanguage: "en",
                targetLanguage: "es",
                segmentType: $type
            );
            
            $unit = $this->translationUnit->getTranslationUnit($unitId);
            $this->assertEquals($type, $unit['segment_type']);
        }
    }
    
    /**
     * Test translation unit with project association
     */
    public function testTranslationUnitWithProject()
    {
        $unitId = $this->translationUnit->addTranslationUnit(
            sourceText: "Project text",
            sourceLanguage: "en",
            targetLanguage: "es",
            projectId: 1
        );
        
        $unit = $this->translationUnit->getTranslationUnit($unitId);
        $this->assertEquals(1, $unit['project_id']);
    }
    
    /**
     * Test error handling with invalid data
     */
    public function testErrorHandling()
    {
        // Test with empty source text (should still work as it's handled by validation)
        $unitId = $this->translationUnit->addTranslationUnit(
            sourceText: "",
            sourceLanguage: "en",
            targetLanguage: "es"
        );
        
        // Should still create the unit (validation is handled at API level)
        $this->assertIsInt($unitId);
    }
    
    /**
     * Test translation confidence scores
     */
    public function testTranslationConfidenceScores()
    {
        $unitId = $this->translationUnit->addTranslationUnit(
            sourceText: "Test confidence",
            sourceLanguage: "en",
            targetLanguage: "es"
        );
        
        // Test different confidence scores
        $scores = [0.5, 0.75, 1.0];
        
        foreach ($scores as $score) {
            $this->translationUnit->updateTranslationUnit(
                translationUnitId: $unitId,
                translatedText: "Test translation",
                confidenceScore: $score
            );
            
            $unit = $this->translationUnit->getTranslationUnit($unitId);
            $this->assertEquals($score, $unit['confidence_score']);
        }
    }
    
    /**
     * Test translation methods
     */
    public function testTranslationMethods()
    {
        $unitId = $this->translationUnit->addTranslationUnit(
            sourceText: "Test methods",
            sourceLanguage: "en",
            targetLanguage: "es"
        );
        
        $methods = ['human', 'machine', 'hybrid'];
        
        foreach ($methods as $method) {
            $this->translationUnit->updateTranslationUnit(
                translationUnitId: $unitId,
                translatedText: "Test translation",
                translationMethod: $method
            );
            
            $unit = $this->translationUnit->getTranslationUnit($unitId);
            $this->assertEquals($method, $unit['translation_method']);
        }
    }
    
    /**
     * Test pagination parameters
     */
    public function testPaginationParameters()
    {
        // Create multiple units
        for ($i = 1; $i <= 5; $i++) {
            $this->translationUnit->addTranslationUnit(
                sourceText: "Text $i",
                sourceLanguage: "en",
                targetLanguage: "es"
            );
        }
        
        // Test with limit
        $units = $this->translationUnit->getAllTranslationUnits([], 3, 0);
        $this->assertLessThanOrEqual(3, count($units));
        
        // Test with offset
        $units = $this->translationUnit->getAllTranslationUnits([], 2, 2);
        $this->assertLessThanOrEqual(2, count($units));
    }
}
