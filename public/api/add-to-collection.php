<?php
/**
 * ============================================================================
 * INTERNAL API ENDPOINT: Add Monster to Collection
 * ============================================================================
 * 
 * WHAT IS AN INTERNAL API?
 * An "internal API" is a PHP file that acts like a web service, but only for
 * your own website (not public like Twitter's API). JavaScript on your pages
 * can send requests to this file and get JSON responses back.
 * 
 * Think of it like a restaurant:
 * - Frontend (JavaScript): Customer ordering food
 * - API Endpoint (this file): Waiter taking the order
 * - Backend (Models): Kitchen preparing the food
 * - Response (JSON): Waiter bringing the food back
 * 
 * WHY USE JSON?
 * JSON (JavaScript Object Notation) is the universal language for APIs.
 * 
 * Benefits:
 * - JavaScript can read it directly: JSON.parse('{"name":"Goblin"}')
 * - PHP can create it easily: json_encode(['name' => 'Goblin'])
 * - Lightweight: Less data than HTML
 * - Structured: Objects and arrays, not messy HTML strings
 * 
 * Example:
 * HTML Response (old way):  <div class="name">Goblin</div><div class="hp">7</div>
 * JSON Response (modern):   {"name":"Goblin","hp":7}
 * 
 * HTTP STATUS CODES EXPLAINED:
 * ----------------------------
 * Every HTTP response includes a status code telling the client what happened.
 * Think of them as restaurant service responses:
 * 
 * 2xx = Success (food delivered)
 *   200 OK: Request succeeded, here's your data
 * 
 * 4xx = Client Error (customer's fault)
 *   400 Bad Request: Missing/invalid data (forgot to say what you want)
 *   401 Unauthorized: Not logged in (need to show your membership card)
 *   403 Forbidden: Logged in but not allowed (not your table)
 *   404 Not Found: Resource doesn't exist (item not on menu)
 *   405 Method Not Allowed: Wrong HTTP method (can't order at the bar)
 *   409 Conflict: Duplicate/conflicting data (already have this order)
 * 
 * 5xx = Server Error (kitchen's fault)
 *   500 Internal Server Error: Something broke on our end
 * 
 * WHY STATUS CODES MATTER:
 * JavaScript can check response.status to handle errors differently:
 * - 401: Redirect to login page
 * - 403: Show "permission denied" message
 * - 409: Show "already exists" message
 * - 500: Show "server error, try again later"
 * 
 * Without status codes, JavaScript only knows "success" or "failure" -
 * can't tell WHY it failed or how to respond appropriately.
 * 
 * HOW JSON WORKS IN THIS FILE:
 * 
 * 1. RECEIVE JSON:
 *    JavaScript sends: {"monster_id":123, "collection_id":456}
 *    PHP receives:     file_get_contents('php://input')
 *    PHP parses:       json_decode($jsonString, true) → array
 * 
 * 2. PROCESS REQUEST:
 *    Validate data, check permissions, add to database
 * 
 * 3. SEND JSON:
 *    PHP creates:      ['success' => true, 'message' => 'Added!']
 *    PHP encodes:      json_encode($array) → string
 *    JavaScript gets:  {"success":true,"message":"Added!"}
 * 
 * API SPECIFICATION:
 * ------------------
 * Method:  POST
 * URL:     /api/add-to-collection.php
 * Headers: Content-Type: application/json
 * Auth:    Session-based (user must be logged in)
 * 
 * REQUEST BODY (JSON):
 * {
 *   "monster_id": 123,      // Integer: ID of monster to add
 *   "collection_id": 456    // Integer: ID of collection
 * }
 * 
 * RESPONSE (JSON):
 * Success (200):
 * {
 *   "success": true,
 *   "message": "Added 'Goblin' to 'To Print'."
 * }
 * 
 * Error (400 Bad Request):
 * {
 *   "success": false,
 *   "message": "Missing required fields: monster_id, collection_id"
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
 *   "message": "'Goblin' is already in 'To Print'."
 * }
 * 
 * SECURITY FEATURES:
 * - Session authentication (user must be logged in)
 * - Ownership validation (user must own monster AND collection)
 * - Input sanitization (cast to int)
 * - Prepared statements (SQL injection prevention in models)
 * - HTTP status codes (proper REST API practice)
 * 
 * CALLED BY:
 * - public/js/collection-manager.js → addToCollection()
 * - Used in monster detail views and browse pages
 * 
 * MVC ARCHITECTURE:
 * This is a thin API wrapper that validates input then routes to
 * CollectionController for business logic. Following MVC pattern:
 * - API file: Input validation, auth check, response formatting
 * - Controller: Business logic, orchestration
 * - Model: Database operations
 */

// STEP 1: Set response type to JSON
// This tells the browser to expect JSON, not HTML
// charset=utf-8 ensures proper encoding for special characters
header('Content-Type: application/json; charset=utf-8');

// STEP 2: Start session for authentication
// Session contains logged-in user information
session_start();

// STEP 3: Define paths for autoloader
define('ROOT', dirname(__DIR__, 2));

// STEP 4: Autoload classes following PSR-4 convention
// Automatically includes class files when needed (no manual require statements)
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

// STEP 5: Authentication check
// All API endpoints require logged-in user
if (!isset($_SESSION['user'])) {
    http_response_code(401);  // 401 = Unauthorized
    echo json_encode([
        'success' => false,
        'message' => 'Not authenticated. Please log in.'
    ]);
    exit;
}

// STEP 6: Verify HTTP method
// This endpoint only accepts POST (creating/adding data)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);  // 405 = Method Not Allowed
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method. Use POST.'
    ]);
    exit;
}

// STEP 7: Parse and validate JSON input
// file_get_contents('php://input') reads raw POST body (the JSON string)
// json_decode() converts JSON string to PHP array
// Second parameter true = return associative array instead of object
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields exist
if (!$input || !isset($input['monster_id']) || !isset($input['collection_id'])) {
    http_response_code(400);  // 400 = Bad Request
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: monster_id, collection_id'
    ]);
    exit;
}

// Route to controller (MVC pattern)
// Controller handles business logic and model interaction
use App\Controllers\CollectionController;

$controller = new CollectionController();
$controller->addMonsterApi($input);

// STEP 8: Process request
// Extract and sanitize input values
try {
    $monsterId = (int)$input['monster_id'];        // Cast to int for security
    $collectionId = (int)$input['collection_id'];  // Cast to int for security
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
            'message' => 'Monster not found or you do not have permission to access it.'
        ]);
        exit;
    }
    
    // STEP 10: Verify collection exists and belongs to user
    $collection = $collectionModel->getById($collectionId);
    if (!$collection || $collection['u_id'] != $userId) {
        http_response_code(403);  // 403 = Forbidden
        echo json_encode([
            'success' => false,
            'message' => 'Collection not found or you do not have permission to access it.'
        ]);
        exit;
    }
    
    // STEP 11: Attempt to add monster to collection
    // Returns true if added, false if already exists
    $added = $collectionModel->addMonster($collectionId, $monsterId);
    
    if ($added) {
        // SUCCESS: Monster added to collection
        // http_response_code 200 is default, no need to set
        echo json_encode([
            'success' => true,
            'message' => "Added '{$monster['name']}' to '{$collection['collection_name']}'."
        ]);
    } else {
        // DUPLICATE: Monster already in collection
        http_response_code(409);  // 409 = Conflict
        echo json_encode([
            'success' => false,
            'message' => "'{$monster['name']}' is already in '{$collection['collection_name']}'."
        ]);
    }
    
} catch (\Exception $e) {
    // STEP 12: Handle unexpected errors
    http_response_code(500);  // 500 = Internal Server Error
    echo json_encode([
        'success' => false,
        'message' => 'Error adding to collection: ' . $e->getMessage()
    ]);
}
