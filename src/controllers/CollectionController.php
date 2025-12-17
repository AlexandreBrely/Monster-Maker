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
     */
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
        
        // Verify monster exists in database
        // Prevents: Adding non-existent monster (monster_id=999999)
        $monster = $this->monsterModel->getById($monsterId);
        if (!$monster) {
            echo json_encode(['success' => false, 'message' => 'Monster not found']);
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
}
