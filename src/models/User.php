<?php

namespace App\Models;

use App\Models\Database;
use PDO;
use PDOException;

/**
 * User Model
 * Gère les opérations CRUD pour les utilisateurs et les profils
 * 
 * Inclut :
 * - Création de compte utilisateur
 * - Authentification (connexion)
 * - Gestion de profil (username, email, avatar)
 * - Vérification d'unicité (email, username)
 */
class User
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Crée un nouvel utilisateur
     * 
     * Param :
     * - $username : nom d'utilisateur unique
     * - $email : email unique
     * - $password : mot de passe (sera hashé)
     * 
     * Retour :
     * - true si succès
     * - false si erreur
     */
    public function create($username, $email, $password)
    {
        try {
            $sql = "INSERT INTO users (u_name, u_email, u_password)
                    VALUES (:username, :email, :password)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password' => $password
            ]);
            return true;
        } catch (PDOException $e) {
            // Log the actual error for debugging
            error_log("Registration error: " . $e->getMessage() . " Code: " . $e->getCode());
            
            // Check error code for duplicate entry
            if ($e->getCode() == 23000) {
                // Duplicate key error
                if (strpos($e->getMessage(), 'u_name') !== false) {
                    return 'username'; // Username already exists
                } elseif (strpos($e->getMessage(), 'u_email') !== false) {
                    return 'email'; // Email already exists
                }
            }
            return 'server'; // Generic error
        }
    }

    /**
     * Récupère un utilisateur par son email
     * 
     * Param :
     * - $email : adresse email
     * 
     * Retour :
     * - array : données utilisateur si trouvé
     * - false : si non trouvé
     */
    public function findByEmail($email)
    {
        $sql = "SELECT * FROM users WHERE u_email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère un utilisateur par son ID
     * 
     * Param :
     * - $id : identifiant utilisateur
     * 
     * Retour :
     * - array : données utilisateur
     * - false : si non trouvé
     */
    public function findById($id)
    {
        $sql = "SELECT * FROM users WHERE u_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère un utilisateur par son username
     * 
     * Param :
     * - $username : nom d'utilisateur
     * 
     * Retour :
     * - array : données utilisateur
     * - false : si non trouvé
     */
    public function findByUsername($username)
    {
        $sql = "SELECT * FROM users WHERE u_name = :username";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':username' => $username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Vérifie si un email existe déjà
     * 
     * Param :
     * - $email : adresse email à vérifier
     * 
     * Retour :
     * - true : email existe
     * - false : email n'existe pas
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
     * Vérifie si un username existe déjà
     * 
     * Param :
     * - $username : nom d'utilisateur à vérifier
     * 
     * Retour :
     * - true : username existe
     * - false : username n'existe pas
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
     * Met à jour le profil utilisateur
     * 
     * Param :
     * - $id : identifiant utilisateur
     * - $data : tableau avec 'username', 'email', 'avatar' (optionnel)
     * 
     * Retour :
     * - true : succès
     * - false : erreur
     */
    public function updateProfile($id, array $data)
    {
        try {
            $updates = [];
            $params = [':id' => $id];

            if (!empty($data['username'])) {
                $updates[] = 'u_name = :username';
                $params[':username'] = $data['username'];
            }

            if (!empty($data['email'])) {
                $updates[] = 'u_email = :email';
                $params[':email'] = $data['email'];
            }

            if (!empty($data['avatar'])) {
                $updates[] = 'u_avatar = :avatar';
                $params[':avatar'] = $data['avatar'];
            }

            if (empty($updates)) {
                return true; // Rien à mettre à jour
            }

            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE u_id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Change le mot de passe d'un utilisateur
     * 
     * Param :
     * - $id : identifiant utilisateur
     * - $newPassword : nouveau mot de passe hashé
     * 
     * Retour :
     * - true : succès
     * - false : erreur
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
     * Valide les données d'inscription
     * 
     * Param :
     * - $data : tableau avec 'username', 'email', 'password', 'confirm_password'
     * 
     * Retour :
     * - array : tableau des erreurs (vide si valide)
     */
    public function validateRegister(array $data): array
    {
        $errors = [];

        // 🔹 Username validation
        $username = trim($data['username'] ?? '');
        if ($username === '') {
            $errors['username'] = 'Username is required';
        } elseif (strlen($username) < 3) {
            $errors['username'] = 'Username must be at least 3 characters long';
        } elseif (self::checkUsername($username)) {
            $errors['username'] = "This username isn't available";
        }

        // 🔹 Email validation
        $email = trim($data['email'] ?? '');
        if ($email === '') {
            $errors['email'] = 'Email address is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        } elseif (self::checkMail($email)) {
            $errors['email'] = 'This email is already in use';
        }

        // 🔹 Password validation
        $password = $data['password'] ?? '';
        if ($password === '') {
            $errors['password'] = 'Password is required';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long';
        }

        // 🔹 Password confirmation validation
        $confirm_password = $data['confirm_password'] ?? '';
        if ($confirm_password === '') {
            $errors['confirm_password'] = 'Password confirmation is required';
        } elseif ($confirm_password !== $password) {
            $errors['confirm_password'] = 'Passwords do not match';
        }

        return $errors;
    }

    /**
     * Valide les données de mise à jour de profil
     * 
     * Param :
     * - $data : tableau avec 'username', 'email', 'current_password' (pour changement email/username)
     * - $currentUserId : ID de l'utilisateur actuel (pour vérifier l'unicité)
     * 
     * Retour :
     * - array : tableau des erreurs (vide si valide)
     */
    public function validateProfileUpdate(array $data, $currentUserId): array
    {
        $errors = [];

        if (!empty($data['username'])) {
            $username = trim($data['username']);
            if (strlen($username) < 3) {
                $errors['username'] = 'Username too short (minimum 3 characters)';
            } else {
                // Vérifier que le pseudo n'existe pas (sauf si c'est le sien)
                $user = $this->findByUsername($username);
                if ($user && $user['u_id'] != $currentUserId) {
                    $errors['username'] = 'Username already in use';
                }
            }
        }

        if (!empty($data['email'])) {
            $email = trim($data['email']);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email address';
            } else {
                // Vérifier que l'email n'existe pas (sauf si c'est le sien)
                $user = $this->findByEmail($email);
                if ($user && $user['u_id'] != $currentUserId) {
                    $errors['email'] = 'Email already in use';
                }
            }
        }

        return $errors;
    }
}
