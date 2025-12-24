<?php
/**
 * ============================================================================
 * INTERNAL API ENDPOINT: Create Collection and Add Monster (Atomic Operation)
 * ============================================================================
 * 
 * PURPOSE:
 * Creates a new collection and immediately adds a monster to it - all in one request.
 * This is a "convenience endpoint" that combines two operations for better UX.
 * 
 * WHY COMBINE TWO OPERATIONS?
 * User workflow: Click "Add to Collection" → Type new collection name → Click "Create & Add"
 * 
 * Without this endpoint:
 * 1. Send request to create collection
 * 2. Wait for response with collection ID
 * 3. Send second request to add monster to collection
 * = Two round trips, slower, more code
 * 
 * With this endpoint:
 * 1. Send one request with collection name + monster ID
 * 2. Server creates collection AND adds monster
 * = One round trip, faster, simpler
 * 
 * ATOMIC OPERATION:
 * "Atomic" means all-or-nothing - either BOTH succeed or BOTH fail.
 * If collection creation succeeds but adding monster fails, we should
 * rollback (delete) the collection. This prevents orphaned empty collections.
 * 
 * JSON REQUEST/RESPONSE:
 * ----------------------
 * JavaScript sends collection name and monster to add together.
 * Server creates collection, gets its ID, adds monster, returns both pieces of info.
 * 
 * Example request:
 * {
 *   "monster_id": 123,
 *   "collection_name": "Kobold Encounters",
 *   "description": "Kobold-themed monsters for level 1-3 parties"
 * }
 * 
 * Example response:
 * {
 *   "success": true,
 *   "message": "Created 'Kobold Encounters' and added 'Kobold Warrior'.",
 *   "collection_id": 15
 * }
 * 
 * API SPECIFICATION:
 * ------------------
 * Method:  POST
 * URL:     /api/create-collection-and-add.php
 * Headers: Content-Type: application/json
 * Auth:    Session-based (user must be logged in)
 * 
 * REQUEST BODY (JSON):
 * {
 *   "monster_id": 123,           // Integer: Monster to add
 *   "collection_name": "Name",   // String: 1-100 chars
 *   "description": "..."         // String (optional): Can be null/empty
 * }
 * 
 * RESPONSE (JSON):
 * Success (200):
 * {
 *   "success": true,
 *   "message": "Created 'Name' and added 'Monster'.",
 *   "collection_id": 15
 * }
 * 
 * Error (400 Bad Request):
 * {
 *   "success": false,
 *   "message": "Missing required fields: monster_id, collection_name"
 * }
 * 
 * Error (401 Unauthorized):
 * {
 *   "success": false,
 *   "message": "Not authenticated. Please log in."
 * }
 * 
 * Error (403 Forbidden):
 * {
 *   "success": false,
 *   "message": "Monster not found or you do not have permission."
 * }
 * 
 * Error (409 Conflict):
 * {
 *   "success": false,
 *   "message": "Collection 'Name' already exists."
 * }
 * 
 * CALLED BY:
 * - public/js/collection-manager.js → createAndAddToNewCollection()
 * - Used in "Create New Collection" form in dropdown
 * 
 * SECURITY:
 * - Authentication required (session)
 * - User must own the monster being added
 * - Collection name sanitized and validated
 * - Duplicate name prevention
 * - Transaction-like behavior (rollback on failure)
 * 
 * MVC ARCHITECTURE:
 * API → CollectionController → Collection Model + Monster Model → Database
 */

// STEP 1: Set JSON response headers
header('Content-Type: application/json; charset=utf-8');

// STEP 2: Start session for authentication
session_start();

// STEP 3: Define root path for autoloader
define('ROOT', dirname(__DIR__, 2));

// STEP 4: Autoload classes following PSR-4 convention
spl_autoload_register(function ($class) {
    $class = str_replace('App\\', '', $class);
    $file = ROOT . '/src/' . str_replace('\\', '/', $class) . '.php';
    $file = preg_replace_callback('/\/([A-Z][a-z]+)\//', function($matches) {
        return '/' . strtolower($matches[1]) . '/';
    }, $file);
    
    if (file_exists($file)) {
        require_once $file;
    }
});

// STEP 5: Verify user authentication
if (!isset($_SESSION['user'])) {
    http_response_code(401);  // 401 = Unauthorized
    echo json_encode([
        'success' => false,
        'message' => 'Not authenticated. Please log in.'
    ]);
    exit;
}

// STEP 6: Verify HTTP method (must be POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);  // 405 = Method Not Allowed
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method. Use POST.'
    ]);
    exit;
}

// STEP 7: Parse and validate JSON input
// file_get_contents('php://input') reads raw POST body
// json_decode() converts JSON string → PHP array
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!$input || !isset($input['monster_id']) || !isset($input['collection_name'])) {
    http_response_code(400);  // 400 = Bad Request
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: monster_id, collection_name'
    ]);
    exit;
}

// STEP 8: Process the atomic operation
try {
    // Extract and sanitize input
    $monsterId = (int)$input['monster_id'];
    $collectionName = trim($input['collection_name']);
    $description = isset($input['description']) ? trim($input['description']) : null;
    $userId = $_SESSION['user']['u_id'];
    
    // Initialize models
    $monsterModel = new \App\Models\Monster();
    $collectionModel = new \App\Models\Collection();
    
    // STEP 9: Verify monster exists and belongs to user
    $monster = $monsterModel->getById($monsterId);
    if (!$monster || $monster['u_id'] != $userId) {
        http_response_code(403);  // 403 = Forbidden
        echo json_encode([
            'success' => false,
            'message' => 'Monster not found or you do not have permission.'
        ]);
        exit;
    }
    
    // STEP 10: Validate collection name
    if (empty($collectionName) || strlen($collectionName) > 100) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Collection name must be 1-100 characters.'
        ]);
        exit;
    }
    
    // STEP 11: Create the collection
    // Returns collection ID if successful, false if duplicate name
    $collectionId = $collectionModel->create($userId, $collectionName, $description);
    
    if (!$collectionId) {
        http_response_code(409);  // 409 = Conflict
        echo json_encode([
            'success' => false,
            'message' => "Collection '$collectionName' already exists."
        ]);
        exit;
    }
    
    // STEP 12: Add monster to the newly created collection
    $added = $collectionModel->addMonster($collectionId, $monsterId);
    
    if (!$added) {
        // ROLLBACK: If adding monster fails, delete the collection
        // This prevents orphaned empty collections
        $collectionModel->delete($collectionId);
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to add monster to collection. Operation rolled back.'
        ]);
        exit;
    }
    
    // STEP 13: Success - both operations completed
    echo json_encode([
        'success' => true,
        'message' => "Created '$collectionName' and added '{$monster['name']}'.",
        'collection_id' => $collectionId
    ]);
    
} catch (\Exception $e) {
    // STEP 14: Handle unexpected errors
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error creating collection: ' . $e->getMessage()
    ]);
}
