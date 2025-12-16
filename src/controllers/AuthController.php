<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Collection;
use App\Services\FileUploadService;

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
    private $collectionModel;
    private $fileUploadService;

    public function __construct()
    {
        // Instantiate the User model (delegates data access and validation)
        $this->userModel = new User();
        // Instantiate Collection model (for creating default collection on registration)
        $this->collectionModel = new Collection();
        // Instantiate file upload service (handles all file uploads)
        $this->fileUploadService = new FileUploadService();
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
     * 
     * Security notes:
     * - password_verify() compares hashed password securely (bcrypt algorithm)
     * - Session data is stored server-side (only session ID is in cookie)
     * - Error messages don't reveal whether email or password was wrong (prevents user enumeration)
     */
    public function login()
    {
        // Redirect logged-in users to home (prevent duplicate login)
        if (isset($_SESSION['user'])) {
            header('Location: index.php?url=home');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // ?? operator: use value from $_POST if it exists, otherwise empty string
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            // Find user by email; if not found, $user will be false
            $user = $this->userModel->findByEmail($email);

            // password_verify() uses bcrypt to safely compare plaintext password vs hashed password
            // Why bcrypt? Designed to be slow (prevents brute-force attacks), auto-salts, one-way encryption
            // Returns true if password matches, false otherwise
            if (!$user || !password_verify($password, $user['u_password'])) {
                // Generic error message (don't reveal which field was incorrect - security best practice)
                $errors['login'] = "Invalid credentials.";
                require_once __DIR__ . '/../views/auth/login.php';
                return;
            }

            // Successful login: store minimal user data in session
            // Session persists across requests (server-side storage, client gets cookie with session ID)
            // Session destroyed on logout or timeout (see logout() method)
            $_SESSION['user'] = [
                'u_id' => $user['u_id'],
                'u_username' => $user['u_name'],
                'u_email' => $user['u_email'],
                'u_avatar' => $user['u_avatar'] ?? null
            ];

            // Redirect: header() sends HTTP Location header, exit() prevents further code execution
            header('Location: index.php?url=home');
            exit;
        }

        // GET request: display login form
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
     * 
     * Security notes:
     * - password_hash() with PASSWORD_DEFAULT uses bcrypt (auto-salting, one-way)
     * - Validation occurs before database operations (fail fast)
     * - Duplicate checks prevent user enumeration (errors are specific to field)
     */
    public function register()
    {
        // Redirect logged-in users to home (prevent duplicate registration)
        if (isset($_SESSION['user'])) {
            header('Location: index.php?url=home');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Extract form data using null coalescing operator (default to empty string)
            $data = [
                'username' => $_POST['username'] ?? '',
                'email' => $_POST['email'] ?? '',
                'password' => $_POST['password'] ?? '',
                'confirm_password' => $_POST['confirm_password'] ?? ''
            ];

            // Validate input (User model contains validation logic)
            // Returns associative array of field => error message
            $errors = $this->userModel->validateRegister($data);

            // Handle profile picture upload (optional)
            $avatarFilename = null;
            if (!empty($_FILES['profile_picture']['name'])) {
                // uploadAvatar() handles MIME validation, size limits, and unique naming
                $uploadResult = $this->uploadAvatar($_FILES['profile_picture']);
                if ($uploadResult['success']) {
                    $avatarFilename = $uploadResult['filename'];
                } else {
                    // Add file upload error to existing validation errors
                    $errors['avatar'] = $uploadResult['error'];
                }
            }

            // Proceed only if no validation errors occurred
            if (empty($errors)) {
                // password_hash() with PASSWORD_DEFAULT:
                // - Uses bcrypt algorithm (current best practice)
                // - Auto-generates random salt (stored in hash string)
                // - One-way: cannot reverse hash to get password
                // - Result is 60-char string: $2y$10$[salt][hash]
                $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

                // Attempt to create user in database
                $result = $this->userModel->create($data['username'], $data['email'], $hashedPassword, $avatarFilename);
                
                // Handle different return values from User->create()
                if ($result === true) {
                    // Success: Get the new user's ID to create default collection
                    $newUser = $this->userModel->findByEmail($data['email']);
                    
                    if ($newUser) {
                        // Create default "To Print" collection for new user
                        $this->collectionModel->createDefaultCollection($newUser['u_id']);
                    }
                    
                    // Redirect to login page
                    header('Location: index.php?url=login');
                    exit;
                } elseif ($result === 'username') {
                    // Duplicate username (database unique constraint violation)
                    $errors['username'] = "This username isn't available";
                } elseif ($result === 'email') {
                    // Duplicate email (database unique constraint violation)
                    $errors['email'] = "This email is already in use";
                } else {
                    // Unexpected error: log for debugging, show generic error to user
                    // error_log() writes to PHP error log (don't expose to user)
                    error_log("Registration failed with result: " . print_r($result, true));
                    $errors['server'] = "An error occurred, please try again later";
                }
            }

            // Preserve old values to repopulate form after validation errors
            // Don't preserve password (security best practice)
            $old = [
                'username' => $data['username'],
                'email' => $data['email']
            ];
        }

        // Display registration form (with errors and old values if POST failed)
        require_once __DIR__ . '/../views/auth/register.php';
    }

    /**
     * Log out the current user by destroying session data.
     * 
     * session_unset() removes all session variables ($_SESSION['user'], etc.)
     * session_destroy() deletes the session file on server
     * Client's session cookie expires automatically
     */
    public function logout()
    {
        session_unset();   // Clear all $_SESSION variables
        session_destroy(); // Delete session file from server

        header('Location: index.php?url=home');
        exit;
    }

    /**
     * Display profile edit form and handle username/email updates.
     * 
     * Process:
     * 1. Verify user is logged in (ensureAuthenticated)
     * 2. Load current user data from database
     * 3. On POST: validate changes, handle avatar upload
     * 4. Update database if valid
     * 5. Refresh session data with new values
     */
    public function editProfile()
    {
        $this->ensureAuthenticated();

        $userId = $_SESSION['user']['u_id'];
        
        // Fetch fresh user data from database (don't trust session data for edits)
        $user = $this->userModel->findById($userId);

        if (!$user) {
            // User ID in session doesn't exist in database (shouldn't happen)
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

    /**
     * Upload avatar using centralized FileUploadService
     * 
     * Delegates to FileUploadService for consistent security and validation.
     * All avatar uploads follow same pattern: validate MIME type, generate unique name, save.
     * 
     * @param array $file The $_FILES['avatar'] array
     * @return array Result ['success' => bool, 'error' => string|null, 'filename' => string|null]
     */
    private function uploadAvatar($file): array
    {
        $result = $this->fileUploadService->upload($file, 'avatars');
        
        // Convert error messages back to French for consistency with existing code
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
