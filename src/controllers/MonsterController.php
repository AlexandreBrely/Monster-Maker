<?php

namespace App\Controllers;

use App\Models\Monster;

/**
 * MonsterController
 * Gère les opérations CRUD des monstres et les uploads d'images.
 */
class MonsterController
{
    private $monsterModel;

    public function __construct()
    {
        $this->monsterModel = new Monster();
    }

    private function ensureAuthenticated()
    {
        if (!isset($_SESSION['user']) || !isset($_SESSION['user']['u_id'])) {
            header('Location: index.php?url=login');
            exit;
        }
    }

    // Affiche le formulaire de création de monstre
    // Main create logic (called by createBoss and createSmall)
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

            // Traitement des images si présentes
            if (!empty($_FILES['image_portrait']['name'])) {
                $imageResult = $this->uploadImage($_FILES['image_portrait'], 'monsters');
                if ($imageResult['success']) {
                    $images['portrait'] = $imageResult['filename'];
                } else {
                    $errors['image_portrait'] = $imageResult['error'];
                }
            }

            if (!empty($_FILES['image_fullbody']['name'])) {
                $imageResult = $this->uploadImage($_FILES['image_fullbody'], 'monsters');
                if ($imageResult['success']) {
                    $images['fullbody'] = $imageResult['filename'];
                } else {
                    $errors['image_fullbody'] = $imageResult['error'];
                }
            }

            // Si erreurs, réafficher le formulaire
            if (!empty($errors)) {
                extract(['errors' => $errors, 'old' => $data]);
                $viewFile = ($type === 'boss') ? 'create.php' : 'create_small.php';
                require_once __DIR__ . '/../views/monster/' . $viewFile;
                return;
            }

            // Création du monstre
            $userId = $_SESSION['user']['u_id'];
            $data = array_merge($data, $images);
            $monsterId = $this->monsterModel->create($data, $userId);

            if ($monsterId) {
                header('Location: index.php?url=monster&id=' . $monsterId);
                exit;
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

    // Affiche les détails d'un monstre spécifique
    public function show($id)
    {
        $monster = $this->monsterModel->getById($id);

        if (!$monster) {
            require_once __DIR__ . '/../views/pages/error-404.php';
            return;
        }

        // Vérifier accès : public ou propriétaire
        $userId = isset($_SESSION['user']) ? $_SESSION['user']['u_id'] : null;
        if (!$monster['is_public'] && $monster['u_id'] != $userId) {
            require_once __DIR__ . '/../views/pages/error-403.php';
            return;
        }

        require_once __DIR__ . '/../views/monster/show.php';
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

    // Affiche la liste de tous les monstres
    public function index()
    {
        $monsters = $this->monsterModel->getAll();
        require_once __DIR__ . '/../views/monster/index.php';
    }

    // Affiche le formulaire d'édition
    public function edit($id)
    {
        $this->ensureAuthenticated();
        $monster = $this->monsterModel->getById($id);

        if (!$monster) {
            require_once __DIR__ . '/../views/pages/error-404.php';
            return;
        }

        // Vérifier propriété
        $userId = $_SESSION['user']['u_id'];
        if ($monster['u_id'] != $userId) {
            require_once __DIR__ . '/../views/pages/error-403.php';
            return;
        }

        require_once __DIR__ . '/../views/monster/edit.php';
    }

    // Traite la mise à jour d'un monstre
    public function update($id)
    {
        $this->ensureAuthenticated();

        $monster = $this->monsterModel->getById($id);
        if (!$monster) {
            require_once __DIR__ . '/../views/pages/error-404.php';
            return;
        }

        // Vérifier propriété
        $userId = $_SESSION['user']['u_id'];
        if ($monster['u_id'] != $userId) {
            require_once __DIR__ . '/../views/pages/error-403.php';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->getFormData();
            $errors = $this->monsterModel->validate($data);
            $images = [];

            // Traitement des images
            if (!empty($_FILES['image_portrait']['name'])) {
                $imageResult = $this->uploadImage($_FILES['image_portrait'], 'monsters');
                if ($imageResult['success']) {
                    $images['portrait'] = $imageResult['filename'];
                } else {
                    $errors['image_portrait'] = $imageResult['error'];
                }
            }

            if (!empty($_FILES['image_fullbody']['name'])) {
                $imageResult = $this->uploadImage($_FILES['image_fullbody'], 'monsters');
                if ($imageResult['success']) {
                    $images['fullbody'] = $imageResult['filename'];
                } else {
                    $errors['image_fullbody'] = $imageResult['error'];
                }
            }

            // Si erreurs
            if (!empty($errors)) {
                extract(['errors' => $errors, 'old' => $data, 'monster' => $monster]);
                require_once __DIR__ . '/../views/monster/edit.php';
                return;
            }

            // Mise à jour
            $data = array_merge($data, $images);
            $this->monsterModel->update($id, $data, $userId);
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

        // Vérifier propriété
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

        require_once __DIR__ . '/../views/monster/show.php';
    }

    // Affiche les monstres de l'utilisateur connecté
    public function myMonsters()
    {
        $this->ensureAuthenticated();
        $userId = $_SESSION['user']['u_id'];
        $monsters = $this->monsterModel->getByUser($userId);
        require_once __DIR__ . '/../views/monster/my-monsters.php';
    }

    // ===== MÉTHODES HELPER =====

    // Extrait et prépare les données du formulaire
    private function getFormData(): array
    {
        return [
            'card_size' => (int) ($_POST['card_size'] ?? 1),
            'name' => trim($_POST['name'] ?? ''),
            'size' => $_POST['size'] ?? '',
            'type' => trim($_POST['type'] ?? ''),
            'alignment' => trim($_POST['alignment'] ?? ''),
            'ac' => (int) ($_POST['ac'] ?? 10),
            'hp' => (int) ($_POST['hp'] ?? 1),
            'hit_dice' => trim($_POST['hit_dice'] ?? ''),
            'ac_notes' => trim($_POST['ac_notes'] ?? ''),
            'equipment_variants' => trim($_POST['equipment_variants'] ?? ''),
            'speed' => trim($_POST['speed'] ?? ''),
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
            'legendary_action_uses' => trim($_POST['legendary_action_uses'] ?? ''),
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
                $actions[] = [
                    'name' => $name,
                    'description' => $description
                ];
            }
        }
        return $actions;
    }

    // Parse les actions depuis le formulaire - OLD METHOD (no longer used)
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

    // Parse les réactions depuis le formulaire
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

    // Parse les capacités légendaires depuis le formulaire
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

    // Traite l'upload d'une image unique
    private function uploadImage($file, $uploadDir = 'monsters'): array
    {
        $maxSize = 5 * 1024 * 1024; // 5MB
        $allowedMime = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp'
        ];

        // Vérification de l'erreur d'upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'error' => 'Erreur lors du téléchargement du fichier.'
            ];
        }

        // Vérification de la taille
        if ($file['size'] > $maxSize) {
            return [
                'success' => false,
                'error' => 'Le fichier est trop volumineux (max 5 Mo).'
            ];
        }

        // Vérification du type MIME réel
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!array_key_exists($mime, $allowedMime)) {
            return [
                'success' => false,
                'error' => 'Type de fichier non autorisé.'
            ];
        }

        // Génération d'un nom de fichier unique et sécurisé
        $extension = $allowedMime[$mime];
        $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
        $sanitizedName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
        $truncatedName = substr($sanitizedName, 0, 20);
        $uniqueId = bin2hex(random_bytes(8));
        $uniqueName = $uniqueId . '_' . $truncatedName . '.' . $extension;

        // Création du dossier s'il n'existe pas
        $uploadPath = __DIR__ . '/../../public/uploads/' . $uploadDir . '/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $destination = $uploadPath . $uniqueName;

        // Déplacement du fichier
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return [
                'success' => false,
                'error' => 'Impossible de sauvegarder l\'image.'
            ];
        }

        return [
            'success' => true,
            'filename' => $uniqueName
        ];
    }

    // Display monster creation type selection page
    public function selectCreate()
    {
        require_once __DIR__ . '/../views/monster/create_select.php';
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
}