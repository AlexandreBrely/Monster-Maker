<?php

namespace App\Controllers;

use App\Models\Collection;
use App\Models\Monster;

/**
 * CollectionController
 * 
 * WHAT IS THIS CONTROLLER?
 * Handles all operations related to Collections (organizing monsters for printing).
 * MVC role:
 * - Handle request, call models, render views, return response.
 * 
 * ROUTES HANDLED:
 * - GET  /collections              → index() - List all user's collections
 * - GET  /collection-view?id=X     → view() - View specific collection with monsters
 * - GET  /collection-create        → create() - Show create form
 * - POST /collection-create        → create() - Process creation
 * - GET  /collection-edit?id=X     → edit() - Show edit form
 * - POST /collection-edit          → edit() - Process update
 * - POST /collection-delete        → delete() - Delete collection
 * - POST /collection-add-monster   → addMonster() - AJAX endpoint
 * - POST /collection-remove-monster → removeMonster() - AJAX endpoint
 * 
 * SECURITY FEATURES:
 * - Authentication: Must be logged in to access
 * - Authorization: Can only manage own collections
 * - Ownership verification: Before any modify/delete operation
 * - Input validation: All user input is validated and sanitized
 */
class CollectionController
{
    private $collectionModel;
    private $monsterModel;

    public function __construct()
    {
        $this->collectionModel = new Collection();
        $this->monsterModel = new Monster();
    }

    /**
     * Ensure user is authenticated
     * 
     * Redirects to login page if user is not logged in.
     */
    private function ensureAuthenticated()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?url=login');
            exit;
        }
    }

    /**
     * Verify user owns a collection
     * Ownership check (authorization) after login (authentication).
     * @param int $collectionId Collection ID to check
     * @return bool True if user owns collection, false otherwise
     */
    private function verifyOwnership(int $collectionId): bool
    {
        $collection = $this->collectionModel->getById($collectionId);
        
        if (!$collection || $collection['u_id'] != $_SESSION['user']['u_id']) {
            return false;
        }
        
        return true;
    }

    /**
     * Display all collections for logged-in user
     * GET /collections: fetch user's collections and render grid view.
     */
    public function index()
    {
        $this->ensureAuthenticated();
        
        $userId = $_SESSION['user']['u_id'];
        $collections = $this->collectionModel->getByUser($userId);
        
        require_once __DIR__ . '/../views/collection/index.php';
    }

    /**
     * View a specific collection with all its monsters
     * GET /collection-view?id=X — uses $_GET['id'] query parameter.
     */
    public function view()
    {
        $this->ensureAuthenticated();
        
        $collectionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if (!$this->verifyOwnership($collectionId)) {
            $_SESSION['error'] = 'Collection not found or access denied.';
            header('Location: index.php?url=collections');
            exit;
        }
        
        $collection = $this->collectionModel->getById($collectionId);
        $monsters = $this->collectionModel->getMonsters($collectionId);
        
        // Get all user's collections for the add-to-collection dropdown in mini cards
        $userCollections = $this->collectionModel->getByUser($_SESSION['user']['u_id']);
        
        // Get like data for current user
        $userLikes = [];
        if (!empty($monsters)) {
            $monsterIds = array_column($monsters, 'monster_id');
            $likeModel = new \App\Models\MonsterLike();
            $userLikes = $likeModel->getUserLikes($_SESSION['user']['u_id'], $monsterIds);
        }
        
        // Add CSS for monster card mini template
        $extraStyles = ['/css/monster-card-mini.css'];
        
        require_once __DIR__ . '/../views/collection/view.php';
    }

    /**
     * Create a new collection.
     * GET: Show form; POST: Process form submission.
     */
    public function create()
    {
        $this->ensureAuthenticated();
        
        // GET request: Show create form
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            require_once __DIR__ . '/../views/collection/create.php';
            return;
        }
        
        // POST request: Process form submission
        $userId = $_SESSION['user']['u_id'];
        $collectionName = trim($_POST['collection_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        // Validate input
        if (empty($collectionName)) {
            $_SESSION['error'] = 'Collection name is required.';
            require_once __DIR__ . '/../views/collection/create.php';
            return;
        }
        
        if (strlen($collectionName) > 100) {
            $_SESSION['error'] = 'Collection name is too long (max 100 characters).';
            require_once __DIR__ . '/../views/collection/create.php';
            return;
        }
        
        // Attempt to create collection
        $collectionId = $this->collectionModel->create($userId, $collectionName, $description);
        
        if ($collectionId) {
            $_SESSION['success'] = 'Collection created successfully!';
            header('Location: index.php?url=collection-view&id=' . $collectionId);
            exit;
        } else {
            $_SESSION['error'] = 'Failed to create collection. Name might already exist.';
            require_once __DIR__ . '/../views/collection/create.php';
        }
    }

    /**
     * Edit an existing collection
     * 
     * GET  /collection-edit?id=X → Show edit form
     * POST /collection-edit → Process update
     */
    public function edit()
    {
        $this->ensureAuthenticated();
        
        $collectionId = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['collection_id']) ? (int)$_POST['collection_id'] : 0);
        
        if (!$this->verifyOwnership($collectionId)) {
            $_SESSION['error'] = 'Collection not found or access denied.';
            header('Location: index.php?url=collections');
            exit;
        }
        
        $collection = $this->collectionModel->getById($collectionId);
        
        // GET request: Show edit form
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            require_once __DIR__ . '/../views/collection/edit.php';
            return;
        }
        
        // POST request: Process update
        $collectionName = trim($_POST['collection_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        // Validate input
        if (empty($collectionName)) {
            $_SESSION['error'] = 'Collection name is required.';
            require_once __DIR__ . '/../views/collection/edit.php';
            return;
        }
        
        if (strlen($collectionName) > 100) {
            $_SESSION['error'] = 'Collection name is too long (max 100 characters).';
            require_once __DIR__ . '/../views/collection/edit.php';
            return;
        }
        
        // Attempt to update
        if ($this->collectionModel->update($collectionId, $collectionName, $description)) {
            $_SESSION['success'] = 'Collection updated successfully!';
            header('Location: index.php?url=collection-view&id=' . $collectionId);
            exit;
        } else {
            $_SESSION['error'] = 'Failed to update collection.';
            require_once __DIR__ . '/../views/collection/edit.php';
        }
    }

    /**
     * Delete a collection (POST only).
     * Prevents accidental deletes via GET requests; cannot delete default "To Print".
     */
    public function delete()
    {
        $this->ensureAuthenticated();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?url=collections');
            exit;
        }
        
        $collectionId = isset($_POST['collection_id']) ? (int)$_POST['collection_id'] : 0;
        
        if (!$this->verifyOwnership($collectionId)) {
            $_SESSION['error'] = 'Collection not found or access denied.';
            header('Location: index.php?url=collections');
            exit;
        }
        
        // Verify it's not the default collection
        $collection = $this->collectionModel->getById($collectionId);
        if ($collection['is_default'] == 1) {
            $_SESSION['error'] = 'Cannot delete the default "To Print" collection.';
            header('Location: index.php?url=collections');
            exit;
        }
        
        // Delete collection
        if ($this->collectionModel->delete($collectionId)) {
            $_SESSION['success'] = 'Collection deleted successfully.';
        } else {
            $_SESSION['error'] = 'Failed to delete collection.';
        }
        
        header('Location: index.php?url=collections');
        exit;
    }

    /**
     * Add a monster to a collection (AJAX endpoint).
     * Returns JSON; no page reload. Validates ownership before inserting.
     *
     * 2. JavaScript sends request in background (fetch())
     * 3. Server processes request
     * 4. Server returns JSON: { "success": true, "message": "Added!" }
     * 5. JavaScript updates page (shows success toast)
     * 6. NO PAGE RELOAD - much faster and smoother!
     * 
     * HOW THIS METHOD WORKS:
     * - Receives POST request with collection_id and monster_id
     * - Validates ownership and monster existence
     * - Adds monster to collection
     * - Returns JSON response (not HTML!)
     * 
     * RETURN FORMAT:
     * Success: { "success": true, "message": "Monster added to collection" }
     * Failure: { "success": false, "message": "Collection not found" }
     * 
     * See docs/AJAX_EXPLAINED.md for comprehensive AJAX tutorial!
     * 
     * POST /collection-add-monster
     * Body: collection_id=1&monster_id=42
     */
    public function addMonster()
    {
        // Tell browser we're sending JSON, not HTML
        // This is CRITICAL for AJAX - browser needs to know data format
        header('Content-Type: application/json');
        
        // Check if user is logged in
        // For AJAX, we return JSON error instead of redirecting
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }
        
        // Only accept POST requests
        // GET is for retrieving data, POST is for creating/modifying
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }
        
        // Get and validate input
        // isset() checks if variable exists in $_POST
        // (int) converts to integer (prevents SQL injection via type safety)
        // If not set, default to 0 (which will fail validation)
        $collectionId = isset($_POST['collection_id']) ? (int)$_POST['collection_id'] : 0;
        $monsterId = isset($_POST['monster_id']) ? (int)$_POST['monster_id'] : 0;
        
        // Verify user owns the collection they're trying to add to
        // Prevents: User A adding monsters to User B's collection
        if (!$this->verifyOwnership($collectionId)) {
            echo json_encode(['success' => false, 'message' => 'Collection not found or access denied']);
            exit;
        }
        
        // IMPROVED: Verify monster is PUBLIC or owned by user
        // This allows adding any public monster to collections
        // But still prevents adding private monsters owned by others
        $monster = $this->monsterModel->getById($monsterId);
        if (!$monster) {
            echo json_encode(['success' => false, 'message' => 'Monster not found']);
            exit;
        }
        
        // Check if monster is public OR owned by current user
        $userId = $_SESSION['user']['u_id'];
        $isOwner = ($monster['u_id'] == $userId);
        $isPublic = ($monster['is_public'] == 1);
        
        if (!$isPublic && !$isOwner) {
            echo json_encode(['success' => false, 'message' => 'Monster is private and belongs to another user']);
            exit;
        }
        
        // All checks passed - add monster to collection
        if ($this->collectionModel->addMonster($collectionId, $monsterId)) {
            // json_encode() converts PHP array to JSON string
            // JavaScript can then parse this and use the data
            echo json_encode(['success' => true, 'message' => 'Monster added to collection']);
        } else {
            // Failed - probably already in collection
            echo json_encode(['success' => false, 'message' => 'Monster already in collection']);
        }
        
        // exit prevents any view from loading (we only want JSON, not HTML)
        exit;
    }

    /**
     * Remove a monster from a collection (AJAX endpoint)
     * 
     * Similar to addMonster() but removes the association.
     * Also returns JSON for AJAX consumption.
     * 
     * POST /collection-remove-monster
     * Body: { collection_id: int, monster_id: int }
     * Returns: JSON { success: bool, message: string }
     */
    public function removeMonster()
    {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }
        
        $collectionId = isset($_POST['collection_id']) ? (int)$_POST['collection_id'] : 0;
        $monsterId = isset($_POST['monster_id']) ? (int)$_POST['monster_id'] : 0;
        
        if (!$this->verifyOwnership($collectionId)) {
            echo json_encode(['success' => false, 'message' => 'Collection not found or access denied']);
            exit;
        }
        
        if ($this->collectionModel->removeMonster($collectionId, $monsterId)) {
            echo json_encode(['success' => true, 'message' => 'Monster removed from collection']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove monster']);
        }
        exit;
    }

    /**
     * API METHOD: Get user's collections (JSON response)
     * 
     * MVC-compliant API endpoint that returns user's collections as JSON.
     * Called from /api/get-collections.php for AJAX dropdown population.
     * 
     * RETURNS: JSON with structure:
     * {
     *   "success": true,
     *   "collections": [
     *     {
     *       "collection_id": 1,
     *       "collection_name": "To Print",
     *       "description": "...",
     *       "is_default": 1,
     *       "monster_count": 5
     *     },
     *     ...
     *   ]
     * }
     * 
     * AUTHENTICATION: User must be logged in (verified by API file)
     * AUTHORIZATION: Returns only collections owned by authenticated user
     */
    public function getCollectionsApi()
    {
        // Set JSON response header
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $userId = $_SESSION['user']['u_id'];
            
            // Fetch all collections for the authenticated user
            // Model handles database query and returns array
            $collections = $this->collectionModel->getByUser($userId);
            
            // Return success response with collections data
            echo json_encode([
                'success' => true,
                'collections' => $collections
            ]);
            
        } catch (\Exception $e) {
            // Handle any unexpected errors gracefully
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error loading collections: ' . $e->getMessage()
            ]);
        }
        
        exit; // Prevent any view rendering
    }

    /**
     * API METHOD: Add monster to collection (JSON response)
     * 
     * MVC-compliant API endpoint for adding monsters to collections.
     * Called from /api/add-to-collection.php for AJAX operations.
     * 
     * INPUT: $input array with:
     * - monster_id (int): Monster to add
     * - collection_id (int): Collection to add to
     * 
     * RETURNS: JSON with structure:
     * {
     *   "success": true|false,
     *   "message": "Success/error message"
     * }
     * 
     * VALIDATION:
     * - User must own the monster
     * - User must own the collection
     * - Monster must exist
     * - Collection must exist
     * - Prevents duplicate entries
     */
    public function addMonsterApi(array $input)
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $monsterId = (int)$input['monster_id'];
            $collectionId = (int)$input['collection_id'];
            $userId = $_SESSION['user']['u_id'];
            
            // SECURITY CHECK 1: Verify monster exists
            $monster = $this->monsterModel->getById($monsterId);
            if (!$monster) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Monster not found.'
                ]);
                exit;
            }
            
            // IMPROVED: Check if monster is PUBLIC or owned by user
            // This allows adding any public monster to collections
            $isOwner = ($monster['u_id'] == $userId);
            $isPublic = ($monster['is_public'] == 1);
            
            if (!$isPublic && !$isOwner) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Monster is private and belongs to another user.'
                ]);
                exit;
            }
            
            // SECURITY CHECK 2: Verify collection exists and belongs to user
            if (!$this->verifyOwnership($collectionId)) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Collection not found or you do not have permission to access it.'
                ]);
                exit;
            }
            
            // Attempt to add monster to collection
            // Model handles duplicate prevention via UNIQUE constraint
            if ($this->collectionModel->addMonster($collectionId, $monsterId)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Monster added to collection successfully!'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Monster is already in this collection.'
                ]);
            }
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error adding monster to collection: ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    /**
     * API METHOD: Create collection and add monster (JSON response)
     * 
     * Combines two operations for better UX: creates a new collection
     * and immediately adds a monster to it in a single API call.
     * 
     * INPUT: $input array with:
     * - monster_id (int): Monster to add
     * - collection_name (string): Name for new collection
     * - description (string, optional): Collection description
     * 
     * RETURNS: JSON with structure:
     * {
     *   "success": true|false,
     *   "message": "Success/error message",
     *   "collection_id": 123 (on success only)
     * }
     * 
     * VALIDATION:
     * - Collection name cannot be empty
     * - Collection name max 100 characters
     * - User must own the monster
     * - Collection name must be unique for user
     * - Handles database errors gracefully
     */
    public function createCollectionAndAddMonsterApi(array $input)
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $monsterId = (int)$input['monster_id'];
            $collectionName = trim($input['collection_name']);
            $description = isset($input['description']) ? trim($input['description']) : null;
            $userId = $_SESSION['user']['u_id'];
            
            // VALIDATION 1: Collection name cannot be empty
            if (empty($collectionName)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Collection name cannot be empty.'
                ]);
                exit;
            }
            
            // VALIDATION 2: Collection name length limit
            if (strlen($collectionName) > 100) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Collection name must be 100 characters or less.'
                ]);
                exit;
            }
            
            // SECURITY CHECK: Verify monster exists and belongs to user
            $monster = $this->monsterModel->getById($monsterId);
            if (!$monster || $monster['u_id'] != $userId) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Monster not found or you do not have permission to access it.'
                ]);
                exit;
            }
            
            // STEP 1: Create new collection
            // Returns false if collection name already exists for this user
            $newCollectionId = $this->collectionModel->create($userId, $collectionName, $description);
            
            if ($newCollectionId === false) {
                http_response_code(409); // 409 Conflict
                echo json_encode([
                    'success' => false,
                    'message' => "Collection '{$collectionName}' already exists. Please choose a different name."
                ]);
                exit;
            }
            
            // STEP 2: Add monster to newly created collection
            $added = $this->collectionModel->addMonster($newCollectionId, $monsterId);
            
            if ($added) {
                // SUCCESS: Both operations completed
                echo json_encode([
                    'success' => true,
                    'message' => "Collection '{$collectionName}' created and '{$monster['name']}' added!",
                    'collection_id' => $newCollectionId
                ]);
            } else {
                // PARTIAL FAILURE: Collection created but monster not added
                // This is rare but can happen if database connection fails between operations
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Collection created but failed to add monster. Please try again.'
                ]);
            }
            
        } catch (\Exception $e) {
            // Handle unexpected errors
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error creating collection: ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    /**
     * Generate a shareable link for a collection
     * 
     * Creates a unique token that allows anyone with the link to view the collection
     * without authentication. This is useful for sharing collections on social media,
     * Discord, Reddit, etc.
     * 
     * Only the collection owner can generate a share link.
     * 
     * POST /collection-share
     * Body: { collection_id: int }
     * Returns: JSON { success: bool, token: string, share_url: string, message: string }
     */
    public function shareCollection(array $input)
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $collectionId = (int)$input['collection_id'] ?? null;
            
            if (!$collectionId) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Collection ID is required.'
                ]);
                exit;
            }
            
            // SECURITY: Verify user owns this collection
            if (!$this->verifyOwnership($collectionId)) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'You do not have permission to share this collection.'
                ]);
                exit;
            }
            
            // Generate new share token
            $token = $this->collectionModel->generateShareToken($collectionId);
            
            if ($token) {
                // Build shareable URL (adjust based on your domain)
                // Format: https://yourdomain.com/?url=collection-public&token=TOKEN
                $baseUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'];
                $shareUrl = $baseUrl . '/?url=collection-public&token=' . $token;
                
                echo json_encode([
                    'success' => true,
                    'token' => $token,
                    'share_url' => $shareUrl,
                    'message' => 'Share link generated successfully!'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to generate share token.'
                ]);
            }
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error generating share link: ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    /**
     * Revoke a collection's share link
     * 
     * Removes the share token, making the collection private again.
     * Only the owner can revoke the share.
     * 
     * POST /collection-unshare
     * Body: { collection_id: int }
     * Returns: JSON { success: bool, message: string }
     */
    public function unshareCollection(array $input)
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $collectionId = (int)$input['collection_id'] ?? null;
            
            if (!$collectionId) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Collection ID is required.'
                ]);
                exit;
            }
            
            // SECURITY: Verify user owns this collection
            if (!$this->verifyOwnership($collectionId)) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'You do not have permission to unshare this collection.'
                ]);
                exit;
            }
            
            // Revoke share token (sets to NULL in database)
            if ($this->collectionModel->revokeShareToken($collectionId)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Collection is no longer shared.'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to revoke share token.'
                ]);
            }
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error unsharing collection: ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    /**
     * View a shared collection via token (public, no authentication required)
     * 
     * Displays a read-only view of a collection if a valid share token is provided.
     * This allows anyone with the link to view the collection without logging in.
     * 
     * Query Parameter: ?token=SHARETOKEN
     * Returns: HTML view of collection with monsters
     */
    public function viewPublic()
    {
        // Get token from URL parameter
        $token = $_GET['token'] ?? null;
        
        if (!$token) {
            // No token provided, show error
            $error = 'Share token is required to view this collection.';
            require dirname(__DIR__) . '/views/pages/error-403.php';
            return;
        }
        
        try {
            // Retrieve collection by share token
            $collection = $this->collectionModel->getByShareToken($token);
            
            if (!$collection) {
                // Invalid or expired token
                http_response_code(404);
                $error = 'Collection not found. The share link may have expired.';
                require dirname(__DIR__) . '/views/pages/error-404.php';
                return;
            }
            
            // Get collection creator info
            $userModel = new \App\Models\User();
            $creator = $userModel->findById($collection['user_id']);
            
            // Retrieve monsters in this collection
            $monsters = $this->collectionModel->getMonsters($collection['id']);
            
        // Fetch user likes for persistence (if user is logged in)
        $userLikes = [];
        if (isset($_SESSION['user'])) {
            $userId = $_SESSION['user']['u_id'];
            $likeModel = new \App\Models\MonsterLike();
            $monsterIds = array_column($monsters, 'monster_id');
            if (!empty($monsterIds)) {
                $userLikes = $likeModel->getUserLikes($userId, $monsterIds);
            }
        }
        
            // Add CSS for monster card mini template
            $extraStyles = ['/css/monster-card-mini.css'];
            
            // Load public collection view
            // This is a read-only template that doesn't show edit/delete buttons
            require dirname(__DIR__) . '/views/collection/public-view.php';
            
        } catch (\Exception $e) {
            http_response_code(500);
            $error = 'Error loading shared collection: ' . $e->getMessage();
            require dirname(__DIR__) . '/views/pages/error-403.php';
        }
    }
}
