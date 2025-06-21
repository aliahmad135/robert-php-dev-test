<?php

/**
 * TranslationUnit Usage Example
 * 
 * This file demonstrates how to use the TranslationUnit class
 * for managing translation units in the Robert CAT tool.
 */

require_once 'TranslationUnit.php';

// Database configuration
$dbConfig = [
    'host' => 'localhost',
    'dbname' => 'robert_cat_tool',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
];

try {
    // Create PDO connection
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    // Initialize TranslationUnit class
    $translationUnit = new TranslationUnit($pdo);
    
    echo "=== Robert CAT Tool - TranslationUnit Usage Example ===\n\n";
    
    // Example 1: Add a new translation unit
    echo "1. Adding a new translation unit...\n";
    $unitId = $translationUnit->addTranslationUnit(
        sourceText: "Welcome to our website!",
        sourceLanguage: "en",
        targetLanguage: "es",
        context: "Website greeting",
        segmentType: "sentence",
        projectId: 1
    );
    
    if ($unitId) {
        echo "✓ Translation unit created with ID: $unitId\n\n";
    } else {
        echo "✗ Failed to create translation unit\n\n";
    }
    
    // Example 2: Retrieve a translation unit
    echo "2. Retrieving translation unit...\n";
    $unit = $translationUnit->getTranslationUnit($unitId);
    
    if ($unit) {
        echo "✓ Translation unit retrieved:\n";
        echo "   ID: {$unit['id']}\n";
        echo "   Source Text: {$unit['source_text']}\n";
        echo "   Source Language: {$unit['source_language']}\n";
        echo "   Target Language: {$unit['target_language']}\n";
        echo "   Status: {$unit['status']}\n";
        echo "   Word Count: {$unit['word_count']}\n\n";
    } else {
        echo "✗ Failed to retrieve translation unit\n\n";
    }
    
    // Example 3: Update translation unit with translation
    echo "3. Adding translation to the unit...\n";
    $success = $translationUnit->updateTranslationUnit(
        translationUnitId: $unitId,
        translatedText: "¡Bienvenido a nuestro sitio web!",
        translatorId: 1,
        translationMethod: "human",
        confidenceScore: 1.0,
        changeReason: "Initial translation"
    );
    
    if ($success) {
        echo "✓ Translation added successfully\n\n";
    } else {
        echo "✗ Failed to add translation\n\n";
    }
    
    // Example 4: Retrieve updated translation unit with translation
    echo "4. Retrieving updated translation unit...\n";
    $updatedUnit = $translationUnit->getTranslationUnit($unitId);
    
    if ($updatedUnit) {
        echo "✓ Updated translation unit:\n";
        echo "   Source Text: {$updatedUnit['source_text']}\n";
        echo "   Translated Text: {$updatedUnit['translated_text']}\n";
        echo "   Translation Method: {$updatedUnit['translation_method']}\n";
        echo "   Confidence Score: {$updatedUnit['confidence_score']}\n";
        echo "   Status: {$updatedUnit['status']}\n";
        
        if (isset($updatedUnit['history']) && !empty($updatedUnit['history'])) {
            echo "   Translation History:\n";
            foreach ($updatedUnit['history'] as $history) {
                echo "     Version {$history['version_number']}: {$history['translated_text']} ({$history['created_at']})\n";
            }
        }
        echo "\n";
    }
    
    // Example 5: Update translation (creating history)
    echo "5. Updating translation (creating history)...\n";
    $updateSuccess = $translationUnit->updateTranslationUnit(
        translationUnitId: $unitId,
        translatedText: "¡Bienvenidos a nuestro sitio web!",
        translatorId: 1,
        translationMethod: "human",
        confidenceScore: 1.0,
        changeReason: "Improved translation - added plural form"
    );
    
    if ($updateSuccess) {
        echo "✓ Translation updated successfully\n\n";
    } else {
        echo "✗ Failed to update translation\n\n";
    }
    
    // Example 6: Retrieve final version with complete history
    echo "6. Retrieving final version with history...\n";
    $finalUnit = $translationUnit->getTranslationUnit($unitId);
    
    if ($finalUnit && isset($finalUnit['history'])) {
        echo "✓ Final translation unit with history:\n";
        echo "   Current Translation: {$finalUnit['translated_text']}\n";
        echo "   Translation History:\n";
        foreach ($finalUnit['history'] as $history) {
            echo "     Version {$history['version_number']}: {$history['translated_text']}\n";
            echo "       Reason: {$history['change_reason']}\n";
            echo "       Date: {$history['created_at']}\n";
        }
        echo "\n";
    }
    
    // Example 7: Get all translation units
    echo "7. Retrieving all translation units...\n";
    $allUnits = $translationUnit->getAllTranslationUnits([], 5, 0);
    
    echo "✓ Found " . count($allUnits) . " translation units:\n";
    foreach ($allUnits as $index => $unit) {
        echo "   " . ($index + 1) . ". ID: {$unit['id']} - {$unit['source_text']}\n";
        if ($unit['translated_text']) {
            echo "      Translation: {$unit['translated_text']}\n";
        }
    }
    echo "\n";
    
    // Example 8: Search translation units
    echo "8. Searching translation units...\n";
    $searchResults = $translationUnit->searchTranslationUnits("website", 3);
    
    echo "✓ Search results for 'website':\n";
    foreach ($searchResults as $index => $result) {
        echo "   " . ($index + 1) . ". {$result['source_text']}\n";
    }
    echo "\n";
    
    // Example 9: Add multiple translation units
    echo "9. Adding multiple translation units...\n";
    $sampleTexts = [
        "Please contact us for more information.",
        "Thank you for your interest in our services.",
        "We look forward to hearing from you soon."
    ];
    
    $addedUnits = [];
    foreach ($sampleTexts as $text) {
        $newUnitId = $translationUnit->addTranslationUnit(
            sourceText: $text,
            sourceLanguage: "en",
            targetLanguage: "es",
            context: "Contact page",
            segmentType: "sentence"
        );
        
        if ($newUnitId) {
            $addedUnits[] = $newUnitId;
            echo "   ✓ Added: $text (ID: $newUnitId)\n";
        }
    }
    echo "\n";
    
    // Example 10: Demonstrate error handling
    echo "10. Demonstrating error handling...\n";
    
    // Try to get non-existent translation unit
    $nonExistent = $translationUnit->getTranslationUnit(99999);
    if ($nonExistent === false) {
        echo "✓ Correctly handled non-existent translation unit\n";
    }
    
    // Try to update non-existent translation unit
    $updateNonExistent = $translationUnit->updateTranslationUnit(
        translationUnitId: 99999,
        translatedText: "This should fail"
    );
    if ($updateNonExistent === false) {
        echo "✓ Correctly handled update of non-existent translation unit\n";
    }
    echo "\n";
    
    echo "=== Example completed successfully! ===\n";
    echo "The TranslationUnit class provides comprehensive functionality for:\n";
    echo "- Adding new translation units\n";
    echo "- Retrieving translation units by ID\n";
    echo "- Updating translations with automatic history tracking\n";
    echo "- Searching and filtering translation units\n";
    echo "- Error handling and validation\n\n";
    
} catch (PDOException $e) {
    echo "Database connection error: " . $e->getMessage() . "\n";
    echo "Please ensure the database is set up correctly using the schema.sql file.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 