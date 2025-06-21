<?php

/**
 * RESTful API for Robert CAT Tool
 * 
 * Provides CRUD operations for translation units
 * Endpoints:
 * - GET /api/translations - List all translation units
 * - GET /api/translations/{id} - Get specific translation unit
 * - POST /api/translations - Create new translation unit
 * - PUT /api/translations/{id} - Update translation unit
 * - DELETE /api/translations/{id} - Delete translation unit
 */

// Enable CORS for cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include the TranslationUnit class
require_once '../src/TranslationUnit.php';

// Database configuration
$dbConfig = [
    'host' => 'localhost',
    'dbname' => 'robert_cat_tool',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
];

// Error handler
function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'error' => true,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

// Success response
function sendResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'error' => false,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

// Validate JSON input
function getJsonInput() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendError('Invalid JSON format');
    }
    
    return $data;
}

// Validate required fields
function validateRequired($data, $fields) {
    foreach ($fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            sendError("Missing required field: $field");
        }
    }
}

// Parse URL path
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$pathSegments = explode('/', trim($path, '/'));

// Find the API endpoint
$apiIndex = array_search('api', $pathSegments);
if ($apiIndex === false) {
    sendError('Invalid API endpoint', 404);
}

$endpoint = $pathSegments[$apiIndex + 1] ?? '';
$resourceId = $pathSegments[$apiIndex + 2] ?? null;

try {
    // Create database connection
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    // Initialize TranslationUnit class
    $translationUnit = new TranslationUnit($pdo);
    
    // Route the request
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if ($resourceId) {
                // GET /api/translations/{id}
                if (!is_numeric($resourceId)) {
                    sendError('Invalid translation unit ID', 400);
                }
                
                $unit = $translationUnit->getTranslationUnit((int)$resourceId);
                if ($unit === false) {
                    sendError('Translation unit not found', 404);
                }
                
                sendResponse($unit);
            } else {
                // GET /api/translations
                $filters = [];
                
                // Apply query parameters as filters
                if (isset($_GET['status'])) {
                    $filters['status'] = $_GET['status'];
                }
                if (isset($_GET['source_language'])) {
                    $filters['source_language'] = $_GET['source_language'];
                }
                if (isset($_GET['target_language'])) {
                    $filters['target_language'] = $_GET['target_language'];
                }
                if (isset($_GET['project_id'])) {
                    $filters['project_id'] = (int)$_GET['project_id'];
                }
                
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
                $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
                
                // Validate pagination parameters
                if ($limit < 1 || $limit > 100) {
                    $limit = 10;
                }
                if ($offset < 0) {
                    $offset = 0;
                }
                
                $units = $translationUnit->getAllTranslationUnits($filters, $limit, $offset);
                
                sendResponse([
                    'units' => $units,
                    'pagination' => [
                        'limit' => $limit,
                        'offset' => $offset,
                        'count' => count($units)
                    ]
                ]);
            }
            break;
            
        case 'POST':
            // POST /api/translations
            $data = getJsonInput();
            
            // Validate required fields
            validateRequired($data, ['source_text', 'source_language', 'target_language']);
            
            // Validate language codes
            $validLanguages = ['en', 'es', 'fr', 'de', 'it', 'pt', 'ar', 'zh', 'ja', 'ko'];
            if (!in_array($data['source_language'], $validLanguages)) {
                sendError('Invalid source language code');
            }
            if (!in_array($data['target_language'], $validLanguages)) {
                sendError('Invalid target language code');
            }
            
            // Validate segment type
            $validSegmentTypes = ['sentence', 'paragraph', 'phrase'];
            $segmentType = $data['segment_type'] ?? 'sentence';
            if (!in_array($segmentType, $validSegmentTypes)) {
                sendError('Invalid segment type');
            }
            
            $unitId = $translationUnit->addTranslationUnit(
                sourceText: $data['source_text'],
                sourceLanguage: $data['source_language'],
                targetLanguage: $data['target_language'],
                context: $data['context'] ?? '',
                segmentType: $segmentType,
                projectId: $data['project_id'] ?? null
            );
            
            if ($unitId === false) {
                sendError('Failed to create translation unit', 500);
            }
            
            // Return the created translation unit
            $createdUnit = $translationUnit->getTranslationUnit($unitId);
            sendResponse($createdUnit, 201);
            break;
            
        case 'PUT':
            // PUT /api/translations/{id}
            if (!$resourceId || !is_numeric($resourceId)) {
                sendError('Invalid translation unit ID', 400);
            }
            
            $data = getJsonInput();
            
            // Check if translation unit exists
            $existingUnit = $translationUnit->getTranslationUnit((int)$resourceId);
            if ($existingUnit === false) {
                sendError('Translation unit not found', 404);
            }
            
            // Validate required fields for update
            validateRequired($data, ['translated_text']);
            
            // Validate translation method
            $validMethods = ['human', 'machine', 'hybrid'];
            $translationMethod = $data['translation_method'] ?? 'human';
            if (!in_array($translationMethod, $validMethods)) {
                sendError('Invalid translation method');
            }
            
            // Validate confidence score
            $confidenceScore = $data['confidence_score'] ?? 1.0;
            if (!is_numeric($confidenceScore) || $confidenceScore < 0 || $confidenceScore > 1) {
                sendError('Confidence score must be between 0 and 1');
            }
            
            $success = $translationUnit->updateTranslationUnit(
                translationUnitId: (int)$resourceId,
                translatedText: $data['translated_text'],
                translatorId: $data['translator_id'] ?? null,
                translationMethod: $translationMethod,
                confidenceScore: (float)$confidenceScore,
                changeReason: $data['change_reason'] ?? 'Translation updated via API'
            );
            
            if ($success === false) {
                sendError('Failed to update translation unit', 500);
            }
            
            // Return the updated translation unit
            $updatedUnit = $translationUnit->getTranslationUnit((int)$resourceId);
            sendResponse($updatedUnit);
            break;
            
        case 'DELETE':
            // DELETE /api/translations/{id}
            if (!$resourceId || !is_numeric($resourceId)) {
                sendError('Invalid translation unit ID', 400);
            }
            
            // Check if translation unit exists
            $existingUnit = $translationUnit->getTranslationUnit((int)$resourceId);
            if ($existingUnit === false) {
                sendError('Translation unit not found', 404);
            }
            
            $success = $translationUnit->deleteTranslationUnit((int)$resourceId);
            if ($success === false) {
                sendError('Failed to delete translation unit', 500);
            }
            
            sendResponse(['message' => 'Translation unit deleted successfully']);
            break;
            
        default:
            sendError('Method not allowed', 405);
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    sendError('Database connection error', 500);
} catch (Exception $e) {
    error_log("API error: " . $e->getMessage());
    sendError('Internal server error', 500);
}
