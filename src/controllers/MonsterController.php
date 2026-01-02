<?php

namespace App\Controllers;

use App\Models\Monster;
use App\Models\Collection;
use App\Services\FileUploadService;
use App\Models\LairCard;
use App\Models\MonsterLike;

/**
 * MonsterController
 * Handles monster CRUD operations and image uploads.
 */
class MonsterController
{
    private $monsterModel;
    private $likeModel;
    private $collectionModel;
    private $fileUploadService;

    public function __construct()
    {
        // Instantiate the Monster model (data access layer)
        // Controllers should delegate data operations to models
        $this->monsterModel = new Monster();
        $this->likeModel = new MonsterLike();
        $this->collectionModel = new Collection();
        // Instantiate file upload service (handles all file uploads)
        $this->fileUploadService = new FileUploadService();
    }

    // Fast guard: redirect visitors who are not logged in
    /**
     * Ensure a user is logged in before allowing access to protected routes.
     * If not authenticated, redirects to login.
     */
    private function ensureAuthenticated()
    {
        if (!isset($_SESSION['user']) || !isset($_SESSION['user']['u_id'])) {
            header('Location: index.php?url=login');
            exit;
        }
    }

    // Display monster creation form
    // Main create logic (called by createBoss and createSmall)
    /**
     * Display create form (boss or small) and handle submission.
     * Validates input, uploads images, delegates creation to the model.
     * Uses guard clauses to early-return on validation/add-button cases.
     */
    public function createForm($type = null)
    {
        $this->ensureAuthenticated();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->getFormData();
            
            // Check if user clicked an "add" button instead of submitting
            if (!empty($_POST['add_action']) || !empty($_POST['add_bonus_action']) || 
                !empty($_POST['add_reaction']) || !empty($_POST['add_legendary_action']) ||
                !empty($_POST['add_trait'])) {
                // Just redisplay the form with the current data preserved
                extract(['old' => $data]);
                $viewFile = ($type === 'boss') ? 'create.php' : 'create_small.php';
                require_once __DIR__ . '/../views/monster/' . $viewFile;
                return;
            }
            
            $errors = $this->monsterModel->validate($data);
            $images = [];

            // Handle images when provided
            if (isset($_FILES['image_portrait']) && !empty($_FILES['image_portrait']['name'])) {
                $imageResult = $this->uploadImage($_FILES['image_portrait'], 'monsters');
                if ($imageResult['success']) {
                    // Use database column key name for consistency with Model::create
                    $images['image_portrait'] = $imageResult['filename'];
                } else {
                    $errors['image_portrait'] = $imageResult['error'];
                }
            }

            if (!empty($_FILES['image_fullbody']['name'])) {
                $imageResult = $this->uploadImage($_FILES['image_fullbody'], 'monsters');
                if ($imageResult['success']) {
                    // Use database column key name for consistency with Model::create
                    $images['image_fullbody'] = $imageResult['filename'];
                } else {
                    $errors['image_fullbody'] = $imageResult['error'];
                }
            }

            // If there are errors, re-render the form
            if (!empty($errors)) {
                extract(['errors' => $errors, 'old' => $data]);
                $viewFile = ($type === 'boss') ? 'create.php' : 'create_small.php';
                require_once __DIR__ . '/../views/monster/' . $viewFile;
                return;
            }

            // Create monster record
            $userId = $_SESSION['user']['u_id'];
            $data = array_merge($data, $images);
            $monsterId = $this->monsterModel->create($data, $userId);

            if ($monsterId) {
                header('Location: index.php?url=monster&id=' . $monsterId);
                exit;
            } else {
                // If creation failed, show a general error message
                $errors = ['general' => 'Failed to create monster. Please check your data and try again.'];
                extract(['errors' => $errors, 'old' => $data]);
                $viewFile = ($type === 'boss') ? 'create.php' : 'create_small.php';
                require_once __DIR__ . '/../views/monster/' . $viewFile;
                return;
            }
        }

        // Display appropriate form based on type
        $viewFile = ($type === 'boss') ? 'create.php' : 'create_small.php';
        require_once __DIR__ . '/../views/monster/' . $viewFile;
    }

    public function create()
    {
        // Legacy method - show selection page
        $this->selectCreate();
    }

    // Display details for a specific monster
    /**
     * Show a single monster (small statblock view).
     * Handles authorization and prepares derived view data (abilities grid, etc.).
     */
    public function show($id)
    {
        $monster = $this->monsterModel->getById($id);

        if (!$monster) {
            require_once __DIR__ . '/../views/pages/error-404.php';
            return;
        }

        // Check access: public monster or owned by current user
        $userId = isset($_SESSION['user']) ? $_SESSION['user']['u_id'] : null;
        if (!$monster['is_public'] && $monster['u_id'] != $userId) {
            require_once __DIR__ . '/../views/pages/error-403.php';
            return;
        }

        // Prepare view variables using helper methods
        $abilitiesGrid = $this->prepareAbilityGrid($monster);
        $parsedFields = $this->parseMonsterFields($monster);
        $skills = $parsedFields['skills'];
        $senses = $parsedFields['senses'];

        // Ensure JSON arrays are properly populated
        $traits = $monster['traits'] ?? [];
        $actions = $monster['actions'] ?? [];
        $bonusActions = $monster['bonus_actions'] ?? [];
        $reactions = $monster['reactions'] ?? [];
        $legendaryActions = $monster['legendary_actions'] ?? [];
        $monsterXp = $this->getXpForCR((string) ($monster['challenge_rating'] ?? ''));

        // Route to correct view based on card size
        // card_size: 1 = Boss (A6 horizontal), 2 = Small (playing card)
        // Fallback: use is_legendary if card_size is not set
        $isBoss = false;
        if (isset($monster['card_size'])) {
            $isBoss = ((int)$monster['card_size'] === 1);
        } elseif (isset($monster['is_legendary'])) {
            $isBoss = ((int)$monster['is_legendary'] === 1);
        }

        if ($isBoss) {
            // Boss monster: A6 horizontal two-column layout
            require_once __DIR__ . '/../views/monster/boss-card.php';
        } else {
            // Small monster: Playing card vertical layout
            require_once __DIR__ . '/../views/monster/small-statblock.php';
        }
    }

    // Route dispatcher for monster actions
    public function handleMonsterRoute()
    {
        $id = $_GET['id'] ?? null;
        $action = $_GET['action'] ?? 'show';

        if (!$id) {
            require_once __DIR__ . '/../views/pages/error-404.php';
            return;
        }

        switch ($action) {
            case 'show':
                $this->show($id);
                break;
            case 'edit':
                $this->edit($id);
                break;
            case 'update':
                $this->update($id);
                break;
            case 'delete':
                $this->delete($id);
                break;
            default:
                $this->show($id);
        }
    }

    // Display all monsters, lairs, or users with filters
    public function index()
    {
        // Get search type (monster, lair, or user)
        $searchType = $_GET['search_type'] ?? 'monster';
        $validSearchTypes = ['monster', 'lair', 'user'];
        if (!in_array($searchType, $validSearchTypes)) {
            $searchType = 'monster';
        }
        
        // Get filter parameters from URL
        $orderBy = $_GET['order'] ?? 'newest';
        $search = $_GET['search'] ?? null;
        $size = $_GET['size'] ?? null;
        $type = $_GET['type'] ?? null;
        $filterUser = isset($_GET['user']) && $_GET['user'] !== '' ? (int)$_GET['user'] : null;
        
        // Initialize results
        $monsters = [];
        $lairCards = [];
        $users = [];
        
        if ($searchType === 'monster') {
            // Validate order parameter
            $validOrders = ['random', 'newest', 'oldest', 'most_liked'];
            if (!in_array($orderBy, $validOrders)) {
                $orderBy = 'newest';
            }
            
            // Get filtered monsters
            $monsters = $this->monsterModel->getAllFiltered($orderBy, $filterUser, $search, $size, $type);
            
            // Get like data if user is logged in
            $userLikes = [];
            if (isset($_SESSION['user'])) {
                $monsterIds = array_column($monsters, 'monster_id');
                if (!empty($monsterIds)) {
                    $userLikes = $this->likeModel->getUserLikes($_SESSION['user']['u_id'], $monsterIds);
                }
            }
        } elseif ($searchType === 'lair') {
            // Validate order parameter
            $validOrders = ['random', 'newest', 'oldest', 'most_liked'];
            if (!in_array($orderBy, $validOrders)) {
                $orderBy = 'newest';
            }
            
            // Get filtered lair cards
            $lairModel = new LairCard();
            $lairCards = $lairModel->getAllFiltered($orderBy, $search);
        } elseif ($searchType === 'user') {
            // Search users by username
            $userModel = new \App\Models\User();
            $users = $userModel->searchByUsername($search);
        }
        
        // Get user's collections for "Add to Collection" dropdown
        $userCollections = [];
        if (isset($_SESSION['user'])) {
            $userCollections = $this->collectionModel->getByUser($_SESSION['user']['u_id']);
        }
        
        require_once __DIR__ . '/../views/monster/index.php';
    }
    
    /**
     * Toggle Like - AJAX Endpoint for Like/Unlike Monster
     * =====================================================
     * 
     * WHAT IS AN AJAX ENDPOINT?
     * Unlike regular PHP pages that return HTML, AJAX endpoints return JSON data.
     * JavaScript fetch() calls this endpoint, gets JSON response, updates page without reload.
     * 
     * HOW IT WORKS (Server-Side):
     * 
     * 1. RECEIVE REQUEST: JavaScript sends GET request with monster ID
     *    URL: index.php?url=monster-like&id=123
     * 
     * 2. AUTHENTICATE: Check if user is logged in via $_SESSION
     *    - If not logged in: Return error JSON
     * 
     * 3. VALIDATE: Check monster exists and is accessible
     *    - Monster must exist in database
     *    - Monster must be public OR owned by current user
     *    - If invalid: Return error JSON
     * 
     * 4. TOGGLE LIKE: Add or remove like from database
     *    - MonsterLike->toggleLike() checks if already liked
     *    - If already liked: DELETE record, return "removed"
     *    - If not liked: INSERT record, return "added"
     * 
     * 5. COUNT LIKES: Get updated total like count
     *    - MonsterLike->countLikes() runs COUNT(*) query
     *    - Returns integer (0 or more)
     * 
     * 6. SEND RESPONSE: Return JSON with results
     *    - { success: true, action: "added", count: 5, liked: true }
     *    - JavaScript receives this and updates UI
     * 
     * RELATED CODE:
     * - Frontend: public/js/monster-actions.js -> toggleLike()
     * - Model: src/models/MonsterLike.php
     * - Template: src/views/templates/monster-card-mini.php (like button)
     */
    public function toggleLike()
    {
        // STEP 1: Set response type to JSON
        // Tells browser to expect JSON, not HTML
        header('Content-Type: application/json');
        
        // STEP 2: Check authentication
        // User must be logged in to like monsters
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            return;
        }
        
        // STEP 3: Get and validate monster ID from URL parameter
        $monsterId = $_GET['id'] ?? null;
        if (!$monsterId) {
            echo json_encode(['success' => false, 'error' => 'Monster ID required']);
            return;
        }
        
        // Cast to integer for security (prevents SQL injection)
        $userId = $_SESSION['user']['u_id'];
        $monsterId = (int)$monsterId;
        
        // STEP 4: Verify monster exists and is accessible
        $monster = $this->monsterModel->getById($monsterId);
        if (!$monster) {
            echo json_encode(['success' => false, 'error' => 'Monster not found']);
            return;
        }
        
        // STEP 5: Check permissions - monster must be public OR owned by user
        if (!$monster['is_public'] && $monster['u_id'] != $userId) {
            echo json_encode(['success' => false, 'error' => 'Cannot like private monster']);
            return;
        }
        
        // STEP 6: Toggle the like (add if not liked, remove if already liked)
        // Returns "added" or "removed" string
        $action = $this->likeModel->toggleLike($userId, $monsterId);
        
        // STEP 7: Get updated like count after toggle
        $newCount = $this->likeModel->countLikes($monsterId);
        
        // STEP 8: Build response object
        $response = [
            'success' => true,
            'action' => $action,              // "added" or "removed"
            'count' => $newCount,             // Total like count (integer)
            'liked' => ($action === 'added')  // Boolean for UI
        ];
        
        // STEP 9: Send JSON response to JavaScript
        echo json_encode($response);
        exit;
    }

    // Display edit form
    public function edit($id)
    {
        $this->ensureAuthenticated();
        $monster = $this->monsterModel->getById($id);

        if (!$monster) {
            require_once __DIR__ . '/../views/pages/error-404.php';
            return;
        }

        // Verify ownership
        $userId = $_SESSION['user']['u_id'];
        if ($monster['u_id'] != $userId) {
            require_once __DIR__ . '/../views/pages/error-403.php';
            return;
        }

        require_once __DIR__ . '/../views/monster/edit.php';
    }

    // Handle monster update
    public function update($id)
    {
        $this->ensureAuthenticated();

        $monster = $this->monsterModel->getById($id);
        if (!$monster) {
            require_once __DIR__ . '/../views/pages/error-404.php';
            return;
        }

        // Verify ownership
        $userId = $_SESSION['user']['u_id'];
        if ($monster['u_id'] != $userId) {
            require_once __DIR__ . '/../views/pages/error-403.php';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->getFormData();
            $errors = $this->monsterModel->validate($data);
            $images = [];

            // Keep current filenames so we can clean up replaced files after a successful save
            // Process images
            if (isset($_FILES['image_portrait']) && !empty($_FILES['image_portrait']['name'])) {
                $imageResult = $this->uploadImage($_FILES['image_portrait'], 'monsters');
                if ($imageResult['success']) {
                    $images['image_portrait'] = $imageResult['filename'];
                } else {
                    $errors['image_portrait'] = $imageResult['error'];
                }
            }

            if (!empty($_FILES['image_fullbody']['name'])) {
                $imageResult = $this->uploadImage($_FILES['image_fullbody'], 'monsters');
                if ($imageResult['success']) {
                    $images['image_fullbody'] = $imageResult['filename'];
                } else {
                    $errors['image_fullbody'] = $imageResult['error'];
                }
            }

            // If errors
            if (!empty($errors)) {
                extract(['errors' => $errors, 'old' => $data, 'monster' => $monster]);
                require_once __DIR__ . '/../views/monster/edit.php';
                return;
            }

            // Update record
            $data = array_merge($data, $images);
            $updated = $this->monsterModel->update($id, $data, $userId);

            // If update succeeded and a new image was uploaded, delete old files to avoid orphaned uploads
            if ($updated) {
                $uploadPath = __DIR__ . '/../../public/uploads/monsters/';
                if (!empty($images['image_portrait']) && !empty($oldPortrait) && $oldPortrait !== $images['image_portrait']) {
                    @unlink($uploadPath . $oldPortrait);
                }
                if (!empty($images['image_fullbody']) && !empty($oldFullbody) && $oldFullbody !== $images['image_fullbody']) {
                    @unlink($uploadPath . $oldFullbody);
                }
            }

            header('Location: index.php?url=monster&id=' . $id);
            exit;
        }

        require_once __DIR__ . '/../views/monster/edit.php';
    }

    // Supprime un monstre
    public function delete($id)
    {
        $this->ensureAuthenticated();

        $monster = $this->monsterModel->getById($id);
        if (!$monster) {
            require_once __DIR__ . '/../views/pages/error-404.php';
            return;
        }

        // Verify ownership
        $userId = $_SESSION['user']['u_id'];
        if ($monster['u_id'] != $userId) {
            require_once __DIR__ . '/../views/pages/error-403.php';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->monsterModel->delete($id, $userId);
            header('Location: index.php?url=monsters');
            exit;
        }

        // Prepare view data using helper methods
        $abilitiesGrid = $this->prepareAbilityGrid($monster);
        $parsedFields = $this->parseMonsterFields($monster);
        $skills = $parsedFields['skills'];
        $senses = $parsedFields['senses'];

        // Ensure JSON arrays are properly populated
        $traits = $monster['traits'] ?? [];
        $actions = $monster['actions'] ?? [];
        $bonusActions = $monster['bonus_actions'] ?? [];
        $reactions = $monster['reactions'] ?? [];
        $legendaryActions = $monster['legendary_actions'] ?? [];

        require_once __DIR__ . '/../views/monster/show.php';
    }

    // Show monsters for the logged-in user
    public function myMonsters()
    {
        $this->ensureAuthenticated();
        $userId = $_SESSION['user']['u_id'];
        $monsters = $this->monsterModel->getByUser($userId);
        
        // Get like data for current user
        $userLikes = [];
        if (!empty($monsters)) {
            $monsterIds = array_column($monsters, 'monster_id');
            $userLikes = $this->likeModel->getUserLikes($userId, $monsterIds);
        }
        
        require_once __DIR__ . '/../views/monster/my-monsters.php';
    }

    // ===== HELPER METHODS =====
    
    /**
     * Prepare ability grid data for monster display.
     * Calculates modifiers and maps saving throw bonuses.
     * 
     * @param array $monster Monster data with ability scores
     * @return array Ability grid with labels, modifiers, and save bonuses
     */
    private function prepareAbilityGrid($monster): array
    {
        $abilityLabels = [
            'strength' => 'STR',
            'dexterity' => 'DEX',
            'constitution' => 'CON',
            'intelligence' => 'INT',
            'wisdom' => 'WIS',
            'charisma' => 'CHA'
        ];

        // Build saving throws map
        $savesMap = [];
        if (!empty($monster['saving_throws']) && is_string($monster['saving_throws'])) {
            $throwPairs = explode(',', $monster['saving_throws']);
            foreach ($throwPairs as $pair) {
                $pair = trim($pair);
                // Handle both "STR +5" and "STR:+5" formats
                if (strpos($pair, ':') !== false) {
                    // Colon format: "STR:+5"
                    $parts = explode(':', $pair);
                } else {
                    // Space format: "STR +5"
                    $parts = preg_split('/\s+/', $pair, 2);
                }
                
                if (count($parts) === 2) {
                    $ability = strtolower(trim($parts[0]));
                    $bonus = trim($parts[1]);
                    $savesMap[$ability] = $bonus;
                }
            }
        }

        // Build abilities grid with modifiers
        $abilitiesGrid = [];
        foreach ($abilityLabels as $key => $label) {
            $score = isset($monster[$key]) ? (int)$monster[$key] : 10;
            $mod = $this->calculateModifier($score);
            $abilityShort = strtolower($label);
            $saveBonus = $savesMap[$abilityShort] ?? '';
            $abilitiesGrid[] = [
                'label' => $label,
                'mod_display' => $this->formatModifier($mod),
                'save_bonus' => $saveBonus,
            ];
        }

        return $abilitiesGrid;
    }

    /**
     * Parse comma-separated fields into arrays.
     * 
     * @param array $monster Monster data
     * @return array Parsed skills and senses arrays
     */
    private function parseMonsterFields($monster): array
    {
        // Build skills array
        $skills = [];
        if (!empty($monster['skills']) && is_string($monster['skills'])) {
            $skillPairs = explode(',', $monster['skills']);
            foreach ($skillPairs as $pair) {
                $pair = trim($pair);
                if (!empty($pair)) {
                    $skills[] = $pair;
                }
            }
        }

        // Build senses array
        $senses = [];
        if (!empty($monster['senses']) && is_string($monster['senses'])) {
            $senseParts = explode(',', $monster['senses']);
            foreach ($senseParts as $sense) {
                $sense = trim($sense);
                if (!empty($sense)) {
                    $senses[] = $sense;
                }
            }
        }

        return compact('skills', 'senses');
    }

    /**
     * Map Challenge Rating to XP (5e standard)
     */
    private function getXpForCR(string $cr): ?int
    {
        $xpByCR = [
            '0' => 10,
            '1/8' => 25,
            '1/4' => 50,
            '1/2' => 100,
            '1' => 200,
            '2' => 450,
            '3' => 700,
            '4' => 1100,
            '5' => 1800,
            '6' => 2300,
            '7' => 2900,
            '8' => 3900,
            '9' => 5000,
            '10' => 5900,
            '11' => 7200,
            '12' => 8400,
            '13' => 10000,
            '14' => 11500,
            '15' => 13000,
            '16' => 15000,
            '17' => 18000,
            '18' => 20000,
            '19' => 22000,
            '20' => 25000,
            '21' => 33000,
            '22' => 41000,
            '23' => 50000,
            '24' => 62000,
            '25' => 75000,
            '26' => 90000,
            '27' => 105000,
            '28' => 120000,
            '29' => 135000,
            '30' => 155000,
        ];
        return $xpByCR[$cr] ?? null;
    }

    // Extract and normalize form data
    private function getFormData(): array
    {
        return [
            'card_size' => (int) ($_POST['card_size'] ?? 2),
            'name' => trim($_POST['name'] ?? ''),
            'size' => $_POST['size'] ?? '',
            'type' => trim($_POST['type'] ?? ''),
            'alignment' => trim($_POST['alignment'] ?? ''),
            'ac' => (int) ($_POST['ac'] ?? 10),
            'ac_notes' => trim($_POST['ac_notes'] ?? ''),
            'hp' => (int) ($_POST['hp'] ?? 1),
            'hit_dice' => trim($_POST['hit_dice'] ?? ''),
            'speed' => trim($_POST['speed'] ?? ''),
            'initiative' => (int) ($_POST['initiative'] ?? 0),
            'proficiency_bonus' => $this->parseProficiencyBonus($_POST['proficiency_bonus'] ?? '0'),
            'strength' => (int) ($_POST['strength'] ?? 10),
            'dexterity' => (int) ($_POST['dexterity'] ?? 10),
            'constitution' => (int) ($_POST['constitution'] ?? 10),
            'intelligence' => (int) ($_POST['intelligence'] ?? 10),
            'wisdom' => (int) ($_POST['wisdom'] ?? 10),
            'charisma' => (int) ($_POST['charisma'] ?? 10),
            'saving_throws' => $this->buildSavingThrows(),
            'skills' => trim($_POST['skills'] ?? ''),
            'senses' => trim($_POST['senses'] ?? ''),
            'languages' => trim($_POST['languages'] ?? ''),
            'challenge_rating' => trim($_POST['challenge_rating'] ?? '0'),
            'damage_immunities' => trim($_POST['damage_immunities'] ?? ''),
            'condition_immunities' => trim($_POST['condition_immunities'] ?? ''),
            'damage_resistances' => trim($_POST['damage_resistances'] ?? ''),
            'damage_vulnerabilities' => trim($_POST['damage_vulnerabilities'] ?? ''),
            'traits' => $this->buildTraits(),
            'actions' => $this->buildActions(),
            'bonus_actions' => $this->buildBonusActions(),
            'reactions' => $this->buildReactions(),
            'legendary_actions' => $this->buildLegendaryActions(),
            'is_legendary' => (int) ($_POST['is_legendary'] ?? 0),
            'legendary_resistance' => trim($_POST['legendary_resistance'] ?? ''),
            'legendary_resistance_lair' => trim($_POST['legendary_resistance_lair'] ?? ''),
            'lair_actions' => trim($_POST['lair_actions'] ?? ''),
            'is_public' => (int) ($_POST['is_public'] ?? 0)
        ];
    }

    // Parse proficiency bonus and handle +X format
    private function parseProficiencyBonus($value): int
    {
        // Remove + prefix if present
        $value = trim($value);
        if (strpos($value, '+') === 0) {
            $value = substr($value, 1);
        }
        return (int) $value;
    }

    // Build saving throws string from proficiency checkboxes
    private function buildSavingThrows(): string
    {
        $proficiencies = $_POST['save_proficiencies'] ?? [];
        if (empty($proficiencies)) {
            return '';
        }

        $profBonus = $this->parseProficiencyBonus($_POST['proficiency_bonus'] ?? 0);
        $abilities = [
            'str' => (int) ($_POST['strength'] ?? 10),
            'dex' => (int) ($_POST['dexterity'] ?? 10),
            'con' => (int) ($_POST['constitution'] ?? 10),
            'int' => (int) ($_POST['intelligence'] ?? 10),
            'wis' => (int) ($_POST['wisdom'] ?? 10),
            'cha' => (int) ($_POST['charisma'] ?? 10)
        ];

        $saves = [];
        foreach ($proficiencies as $ability) {
            $score = $abilities[$ability] ?? 10;
            $modifier = floor(($score - 10) / 2);
            $saveBonus = $modifier + $profBonus;
            $formattedBonus = $saveBonus >= 0 ? '+' . $saveBonus : (string) $saveBonus;
            $saves[] = strtoupper($ability) . ' ' . $formattedBonus;
        }

        return implode(', ', $saves);
    }

    // Build traits array from name and description fields
    private function buildTraits(): array
    {
        $traits = [];
        $names = $_POST['trait_names'] ?? [];
        $descriptions = $_POST['traits'] ?? [];

        foreach ($names as $index => $name) {
            $name = trim($name ?? '');
            $description = trim($descriptions[$index] ?? '');
            
            if (!empty($name) || !empty($description)) {
                $traits[] = [
                    'name' => $name,
                    'description' => $description
                ];
            }
        }
        return $traits;
    }

    // Build actions array from name and description fields
    private function buildActions(): array
    {
        $actions = [];
        $names = $_POST['action_names'] ?? [];
        $descriptions = $_POST['actions'] ?? [];

        foreach ($names as $index => $name) {
            $name = trim($name ?? '');
            $description = trim($descriptions[$index] ?? '');
            
            if (!empty($name) || !empty($description)) {
                $actions[] = [
                    'name' => $name,
                    'description' => $description
                ];
            }
        }
        return $actions;
    }

    // Build bonus actions array
    private function buildBonusActions(): array
    {
        $actions = [];
        $names = $_POST['bonus_action_names'] ?? [];
        $descriptions = $_POST['bonus_actions'] ?? [];

        foreach ($names as $index => $name) {
            $name = trim($name ?? '');
            $description = trim($descriptions[$index] ?? '');
            
            if (!empty($name) || !empty($description)) {
                $actions[] = [
                    'name' => $name,
                    'description' => $description
                ];
            }
        }
        return $actions;
    }

    // Build reactions array
    private function buildReactions(): array
    {
        $actions = [];
        $names = $_POST['reaction_names'] ?? [];
        $descriptions = $_POST['reactions'] ?? [];

        foreach ($names as $index => $name) {
            $name = trim($name ?? '');
            $description = trim($descriptions[$index] ?? '');
            
            if (!empty($name) || !empty($description)) {
                $actions[] = [
                    'name' => $name,
                    'description' => $description
                ];
            }
        }
        return $actions;
    }

    // Build legendary actions array
    private function buildLegendaryActions(): array
    {
        $actions = [];
        $names = $_POST['legendary_action_names'] ?? [];
        $descriptions = $_POST['legendary_actions'] ?? [];

        foreach ($names as $index => $name) {
            $name = trim($name ?? '');
            $description = trim($descriptions[$index] ?? '');
            
            if (!empty($name) || !empty($description)) {
                // Parse cost from name if it contains "(Costs X Actions)"
                $cost = 1; // Default cost
                if (preg_match('/\(Costs?\s+(\d+)\s+Actions?\)/i', $name, $matches)) {
                    $cost = (int)$matches[1];
                }
                
                $actions[] = [
                    'name' => $name,
                    'cost' => $cost,
                    'description' => $description
                ];
            }
        }
        return $actions;
    }

    // Parse actions from the form - OLD METHOD (no longer used)
    private function parseActions(): array
    {
        $actions = [];
        $actionNames = $_POST['action_name'] ?? [];

        foreach ($actionNames as $index => $name) {
            if (empty(trim($name))) continue;

            $type = $_POST['action_type'][$index] ?? 'description';
            $action = ['name' => trim($name), 'type' => $type];

            switch ($type) {
                case 'description':
                    $action['description'] = $_POST['action_description'][$index] ?? '';
                    break;

                case 'melee':
                    $action['weapon'] = $_POST['melee_weapon'][$index] ?? '';
                    $action['to_hit'] = $_POST['melee_to_hit'][$index] ?? '';
                    $action['reach'] = $_POST['melee_reach'][$index] ?? '';
                    $action['damage_dice'] = $_POST['melee_damage_dice'][$index] ?? '';
                    $action['damage_type'] = $_POST['melee_damage_type'][$index] ?? '';
                    if (!empty($_POST['melee_extra_damage'][$index])) {
                        $action['extra_damage_dice'] = $_POST['melee_extra_dice'][$index] ?? '';
                        $action['extra_damage_type'] = $_POST['melee_extra_type'][$index] ?? '';
                    }
                    $action['description'] = $_POST['melee_description'][$index] ?? '';
                    break;

                case 'ranged':
                    $action['weapon'] = $_POST['ranged_weapon'][$index] ?? '';
                    $action['to_hit'] = $_POST['ranged_to_hit'][$index] ?? '';
                    $action['range'] = $_POST['ranged_range'][$index] ?? '';
                    $action['damage_dice'] = $_POST['ranged_damage_dice'][$index] ?? '';
                    $action['damage_type'] = $_POST['ranged_damage_type'][$index] ?? '';
                    if (!empty($_POST['ranged_extra_damage'][$index])) {
                        $action['extra_damage_dice'] = $_POST['ranged_extra_dice'][$index] ?? '';
                        $action['extra_damage_type'] = $_POST['ranged_extra_type'][$index] ?? '';
                    }
                    $action['description'] = $_POST['ranged_description'][$index] ?? '';
                    break;

                case 'special':
                    $action['special_name'] = $_POST['special_name'][$index] ?? '';
                    $action['recharge'] = $_POST['special_recharge'][$index] ?? '';
                    $action['save'] = $_POST['special_save'][$index] ?? '';
                    $action['description'] = $_POST['special_description'][$index] ?? '';
                    break;

                case 'spellcasting':
                    $action['ability'] = $_POST['spell_ability'][$index] ?? '';
                    $action['dc'] = $_POST['spell_dc'][$index] ?? '';
                    $action['format'] = $_POST['spell_format'][$index] ?? 'daily';

                    if ($action['format'] === 'daily') {
                        $action['at_will'] = $_POST['spell_at_will'][$index] ?? '';
                        $action['1_day'] = $_POST['spell_1_day'][$index] ?? '';
                        $action['2_day'] = $_POST['spell_2_day'][$index] ?? '';
                        $action['3_day'] = $_POST['spell_3_day'][$index] ?? '';
                    } else {
                        $action['cantrips'] = $_POST['spell_cantrips'][$index] ?? '';
                        $action['1st'] = $_POST['spell_1st'][$index] ?? '';
                        $action['2nd'] = $_POST['spell_2nd'][$index] ?? '';
                        $action['3rd'] = $_POST['spell_3rd'][$index] ?? '';
                        $action['4th'] = $_POST['spell_4th'][$index] ?? '';
                        $action['5th'] = $_POST['spell_5th'][$index] ?? '';
                        $action['6th'] = $_POST['spell_6th'][$index] ?? '';
                        $action['7th'] = $_POST['spell_7th'][$index] ?? '';
                        $action['8th'] = $_POST['spell_8th'][$index] ?? '';
                        $action['9th'] = $_POST['spell_9th'][$index] ?? '';
                    }

                    $action['description'] = $_POST['spell_description'][$index] ?? '';
                    break;
            }

            $actions[] = $action;
        }

        return $actions;
    }

    // Parse reactions from the form
    private function parseReactions(): array
    {
        $reactions = [];
        $reactionNames = $_POST['reaction_name'] ?? [];

        foreach ($reactionNames as $index => $name) {
            if (empty(trim($name))) continue;

            $reactions[] = [
                'name' => trim($name),
                'trigger' => trim($_POST['reaction_trigger'][$index] ?? ''),
                'description' => trim($_POST['reaction_description'][$index] ?? '')
            ];
        }

        return $reactions;
    }

    // Parse legendary actions from the form
    private function parseLegendaryActions(): array
    {
        $actions = [];
        $actionNames = $_POST['leg_action_name'] ?? [];

        foreach ($actionNames as $index => $name) {
            if (empty(trim($name))) continue;

            $actions[] = [
                'name' => trim($name),
                'cost' => (int) ($_POST['leg_action_cost'][$index] ?? 1),
                'description' => trim($_POST['leg_action_description'][$index] ?? '')
            ];
        }

        return $actions;
    }

    /**
     * Upload monster image using centralized FileUploadService
     * 
     * Delegates to FileUploadService for consistent security and validation.
     * Used for both main monster images and lair action images.
     * 
     * @param array $file The $_FILES array element
     * @param string $uploadDir Subdirectory (default 'monsters', can be 'lair')
     * @return array Result ['success' => bool, 'error' => string|null, 'filename' => string|null]
     */
    private function uploadImage($file, $uploadDir = 'monsters'): array
    {
        $result = $this->fileUploadService->upload($file, $uploadDir);
        
        // Convert error messages for consistency (English from service → French for display)
        if (!$result['success']) {
            $errorMap = [
                'File upload error:' => 'Erreur lors du téléchargement du fichier',
                'File too large' => 'Le fichier est trop volumineux (max 5 Mo)',
                'Invalid file type' => 'Type de fichier non autorisé',
                'Failed to save' => 'Impossible de sauvegarder l\'image'
            ];
            
            $translatedError = 'Erreur lors du téléchargement du fichier.';
            foreach ($errorMap as $enKey => $frValue) {
                if (strpos($result['error'], $enKey) !== false) {
                    $translatedError = $frValue . '.';
                    break;
                }
            }
            
            return ['success' => false, 'error' => $translatedError];
        }
        
        return ['success' => true, 'filename' => $result['filename']];
    }

    // Display monster creation type selection page
    public function selectCreate()
    {
        // Load public monsters to preview on the selection page
        // Use getAllFiltered which deserializes automatically
        $allPublic = $this->monsterModel->getAllFiltered('newest');

        $randomSmall = null;
        $randomBoss = null;

        if (is_array($allPublic) && !empty($allPublic)) {
            // Differentiate previews by card_size:
            // - Small preview: card_size = 2 (playing card)
            // - Boss preview: card_size = 1 (A6 sheet)
            $smalls = array_values(array_filter($allPublic, function ($m) {
                return isset($m['card_size']) && (int)$m['card_size'] === 2;
            }));

            $bosses = array_values(array_filter($allPublic, function ($m) {
                return isset($m['card_size']) && (int)$m['card_size'] === 1;
            }));

            if (!empty($smalls)) {
                $randomSmall = $smalls[array_rand($smalls)];
            }
            if (!empty($bosses)) {
                $randomBoss = $bosses[array_rand($bosses)];
            }
        }

        // Fetch user likes for persistence
        $userLikes = [];
        if (isset($_SESSION['user'])) {
            $userId = $_SESSION['user']['u_id'];
            $likeModel = new MonsterLike();
            $monsterIds = [];
            if ($randomSmall) $monsterIds[] = $randomSmall['monster_id'];
            if ($randomBoss) $monsterIds[] = $randomBoss['monster_id'];
            if (!empty($monsterIds)) {
                $userLikes = $likeModel->getUserLikes($userId, $monsterIds);
            }
        }

        // Include mini card CSS for the preview templates
        $extraStyles = ['/css/monster-card-mini.css'];

        // Random lair card preview
        $lairModel = new LairCard();
        $randomLair = $lairModel->getRandom();

        // Expose variables to the view
        // $randomSmall and $randomBoss will be available in the required file
        require_once __DIR__ . '/../views/monster/create_select.php';
    }

    // Combined view: My Monsters + My Lair Cards
    public function myCards()
    {
        $this->ensureAuthenticated();

        $userId = $_SESSION['user']['u_id'];
        $monsters = $this->monsterModel->getByUser($userId);

        // Load lair cards via model
        $lairModel = new LairCard();
        $lairCards = $lairModel->getByUser($userId);
        
        // Get like data for current user
        $userLikes = [];
        if (!empty($monsters)) {
            $monsterIds = array_column($monsters, 'monster_id');
            $userLikes = $this->likeModel->getUserLikes($userId, $monsterIds);
        }

        // Include mini-card styles for monster previews
        $extraStyles = ['/css/monster-card-mini.css'];

        require_once __DIR__ . '/../views/dashboard/my-cards.php';
    }

    // Create boss monster
    public function createBoss()
    {
        $this->createForm('boss');
    }

    // Create small monster
    public function createSmall()
    {
        $this->createForm('small');
    }

    /**
     * Calculate D&D ability modifier from ability score.
     * Formula: (score - 10) / 2, rounded down
     * Example: 10 → 0, 12 → 1, 8 → -1, 14 → 2
     */
    private function calculateModifier($score): int
    {
        return (int)floor(($score - 10) / 2);
    }

    /**
     * Format ability modifier as a signed string.
     * Example: 1 → "+1", -1 → "-1", 0 → "+0"
     */
    private function formatModifier($modifier): string
    {
        if ($modifier > 0) {
            return '+' . $modifier;
        } elseif ($modifier < 0) {
            return (string)$modifier;
        } else {
            return '+0';
        }
    }

    /**
     * Render clean HTML for monster card PDF generation
     * 
     * This method is called by Puppeteer to fetch the HTML that will be rendered to PDF.
     * Returns plain HTML without header/navbar/footer - just the card content with CSS.
     * 
     * Route: ?url=monster-print&id={id}
     * 
     * @return void Outputs HTML directly (no JSON response)
     */
    /**
     * RENDER CLEAN HTML FOR PDF GENERATION - printPreview()
     * =========================================================
     * 
     * PURPOSE:
     * Renders a monster card as clean HTML (without header, footer, navigation).
     * This HTML is fetched by the Puppeteer service to render as PDF.
     * 
     * WHY SEPARATE FROM SHOW()?
     * - show() includes header, navbar, footer (for web display)
     * - Puppeteer needs just the card content (for PDF generation)
     * - Prevents extra elements from appearing in the printed PDF
     * 
     * WHEN IS THIS CALLED?
     * Indirectly by Puppeteer microservice when generating PDF:
     * 1. User clicks "Download for Print" button
     * 2. JavaScript calls MonsterController::generatePdf()
     * 3. generatePdf() calls Puppeteer service with URL:
     *    http://web/index.php?url=monster-print&id=42
     * 4. Router recognizes 'monster-print' and calls this method
     * 5. This method returns clean HTML
     * 6. Puppeteer service renders HTML as PDF
     * 7. PDF returned to user
     * 
     * FLOW:
     * 
     * STEP 1: Get monster ID from URL parameter
     * @param GET['id'] Monster database ID (from JavaScript fetch request)
     * 
     * STEP 2: Validate ID exists
     * If missing → 404 error page
     * 
     * STEP 3: Fetch monster from database
     * Uses Monster model to get all monster properties
     * 
     * STEP 4: Check monster exists
     * If not found → 404 error page
     * 
     * STEP 5: Validate user permissions
     * - If monster is public: Anyone can view
     * - If monster is private: Only owner can view
     * If unauthorized → 403 access denied page
     * 
     * STEP 6: Prepare monster data (same as show.php)
     * Convert database fields to display format:
     * - parseMonsterFields() → Format skills, senses, resistances
     * - prepareAbilityGrid() → Format ability scores
     * - getXpForCR() → Calculate XP reward
     * 
     * STEP 7: Determine card type
     * Boss cards (legendary) use different CSS styling than small cards
     * - Boss: Full 2-column layout with large stat block
     * - Small: Compact statblock
     * 
     * STEP 8: Load print-specific styles
     * CSS files optimized for printing:
     * - boss-card.css → Boss monster styling
     * - small-statblock.css → Small statblock styling
     * - monster-card-mini.css → Shared card styles
     * 
     * STEP 9: Render clean template
     * print-wrapper.php = template with NO header/nav/footer
     * Only shows: Card title, abilities, AC, HP, traits, actions
     * 
     * RELATED METHODS:
     * - generatePdf() → Calls Puppeteer with this output
     * - show() → Similar logic but includes web navigation
     * - Router (index.php) → Maps url=monster-print to this method
     * 
     * TEMPLATES USED:
     * - print-templates/print-wrapper.php → Main scaffold
     * - monster/boss-card.php → Boss card rendering
     * - monster/small-statblock.php → Small card rendering
     * 
     * MODELS USED:
     * - Monster::getById() → Fetch from database
     * - Authorization logic → Check is_public and u_id
     * 
     * @return void Outputs HTML directly (no JSON, no redirect)
     */
    public function printPreview()
    {
        // STEP 1: Get monster ID from URL
        // Provided by JavaScript when requesting the print template
        // Example: index.php?url=monster-print&id=42
        $id = $_GET['id'] ?? null;
        
        // STEP 2: Validate ID exists
        if (!$id) {
            require_once __DIR__ . '/../views/pages/error-404.php';
            return;
        }
        
        // STEP 3: Fetch monster from database
        $monster = $this->monsterModel->getById($id);

        // STEP 4: Check if monster was found
        if (!$monster) {
            require_once __DIR__ . '/../views/pages/error-404.php';
            return;
        }

        // STEP 5: Check permissions
        // Is user allowed to view this monster?
        // - Public monsters: Anyone (no user required)
        // - Private monsters: Only owner
        $userId = isset($_SESSION['user']) ? $_SESSION['user']['u_id'] : null;
        if (!$monster['is_public'] && $monster['u_id'] != $userId) {
            // User doesn't have permission
            require_once __DIR__ . '/../views/pages/error-403.php';
            return;
        }

        // STEP 6: Prepare monster data (convert database format to display format)
        // prepareAbilityGrid() formats ability scores (STR, DEX, CON, etc.)
        $abilitiesGrid = $this->prepareAbilityGrid($monster);
        
        // parseMonsterFields() formats various fields:
        // - Skills (Perception, Stealth, etc.)
        // - Senses (Darkvision, Blindsight, etc.)
        // - Damage resistances, immunities, etc.
        $parsedFields = $this->parseMonsterFields($monster);
        $skills = $parsedFields['skills'];
        $senses = $parsedFields['senses'];

        // Extract action lists from monster
        // Each is optional (some monsters have no reactions, for example)
        $traits = $monster['traits'] ?? [];
        $actions = $monster['actions'] ?? [];
        $bonusActions = $monster['bonus_actions'] ?? [];
        $reactions = $monster['reactions'] ?? [];
        $legendaryActions = $monster['legendary_actions'] ?? [];
        
        // Convert challenge rating to XP value
        // Example: CR 10 → 5,900 XP
        $monsterXp = $this->getXpForCR((string) ($monster['challenge_rating'] ?? ''));

        // STEP 7: Determine card type
        // Different styling for boss vs small creatures
        $isBoss = false;
        if (isset($monster['card_size'])) {
            // card_size: 0 = small, 1 = boss
            $isBoss = ((int)$monster['card_size'] === 1);
        } elseif (isset($monster['is_legendary'])) {
            // is_legendary: 1 = boss card
            $isBoss = ((int)$monster['is_legendary'] === 1);
        }

        // STEP 8: Load print-specific stylesheets
        // These CSS files are optimized for PDF printing
        $extraStyles = [
            '/css/boss-card.css',           // Boss monster styling
            '/css/small-statblock.css',     // Small statblock styling
            '/css/monster-card-mini.css'    // Shared card styles
        ];

        // STEP 9: Set output type and render template
        // Tell browser this is HTML (not JSON or PDF)
        header('Content-Type: text/html; charset=utf-8');
        
        // Render print-only template (no header/navbar/footer)
        // This template is much cleaner than show.php - only card content
        require_once __DIR__ . '/../views/print-templates/print-wrapper.php';
    }

    /**
     * GENERATE PDF VIA PUPPETEER MICROSERVICE - generatePdf()
     * ===========================================================
     * 
     * WHAT DOES THIS METHOD DO?
     * Acts as the API endpoint for PDF generation. When user clicks "Download for Print":
     * 1. JavaScript calls this endpoint via fetch()
     * 2. This method validates permissions
     * 3. Calls PrintService which communicates with Puppeteer service
     * 4. Streams PDF back to user's browser as a downloadable file
     * 
     * ARCHITECTURE OVERVIEW:
     * 
     *     User Browser (JavaScript)
     *            ↓ fetch('?url=monster-pdf&id=42')
     *     this method (generatePdf)
     *            ↓ Creates print URL
     *     PrintService->generatePdf($url)
     *            ↓ HTTP POST to microservice
     *     Puppeteer Service (Node.js) - Docker container
     *            ↓ Launches Chrome, renders HTML, generates PDF
     *     PrintService receives PDF bytes
     *            ↓ cURL response
     *     this method echoes binary PDF
     *            ↓ HTTP response with Content-Type: application/pdf
     *     User's browser receives PDF
     *            ↓ Browser download dialog
     *     User saves file: MonsterMaker_DragonName.pdf
     * 
     * SECURITY CONSIDERATIONS:
     * - Validates monster exists
     * - Validates user has permission (public or owner)
     * - Uses session user ID for ownership check
     * - Sanitizes monster name for filename
     * - All errors returned as JSON (prevents XSS in error messages)
     * 
     * REQUEST FLOW:
     * 
     * STEP 1: Get monster ID from URL parameter
     * @param GET['id'] Monster ID from JavaScript fetch URL
     * 
     * STEP 2: Validate ID provided
     * If missing → HTTP 400 Bad Request + JSON error
     * 
     * STEP 3: Fetch monster from database
     * Uses Monster model to get all monster properties
     * 
     * STEP 4: Validate monster exists
     * If not found → HTTP 404 Not Found + JSON error
     * 
     * STEP 5: Check permissions
     * Is user authorized to view this monster?
     * - Public monsters: Yes (no authentication required)
     * - Private monsters: Only owner (checked via u_id)
     * If unauthorized → HTTP 403 Forbidden + JSON error
     * 
     * STEP 6: Build print URL
     * URL points to printPreview() method which renders clean HTML
     * URL format: http://web/index.php?url=monster-print&id=42
     * Note: 'web' = Docker service hostname (not localhost)
     * 
     * STEP 7: Call PrintService
     * PrintService::generatePdf() handles:
     * - Creating HTTP request to Puppeteer service
     * - Sending request via cURL (Docker network)
     * - Parsing response and extracting PDF
     * - Error handling for service failures
     * 
     * PUPPETEER SERVICE PROCESS:
     * What happens inside Puppeteer (Node.js/Chrome):
     * 1. Receives request with print URL
     * 2. Launches headless Chrome browser
     * 3. Navigates to print URL (fetches from PHP web server)
     * 4. Chrome renders HTML with full CSS support
     * 5. Converts rendered page to PDF using Chrome's print engine
     * 6. Returns PDF as base64-encoded JSON
     * 
     * STEP 8: Prepare filename
     * Converts monster name to safe filename:
     * - Input: "Ancient Red Dragon"
     * - After regex: "Ancient_Red_Dragon"
     * - Final: "MonsterMaker_Ancient_Red_Dragon.pdf"
     * Sanitization prevents issues with special characters
     * 
     * STEP 9: Set HTTP headers
     * Content-Type: application/pdf
     *   → Tells browser this is a PDF file
     * Content-Disposition: attachment; filename="..."
     *   → Tells browser to download (not display inline)
     * Content-Length: bytes
     *   → Tells browser how large the file is
     * 
     * STEP 10: Stream PDF binary data
     * echo $pdf; sends the raw binary PDF bytes to browser
     * Browser receives complete PDF and starts download
     * 
     * ERROR HANDLING:
     * All errors return HTTP error code + JSON message:
     * - 400: Missing ID parameter
     * - 404: Monster not found
     * - 403: Access denied (not public, not owner)
     * - 500: Server error (Puppeteer failed, network error, etc.)
     * 
     * JavaScript can detect errors:
     * - Check response.ok (true for 200-299)
     * - Parse error message from JSON
     * - Show user-friendly alert
     * 
     * RELATED COMPONENTS:
     * - Frontend: public/js/monster-actions.js -> downloadCardPuppeteer()
     * - Backend: src/services/PrintService.php -> generatePdf()
     * - Microservice: puppeteer-service/index.js -> /render-pdf endpoint
     * - Template: printPreview() -> Renders clean HTML for Puppeteer
     * - Router: index.php -> Maps 'monster-pdf' to this method
     * 
     * PARAMETERS:
     * @param GET['id'] Monster database ID (required)
     * 
     * @return void Outputs PDF binary with headers (or JSON error)
     * 
     * HTTP RESPONSES:
     * 
     * Success (200):
     *   HTTP/1.1 200 OK
     *   Content-Type: application/pdf
     *   Content-Disposition: attachment; filename="MonsterMaker_DragonName.pdf"
     *   Content-Length: 87420
     *   [PDF binary data - 87420 bytes]
     * 
     * Error - Missing ID (400):
     *   HTTP/1.1 400 Bad Request
     *   Content-Type: application/json
     *   {"error": "Monster ID is required"}
     * 
     * Error - Not Found (404):
     *   HTTP/1.1 404 Not Found
     *   Content-Type: application/json
     *   {"error": "Monster not found"}
     * 
     * Error - Forbidden (403):
     *   HTTP/1.1 403 Forbidden
     *   Content-Type: application/json
     *   {"error": "Access denied"}
     * 
     * Error - Server Error (500):
     *   HTTP/1.1 500 Internal Server Error
     *   Content-Type: application/json
     *   {"error": "Service unavailable or rendering failed"}
     * 
     * DEBUGGING:
     * If PDF generation fails:
     * 1. Open browser DevTools (F12)
     * 2. Go to Network tab
     * 3. Click "Download for Print" button
     * 4. Find request to "?url=monster-pdf"
     * 5. Check response status code (tells you which error)
     * 6. Check response body (JSON error message)
     * 7. Check Console tab for JavaScript errors
     * 
     * Docker Service Debugging:
     * docker logs pdf-renderer → See Puppeteer logs
     * docker logs php-apache-monster-maker → See PHP errors
     * 
     * EXAMPLE JAVASCRIPT USAGE:
     * 
     * async function downloadCardPuppeteer(event) {
     *     const monsterId = 42;
     *     try {
     *         // Call this endpoint
     *         const response = await fetch('?url=monster-pdf&id=' + monsterId);
     *         
     *         // Check for HTTP errors
     *         if (!response.ok) {
     *             const error = await response.json();
     *             throw new Error(error.error);
     *         }
     *         
     *         // Get PDF as binary
     *         const pdfBlob = await response.blob();
     *         
     *         // Trigger download
     *         const url = URL.createObjectURL(pdfBlob);
     *         const link = document.createElement('a');
     *         link.href = url;
     *         link.download = 'MonsterMaker_Dragon.pdf';
     *         link.click();
     *         
     *     } catch (error) {
     *         alert('Error: ' + error.message);
     *     }
     * }
     * 
     * WHY THIS ARCHITECTURE?
     * 
     * Why not use mPDF or TCPDF (PHP libraries)?
     * - Can't handle modern CSS (flexbox, grid, transforms)
     * - Font rendering issues
     * - Complex layouts break
     * - Doesn't match what users see in browser
     * 
     * Why not run Puppeteer inside PHP?
     * - Puppeteer is Node.js library (not PHP)
     * - Wrapping it with shell_exec() is slow and unreliable
     * - Blocks PHP process while Chrome renders (hangs web server)
     * 
     * Why microservice architecture?
     * - Separation of concerns (PHP ≠ rendering)
     * - Scalability (can run multiple Puppeteer instances)
     * - Reliability (Chrome crash doesn't crash web server)
     * - Language flexibility (use best tool for job)
     * 
     * Docker networking allows service-to-service communication:
     * - PHP container calls Puppeteer container by hostname 'pdf-renderer'
     * - Secure internal network (not exposed to internet)
     * - Automatically load-balanced by Docker
     * 
     * @throws Exception If Puppeteer service fails
     */
    public function generatePdf()
    {
        // STEP 1 & 2: Get and validate monster ID
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            // Missing parameter - return JSON error
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Monster ID is required']);
            return;
        }

        try {
            // STEP 3 & 4: Fetch monster from database and validate
            $monster = $this->monsterModel->getById($id);
            if (!$monster) {
                // Monster not found
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Monster not found']);
                return;
            }

            // STEP 5: Check permissions
            // Is user allowed to access this monster?
            // - Public monsters: No auth required
            // - Private monsters: Only owner (check u_id)
            $userId = isset($_SESSION['user']) ? $_SESSION['user']['u_id'] : null;
            if (!$monster['is_public'] && $monster['u_id'] != $userId) {
                // User doesn't have permission
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Access denied']);
                return;
            }

            // STEP 6: Build URL for print template
            // printPreview() renders clean HTML (no header/nav/footer)
            // This URL is what Puppeteer service will fetch and render
            // Using 'web' service name (Docker hostname) instead of localhost
            $printUrl = 'http://web/index.php?url=monster-print&id=' . $id;

            // STEP 7: Call Puppeteer service via PrintService
            // PrintService handles:
            // - Creating HTTP POST request
            // - Sending to Puppeteer container
            // - Parsing response
            // - Error handling
            $printService = new \App\Services\PrintService();
            $pdf = $printService->generatePdf($printUrl, [
                'format' => 'A4',               // Paper size
                'printBackground' => true,     // Include background colors
                'preferCSSPageSize' => true    // Respect CSS @page rules
            ]);

            // STEP 8: Prepare filename
            // Sanitize monster name: "Ancient Red Dragon" → "Ancient_Red_Dragon"
            // This prevents issues with special characters
            $sanitizedName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $monster['name']);
            $filename = 'MonsterMaker_' . $sanitizedName . '.pdf';
            
            // STEP 9: Set HTTP response headers
            // Content-Type tells browser this is PDF
            header('Content-Type: application/pdf');
            // Content-Disposition tells browser to download (not display inline)
            // Filename is what appears in save dialog
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            // Content-Length tells browser file size (for progress bar)
            header('Content-Length: ' . strlen($pdf));
            
            // STEP 10: Stream PDF to browser
            // echo sends binary PDF data
            // Browser receives complete file and starts download
            echo $pdf;

        } catch (\Exception $e) {
            // STEP 11: Error handling
            // If anything fails (invalid PDF, service down, etc.)
            // Return JSON error instead of crashing
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}