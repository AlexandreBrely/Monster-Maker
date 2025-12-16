<?php

namespace App\Controllers;

use App\Models\LairCard;

/**
 * Lair Card Controller
 * Handles CRUD operations for lair action cards (horizontal landscape format)
 * 
 * For beginners:
 * A controller is the "traffic director" of your app.
 * When a user visits a URL like index.php?url=lair-card-create,
 * the router sends the request to this controller.
 * The controller then:
 * 1. Gets data from the user (forms)
 * 2. Validates it
 * 3. Uses the model to save/retrieve from database
 * 4. Shows the appropriate view (HTML page)
 */
class LairCardController
{
    private $lairCardModel; // Model for database operations

    public function __construct()
    {
        // Create model instance when controller is created
        $this->lairCardModel = new LairCard();
    }

    /**
     * Ensure user is authenticated
     */
    private function ensureAuthenticated()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?url=login');
            exit;
        }
    }

    /**
     * Display list of user's lair cards
     */
    public function myLairCards()
    {
        $this->ensureAuthenticated();
        $userId = $_SESSION['user']['u_id'];
        $lairCards = $this->lairCardModel->getByUser($userId);
        require_once __DIR__ . '/../views/lair/my-lair-cards.php';
    }

    /**
     * Show create lair card form
     */
    public function create()
    {
        $this->ensureAuthenticated();
        require_once __DIR__ . '/../views/lair/create.php';
    }

    /**
     * Handle lair card creation
     * 
     * For beginners:
     * This processes the form when user submits a new lair card.
     * Steps:
     * 1. Extract form data (monster name, lair actions, etc.)
     * 2. Validate it (check required fields)
     * 3. Handle image upload if provided
     * 4. If valid: save to database and redirect to list
     * 5. If errors: show form again with error messages
     */
    public function store()
    {
        $this->ensureAuthenticated(); // Make sure user is logged in
        $userId = $_SESSION['user']['u_id'];

        // Extract all form data into an array
        $data = $this->getFormData();
        
        // Validate: check required fields, return error messages if invalid
        $errors = $this->lairCardModel->validate($data);

        // Handle image upload
        if (!empty($_FILES['image_back']['name'])) {
            $uploadResult = $this->uploadImage($_FILES['image_back']);
            if ($uploadResult['success']) {
                $data['image_back'] = $uploadResult['filename'];
            } else {
                $errors['image_back'] = $uploadResult['error'];
            }
        }

        if (!empty($errors)) {
            $old = $data;
            require_once __DIR__ . '/../views/lair/create.php';
            return;
        }

        if ($this->lairCardModel->create($data, $userId)) {
            header('Location: index.php?url=my-lair-cards');
            exit;
        } else {
            $errors['server'] = 'Failed to create lair card';
            $old = $data;
            require_once __DIR__ . '/../views/lair/create.php';
        }
    }

    /**
     * Display a single lair card
     */
    public function show($id)
    {
        $lairCard = $this->lairCardModel->getById($id);

        if (!$lairCard) {
            require_once __DIR__ . '/../views/pages/error-404.php';
            return;
        }

        require_once __DIR__ . '/../views/lair/show.php';
    }

    /**
     * Show edit form for lair card
     */
    public function edit($id)
    {
        $this->ensureAuthenticated();
        $userId = $_SESSION['user']['u_id'];
        $lairCard = $this->lairCardModel->getById($id);

        if (!$lairCard || $lairCard['u_id'] != $userId) {
            require_once __DIR__ . '/../views/pages/error-403.php';
            return;
        }

        require_once __DIR__ . '/../views/lair/edit.php';
    }

    /**
     * Handle lair card update
     */
    public function update($id)
    {
        $this->ensureAuthenticated();
        $userId = $_SESSION['user']['u_id'];

        $data = $this->getFormData();
        $errors = $this->lairCardModel->validate($data);

        // Handle image upload
        if (!empty($_FILES['image_back']['name'])) {
            $uploadResult = $this->uploadImage($_FILES['image_back']);
            if ($uploadResult['success']) {
                $data['image_back'] = $uploadResult['filename'];
            } else {
                $errors['image_back'] = $uploadResult['error'];
            }
        }

        if (!empty($errors)) {
            $old = $data;
            $lairCard = $data;
            require_once __DIR__ . '/../views/lair/edit.php';
            return;
        }

        if ($this->lairCardModel->update($id, $data, $userId)) {
            header('Location: index.php?url=lair-card&id=' . $id);
            exit;
        } else {
            $errors['server'] = 'Failed to update lair card';
            $old = $data;
            $lairCard = $data;
            require_once __DIR__ . '/../views/lair/edit.php';
        }
    }

    /**
     * Delete lair card
     */
    public function delete($id)
    {
        $this->ensureAuthenticated();
        $userId = $_SESSION['user']['u_id'];

        if ($this->lairCardModel->delete($id, $userId)) {
            header('Location: index.php?url=my-lair-cards');
            exit;
        } else {
            require_once __DIR__ . '/../views/pages/error-403.php';
        }
    }

    /**
     * Extract form data
     * 
     * For beginners:
     * This reads all the form fields from $_POST and organizes them.
     * Special handling for lair actions:
     * - The form has multiple action fields (name[], description[])
     * - We loop through them and build an array of action objects
     */
    private function getFormData()
    {
        // Parse lair actions from multiple form inputs
        $lairActions = [];
        
        // Get all action names (array of inputs with name="lair_action_name[]")
        $actionNames = $_POST['lair_action_name'] ?? [];
        
        // Loop through each action name by index
        foreach ($actionNames as $index => $name) {
            if (empty(trim($name))) continue; // Skip empty names

            // Build action object with name and description
            $lairActions[] = [
                'name' => trim($name),
                'description' => trim($_POST['lair_action_description'][$index] ?? '')
            ];
        }

        return [
            'monster_name' => trim($_POST['monster_name'] ?? ''),
            'lair_name' => trim($_POST['lair_name'] ?? ''),
            'lair_description' => trim($_POST['lair_description'] ?? ''),
            'lair_initiative' => (int)($_POST['lair_initiative'] ?? 20),
            'lair_actions' => $lairActions,
            'regional_effects' => trim($_POST['regional_effects'] ?? ''),
            'image_back' => '' // Will be set by upload if provided
        ];
    }

    /**
     * Upload landscape image for lair card back
     */
    private function uploadImage($file)
    {
        $maxSize = 5 * 1024 * 1024; // 5MB
        $allowedMime = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp'
        ];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Upload error'];
        }

        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'File too large (max 5MB)'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);

        if (!array_key_exists($mime, $allowedMime)) {
            return ['success' => false, 'error' => 'Invalid file type'];
        }

        $extension = $allowedMime[$mime];
        $uniqueName = bin2hex(random_bytes(8)) . '_lair.' . $extension;

        $uploadPath = __DIR__ . '/../../public/uploads/lair/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $destination = $uploadPath . $uniqueName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => false, 'error' => 'Failed to save image'];
        }

        return ['success' => true, 'filename' => $uniqueName];
    }
}
