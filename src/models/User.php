<?php

namespace App\Models;

use App\Models\Database;
use PDO;
use PDOException;

/**
 * User Model
 * Handles all database operations for user accounts: registration, login, profile management.
 * 
 * Key responsibilities:
 * - User registration: Validate and create new accounts
 * - User lookup: Find by email, username, or ID
 * - Profile updates: Change username, email, password, avatar
 * - Validation: Check for duplicates, validate input format
 * 
 * Security features:
 * - Password hashing (done in controller, not model)
 * - Prepared statements prevent SQL injection
 * - Unique constraints on email and username at database level
 * - Static methods for existence checks without instantiation
 */
class User
{
    // Property to hold the PDO database connection
    private $db;

    /**
     * Constructor: Initialize the User model with a database connection.
     * 
     * This follows the Dependency Injection pattern:
     * - We create a Database instance here (tight coupling)
     * - The Database instance provides a PDO connection via getConnection()
     * - We store the PDO connection in $this->db for all User methods to use
     * 
     * All methods in this class use $this->db to execute SQL queries.
     */
    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Create a new user account in the database.
     * 
     * Parameters:
     * - $username: Unique username (checked for uniqueness by database constraint)
     * - $email: Unique email address (checked for uniqueness by database constraint)
     * - $password: Plain text password (should be hashed before calling this method)
     * - $avatar: Optional avatar filename (default: null)
     * 
     * Return values:
     * - true: User created successfully
     * - 'username': Username already exists (duplicate key error on u_name column)
     * - 'email': Email already exists (duplicate key error on u_email column)
     * - 'server': Generic database error
     * 
     * How it works:
     * 1. Build SQL INSERT statement with placeholders (:name becomes :username, etc.)
     * 2. Prepare the statement (send to database for parsing before execution)
     * 3. Execute with bound parameters (replace :username with actual value)
     * 4. If duplicate key error (code 23000), return which field caused conflict
     * 5. If other error occurs, return 'server' error code
     */
    public function create($username, $email, $password, $avatar = null)
    {
        try {
            // SQL INSERT: Add new row to users table with provided data
            // Placeholders (:username, :email, etc.) are replaced during execute()
            $sql = "INSERT INTO users (u_name, u_email, u_password, u_avatar, u_created_at)
                    VALUES (:username, :email, :password, :avatar, NOW())";
            
            // Prepare the statement: sends SQL to database for parsing/validation
            // This happens BEFORE values are inserted (more secure)
            $stmt = $this->db->prepare($sql);
            
            // Execute: Replace placeholders with actual values and run the query
            // Using an array of placeholder => value pairs
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password' => $password,
                ':avatar' => $avatar
            ]);
            
            // If we reach here, no exception was thrown, so insertion succeeded
            return true;
        } catch (PDOException $e) {
            // Catch database errors (SQL errors, constraint violations, etc.)
            // Log the full error for debugging (includes database message and error code)
            error_log("Registration error: " . $e->getMessage() . " Code: " . $e->getCode());
            
            // Error code 23000 = Integrity Constraint Violation
            // This usually means a UNIQUE or PRIMARY KEY constraint was violated
            if ($e->getCode() == 23000) {
                // Check which column caused the duplicate key error
                // If 'u_name' appears in the error message, username is duplicate
                if (strpos($e->getMessage(), 'u_name') !== false) {
                    return 'username';
                } 
                // If 'u_email' appears in the error message, email is duplicate
                elseif (strpos($e->getMessage(), 'u_email') !== false) {
                    return 'email';
                }
            }
            // For all other database errors, return generic 'server' error
            return 'server';
        }
    }

    /**
     * Find a user account by email address.
     * 
     * How prepared statements work:
     * 1. Prepare: Send the SQL structure to database BEFORE adding values
     *    This prevents SQL injection (malicious code can't hide in the SQL structure)
     * 2. Execute: Replace the placeholder (:email) with the actual email value
     * 3. Fetch: Get the result as an associative array
     * 
     * Return values:
     * - Array of user data if found: ['u_id' => 1, 'u_name' => 'john', 'u_email' => 'john@example.com', ...]
     * - false if no user found with that email
     * 
     * @param string $email The email address to search for
     * @return array|false The user record or false if not found
     */
    public function findByEmail($email)
    {
        $sql = "SELECT * FROM users WHERE u_email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Find a user account by their unique ID number.
     * 
     * This is the fastest lookup because u_id is the PRIMARY KEY (indexed by database).
     * PRIMARY KEY means: each user has a unique ID, database optimizes searches on this column.
     * 
     * @param int $id The user ID to search for
     * @return array|false The user record or false if not found
     */
    public function findById($id)
    {
        $sql = "SELECT * FROM users WHERE u_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Find a user account by their username.
     * 
     * Case sensitivity: The database uses COLLATE utf8mb4_unicode_ci,
     * which means 'John' and 'john' are treated as the same (case-insensitive).
     * 
     * @param string $username The username to search for
     * @return array|false The user record or false if not found
     */
    public function findByUsername($username)
    {
        $sql = "SELECT * FROM users WHERE u_name = :username";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':username' => $username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Check if an email address already exists in the database.
     * 
     * This is a STATIC method, meaning:
     * - You can call it without creating a User instance: User::checkMail('test@example.com')
     * - It creates its own temporary database connection
     * - Useful for quick existence checks during validation
     * 
     * SELECT 1: We only need to know IF the record exists, not what data it has,
     * so we select the number 1 (minimal data transfer)
     * 
     * LIMIT 1: Stop searching after finding the first match (optimization)
     * 
     * @param string $email The email to check
     * @return bool true if email exists, false if not found or error
     */
    public static function checkMail(string $email): bool
    {
        try {
            $db = (new Database())->getConnection();
            $sql = 'SELECT 1 FROM users WHERE u_email = :email LIMIT 1';
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchColumn() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Check if a username already exists in the database.
     * 
     * Similar to checkMail(), this is a static existence check.
     * Usage: User::checkUsername('john123')
     * 
     * Note: bindValue() and bindParam() both work similarly:
     * - bindValue(name, value, type): Bind a specific value
     * - Both ensure the value is properly escaped and type-checked
     * 
     * @param string $username The username to check
     * @return bool true if username exists, false if not found or error
     */
    public static function checkUsername(string $username): bool
    {
        try {
            $db = (new Database())->getConnection();
            $sql = 'SELECT 1 FROM users WHERE u_name = :username LIMIT 1';
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchColumn() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Update a user's profile information.
     * 
     * This demonstrates DYNAMIC SQL BUILDING:
     * - We only update the fields that were provided (not empty)
     * - Build the SET clause dynamically: 'u_name = :username, u_email = :email'
     * - Use implode() to join multiple updates with commas
     * 
     * The isset() check for avatar is special:
     * - isset(): true if key exists AND value is not null
     * - empty(): true if key doesn't exist, is null, is empty string, is 0, is false
     * - For avatar, we use isset() to allow empty string (delete avatar)
     * 
     * Examples:
     * - updateProfile(1, ['username' => 'john'])
     *   Results in: UPDATE users SET u_name = 'john' WHERE u_id = 1
     * - updateProfile(1, ['username' => 'john', 'email' => 'john@example.com'])
     *   Results in: UPDATE users SET u_name = 'john', u_email = 'john@example.com' WHERE u_id = 1
     * 
     * @param int $id The user ID to update
     * @param array $data Array with keys 'username', 'email', 'avatar' (all optional)
     * @return bool true on success, false on error
     */
    public function updateProfile($id, array $data)
    {
        try {
            // Initialize updates array to hold the SET clause pieces
            $updates = [];
            // Initialize params array with the user ID for the WHERE clause
            $params = [':id' => $id];

            // Guard clause: If username is provided and not empty, add update
            if (!empty($data['username'])) {
                $updates[] = 'u_name = :username';
                $params[':username'] = $data['username'];
            }

            // Guard clause: If email is provided and not empty, add update
            if (!empty($data['email'])) {
                $updates[] = 'u_email = :email';
                $params[':email'] = $data['email'];
            }

            // Special case: Avatar can be set to empty string (to delete it)
            // so we use isset() instead of empty()
            if (isset($data['avatar'])) {
                $updates[] = 'u_avatar = :avatar';
                // Convert empty string to null (database storage)
                $params[':avatar'] = $data['avatar'] === '' ? null : $data['avatar'];
            }

            // Guard clause: If no fields to update, return success (nothing to do)
            if (empty($updates)) {
                return true;
            }

            // Build dynamic UPDATE statement
            // Example: "UPDATE users SET u_name = :username, u_email = :email WHERE u_id = :id"
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE u_id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Change a user's password.
     * 
     * IMPORTANT SECURITY NOTE:
     * The password should be hashed BEFORE calling this method.
     * This method only stores what you give it.
     * Controller should use password_hash($password, PASSWORD_DEFAULT) first.
     * 
     * @param int $id The user ID
     * @param string $newPassword The HASHED password (already processed with password_hash())
     * @return bool true on success, false on error
     */
    public function changePassword($id, $newPassword)
    {
        try {
            $sql = "UPDATE users SET u_password = :password WHERE u_id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':password' => $newPassword,
                ':id' => $id
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Validate user registration input data.
     * 
     * This method checks all registration fields and returns an array of errors.
     * If the array is empty, all validation passed.
     * 
     * Validation includes:
     * - Username: Required, at least 3 characters, unique in database
     * - Email: Required, valid email format, unique in database
     * - Password: Required, at least 8 characters
     * - Password confirmation: Required, must match password
     * 
     * The ?? operator (null coalescing):
     * - Returns the left side if it exists and is not null
     * - Otherwise returns the right side
     * - Example: $data['username'] ?? '' means "use username if it exists, otherwise use empty string"
     * 
     * Pattern: This method demonstrates GUARD CLAUSES
     * - Each if statement checks one condition
     * - If condition fails, add error and continue to next field
     * - This way, users see ALL errors at once, not just the first one
     * 
     * @param array $data Array with keys 'username', 'email', 'password', 'confirm_password'
     * @return array Array of errors (empty if validation passed)
     *               Example: ['username' => 'Username too short', 'email' => 'Email already in use']
     */
    public function validateRegister(array $data): array
    {
        $errors = [];

        // Guard: Check username exists and has minimum length
        $username = trim($data['username'] ?? '');
        if ($username === '') {
            $errors['username'] = 'Username is required';
        } elseif (strlen($username) < 3) {
            $errors['username'] = 'Username must be at least 3 characters long';
        } elseif (self::checkUsername($username)) {
            // Guard: Check username is unique in database
            $errors['username'] = "This username isn't available";
        }

        // Guard: Check email exists and is valid format
        $email = trim($data['email'] ?? '');
        if ($email === '') {
            $errors['email'] = 'Email address is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // filter_var() with FILTER_VALIDATE_EMAIL checks if string looks like a valid email
            $errors['email'] = 'Please enter a valid email address';
        } elseif (self::checkMail($email)) {
            // Guard: Check email is unique in database
            $errors['email'] = 'This email is already in use';
        }

        // Guard: Check password exists and has minimum length
        $password = $data['password'] ?? '';
        if ($password === '') {
            $errors['password'] = 'Password is required';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long';
        }

        // Guard: Check confirmation password exists and matches
        $confirm_password = $data['confirm_password'] ?? '';
        if ($confirm_password === '') {
            $errors['confirm_password'] = 'Password confirmation is required';
        } elseif ($confirm_password !== $password) {
            // !== is "not identical" (different value OR different type)
            $errors['confirm_password'] = 'Passwords do not match';
        }

        return $errors;
    }

    /**
     * Validate user profile update input data.
     * 
     * Similar to validateRegister(), but different rules:
     * - Fields are optional (user might only update username, not email)
     * - Must check if username/email is unique (unless user is keeping their current one)
     * 
     * The $currentUserId is used for this comparison:
     * If the user tries to change username to 'john', we check if 'john' exists.
     * If 'john' already exists but belongs to them ($user['u_id'] == $currentUserId),
     * that's okay (they're keeping the same username).
     * 
     * @param array $data Array with optional keys 'username', 'email' (can have either, both, or neither)
     * @param int $currentUserId The ID of the user being updated (for uniqueness checks)
     * @return array Array of errors (empty if validation passed)
     */
    public function validateProfileUpdate(array $data, $currentUserId): array
    {
        $errors = [];

        // Guard: If username is provided, validate it
        if (!empty($data['username'])) {
            $username = trim($data['username']);
            // Guard: Check minimum length
            if (strlen($username) < 3) {
                $errors['username'] = 'Username too short (minimum 3 characters)';
            } else {
                // Guard: Check username doesn't already exist (unless it's the current user's own username)
                $user = $this->findByUsername($username);
                if ($user && $user['u_id'] != $currentUserId) {
                    // != checks for value equality (type coercion allowed)
                    // So $user['u_id'] != $currentUserId checks if they're different users
                    $errors['username'] = 'Username already in use';
                }
            }
        }

        // Guard: If email is provided, validate it
        if (!empty($data['email'])) {
            $email = trim($data['email']);
            // Guard: Check email format is valid
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email address';
            } else {
                // Guard: Check email doesn't already exist (unless it's the current user's own email)
                $user = $this->findByEmail($email);
                if ($user && $user['u_id'] != $currentUserId) {
                    $errors['email'] = 'Email already in use';
                }
            }
        }

        return $errors;
    }
}
