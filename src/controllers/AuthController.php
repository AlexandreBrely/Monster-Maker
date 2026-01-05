<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Collection;
use App\Services\FileUploadService;

/**
 * AuthController
 * Handles user authentication and profile management.
 * 
 * ORGANIZATION:
 * 1. Constructor & Authentication Guard
 * 2. Authentication (Login, Register, Logout)
 * 3. Profile Management (Edit Profile, Settings)
 * 4. Password Management (Change Password)
 * 5. Avatar Management (Upload, Delete)
 * 6. Helper Methods (private)
 */
class AuthController
{
    private $userModel;
    private $collectionModel;
    private $fileUploadService;

    public function __construct()
    {
        $this->userModel = new User();
        $this->collectionModel = new Collection();
        $this->fileUploadService = new FileUploadService();
    }

    /**
     * Ensure user is logged in; redirect to login if not.
     */
    private function ensureAuthenticated()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?url=login');
            exit;
        }
    }

    // ===================================================================
    // SECTION 1: AUTHENTICATION
    // ===================================================================

    /**
     * Display login form and handle submission.
     * Validates credentials using bcrypt password verification.
     */
    public function login()
    {
        if (isset($_SESSION['user'])) {
            header('Location: index.php?url=home');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $user = $this->userModel->findByEmail($email);

            if (!$user || !password_verify($password, $user['u_password'])) {
                $errors['login'] = "Invalid credentials.";
                require_once __DIR__ . '/../views/auth/login.php';
                return;
            }

            // Store user data in session
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
     * Display registration form and handle submission.
     * Creates new user with bcrypt-hashed password and optional avatar.
     */
    public function register()
    {
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

            // Handle avatar upload
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
                    // Create default "To Print" collection
                    $newUser = $this->userModel->findByEmail($data['email']);
                    
                    if ($newUser) {
                        $this->collectionModel->createDefaultCollection($newUser['u_id']);
                    }
                    
                    header('Location: index.php?url=login');
                    exit;
                } elseif ($result === 'username') {
                    $errors['username'] = "This username isn't available";
                } elseif ($result === 'email') {
                    $errors['email'] = "This email is already in use";
                } else {
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

    /**
     * Log out by destroying session data.
     */
    public function logout()
    {
        session_unset();
        session_destroy();

        header('Location: index.php?url=home');
        exit;
    }

    // ===================================================================
    // SECTION 2: PROFILE MANAGEMENT
    // ===================================================================

    /**
     * Display profile edit form and handle updates.
     * Allows changing username, email, and avatar.
     */
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

            // Handle avatar upload
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
                    // Refresh session data
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

    /**
     * Display settings page.
     */
    public function settings()
    {
        $this->ensureAuthenticated();
        require_once __DIR__ . '/../views/auth/settings.php';
    }

    // ===================================================================
    // SECTION 3: PASSWORD MANAGEMENT
    // ===================================================================

    /**
     * Handle password change.
     * Verifies current password before allowing update.
     */
    public function changePassword()
    {
        $this->ensureAuthenticated();

        $userId = $_SESSION['user']['u_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            $errors = [];

            // Verify current password
            $user = $this->userModel->findById($userId);
            if (!password_verify($currentPassword, $user['u_password'])) {
                $errors['current_password'] = 'Current password is incorrect';
            }

            // Validate new password
            if (empty($newPassword)) {
                $errors['new_password'] = 'New password is required';
            } elseif (strlen($newPassword) < 8) {
                $errors['new_password'] = 'Password too short (minimum 8 characters)';
            }

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

    // ===================================================================
    // SECTION 4: AVATAR MANAGEMENT
    // ===================================================================

    /**
     * Delete user's avatar (AJAX endpoint).
     * Returns JSON response.
     */
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

    // ===================================================================
    // SECTION 5: HELPER METHODS
    // ===================================================================

    /**
     * Upload avatar using centralized FileUploadService.
     * Validates file type, size, and stores securely.
     */
    private function uploadAvatar($file): array
    {
        $result = $this->fileUploadService->upload($file, 'avatars');
        
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
}
