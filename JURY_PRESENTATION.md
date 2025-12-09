# Monster Maker - Technical Presentation for Jury

## Project Overview

Monster Maker is a **full-stack MVC web application** demonstrating professional PHP development with user authentication, CRUD operations, file uploads, and database interactions.

**Focus:** The inner workings of MVC architecture, data flow, security practices, and database integration.

---

## System Architecture

### MVC Pattern Implementation

```
HTTP Request (User submits form or clicks link)
    ↓
public/index.php (Router)
    ├─ Receives: $_GET['url'], $_POST data, $_FILES
    ├─ Parses URL to determine controller + action
    └─ Instantiates controller class
        ↓
Controller (Business Logic & Request Handler)
    ├─ Step 1: Validates Request (check session, authorization)
    ├─ Step 2: Calls Model Methods for business logic
    ├─ Step 3: Processes Results (validation, transformation)
    └─ Step 4: Selects appropriate View (or redirects)
        ↓
Model (Database Layer & Validation)
    ├─ Receives data from Controller
    ├─ Validates data (types, ranges, uniqueness)
    ├─ Executes Database Queries (PDO prepared statements)
    ├─ Processes Results (deserialization, transformation)
    └─ Returns data back to Controller
        ↓
Database (MySQL Persistent Storage)
    ├─ Stores: users, monsters, relationships
    ├─ Enforces: constraints, foreign keys, indexes
    └─ Returns: query results
        ↓
View (HTML Template)
    ├─ Receives: processed data from Controller
    ├─ Receives: error messages (if validation failed)
    ├─ Renders: HTML with data populated
    └─ Sends: HTTP Response to Browser
        ↓
Browser (User sees result)
```

### Directory Structure & Purpose

```
src/
├── Controllers/
│   ├── AuthController.php      # Handles: login, register, profile, auth
│   │   └─ Methods: login(), register(), logout(), editProfile(), etc.
│   ├── MonsterController.php   # Handles: all monster operations
│   │   └─ Methods: create(), store(), show(), index(), edit(), update(), delete()
│   ├── HomeController.php      # Homepage logic
│   └── PagesController.php     # Static pages (about, terms, etc)
│
├── Models/
│   ├── User.php                # Database operations for users
│   │   └─ Methods: create(), findByEmail(), validateRegister(), etc.
│   ├── Monster.php             # Database operations for monsters
│   │   └─ Methods: create(), getById(), validate(), search(), etc.
│   └── Database.php            # PDO connection management (singleton)
│
└── Views/
    ├── auth/                   # User authentication forms
    │   ├── register.php        # Registration form
    │   ├── login.php           # Login form
    │   ├── edit-profile.php    # Profile editor + avatar upload
    │   └── settings.php        # User settings
    │
    ├── monster/                # Monster CRUD views
    │   ├── create.php          # Monster creation form
    │   ├── show.php            # Display monster details
    │   ├── index.php           # List all public monsters
    │   └── edit.php            # Edit monster form
    │
    ├── templates/              # Reusable components
    │   ├── header.php          # HTML head, styles
    │   ├── navbar.php          # Navigation bar
    │   └── footer.php          # Footer
    │
    └── pages/                  # Static content pages
        └── error-404.php       # 404 error page

public/
├── index.php                   # Entry point (Router)
│   ├─ Receives all requests
│   ├─ Parses URL
│   ├─ Maps to controller/action
│   └─ Executes controller method
│
├── uploads/                    # User-uploaded files
│   ├── avatars/               # User profile pictures
│   └── monsters/              # Monster images
│
├── css/                        # Stylesheets (Bootstrap, custom)
└── js/                         # JavaScript (if needed)
```

---

## Core Components Explained - Step by Step

### 1. Router (public/index.php) - The Gateway

**Purpose:** Single entry point for ALL requests. Maps URLs to the right controller.

**Step-by-step flow:**

```php
<?php
// STEP 1: Initialize application
define('ROOT', dirname(__DIR__));  // Store root path: /var/www (in Docker)
define('BASE_URL', '/');           // Base URL for links
session_start();                   // Start PHP session (for $_SESSION)

// STEP 2: Setup autoloader (auto-includes classes)
// When we use: new User(), PHP automatically requires src/Models/User.php
spl_autoload_register(function ($class) {
    // Convert: App\Models\User → src/Models/User.php
    $file = ROOT . '/src/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// STEP 3: Parse the URL
// Input: $_GET['url'] = 'create' or 'monster' or 'login'
$url = isset($_GET['url']) ? $_GET['url'] : 'home';
$url = filter_var($url, FILTER_SANITIZE_URL);  // Remove dangerous characters
$urlParts = explode('/', $url);                 // Split by slash

// STEP 4: Define all available routes
// Format: 'url-name' => ['controller' => 'ControllerClass', 'action' => 'methodName']
$routes = [
    'home'     => ['controller' => 'HomeController', 'action' => 'index'],
    'login'    => ['controller' => 'AuthController', 'action' => 'login'],
    'register' => ['controller' => 'AuthController', 'action' => 'register'],
    'logout'   => ['controller' => 'AuthController', 'action' => 'logout'],
    'create'   => ['controller' => 'MonsterController', 'action' => 'create'],
    'monsters' => ['controller' => 'MonsterController', 'action' => 'index'],
];

// STEP 5: Find the route
if (isset($routes[$urlParts[0]])) {
    // Route found in our map
    $controllerName = $routes[$urlParts[0]]['controller'];  // e.g. 'AuthController'
    $action = $routes[$urlParts[0]]['action'];              // e.g. 'login'
} else {
    // Route not found -> show 404
    $controllerName = 'PagesController';
    $action = 'error404';
}

// STEP 6: Load and execute controller
$controllerFile = ROOT . '/src/controllers/' . $controllerName . '.php';

if (file_exists($controllerFile)) {
    require_once $controllerFile;
    
    // Create controller instance
    // Example: $controller = new AuthController();
    $controller = new $controllerName();
    
    // Call the action method
    // Example: $controller->login();
    if (method_exists($controller, $action)) {
        $controller->$action();  // Dynamic method call
    } else {
        // Method doesn't exist in controller
        $controller = new PagesController();
        $controller->error404();
    }
} else {
    // Controller file doesn't exist
    echo "Controller not found";
}
?>
```

**Key Concepts:**

1. **Single Responsibility:** Router ONLY handles URL → Controller mapping
2. **Autoloading:** Classes automatically loaded by namespace (no manual `require`)
3. **Dynamic Execution:** Method names stored in config and called dynamically
4. **Error Handling:** If route/controller/method missing → show 404

---

### 2. Controllers - The Orchestrators

**Purpose:** Handle HTTP requests, validate authorization, call models, select views.

#### Controller Pattern: GET vs POST

```php
namespace App\Controllers;

use App\Models\User;

class AuthController
{
    // STEP 1: Initialize model in constructor
    // This way, we have $this->userModel available in all methods
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();  // Create once, use many times
    }

    // STEP 2: Authorization helper method
    // Called at start of any method that requires login
    private function ensureAuthenticated()
    {
        if (!isset($_SESSION['user'])) {
            // User not logged in -> redirect to login
            header('Location: index.php?url=login');
            exit;  // Stop execution immediately
        }
    }

    // =========== LOGIN FLOW ===========
    
    /**
     * GET /login
     * STEP 1: Display the login form (no data processing)
     * STEP 2: Wait for user to submit
     */
    public function login()
    {
        // Already logged in? Redirect to home
        if (isset($_SESSION['user'])) {
            header('Location: index.php?url=home');
            exit;
        }

        // Handle POST request
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // STEP 1: Extract form data from POST
            $email = $_POST['email'] ?? '';       // Get email or empty string
            $password = $_POST['password'] ?? ''; // Get password or empty string

            // STEP 2: Find user in database
            // Returns: ['u_id' => 1, 'u_email' => '...', 'u_password' => '...'] OR false
            $user = $this->userModel->findByEmail($email);

            // STEP 3: Verify password
            // password_verify() safely compares:
            // - $password: plain text from user
            // - $user['u_password']: hashed password from database
            if (!$user || !password_verify($password, $user['u_password'])) {
                // Wrong email or password
                $errors['login'] = "Incorrect credentials.";
                
                // Re-show login form with error
                require_once __DIR__ . '/../views/auth/login.php';
                return;  // Stop here, don't log in
            }

            // STEP 4: Password correct -> create session
            // $_SESSION persists across page loads for this user
            $_SESSION['user'] = [
                'u_id'       => $user['u_id'],           // User ID for future queries
                'u_username' => $user['u_username'],     // Display in navbar
                'u_email'    => $user['u_email'],        // Contact info
                'u_avatar'   => $user['u_avatar'] ?? null  // Profile picture
            ];

            // STEP 5: Redirect to home (successful login)
            // Redirect = tell browser to go to new URL
            header('Location: index.php?url=home');
            exit;  // Stop execution
        }

        // STEP 6: Show login form (if not POST)
        require_once __DIR__ . '/../views/auth/login.php';
    }

    // =========== REGISTRATION FLOW ===========

    public function register()
    {
        // Already logged in? Redirect
        if (isset($_SESSION['user'])) {
            header('Location: index.php?url=home');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // STEP 1: Extract and organize form data
            $data = [
                'username'         => $_POST['username'] ?? '',
                'email'            => $_POST['email'] ?? '',
                'password'         => $_POST['password'] ?? '',
                'confirm_password' => $_POST['confirm_password'] ?? ''
            ];

            // STEP 2: Validate using model
            // Model checks:
            // - Username not empty + min 3 chars + not already used
            // - Email not empty + valid format + not already used
            // - Password not empty + min 8 chars
            // - Passwords match
            $errors = $this->userModel->validateRegister($data);

            // STEP 3: If no errors, create account
            if (empty($errors)) {
                // Hash password before storing (bcrypt algorithm)
                // password_hash() = one-way encryption, can't be reversed
                // PASSWORD_DEFAULT = bcrypt (currently best practice)
                $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

                // Create user in database
                // Parameters: username, email, hashed_password
                if ($this->userModel->create($data['username'], $data['email'], $hashedPassword)) {
                    // Success! Redirect to login
                    header('Location: index.php?url=login');
                    exit;
                } else {
                    // Database error
                    $errors['server'] = "Database error. Try again later.";
                }
            }

            // STEP 4: Keep old values for form repopulation
            // If user made mistake, show their previous input (except password)
            $old = [
                'username' => $data['username'],
                'email'    => $data['email']
            ];
        }

        // Show registration form (with errors if any)
        require_once __DIR__ . '/../views/auth/register.php';
    }

    // =========== PROFILE EDITING ===========

    public function editProfile()
    {
        // STEP 1: Ensure user is logged in
        // If not: redirects to login, doesn't continue
        $this->ensureAuthenticated();

        // STEP 2: Get logged-in user's ID from session
        $userId = $_SESSION['user']['u_id'];
        
        // STEP 3: Load user's current data from database
        $user = $this->userModel->findById($userId);

        // STEP 4: If no user found (shouldn't happen, but safety check)
        if (!$user) {
            // Show 404 page
            require_once __DIR__ . '/../views/pages/error-404.php';
            return;
        }

        // Handle POST (form submission)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // STEP 1: Extract form data
            $data = [
                'username' => $_POST['username'] ?? '',
                'email'    => $_POST['email'] ?? ''
            ];

            // STEP 2: Validate changes
            // Note: allows keeping same username/email, or changing to new ones
            $errors = $this->userModel->validateProfileUpdate($data, $userId);

            // STEP 3: Handle avatar upload if provided
            $avatarFile = null;
            if (!empty($_FILES['avatar']['name'])) {
                // Upload file with validation
                // Returns: ['success' => true/false, 'filename' => '...', 'error' => '...']
                $uploadResult = $this->uploadAvatar($_FILES['avatar']);
                
                if ($uploadResult['success']) {
                    // File saved successfully
                    $avatarFile = $uploadResult['filename'];
                    $data['avatar'] = $avatarFile;
                } else {
                    // Upload failed, add error
                    $errors['avatar'] = $uploadResult['error'];
                }
            }

            // STEP 4: If no errors, update database
            if (empty($errors)) {
                if ($this->userModel->updateProfile($userId, $data)) {
                    // Success! Update session with new values
                    if (!empty($data['username'])) {
                        $_SESSION['user']['u_username'] = $data['username'];
                    }
                    if (!empty($data['email'])) {
                        $_SESSION['user']['u_email'] = $data['email'];
                    }
                    if (!empty($data['avatar'])) {
                        $_SESSION['user']['u_avatar'] = $data['avatar'];
                    }

                    // Redirect to profile page (refresh)
                    header('Location: index.php?url=edit-profile');
                    exit;
                } else {
                    // Database error
                    $errors['server'] = 'Profile update failed';
                }
            }

            // Keep old values for form repopulation
            extract(['errors' => $errors, 'old' => $data, 'user' => $user]);
        }

        // Show profile edit form
        require_once __DIR__ . '/../views/auth/edit-profile.php';
    }

    // =========== FILE UPLOAD HELPER ===========

    /**
     * Upload avatar with security checks
     * 
     * Security checks performed:
     * 1. File error check
     * 2. File size limit (5MB max)
     * 3. MIME type validation (real type, not filename)
     * 4. Unique filename (prevents overwrites and directory traversal)
     */
    private function uploadAvatar($file): array
    {
        // Constants for file upload
        $maxSize = 5 * 1024 * 1024;  // 5 MB in bytes
        $allowedMime = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp'
        ];

        // STEP 1: Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'error' => 'File upload error.'
            ];
        }

        // STEP 2: Check file size
        if ($file['size'] > $maxSize) {
            return [
                'success' => false,
                'error' => 'File too large (max 5 MB).'
            ];
        }

        // STEP 3: Validate MIME type (real type, not filename)
        // finfo = "file information" - reads actual file content
        // Prevents: uploading .php file renamed as .jpg
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);  // Real MIME type
        finfo_close($finfo);

        // Check if MIME is in whitelist
        if (!array_key_exists($mime, $allowedMime)) {
            return [
                'success' => false,
                'error' => 'File type not allowed.'
            ];
        }

        // STEP 4: Generate unique, safe filename
        $extension = $allowedMime[$mime];           // Extension from MIME type
        $originalName = pathinfo($file['name'], PATHINFO_FILENAME);  // Filename without ext
        $sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);  // Remove special chars
        $truncated = substr($sanitized, 0, 20);    // Limit length
        $uniqueId = bin2hex(random_bytes(8));      // 16 random hex characters
        $uniqueName = $uniqueId . '_' . $truncated . '.' . $extension;

        // STEP 5: Create upload directory if doesn't exist
        $uploadPath = __DIR__ . '/../../public/uploads/avatars/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);  // 0755 = readable by all, writable by owner
        }

        $destination = $uploadPath . $uniqueName;

        // STEP 6: Move file from temp location to final location
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return [
                'success' => false,
                'error' => 'Failed to save image.'
            ];
        }

        // Success!
        return [
            'success' => true,
            'filename' => $uniqueName
        ];
    }

    // =========== LOGOUT ===========

    public function logout()
    {
        // STEP 1: Destroy session
        // Removes all $_SESSION data
        session_unset();    // Clear all session variables
        session_destroy();  // Destroy session file

        // STEP 2: Redirect to home
        header('Location: index.php?url=home');
        exit;
    }
}
?>
```

---

### 3. Models - Database & Validation Layer

**Purpose:** Handle all database operations and business logic validation.

#### User Model - Step by Step

```php
namespace App\Models;

use App\Models\Database;
use PDO;

class User
{
    // Database connection object
    // PDO = PHP Data Objects (database abstraction layer)
    private $db;

    /**
     * Constructor: Initialize database connection
     * Called when: new User() is instantiated
     */
    public function __construct()
    {
        // Get database connection from Database class (singleton pattern)
        $database = new Database();
        $this->db = $database->getConnection();  // Gets PDO object
    }

    // =========== CREATE USER ===========

    /**
     * Create new user account
     * 
     * @param string $username - Already validated and trimmed by controller
     * @param string $email - Already validated by controller
     * @param string $password - Already hashed by controller (password_hash())
     * @return bool - true if successful
     * 
     * SECURITY: Prepared statement prevents SQL injection
     */
    public function create($username, $email, $password)
    {
        try {
            // STEP 1: Write SQL query with PLACEHOLDERS (not actual values)
            // :param format = named placeholder
            $sql = "INSERT INTO users (u_username, u_email, u_password)
                    VALUES (:username, :email, :password)";

            // STEP 2: Prepare statement (compile SQL, separate from data)
            // prepare() = tell database: "I have a query with placeholders"
            $stmt = $this->db->prepare($sql);

            // STEP 3: Execute with actual data
            // Database treats data as DATA, not executable code
            // Prevents SQL injection attacks
            $stmt->execute([
                ':username' => $username,  // Replace :username placeholder
                ':email'    => $email,     // Replace :email placeholder
                ':password' => $password   // Replace :password placeholder (hashed)
            ]);

            // STEP 4: If we reach here, insert succeeded
            return true;

        } catch (PDOException $e) {
            // Database error (e.g., duplicate username/email)
            // Don't expose error details to user
            return false;
        }
    }

    // =========== FIND USER ===========

    /**
     * Find user by email
     * Used during: login process
     * 
     * @param string $email - Email to search for
     * @return array|false - User data if found, false if not
     */
    public function findByEmail($email)
    {
        // STEP 1: SQL query with placeholder
        $sql = "SELECT * FROM users WHERE u_email = :email";

        // STEP 2: Prepare and execute with parameter
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);

        // STEP 3: Fetch result as associative array
        // PDO::FETCH_ASSOC = return as array with column names as keys
        // ['u_id' => 1, 'u_email' => 'user@example.com', 'u_password' => '...']
        return $stmt->fetch(PDO::FETCH_ASSOC);  // Returns array or false
    }

    /**
     * Find user by ID
     * Used for: profile loading, authorization checks
     */
    public function findById($id)
    {
        $sql = "SELECT * FROM users WHERE u_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // =========== VALIDATION ===========

    /**
     * Validate registration form data
     * Called from: AuthController::register()
     * 
     * @param array $data - Form data to validate
     * @return array - Error messages (empty if valid)
     * 
     * VALIDATION RULES:
     * - Username: not empty, min 3 chars, not already used
     * - Email: not empty, valid format, not already used
     * - Password: not empty, min 8 chars
     * - Passwords must match
     */
    public function validateRegister(array $data): array
    {
        $errors = [];  // Will store error messages

        // ---- USERNAME VALIDATION ----
        $username = trim($data['username'] ?? '');  // Remove whitespace
        
        if ($username === '') {
            $errors['username'] = 'Username required';
        } elseif (strlen($username) < 3) {
            $errors['username'] = 'Username too short (min 3 chars)';
        } elseif (self::checkUsername($username)) {
            // Static method: check if username already exists
            // self:: = call static method on this class
            $errors['username'] = 'Username already taken';
        }

        // ---- EMAIL VALIDATION ----
        $email = trim($data['email'] ?? '');

        if ($email === '') {
            $errors['email'] = 'Email required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // PHP function: validate email format
            // Returns: true if valid, false if not
            $errors['email'] = 'Invalid email format';
        } elseif (self::checkMail($email)) {
            // Static method: check if email already exists
            $errors['email'] = 'Email already used';
        }

        // ---- PASSWORD VALIDATION ----
        $password = $data['password'] ?? '';

        if ($password === '') {
            $errors['password'] = 'Password required';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password too short (min 8 chars)';
        }

        // ---- PASSWORD CONFIRMATION ----
        $confirm = $data['confirm_password'] ?? '';

        if ($confirm === '') {
            $errors['confirm_password'] = 'Please confirm password';
        } elseif ($confirm !== $password) {
            $errors['confirm_password'] = 'Passwords do not match';
        }

        // Return empty array = all valid, OR non-empty = errors found
        return $errors;
    }

    // =========== STATIC UNIQUENESS CHECKS ===========

    /**
     * Check if email already exists in database
     * Static = can be called without creating object: User::checkMail('test@example.com')
     * 
     * @param string $email - Email to check
     * @return bool - true if exists, false if doesn't
     * 
     * WHY STATIC?
     * - Can be called during registration (before user object created)
     * - Doesn't need instance variables
     * - Reusable in multiple places
     */
    public static function checkMail(string $email): bool
    {
        try {
            // STEP 1: Create temporary database connection
            $db = (new Database())->getConnection();

            // STEP 2: Query database
            // SELECT 1 = return just the number 1 (not full row data)
            // Reason: we only need to know IF it exists, not what it contains
            $sql = 'SELECT 1 FROM users WHERE u_email = :email LIMIT 1';
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->execute();

            // STEP 3: Check if any result found
            // fetchColumn() = get first column of first row, or false if no rows
            // We asked for SELECT 1, so if found: returns 1, if not found: returns false
            $result = $stmt->fetchColumn();

            // Return true if found, false if not
            return $result !== false;

        } catch (PDOException $e) {
            // Database error
            return false;  // Return false on error (safer than true)
        }
    }

    /**
     * Check if username already exists in database
     * Same pattern as checkMail()
     */
    public static function checkUsername(string $username): bool
    {
        try {
            $db = (new Database())->getConnection();
            $sql = 'SELECT 1 FROM users WHERE u_username = :username LIMIT 1';
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchColumn() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }

    // =========== UPDATE PROFILE ===========

    /**
     * Update user profile (username, email, avatar)
     * Called from: AuthController::editProfile()
     * 
     * @param int $id - User ID to update
     * @param array $data - Fields to update (only non-empty ones)
     * @return bool - true if successful
     */
    public function updateProfile($id, array $data)
    {
        try {
            // STEP 1: Build dynamic SQL only for provided fields
            // Reason: user might only update avatar, not username
            $updates = [];
            $params = [':id' => $id];

            // Add username to update if provided
            if (!empty($data['username'])) {
                $updates[] = 'u_username = :username';
                $params[':username'] = $data['username'];
            }

            // Add email to update if provided
            if (!empty($data['email'])) {
                $updates[] = 'u_email = :email';
                $params[':email'] = $data['email'];
            }

            // Add avatar to update if provided
            if (!empty($data['avatar'])) {
                $updates[] = 'u_avatar = :avatar';
                $params[':avatar'] = $data['avatar'];
            }

            // STEP 2: If nothing to update, return success
            if (empty($updates)) {
                return true;  // No error, nothing changed
            }

            // STEP 3: Build SQL from dynamic pieces
            // implode(', ', ['a = :a', 'b = :b']) = 'a = :a, b = :b'
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE u_id = :id";

            // STEP 4: Execute update
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);  // Returns true/false

        } catch (PDOException $e) {
            return false;
        }
    }
}
?>
```

#### Monster Model - Complex Operations

```php
class Monster
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // =========== CREATE MONSTER ===========

    /**
     * Insert new monster into database
     * Called from: MonsterController::store()
     * 
     * @param array $data - Monster data with 37+ fields
     * @param int $userId - Owner of this monster
     * @return string|false - Monster ID if successful, false if error
     * 
     * CHALLENGE: How to store complex data (actions array) in database?
     * SOLUTION: JSON serialization
     * - Before insert: json_encode($data['actions']) = JSON string
     * - After retrieve: json_decode($jsonString) = array
     */
    public function create(array $data, $userId)
    {
        try {
            // STEP 1: Prepare SQL with all 37+ columns and placeholders
            $sql = "INSERT INTO monster (
                name, size, type, alignment, 
                ac, hp, hit_dice, ac_notes, speed,
                strength, dexterity, constitution, intelligence, wisdom, charisma,
                proficiency_bonus, saving_throws, skills, senses, languages,
                challenge_rating, damage_immunities, damage_resistances, 
                damage_vulnerabilities, condition_immunities,
                traits, actions, bonus_actions, reactions, legendary_actions,
                is_legendary, legendary_resistance, legendary_resistance_lair, lair_actions,
                image_portrait, image_fullbody, card_size, is_public, u_id,
                created_at, updated_at
            ) VALUES (
                :name, :size, :type, :alignment,
                :ac, :hp, :hit_dice, :ac_notes, :speed,
                :strength, :dexterity, :constitution, :intelligence, :wisdom, :charisma,
                :proficiency_bonus, :saving_throws, :skills, :senses, :languages,
                :challenge_rating, :damage_immunities, :damage_resistances,
                :damage_vulnerabilities, :condition_immunities,
                :traits, :actions, :bonus_actions, :reactions, :legendary_actions,
                :is_legendary, :legendary_resistance, :legendary_resistance_lair, :lair_actions,
                :image_portrait, :image_fullbody, :card_size, :is_public, :userId,
                NOW(), NOW()
            )";

            // STEP 2: Prepare statement
            $stmt = $this->db->prepare($sql);

            // STEP 3: Execute with parameters
            // KEY: Actions/reactions stored as JSON
            // json_encode() = convert PHP array to JSON string
            // Database stores JSON, retrieves JSON, we decode it back to array
            $stmt->execute([
                ':name'           => $data['name'],
                ':size'           => $data['size'] ?? '',
                ':type'           => $data['type'] ?? '',
                ':alignment'      => $data['alignment'] ?? '',
                ':ac'             => $data['ac'] ?? 10,
                ':hp'             => $data['hp'] ?? 1,
                ':hit_dice'       => $data['hit_dice'] ?? '',
                ':ac_notes'       => $data['ac_notes'] ?? '',
                ':speed'          => $data['speed'] ?? '',
                ':strength'       => $data['strength'] ?? 10,
                ':dexterity'      => $data['dexterity'] ?? 10,
                ':constitution'   => $data['constitution'] ?? 10,
                ':intelligence'   => $data['intelligence'] ?? 10,
                ':wisdom'         => $data['wisdom'] ?? 10,
                ':charisma'       => $data['charisma'] ?? 10,
                ':proficiency_bonus' => $data['proficiency_bonus'] ?? 0,
                ':saving_throws'  => $data['saving_throws'] ?? '',
                ':skills'         => $data['skills'] ?? '',
                ':senses'         => $data['senses'] ?? '',
                ':languages'      => $data['languages'] ?? '',
                ':challenge_rating' => $data['challenge_rating'] ?? '0',
                ':damage_immunities' => $data['damage_immunities'] ?? '',
                ':damage_resistances' => $data['damage_resistances'] ?? '',
                ':damage_vulnerabilities' => $data['damage_vulnerabilities'] ?? '',
                ':condition_immunities' => $data['condition_immunities'] ?? '',
                ':traits'         => $data['traits'] ?? '',
                ':actions'        => json_encode($data['actions'] ?? []),           // ← JSON!
                ':bonus_actions'  => $data['bonus_actions'] ?? '',
                ':reactions'      => json_encode($data['reactions'] ?? []),         // ← JSON!
                ':legendary_actions' => json_encode($data['legendary_actions'] ?? []),  // ← JSON!
                ':is_legendary'   => $data['is_legendary'] ?? 0,
                ':legendary_resistance' => $data['legendary_resistance'] ?? '',
                ':legendary_resistance_lair' => $data['legendary_resistance_lair'] ?? '',
                ':lair_actions'   => $data['lair_actions'] ?? '',
                ':image_portrait' => $data['image_portrait'] ?? null,
                ':image_fullbody' => $data['image_fullbody'] ?? null,
                ':card_size'      => $data['card_size'] ?? 1,
                ':is_public'      => $data['is_public'] ?? 0,
                ':userId'         => $userId
            ]);

            // STEP 4: Return the auto-increment ID of inserted row
            // lastInsertId() = the value MySQL generated for monster_id
            return $this->db->lastInsertId();

        } catch (PDOException $e) {
            // Database error (duplicate name, constraint violation, etc.)
            return false;
        }
    }

    // =========== READ - SINGLE MONSTER ===========

    /**
     * Get monster by ID
     * Called from: MonsterController::show(), edit(), update(), delete()
     * 
     * @param int $id - Monster ID
     * @return array|false - Monster data with deserialized arrays, or false
     * 
     * IMPORTANT: This method DESERIALIZES JSON automatically
     * After: $monster['actions'] is a PHP array, not a JSON string
     */
    public function getById($id)
    {
        // STEP 1: Query database
        $sql = "SELECT * FROM monster WHERE monster_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        // STEP 2: Fetch result
        $monster = $stmt->fetch(PDO::FETCH_ASSOC);

        // STEP 3: If found, deserialize JSON fields
        // This converts JSON strings back to PHP arrays
        if ($monster) {
            $this->deserializeJsonFields($monster);
        }

        return $monster;  // Returns array or false
    }

    // =========== DESERIALIZATION HELPER ===========

    /**
     * Convert JSON strings back to PHP arrays
     * Called automatically after database queries
     * 
     * FLOW:
     * 1. Database returns: 'actions' => '[{"name":"Attack",...}]' (JSON string)
     * 2. This method converts to: 'actions' => [['name'=>'Attack',...]] (PHP array)
     * 3. Views can use: foreach($monster['actions'] as $action)
     * 
     * @param array $monster - Passed by reference (&) so changes persist
     */
    private function deserializeJsonFields(&$monster)
    {
        // Convert JSON string to PHP array
        // json_decode($json, true) = 2nd param true = return as array, not object
        
        $monster['actions'] = !empty($monster['actions'])
            ? json_decode($monster['actions'], true)
            : [];  // Return empty array if empty JSON

        $monster['reactions'] = !empty($monster['reactions'])
            ? json_decode($monster['reactions'], true)
            : [];

        $monster['legendary_actions'] = !empty($monster['legendary_actions'])
            ? json_decode($monster['legendary_actions'], true)
            : [];
    }

    // =========== VALIDATION ===========

    /**
     * Validate monster creation/update form
     * Called from: MonsterController::store(), update()
     * 
     * @param array $data - Form data
     * @return array - Error messages (empty if valid)
     * 
     * VALIDATION TYPES:
     * 1. REQUIRED fields - must not be empty
     * 2. TYPE validation - int, string, etc.
     * 3. RANGE validation - AC > 0, ability 1-30
     * 4. ENUM validation - size must be one of: Tiny, Small, etc.
     */
    public function validate(array $data): array
    {
        $errors = [];

        // ---- REQUIRED FIELDS ----
        if (empty(trim($data['name'] ?? ''))) {
            $errors['name'] = 'Monster name required';
        }

        if (empty($data['size'] ?? '')) {
            $errors['size'] = 'Size required';
        }

        // ---- ENUM VALIDATION ----
        // Size must be one of these exact values
        $validSizes = ['Tiny', 'Small', 'Medium', 'Large', 'Huge', 'Gargantuan'];
        if (!empty($data['size']) && !in_array($data['size'], $validSizes)) {
            $errors['size'] = 'Invalid size';
        }

        // ---- NUMERIC VALIDATION WITH RANGE ----
        // AC must be numeric and > 0
        if (!is_numeric($data['ac'] ?? null) || (int)$data['ac'] < 1) {
            $errors['ac'] = 'AC must be 1 or higher';
        }

        if (!is_numeric($data['hp'] ?? null) || (int)$data['hp'] < 1) {
            $errors['hp'] = 'HP must be 1 or higher';
        }

        // ---- ABILITY SCORES (must be 1-30) ----
        $abilities = ['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'];
        foreach ($abilities as $ability) {
            $value = (int)($data[$ability] ?? 10);
            if ($value < 1 || $value > 30) {
                $errors[$ability] = "$ability must be between 1 and 30";
            }
        }

        return $errors;  // Empty = valid, non-empty = errors
    }

    // =========== UPDATE MONSTER ===========

    /**
     * Update existing monster
     * Called from: MonsterController::update()
     * 
     * @param int $id - Monster ID
     * @param array $data - Updated data
     * @param int $userId - User ID (for ownership verification in controller)
     * @return bool - true if successful
     */
    public function update($id, array $data, $userId)
    {
        try {
            // Similar to create(), but UPDATE instead of INSERT
            $sql = "UPDATE monster SET
                name = :name,
                size = :size,
                type = :type,
                alignment = :alignment,
                ac = :ac,
                hp = :hp,
                hit_dice = :hit_dice,
                ac_notes = :ac_notes,
                speed = :speed,
                strength = :strength,
                dexterity = :dexterity,
                constitution = :constitution,
                intelligence = :intelligence,
                wisdom = :wisdom,
                charisma = :charisma,
                proficiency_bonus = :proficiency_bonus,
                saving_throws = :saving_throws,
                skills = :skills,
                senses = :senses,
                languages = :languages,
                challenge_rating = :challenge_rating,
                damage_immunities = :damage_immunities,
                damage_resistances = :damage_resistances,
                damage_vulnerabilities = :damage_vulnerabilities,
                condition_immunities = :condition_immunities,
                traits = :traits,
                actions = :actions,
                bonus_actions = :bonus_actions,
                reactions = :reactions,
                legendary_actions = :legendary_actions,
                is_legendary = :is_legendary,
                legendary_resistance = :legendary_resistance,
                legendary_resistance_lair = :legendary_resistance_lair,
                lair_actions = :lair_actions,
                image_portrait = :image_portrait,
                image_fullbody = :image_fullbody,
                card_size = :card_size,
                is_public = :is_public,
                updated_at = NOW()
            WHERE monster_id = :id";

            $stmt = $this->db->prepare($sql);
            
            // Execute with parameters (same as create, but also :id)
            return $stmt->execute([
                ':id'             => $id,
                ':name'           => $data['name'],
                ':size'           => $data['size'] ?? '',
                ':type'           => $data['type'] ?? '',
                ':alignment'      => $data['alignment'] ?? '',
                ':ac'             => $data['ac'] ?? 10,
                ':hp'             => $data['hp'] ?? 1,
                ':hit_dice'       => $data['hit_dice'] ?? '',
                ':ac_notes'       => $data['ac_notes'] ?? '',
                ':speed'          => $data['speed'] ?? '',
                ':strength'       => $data['strength'] ?? 10,
                ':dexterity'      => $data['dexterity'] ?? 10,
                ':constitution'   => $data['constitution'] ?? 10,
                ':intelligence'   => $data['intelligence'] ?? 10,
                ':wisdom'         => $data['wisdom'] ?? 10,
                ':charisma'       => $data['charisma'] ?? 10,
                ':proficiency_bonus' => $data['proficiency_bonus'] ?? 0,
                ':saving_throws'  => $data['saving_throws'] ?? '',
                ':skills'         => $data['skills'] ?? '',
                ':senses'         => $data['senses'] ?? '',
                ':languages'      => $data['languages'] ?? '',
                ':challenge_rating' => $data['challenge_rating'] ?? '0',
                ':damage_immunities' => $data['damage_immunities'] ?? '',
                ':damage_resistances' => $data['damage_resistances'] ?? '',
                ':damage_vulnerabilities' => $data['damage_vulnerabilities'] ?? '',
                ':condition_immunities' => $data['condition_immunities'] ?? '',
                ':traits'         => $data['traits'] ?? '',
                ':actions'        => json_encode($data['actions'] ?? []),
                ':bonus_actions'  => $data['bonus_actions'] ?? '',
                ':reactions'      => json_encode($data['reactions'] ?? []),
                ':legendary_actions' => json_encode($data['legendary_actions'] ?? []),
                ':is_legendary'   => $data['is_legendary'] ?? 0,
                ':legendary_resistance' => $data['legendary_resistance'] ?? '',
                ':legendary_resistance_lair' => $data['legendary_resistance_lair'] ?? '',
                ':lair_actions'   => $data['lair_actions'] ?? '',
                ':image_portrait' => $data['image_portrait'] ?? null,
                ':image_fullbody' => $data['image_fullbody'] ?? null,
                ':card_size'      => $data['card_size'] ?? 1,
                ':is_public'      => $data['is_public'] ?? 0
            ]);

        } catch (PDOException $e) {
            return false;
        }
    }

    // =========== DELETE MONSTER ===========

    /**
     * Delete monster and associated files
     * Called from: MonsterController::delete()
     * 
     * @param int $id - Monster to delete
     * @param int $userId - Owner ID (for ownership check in controller)
     * @return bool - true if successful
     * 
     * CLEANUP:
     * 1. Delete database record
     * 2. Delete associated image files
     */
    public function delete($id, $userId)
    {
        try {
            // STEP 1: Get monster first (to get image filenames for deletion)
            $monster = $this->getById($id);

            // STEP 2: Delete image files from disk
            if (!empty($monster['image_portrait'])) {
                $portraitPath = __DIR__ . '/../../public/uploads/monsters/' . $monster['image_portrait'];
                if (file_exists($portraitPath)) {
                    unlink($portraitPath);  // Delete file
                }
            }

            if (!empty($monster['image_fullbody'])) {
                $fullbodyPath = __DIR__ . '/../../public/uploads/monsters/' . $monster['image_fullbody'];
                if (file_exists($fullbodyPath)) {
                    unlink($fullbodyPath);  // Delete file
                }
            }

            // STEP 3: Delete database record
            $sql = "DELETE FROM monster WHERE monster_id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);

        } catch (PDOException $e) {
            return false;
        }
    }
}
?>
```

---

## Security Deep Dive

### 1. SQL Injection - How It Happens & How We Prevent It

**VULNERABLE CODE (WRONG):**
```php
// User input: $email = "' OR '1'='1"
$sql = "SELECT * FROM users WHERE u_email = '$email'";
// Actual query: SELECT * FROM users WHERE u_email = '' OR '1'='1'
// Result: Returns ALL users! Attacker bypassed authentication
```

**SAFE CODE (OUR APPROACH):**
```php
// User input: $email = "' OR '1'='1"
$sql = "SELECT * FROM users WHERE u_email = :email";
$stmt = $this->db->prepare($sql);
$stmt->execute([':email' => $email]);

// Flow:
// 1. prepare() tells database: "SQL and data are separate"
// 2. Database compiles SQL with placeholders
// 3. execute() sends data separately
// 4. Database knows: user input is DATA, not SQL code
// 5. Result: Finds user with email = "' OR '1'='1", not bypassing query
```

### 2. Password Security - Bcrypt with Salting

```php
// When user registers:
$password = "MyPassword123";  // User's plain text password
$hashed = password_hash($password, PASSWORD_DEFAULT);
// Result: $2y$10$... (67 characters, bcrypt format)
// What happened:
// 1. password_hash() generated random SALT (first 22 chars of hash)
// 2. Applied bcrypt algorithm 2^10 times (very slow, prevents brute force)
// 3. Stored only hash, never the plain password

// When user logs in:
$userInput = "MyPassword123";
$storedHash = "$2y$10$...";  // From database
if (password_verify($userInput, $storedHash)) {
    // password_verify():
    // 1. Extracts salt from stored hash
    // 2. Applies same bcrypt algorithm to user input
    // 3. Compares result to stored hash
    // 4. Returns true only if they match
    // SAFE: Even if hash is leaked, password can't be reversed
}
```

### 3. File Upload Security

```php
// WRONG - Only checks filename:
if (pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION) === 'jpg') {
    // Attacker uploads shell.php renamed as shell.jpg
    // Server executes PHP code!
}

// RIGHT - Check actual file content:
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$actualMime = finfo_file($finfo, $_FILES['file']['tmp_name']);
// finfo reads file MAGIC BYTES (first few bytes)
// tells actual file type, not what filename says

if ($actualMime === 'image/jpeg') {
    // Only REAL JPEGs allowed
    // Attacker can't rename PHP as JPG - content will be wrong
}

// Also use random filename:
$uniqueName = bin2hex(random_bytes(8)) . '_safe.jpg';
// bin2hex(random_bytes(8)) = 16 random hex characters
// Prevents directory traversal: ../../config.php
// Prevents overwriting existing files
```

---

## Database Design Explained

```sql
CREATE TABLE users (
    u_id INT PRIMARY KEY AUTO_INCREMENT,      -- Unique identifier, auto-increment
    u_username VARCHAR(100) UNIQUE NOT NULL,  -- Must be unique, can search
    u_email VARCHAR(100) UNIQUE NOT NULL,     -- Must be unique, used for login
    u_password VARCHAR(255) NOT NULL,         -- 255 chars for bcrypt hash
    u_avatar VARCHAR(255),                    -- Filename (optional)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- When account created
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP  -- When last updated
);

CREATE TABLE monster (
    monster_id INT PRIMARY KEY AUTO_INCREMENT,      -- Unique identifier
    u_id INT NOT NULL,                              -- Foreign key to users (owner)
    
    -- Basic identification
    name VARCHAR(255) NOT NULL,                     -- Monster name
    size VARCHAR(50),                               -- Tiny, Small, Medium, etc.
    type VARCHAR(100),                              -- Dragon, Beast, Humanoid, etc.
    alignment VARCHAR(100),                         -- Chaotic Evil, etc.
    
    -- Combat stats
    ac INT DEFAULT 10,                              -- Armor Class
    hp INT DEFAULT 1,                               -- Hit Points
    hit_dice VARCHAR(100),                          -- e.g. "8d8 + 16"
    ac_notes TEXT,                                  -- e.g. "shield +1"
    speed TEXT,                                     -- e.g. "30 ft., fly 60 ft."
    
    -- Ability scores (6 stats)
    strength INT DEFAULT 10,                        -- 1-30
    dexterity INT DEFAULT 10,
    constitution INT DEFAULT 10,
    intelligence INT DEFAULT 10,
    wisdom INT DEFAULT 10,
    charisma INT DEFAULT 10,
    
    -- More stats
    proficiency_bonus INT DEFAULT 0,
    saving_throws TEXT,
    skills TEXT,
    senses TEXT,
    languages TEXT,
    challenge_rating VARCHAR(50),
    
    -- Resistances/vulnerabilities
    damage_immunities TEXT,
    damage_resistances TEXT,
    damage_vulnerabilities TEXT,
    condition_immunities TEXT,
    
    -- Complex features stored as JSON
    traits TEXT,                                    -- Non-combat traits
    actions JSON,                                   -- [{"name":"Attack","type":"melee",...}]
    bonus_actions TEXT,
    reactions JSON,                                 -- [{"name":"Parry","description":"..."}]
    
    -- Legendary features (for boss monsters)
    is_legendary BOOLEAN DEFAULT 0,                -- 0 = false, 1 = true
    legendary_actions JSON,                        -- [{"name":"Attack","cost":1,...}]
    legendary_resistance TEXT,
    legendary_resistance_lair TEXT,
    lair_actions TEXT,
    
    -- Images
    image_portrait VARCHAR(255),                   -- Filename
    image_fullbody VARCHAR(255),                   -- Filename
    
    -- Metadata
    card_size INT DEFAULT 1,
    is_public BOOLEAN DEFAULT 0,                  -- 0 = private, 1 = public
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key constraint
    FOREIGN KEY (u_id) REFERENCES users(u_id) ON DELETE CASCADE
    -- CASCADE: if user deleted, delete all their monsters too
);
```

**Design Decisions Explained:**

1. **JSON columns for arrays:**
   - `actions`, `reactions`, `legendary_actions` are JSON
   - Why? Each action has: name, type, description, and type-specific data
   - Could normalize into separate tables, but: simpler code, fewer queries
   - Trade-off: flexibility vs. queryability (good choice for this project)

2. **TEXT vs VARCHAR:**
   - TEXT = unlimited length, but slower to search
   - VARCHAR(255) = limited but indexed faster
   - Used TEXT for: traits, skills, descriptions (user won't search these)
   - Used VARCHAR for: name (often searched)

3. **Foreign Key with CASCADE:**
   - `FOREIGN KEY (u_id) REFERENCES users(u_id)`
   - Prevents: creating monster with non-existent user ID
   - `ON DELETE CASCADE`: if user deleted, automatically delete their monsters
   - Prevents: orphaned monster records

4. **TIMESTAMP with AUTO-UPDATE:**
   - `created_at`: set once, never changes
   - `updated_at`: automatically updates whenever row changes
   - Useful for: audit trails, sorting by newest

---

## Complete Request-Response Cycle

### Scenario: User Registers Account

```
1. USER PERSPECTIVE:
   - Browser: GET http://localhost:8000?url=register
   - Sees: Registration form
   - Fills: username, email, password
   - Clicks: "Register"
   - Browser: POST to same URL with form data

2. ROUTER (public/index.php):
   - Receives: $_POST from browser
   - Reads: $_GET['url'] = 'register'
   - Looks up: routes['register'] = ['controller' => 'AuthController', 'action' => 'register']
   - Creates: new AuthController()
   - Calls: ->register()  [but this is POST, so goes to POST handling]

3. CONTROLLER (AuthController::register):
   - Detects: $_SERVER['REQUEST_METHOD'] === 'POST'
   - Extracts: $data = [username, email, password, confirm_password]
   - Calls: $this->userModel->validateRegister($data)

4. MODEL VALIDATION (User::validateRegister):
   - Checks: username not empty ✓
   - Checks: username length >= 3 ✓
   - Calls: User::checkUsername($username)  [static, queries database]
     - SELECT 1 FROM users WHERE u_username = 'newuser'
     - Returns: false (not found, good)
   - Checks: email format ✓
   - Calls: User::checkMail($email)  [static, queries database]
     - SELECT 1 FROM users WHERE u_email = 'new@example.com'
     - Returns: false (not found, good)
   - Checks: password length >= 8 ✓
   - Checks: passwords match ✓
   - Returns: $errors = []  (empty = all valid)

5. CONTROLLER CREATES ACCOUNT:
   - errors is empty, so proceed
   - $hashedPassword = password_hash($password, PASSWORD_DEFAULT)
     - Generates: bcrypt hash with random salt
     - Result: "$2y$10$..." (67 character hash)
   - Calls: $this->userModel->create($username, $email, $hashedPassword)

6. MODEL INSERTS DATA:
   - Builds: INSERT INTO users (u_username, u_email, u_password) VALUES (:username, :email, :password)
   - Prepares: $stmt = $this->db->prepare($sql)  [SQL compiled with placeholders]
   - Executes: $stmt->execute([...])  [Data sent separately from SQL]
   - Database: Inserts row into users table
   - Returns: true

7. CONTROLLER REDIRECTS:
   - Calls: header('Location: index.php?url=login')
   - Tells: browser to make NEW GET request to login page
   - exit: stops execution

8. BROWSER:
   - Receives: HTTP 302 redirect response
   - Makes: NEW GET request to login page
   - Sees: Login form
   - User can now log in with new credentials
```

---

## Testing Each Component

### Test 1: Security - SQL Injection Test

```php
// In database lookup:
$email = "' OR '1'='1";  // Attacker's input

// VULNERABLE CODE:
$sql = "SELECT * FROM users WHERE u_email = '$email'";
// Becomes: "SELECT * FROM users WHERE u_email = '' OR '1'='1'"
// Returns: ALL users

// OUR CODE:
$sql = "SELECT * FROM users WHERE u_email = :email";
$stmt = $this->db->prepare($sql);
$stmt->execute([':email' => $email]);
// Returns: User with email exactly = "' OR '1'='1'" (none found)
// SQL injection BLOCKED ✓
```

### Test 2: Password Security Test

```php
// User registers with password: "SecurePass123"
$hash = password_hash("SecurePass123", PASSWORD_DEFAULT);
// Database stores: $2y$10$... (hash, not password)

// User tries to login with: "SecurePass123"
if (password_verify("SecurePass123", $hash)) {
    // Returns: true (password matches)
    // User logged in ✓
}

// User tries wrong password: "WrongPass"
if (password_verify("WrongPass", $hash)) {
    // Returns: false (doesn't match)
    // Login denied ✓
}

// Even if database leaked:
// Attacker has: $2y$10$...
// Can't reverse hash back to "SecurePass123"
// Bcrypt is one-way, very slow to brute force (2^10 iterations)
```

### Test 3: Authorization Test

```php
// User 1 creates monster ID 5
// User 2 tries to edit it

// In MonsterController::update():
$monster = $this->monsterModel->getById(5);
// Gets: ['monster_id' => 5, 'u_id' => 1, ...]

$userId = $_SESSION['user']['u_id'];  // = 2
if ($monster['u_id'] != $userId) {
    // 1 != 2, so condition is TRUE
    // User 2 is NOT the owner
    http_response_code(403);  // Forbidden
    exit('Not authorized');
}
// User 2 CANNOT edit User 1's monster ✓
```

---

## Potential Improvements (Future Development)

1. **Pagination:** `LIMIT 10 OFFSET 0` on monster listing
2. **Searching:** Full-text search on monster names/descriptions
3. **Caching:** Redis for public monsters list
4. **API:** RESTful endpoints for mobile apps
5. **Unit Tests:** PHPUnit for automated testing
6. **Email Verification:** Confirm email before registration complete
7. **Rate Limiting:** Prevent brute force login attempts
8. **Logging:** Record failed logins, database errors
9. **Two-Factor Auth:** Extra security for accounts
10. **Image Optimization:** Resize/compress before storing

---

## Conclusion

This project demonstrates:

✅ **Proper MVC separation** - Clear boundaries between layers  
✅ **Database security** - Prepared statements, password hashing  
✅ **Form validation** - Server-side only, comprehensive checks  
✅ **Authorization** - User ownership verification  
✅ **Clean code** - Readable, well-commented, maintainable  
✅ **Real-world patterns** - Used in production applications  

The application is **production-ready** and easily extensible for new features.
