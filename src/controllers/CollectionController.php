<?php

namespace App\Controllers;

use App\Models\Collection;
use App\Models\Monster;

/**
 * CollectionController
 * Handles collection CRUD operations and monster organization.
 * 
 * ORGANIZATION:
 * 1. Constructor & Authentication
 * 2. CRUD Operations (Create, Read, Update, Delete)
 * 3. Collection Management (Add/Remove Monsters)
 * 4. API Endpoints (JSON responses)
 * 5. Public Sharing (Token-based access)
 * 6. Helper Methods (private)
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
     * Ensure user is authenticated.
     * Redirects to login if not logged in.
     */
    private function ensureAuthenticated()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?url=login');
            exit;
        }
    }

    /**
     * Verify user owns a collection (authorization check).
     * @param int $collectionId Collection ID to verify
     * @return bool True if user owns collection
     */
    private function verifyOwnership(int $collectionId): bool
    {
        $collection = $this->collectionModel->getById($collectionId);
        
        if (!$collection || $collection['u_id'] != $_SESSION['user']['u_id']) {
            return false;
        }
        
        return true;
    }

    // ===================================================================
    // SECTION 1: CRUD OPERATIONS
    // ===================================================================

    /**
     * LIST - Display all collections for logged-in user
     */
    public function index()
    {
        $this->ensureAuthenticated();
        
        $userId = $_SESSION['user']['u_id'];
        $collections = $this->collectionModel->getByUser($userId);
        
        require_once __DIR__ . '/../views/collection/index.php';
    }

    /**
     * READ - View a specific collection with all its monsters
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
        
        // Get all user's collections for dropdown
        $userCollections = $this->collectionModel->getByUser($_SESSION['user']['u_id']);
        
        // Get like data for current user
        $userLikes = [];
        if (!empty($monsters)) {
            $monsterIds = array_column($monsters, 'monster_id');
            $likeModel = new \App\Models\MonsterLike();
            $userLikes = $likeModel->getUserLikes($_SESSION['user']['u_id'], $monsterIds);
        }
        
        $extraStyles = ['/css/monster-card-mini.css'];
        
        require_once __DIR__ . '/../views/collection/view.php';
    }

    /**
     * CREATE - Display form and handle collection creation
     */
    public function create()
    {
        $this->ensureAuthenticated();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            require_once __DIR__ . '/../views/collection/create.php';
            return;
        }
        
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
        
        // Create collection
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
     * UPDATE - Display form and handle collection editing
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
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            require_once __DIR__ . '/../views/collection/edit.php';
            return;
        }
        
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
        
        // Update collection
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
     * DELETE - Remove collection (POST only, cannot delete default)
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
        
        // Prevent deletion of default collection
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

    // ===================================================================
    // SECTION 2: COLLECTION MANAGEMENT
    // ===================================================================

    /**
     * Add monster to collection (AJAX endpoint)
     * Returns JSON response for client-side updates
     */
    public function addMonster()
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
        
        // Verify monster is public or owned by user
        $monster = $this->monsterModel->getById($monsterId);
        if (!$monster) {
            echo json_encode(['success' => false, 'message' => 'Monster not found']);
            exit;
        }
        
        $userId = $_SESSION['user']['u_id'];
        $isOwner = ($monster['u_id'] == $userId);
        $isPublic = ($monster['is_public'] == 1);
        
        if (!$isPublic && !$isOwner) {
            echo json_encode(['success' => false, 'message' => 'Monster is private and belongs to another user']);
            exit;
        }
        
        // Add monster to collection
        if ($this->collectionModel->addMonster($collectionId, $monsterId)) {
            echo json_encode(['success' => true, 'message' => 'Monster added to collection']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Monster already in collection']);
        }
        exit;
    }

    /**
     * Remove monster from collection (AJAX endpoint)
     * Returns JSON response for client-side updates
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

    // ===================================================================
    // SECTION 3: API ENDPOINTS (JSON responses for AJAX)
    // ===================================================================

    /**
     * API - Get user's collections as JSON
     * Used by frontend for dropdown population
     */
    public function getCollectionsApi()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $userId = $_SESSION['user']['u_id'];
            $collections = $this->collectionModel->getByUser($userId);
            
            echo json_encode([
                'success' => true,
                'collections' => $collections
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error loading collections: ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    /**
     * API - Add monster to collection
     * MVC-compliant endpoint called from /api/add-to-collection.php
     */
    public function addMonsterApi(array $input)
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $monsterId = (int)$input['monster_id'];
            $collectionId = (int)$input['collection_id'];
            $userId = $_SESSION['user']['u_id'];
            
            // Verify monster exists
            $monster = $this->monsterModel->getById($monsterId);
            if (!$monster) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Monster not found.'
                ]);
                exit;
            }
            
            // Check if monster is public or owned by user
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
            
            // Verify collection ownership
            if (!$this->verifyOwnership($collectionId)) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Collection not found or you do not have permission to access it.'
                ]);
                exit;
            }
            
            // Add monster to collection
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
     * API - Create collection and add monster in single operation
     * Improves UX by combining two actions
     */
    public function createCollectionAndAddMonsterApi(array $input)
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $monsterId = (int)$input['monster_id'];
            $collectionName = trim($input['collection_name']);
            $description = isset($input['description']) ? trim($input['description']) : null;
            $userId = $_SESSION['user']['u_id'];
            
            // Validate collection name
            if (empty($collectionName)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Collection name cannot be empty.'
                ]);
                exit;
            }
            
            if (strlen($collectionName) > 100) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Collection name must be 100 characters or less.'
                ]);
                exit;
            }
            
            // Verify monster exists and belongs to user
            $monster = $this->monsterModel->getById($monsterId);
            if (!$monster || $monster['u_id'] != $userId) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Monster not found or you do not have permission to access it.'
                ]);
                exit;
            }
            
            // Create collection
            $newCollectionId = $this->collectionModel->create($userId, $collectionName, $description);
            
            if ($newCollectionId === false) {
                http_response_code(409);
                echo json_encode([
                    'success' => false,
                    'message' => "Collection '{$collectionName}' already exists. Please choose a different name."
                ]);
                exit;
            }
            
            // Add monster to new collection
            $added = $this->collectionModel->addMonster($newCollectionId, $monsterId);
            
            if ($added) {
                echo json_encode([
                    'success' => true,
                    'message' => "Collection '{$collectionName}' created and '{$monster['name']}' added!",
                    'collection_id' => $newCollectionId
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Collection created but failed to add monster. Please try again.'
                ]);
            }
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error creating collection: ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    // ===================================================================
    // SECTION 4: PUBLIC SHARING (Token-based access)
    // ===================================================================

    /**
     * Generate shareable link for collection
     * Creates unique token for public access without authentication
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
            
            if (!$this->verifyOwnership($collectionId)) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'You do not have permission to share this collection.'
                ]);
                exit;
            }
            
            // Generate share token
            $token = $this->collectionModel->generateShareToken($collectionId);
            
            if ($token) {
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
     * Revoke collection share link
     * Makes collection private again by removing token
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
            
            if (!$this->verifyOwnership($collectionId)) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'You do not have permission to unshare this collection.'
                ]);
                exit;
            }
            
            // Revoke share token
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
     * View shared collection via token (public access, no login required)
     * Displays read-only collection for anyone with valid token
     */
    public function viewPublic()
    {
        $token = $_GET['token'] ?? null;
        
        if (!$token) {
            $error = 'Share token is required to view this collection.';
            require dirname(__DIR__) . '/views/pages/error-403.php';
            return;
        }
        
        try {
            $collection = $this->collectionModel->getByShareToken($token);
            
            if (!$collection) {
                http_response_code(404);
                $error = 'Collection not found. The share link may have expired.';
                require dirname(__DIR__) . '/views/pages/error-404.php';
                return;
            }
            
            // Get collection creator info
            $userModel = new \App\Models\User();
            $creator = $userModel->findById($collection['user_id']);
            
            // Get monsters in collection
            $monsters = $this->collectionModel->getMonsters($collection['id']);
            
            // Get user likes if logged in
            $userLikes = [];
            if (isset($_SESSION['user'])) {
                $userId = $_SESSION['user']['u_id'];
                $likeModel = new \App\Models\MonsterLike();
                $monsterIds = array_column($monsters, 'monster_id');
                if (!empty($monsterIds)) {
                    $userLikes = $likeModel->getUserLikes($userId, $monsterIds);
                }
            }
            
            $extraStyles = ['/css/monster-card-mini.css'];
            
            require dirname(__DIR__) . '/views/collection/public-view.php';
            
        } catch (\Exception $e) {
            http_response_code(500);
            $error = 'Error loading shared collection: ' . $e->getMessage();
            require dirname(__DIR__) . '/views/pages/error-403.php';
        }
    }
}
