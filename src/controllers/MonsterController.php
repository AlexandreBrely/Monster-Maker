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
 * 
 * ORGANIZATION:
 * 1. Constructor & Authentication
 * 2. CRUD Operations (Create, Read, Update, Delete)
 * 3. Display & View Methods
 * 4. PDF Generation
 * 5. AJAX Endpoints
 * 6. Helper Methods (private)
 */
class MonsterController
{
    private $monsterModel;
    private $likeModel;
    private $collectionModel;
    private $fileUploadService;

    public function __construct()
    {
        $this->monsterModel = new Monster();
        $this->likeModel = new MonsterLike();
        $this->collectionModel = new Collection();
        $this->fileUploadService = new FileUploadService();
    }

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

    // ===================================================================
    // SECTION 1: CRUD OPERATIONS
    // ===================================================================

    /**
     * CREATE - Display creation type selection page (boss vs small)
     */
    public function selectCreate()
    {
        // Load public monsters to preview on the selection page
        $allPublic = $this->monsterModel->getAllFiltered('newest');

        $randomSmall = null;
        $randomBoss = null;

        if (is_array($allPublic) && !empty($allPublic)) {
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
            $monsterIds = [];
            if ($randomSmall) $monsterIds[] = $randomSmall['monster_id'];
            if ($randomBoss) $monsterIds[] = $randomBoss['monster_id'];
            if (!empty($monsterIds)) {
                $userLikes = $this->likeModel->getUserLikes($userId, $monsterIds);
            }
        }

        $extraStyles = ['/css/monster-card-mini.css'];

        // Random lair card preview
        $lairModel = new LairCard();
        $randomLair = $lairModel->getRandom();

        require_once __DIR__ . '/../views/monster/create_select.php';
    }

    /**
     * CREATE - Handle boss monster creation
     */
    public function createBoss()
    {
        $this->createForm('boss');
    }

    /**
     * CREATE - Handle small monster creation
     */
    public function createSmall()
    {
        $this->createForm('small');
    }

    /**
     * CREATE - Main form logic (boss or small)
     * Validates input, uploads images, delegates creation to model
     */
    private function createForm($type = null)
    {
        $this->ensureAuthenticated();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->getFormData();
            
            // Check if user clicked an "add" button instead of submitting
            if (!empty($_POST['add_action']) || !empty($_POST['add_bonus_action']) || 
                !empty($_POST['add_reaction']) || !empty($_POST['add_legendary_action']) ||
                !empty($_POST['add_trait'])) {
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

    /**
     * READ - Show single monster (public view)
     * Handles authorization and prepares view data
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

        // Prepare view variables
        $abilitiesGrid = $this->prepareAbilityGrid($monster);
        $parsedFields = $this->parseMonsterFields($monster);
        $skills = $parsedFields['skills'];
        $senses = $parsedFields['senses'];

        $traits = $monster['traits'] ?? [];
        $actions = $monster['actions'] ?? [];
        $bonusActions = $monster['bonus_actions'] ?? [];
        $reactions = $monster['reactions'] ?? [];
        $legendaryActions = $monster['legendary_actions'] ?? [];
        $monsterXp = $this->getXpForCR((string) ($monster['challenge_rating'] ?? ''));

        // Route to correct view based on card size
        $isBoss = false;
        if (isset($monster['card_size'])) {
            $isBoss = ((int)$monster['card_size'] === 1);
        } elseif (isset($monster['is_legendary'])) {
            $isBoss = ((int)$monster['is_legendary'] === 1);
        }

        if ($isBoss) {
            require_once __DIR__ . '/../views/monster/boss-card.php';
        } else {
            require_once __DIR__ . '/../views/monster/small-statblock.php';
        }
    }

    /**
     * UPDATE - Display edit form
     */
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

    /**
     * UPDATE - Process update form submission
     */
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

            // If update succeeded and a new image was uploaded, delete old files
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

    /**
     * DELETE - Remove monster (with confirmation)
     */
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

        // Prepare view data for confirmation page
        $abilitiesGrid = $this->prepareAbilityGrid($monster);
        $parsedFields = $this->parseMonsterFields($monster);
        $skills = $parsedFields['skills'];
        $senses = $parsedFields['senses'];

        $traits = $monster['traits'] ?? [];
        $actions = $monster['actions'] ?? [];
        $bonusActions = $monster['bonus_actions'] ?? [];
        $reactions = $monster['reactions'] ?? [];
        $legendaryActions = $monster['legendary_actions'] ?? [];

        require_once __DIR__ . '/../views/monster/show.php';
    }

    // ===================================================================
    // SECTION 2: DISPLAY & VIEW METHODS
    // ===================================================================

    /**
     * LIST - Display all public monsters with filters
     */
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
     * LIST - Show monsters owned by logged-in user
     */
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

    /**
     * LIST - Combined view: My Monsters + My Lair Cards
     */
    public function myCards()
    {
        $this->ensureAuthenticated();

        $userId = $_SESSION['user']['u_id'];
        $monsters = $this->monsterModel->getByUser($userId);

        // Load lair cards
        $lairModel = new LairCard();
        $lairCards = $lairModel->getByUser($userId);
        
        // Get like data for current user
        $userLikes = [];
        if (!empty($monsters)) {
            $monsterIds = array_column($monsters, 'monster_id');
            $userLikes = $this->likeModel->getUserLikes($userId, $monsterIds);
        }

        $extraStyles = ['/css/monster-card-mini.css'];

        require_once __DIR__ . '/../views/dashboard/my-cards.php';
    }

    /**
     * ROUTER - Dispatch monster actions (show/edit/update/delete)
     */
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

    // ===================================================================
    // SECTION 3: PDF GENERATION
    // ===================================================================

    /**
     * PDF - Render clean HTML for Puppeteer (no header/footer)
     * Called by Puppeteer service to fetch HTML for rendering
     */
    public function printPreview()
    {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            require_once __DIR__ . '/../views/pages/error-404.php';
            return;
        }
        
        $monster = $this->monsterModel->getById($id);

        if (!$monster) {
            require_once __DIR__ . '/../views/pages/error-404.php';
            return;
        }

        // Check permissions
        $userId = isset($_SESSION['user']) ? $_SESSION['user']['u_id'] : null;
        if (!$monster['is_public'] && $monster['u_id'] != $userId) {
            require_once __DIR__ . '/../views/pages/error-403.php';
            return;
        }

        // Prepare monster data
        $abilitiesGrid = $this->prepareAbilityGrid($monster);
        $parsedFields = $this->parseMonsterFields($monster);
        $skills = $parsedFields['skills'];
        $senses = $parsedFields['senses'];

        $traits = $monster['traits'] ?? [];
        $actions = $monster['actions'] ?? [];
        $bonusActions = $monster['bonus_actions'] ?? [];
        $reactions = $monster['reactions'] ?? [];
        $legendaryActions = $monster['legendary_actions'] ?? [];
        $monsterXp = $this->getXpForCR((string) ($monster['challenge_rating'] ?? ''));

        // Determine card type
        $isBoss = false;
        if (isset($monster['card_size'])) {
            $isBoss = ((int)$monster['card_size'] === 1);
        } elseif (isset($monster['is_legendary'])) {
            $isBoss = ((int)$monster['is_legendary'] === 1);
        }

        // Load print-specific stylesheets
        $extraStyles = [
            '/css/boss-card.css',
            '/css/small-statblock.css',
            '/css/monster-card-mini.css'
        ];

        header('Content-Type: text/html; charset=utf-8');
        require_once __DIR__ . '/../views/print-templates/print-wrapper.php';
    }

    /**
     * PDF - Generate PDF via Puppeteer microservice
     * Streams PDF binary to browser as downloadable file
     */
    public function generatePdf()
    {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Monster ID is required']);
            return;
        }

        try {
            $monster = $this->monsterModel->getById($id);
            if (!$monster) {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Monster not found']);
                return;
            }

            // Check permissions
            $userId = isset($_SESSION['user']) ? $_SESSION['user']['u_id'] : null;
            if (!$monster['is_public'] && $monster['u_id'] != $userId) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Access denied']);
                return;
            }

            // Build URL for print template
            $printUrl = 'http://web/index.php?url=monster-print&id=' . $id;

            // Call Puppeteer service
            $printService = new \App\Services\PrintService();
            $pdf = $printService->generatePdf($printUrl, [
                'format' => 'A4',
                'printBackground' => true,
                'preferCSSPageSize' => true
            ]);

            // Prepare filename
            $sanitizedName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $monster['name']);
            $filename = 'MonsterMaker_' . $sanitizedName . '.pdf';
            
            // Set headers and stream PDF
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($pdf));
            echo $pdf;

        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // ===================================================================
    // SECTION 4: AJAX ENDPOINTS
    // ===================================================================

    /**
     * AJAX - Toggle Like/Unlike on monster
     * Returns JSON with new like count and state
     */
    public function toggleLike()
    {
        header('Content-Type: application/json');
        
        // Check authentication
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            return;
        }
        
        // Validate monster ID
        $monsterId = $_GET['id'] ?? null;
        if (!$monsterId) {
            echo json_encode(['success' => false, 'error' => 'Monster ID required']);
            return;
        }
        
        $userId = $_SESSION['user']['u_id'];
        $monsterId = (int)$monsterId;
        
        // Verify monster exists and is accessible
        $monster = $this->monsterModel->getById($monsterId);
        if (!$monster) {
            echo json_encode(['success' => false, 'error' => 'Monster not found']);
            return;
        }
        
        // Check permissions
        if (!$monster['is_public'] && $monster['u_id'] != $userId) {
            echo json_encode(['success' => false, 'error' => 'Cannot like private monster']);
            return;
        }
        
        // Toggle the like
        $action = $this->likeModel->toggleLike($userId, $monsterId);
        $newCount = $this->likeModel->countLikes($monsterId);
        
        // Build response
        $response = [
            'success' => true,
            'action' => $action,
            'count' => $newCount,
            'liked' => ($action === 'added')
        ];
        
        echo json_encode($response);
        exit;
    }

    // ===================================================================
    // SECTION 5: HELPER METHODS (private)
    // ===================================================================

    /**
     * Extract and normalize form data from $_POST
     */
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

    /**
     * Parse proficiency bonus and handle +X format
     */
    private function parseProficiencyBonus($value): int
    {
        $value = trim($value);
        if (strpos($value, '+') === 0) {
            $value = substr($value, 1);
        }
        return (int) $value;
    }

    /**
     * Build saving throws string from proficiency checkboxes
     */
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

    /**
     * Build traits array from name and description fields
     */
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

    /**
     * Build actions array from name and description fields
     */
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

    /**
     * Build bonus actions array
     */
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

    /**
     * Build reactions array
     */
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

    /**
     * Build legendary actions array
     */
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
                $cost = 1;
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

    /**
     * Upload monster image using centralized FileUploadService
     */
    private function uploadImage($file, $uploadDir = 'monsters'): array
    {
        $result = $this->fileUploadService->upload($file, $uploadDir);
        
        // Convert error messages for consistency (English → French for UI)
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

    /**
     * Prepare ability grid data for monster display
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
                if (strpos($pair, ':') !== false) {
                    $parts = explode(':', $pair);
                } else {
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
     * Parse comma-separated fields into arrays
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
     * Calculate D&D ability modifier from ability score
     * Formula: (score - 10) / 2, rounded down
     */
    private function calculateModifier($score): int
    {
        return (int)floor(($score - 10) / 2);
    }

    /**
     * Format ability modifier as a signed string
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
}
