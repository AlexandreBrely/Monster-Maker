<?php

namespace App\Controllers;

use App\Models\User;

/**
 * AuthController
 * Handles user authentication: registration, login, logout, and profile management.
 * 
 * Responsibilities:
 * - Registration: Validate input, hash passwords, handle avatar uploads
 * - Login: Verify credentials, create session
 * - Profile: Edit username/email, change password, manage avatar
 * - Logout: Destroy session
 * 
 * Security patterns used:
 * - password_hash() / password_verify() for secure password handling
 * - Session-based authentication ($_SESSION['user'])
 * - Owner verification before profile updates
 * - MIME-based file validation for avatars
 */
class AuthController
{
    private $userModel;

    public function __construct()
    {
        // Instantiate the User model (delegates data access and validation)
        $this->userModel = new User();
    }

    /**
     * Ensure user is logged in; redirect to login if not.
     * Use this in controller methods that require authentication.
     */
    private function ensureAuthenticated()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?url=login');
            exit;
        }
    }

    /**
     * Display login form and handle POST submission.
     * 
     * Process:
     * 1. Redirect if already logged in
     * 2. On POST: validate email exists and password matches
     * 3. If valid: create session and redirect to home
     * 4. If invalid: display error and re-render form
     */
    public function login()
    {
        // Redirect logged-in users to home
        if (isset($_SESSION['user'])) {
            header('Location: index.php?url=home');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            // Find user by email; if not found or password wrong, show error
            $user = $this->userModel->findByEmail($email);

            // password_verify() uses bcrypt to safely compare plaintext vs hashed password
            if (!$user || !password_verify($password, $user['u_password'])) {
                $errors['login'] = "Invalid credentials.";
                require_once __DIR__ . '/../views/auth/login.php';
                return;
            }

            // Successful login: store minimal user data in session
            // Session persists across requests and is destroyed on logout
            $_SESSION['user'] = [
                'u_id' => $user['u_id'],
                'u_username' => $user['u_name'],
                'u_email' => $user['u_email'],
                'u_avatar' => $user['u_avatar'] ?? null
            ];

            header('Location: index.php?url=home');
            exit;
        }

        require_once __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Display registration form and handle POST submission.
     * 
     * Process:
     * 1. Validate username, email, password, confirmation
     * 2. Upload profile picture (optional)
     * 3. Hash password using bcrypt
     * 4. Save user to database
     * 5. Handle duplicate username/email errors
     * 6. Redirect to login on success
     */
    public function register()
    {
        // Redirect logged-in users to home
        if (isset($_SESSION['user'])) {
            header('Location: index.php?url=home');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'username' => $_POST['username'] ?? '',
                'email' => $_POST['email'] ?? '',
                'password' => $_POST['password'] ?? '',
                'confirm_password' => $_POST['confirm_password'] ?? ''
            ];

            $errors = $this->userModel->validateRegister($data);

            // Handle profile picture upload
            $avatarFilename = null;
            if (!empty($_FILES['profile_picture']['name'])) {
                $uploadResult = $this->uploadAvatar($_FILES['profile_picture']);
                if ($uploadResult['success']) {
                    $avatarFilename = $uploadResult['filename'];
                } else {
                    $errors['avatar'] = $uploadResult['error'];
                }
            }

            if (empty($errors)) {
                $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

                $result = $this->userModel->create($data['username'], $data['email'], $hashedPassword, $avatarFilename);
                
                if ($result === true) {
                    header('Location: index.php?url=login');
                    exit;
                } elseif ($result === 'username') {
                    $errors['username'] = "This username isn't available";
                } elseif ($result === 'email') {
                    $errors['email'] = "This email is already in use";
                } else {
                    // Log the actual error for debugging
                    error_log("Registration failed with result: " . print_r($result, true));
                    $errors['server'] = "An error occurred, please try again later";
                }
            }

            $old = [
                'username' => $data['username'],
                'email' => $data['email']
            ];
        }

        require_once __DIR__ . '/../views/auth/register.php';
    }

    // Déconnexion
    public function logout()
    {
        session_unset();
        session_destroy();

        header('Location: index.php?url=home');
        exit;
    }

    // Affiche la page d'édition de profil
    public function editProfile()
    {
        $this->ensureAuthenticated();

        $userId = $_SESSION['user']['u_id'];
        $user = $this->userModel->findById($userId);

        if (!$user) {
            require_once __DIR__ . '/../views/pages/error-404.php';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'username' => $_POST['username'] ?? '',
                'email' => $_POST['email'] ?? ''
            ];

            $errors = $this->userModel->validateProfileUpdate($data, $userId);

            // Traitement de l'avatar s'il est fourni
            $avatarFile = null;
            if (!empty($_FILES['avatar']['name'])) {
                $uploadResult = $this->uploadAvatar($_FILES['avatar']);
                if ($uploadResult['success']) {
                    $avatarFile = $uploadResult['filename'];
                    $data['avatar'] = $avatarFile;
                } else {
                    $errors['avatar'] = $uploadResult['error'];
                }
            }

            if (empty($errors)) {
                if ($this->userModel->updateProfile($userId, $data)) {
                    // Mettre à jour la session
                    if (!empty($data['username'])) {
                        $_SESSION['user']['u_username'] = $data['username'];
                    }
                    if (!empty($data['email'])) {
                        $_SESSION['user']['u_email'] = $data['email'];
                    }
                    if (!empty($data['avatar'])) {
                        $_SESSION['user']['u_avatar'] = $data['avatar'];
                    }

                    header('Location: index.php?url=edit-profile&success=1');
                    exit;
                } else {
                    $errors['server'] = 'An error occurred while updating your profile';
                }
            }

            extract(['errors' => $errors, 'old' => $data, 'user' => $user]);
        }

        require_once __DIR__ . '/../views/auth/edit-profile.php';
    }

    // Page de paramètres
    public function settings()
    {
        $this->ensureAuthenticated();
        require_once __DIR__ . '/../views/auth/settings.php';
    }

    // Traite le changement de mot de passe
    public function changePassword()
    {
        $this->ensureAuthenticated();

        $userId = $_SESSION['user']['u_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            $errors = [];

            // Vérifier le mot de passe actuel
            $user = $this->userModel->findById($userId);
            if (!password_verify($currentPassword, $user['u_password'])) {
                $errors['current_password'] = 'Current password is incorrect';
            }

            // Valider le nouveau mot de passe
            if (empty($newPassword)) {
                $errors['new_password'] = 'New password is required';
            } elseif (strlen($newPassword) < 8) {
                $errors['new_password'] = 'Password too short (minimum 8 characters)';
            }

            // Vérifier la confirmation
            if ($newPassword !== $confirmPassword) {
                $errors['confirm_password'] = 'Passwords do not match';
            }

            if (empty($errors)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                if ($this->userModel->changePassword($userId, $hashedPassword)) {
                    $success = 'Password changed successfully!';
                    require_once __DIR__ . '/../views/auth/settings.php';
                    return;
                } else {
                    $errors['server'] = 'An error occurred while changing your password';
                }
            }

            extract(['errors' => $errors]);
        }

        require_once __DIR__ . '/../views/auth/settings.php';
    }

    // Delete user's avatar
    public function deleteAvatar()
    {
        header('Content-Type: application/json');
        $this->ensureAuthenticated();

        $userId = $_SESSION['user']['u_id'];
        $user = $this->userModel->findById($userId);

        if (!$user || empty($user['u_avatar'])) {
            echo json_encode(['success' => false, 'error' => 'No avatar to delete']);
            return;
        }

        // Delete physical file
        $avatarPath = __DIR__ . '/../../public/uploads/avatars/' . $user['u_avatar'];
        if (file_exists($avatarPath)) {
            unlink($avatarPath);
        }

        // Update database
        $result = $this->userModel->updateProfile($userId, ['avatar' => '']);
        
        if ($result) {
            $_SESSION['user']['u_avatar'] = '';
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database update failed']);
        }
    }

    // Traite l'upload d'avatar
    private function uploadAvatar($file): array
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
        $uploadPath = __DIR__ . '/../../public/uploads/avatars/';
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
}
