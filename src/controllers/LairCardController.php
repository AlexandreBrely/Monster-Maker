<?php

namespace App\Controllers;

use App\Models\LairCard;
use App\Services\FileUploadService;

/**
 * Lair Card Controller
 * Handles CRUD operations for lair action cards (horizontal landscape format)
 * Controller responsibilities:
 * - Handle requests, validate input, call model, render view.
 */
class LairCardController
{
    private $lairCardModel; // Model for database operations
    private $fileUploadService;

    public function __construct()
    {
        // Create model instance when controller is created
        $this->lairCardModel = new LairCard();
        // Instantiate file upload service (handles all file uploads)
        $this->fileUploadService = new FileUploadService();
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
     * Process submitted create form: extract data, validate, upload optional image,
     * save to DB or re-render with errors.
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
     * Extract form data and organize lair actions from multiple form inputs.
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
    /**
     * Upload lair card image using centralized FileUploadService
     * 
     * Delegates to FileUploadService for consistent security and validation.
     * All lair images follow same pattern as other uploads: validate, name safely, store.
     * 
     * @param array $file The $_FILES array element
     * @return array Result ['success' => bool, 'error' => string|null, 'filename' => string|null]
     */
    private function uploadImage($file)
    {
        return $this->fileUploadService->upload($file, 'lair');
    }
}
