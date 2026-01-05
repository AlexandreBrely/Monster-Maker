<?php

namespace App\Models;

use App\Models\Database;
use PDO;
use PDOException;

/**
 * User Model
 * Handles all user-related database operations.
 * 
 * ORGANIZATION:
 * 1. Constructor
 * 2. CRUD Operations (Create, Read)
 * 3. Lookup Methods (Find by email/ID/username)
 * 4. Profile & Security (Update, Password)
 * 5. Validation
 * 6. Static Helpers
 */
class User
{
    private $db;
    const AVATAR_UPLOAD_PATH = __DIR__ . '/../../public/uploads/avatars/';

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // ===================================================================
    // SECTION 1: CRUD OPERATIONS
    // ===================================================================

    /**
     * CREATE - Register new user account.
     * Returns true on success, or 'username'/'email' on uniqueness conflicts.
     */
    public function create($username, $email, $hashedPassword, $avatarFilename = null)
    {
        try {
            if (self::checkUsername($username)) {
                return 'username';
            }
            if (self::checkMail($email)) {
                return 'email';
            }

            $sql = "INSERT INTO users (u_name, u_email, u_password, u_avatar, u_created_at)
                    VALUES (:name, :email, :password, :avatar, NOW())";
            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                ':name' => $username,
                ':email' => $email,
                ':password' => $hashedPassword,
                ':avatar' => $avatarFilename
            ]);

            return true;
        } catch (PDOException $e) {
            error_log("User creation error: " . $e->getMessage());
            return false;
        }
    }

    // ===================================================================
    // SECTION 2: LOOKUP METHODS
    // ===================================================================

    /**
     * Find user by email (for login).
     */
    public function findByEmail($email): array|false
    {
        try {
            $sql = "SELECT * FROM users WHERE email = :email";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':email' => $email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding user by email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Find user by ID (for session validation).
     */
    public function findById($id): array|false
    {
        try {
            $sql = "SELECT * FROM users WHERE u_id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding user by ID: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Find user by username (for profile lookup).
     */
    public function findByUsername($username): array|false
    {
        try {
            $sql = "SELECT u_id, username, email, avatar, bio, created_at 
                    FROM users 
                    WHERE username = :username";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':username' => $username]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding user by username: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Search users by username (partial match).
     */
    public function searchByUsername($query, $limit = 10): array
    {
        try {
            $sql = "SELECT u_id, username, avatar 
                    FROM users 
                    WHERE username LIKE :query 
                    ORDER BY username 
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error searching users: " . $e->getMessage());
            return [];
        }
    }

    // ===================================================================
    // SECTION 3: PROFILE & SECURITY
    // ===================================================================

    /**
     * UPDATE - Modify user profile (username, email, bio, avatar).
     */
    public function updateProfile($userId, $data): bool
    {
        try {
            $updates = [];
            $params = [':userId' => $userId];

            if (isset($data['username'])) {
                $updates[] = 'username = :username';
                $params[':username'] = $data['username'];
            }

            if (isset($data['email'])) {
                $updates[] = 'email = :email';
                $params[':email'] = $data['email'];
            }

            if (isset($data['bio'])) {
                $updates[] = 'bio = :bio';
                $params[':bio'] = $data['bio'];
            }

            if (isset($data['avatar'])) {
                $updates[] = 'avatar = :avatar';
                $params[':avatar'] = $data['avatar'];
            }

            if (empty($updates)) {
                return true;
            }

            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE u_id = :userId";
            $stmt = $this->db->prepare($sql);
            
            return (bool) $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error updating profile: " . $e->getMessage());
            return false;
        }
    }

    /**
     * UPDATE - Change user password.
     */
    public function changePassword($userId, $newPassword): bool
    {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $sql = "UPDATE users SET password = :password WHERE u_id = :userId";
            $stmt = $this->db->prepare($sql);
            
            return (bool) $stmt->execute([
                ':password' => $hashedPassword,
                ':userId' => $userId
            ]);
        } catch (PDOException $e) {
            error_log("Error changing password: " . $e->getMessage());
            return false;
        }
    }

    // ===================================================================
    // SECTION 4: VALIDATION
    // ===================================================================

    /**
     * Validate registration data (returns array of errors).
     */
    public function validateRegister(array $data): array
    {
        $username = $data['username'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';

        $errors = [];

        if (empty(trim($username))) {
            $errors['username'] = 'Username is required';
        } elseif (strlen($username) < 3) {
            $errors['username'] = 'Username must be at least 3 characters';
        } elseif (strlen($username) > 50) {
            $errors['username'] = 'Username must be less than 50 characters';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors['username'] = 'Username can only contain letters, numbers, and underscores';
        } elseif ($this->checkUsername($username)) {
            $errors['username'] = 'Username already taken';
        }

        if (empty(trim($email))) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        } elseif ($this->checkMail($email)) {
            $errors['email'] = 'Email already registered';
        }

        if (empty($password)) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }

        if ($password !== $confirmPassword) {
            $errors['confirm_password'] = 'Passwords do not match';
        }

        return $errors;
    }

    /**
     * Validate profile update data.
     */
    public function validateProfileUpdate(array $data, $userId): array
    {
        $username = $data['username'] ?? '';
        $email = $data['email'] ?? '';
        $errors = [];

        if (!empty($username)) {
            if (strlen($username) < 3) {
                $errors['username'] = 'Username must be at least 3 characters';
            } elseif (strlen($username) > 50) {
                $errors['username'] = 'Username must be less than 50 characters';
            } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                $errors['username'] = 'Username can only contain letters, numbers, and underscores';
            } else {
                $existingUser = $this->findByUsername($username);
                if ($existingUser && $existingUser['u_id'] != $userId) {
                    $errors['username'] = 'Username already taken';
                }
            }
        }

        if (!empty($email)) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            } else {
                $existingUser = $this->findByEmail($email);
                if ($existingUser && $existingUser['u_id'] != $userId) {
                    $errors['email'] = 'Email already registered';
                }
            }
        }

        return $errors;
    }

    // ===================================================================
    // SECTION 5: STATIC HELPERS
    // ===================================================================

    /**
     * Check if email already exists in database.
     */
    public static function checkMail($email): bool
    {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
            $stmt = $db->prepare($sql);
            $stmt->execute([':email' => $email]);
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error checking email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if username already exists in database.
     */
    public static function checkUsername($username): bool
    {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $sql = "SELECT COUNT(*) FROM users WHERE username = :username";
            $stmt = $db->prepare($sql);
            $stmt->execute([':username' => $username]);
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error checking username: " . $e->getMessage());
            return false;
        }
    }
}
