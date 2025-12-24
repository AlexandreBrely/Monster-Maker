<?php
/**
 * ============================================================================
 * INTERNAL API ENDPOINT: Get User's Collections
 * ============================================================================
 * 
 * PURPOSE:
 * Returns a list of all collections belonging to the logged-in user.
 * Used to populate dropdown menus for "Add to Collection" functionality.
 * 
 * WHY THIS IS NEEDED:
 * When user clicks "Add to Collection" on a monster card, JavaScript needs
 * to show a dropdown with all user's collections. Instead of embedding this
 * data in every page's HTML, we fetch it on-demand via AJAX.
 * 
 * BENEFITS OF AJAX LOADING:
 * - Faster page loads (no need to query collections on every page)
 * - Always up-to-date (fetches fresh data when dropdown opens)
 * - Less memory (data not stored in page HTML)
 * - Can be called from any page
 * 
 * JSON RESPONSE STRUCTURE:
 * ------------------------
 * This endpoint returns an array of collection objects. Each collection
 * includes metadata like name, description, and monster count.
 * 
 * Example response:
 * {
 *   "success": true,
 *   "collections": [
 *     {
 *       "collection_id": 1,
 *       "collection_name": "To Print",
 *       "description": "Default collection",
 *       "is_default": 1,
 *       "monster_count": 5,
 *       "created_at": "2025-01-15 10:30:00"
 *     },
 *     {
 *       "collection_id": 2,
 *       "collection_name": "Goblin Patrol",
 *       "description": "Level 1-3 goblin encounters",
 *       "is_default": 0,
 *       "monster_count": 3,
 *       "created_at": "2025-01-16 14:22:00"
 *     }
 *   ]
 * }
 * 
 * API SPECIFICATION:
 * ------------------
 * Method:  GET
 * URL:     /api/get-collections.php
 * Auth:    Session-based (user must be logged in)
 * 
 * REQUEST:
 * No parameters needed - returns current user's collections
 * 
 * RESPONSE (JSON):
 * Success (200):
 * {
 *   "success": true,
 *   "collections": [ {...}, {...} ]  // Array of collection objects
 * }
 * 
 * Error (401 Unauthorized):
 * {
 *   "success": false,
 *   "message": "Not authenticated. Please log in."
 * }
 * 
 * Error (500 Internal Server Error):
 * {
 *   "success": false,
 *   "message": "Error loading collections: [error details]"
 * }
 * 
 * CALLED BY:
 * - public/js/collection-manager.js → loadUserCollections()
 * - Used when opening "Add to Collection" dropdown
 * - Loads dynamically when user interacts with dropdown
 * 
 * SECURITY:
 * - Authentication required via session
 * - User can only see their own collections
 * - No SQL injection risk (uses prepared statements in model)
 * 
 * MVC ARCHITECTURE:
 * API → CollectionController → Collection Model → Database
 */

// STEP 1: Set JSON response headers
// Tells browser to expect JSON, not HTML
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
// Must be logged in to see collections
if (!isset($_SESSION['user'])) {
    http_response_code(401);  // 401 = Unauthorized
    echo json_encode([
        'success' => false,
        'message' => 'Not authenticated. Please log in.'
    ]);
    exit;
}

// STEP 6: Fetch and return collections
try {
    $collectionModel = new \App\Models\Collection();
    $userId = $_SESSION['user']['u_id'];
    
    // Get all collections for this user
    // Returns array of collections with monster counts
    $collections = $collectionModel->getByUser($userId);
    
    // STEP 7: Send successful JSON response
    // JavaScript will receive this and populate dropdown
    echo json_encode([
        'success' => true,
        'collections' => $collections  // Array of collection objects
    ]);
    
} catch (\Exception $e) {
    // STEP 8: Handle errors
    http_response_code(500);  // 500 = Internal Server Error
    echo json_encode([
        'success' => false,
        'message' => 'Error loading collections: ' . $e->getMessage()
    ]);
}
