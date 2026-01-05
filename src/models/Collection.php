<?php

namespace App\Models;

use App\Models\Database;
use PDO;
use PDOException;

/**
 * Collection Model
 * Manages monster collections and sharing functionality.
 * 
 * ORGANIZATION:
 * 1. Constructor
 * 2. CRUD Operations (Create, Read, Update, Delete)
 * 3. Collection Management (Add/Remove Monsters)
 * 4. Sharing Features (Tokens, Public Access)
 * 5. Helper Methods
 */
class Collection
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // ===================================================================
    // SECTION 1: CRUD OPERATIONS
    // ===================================================================

    /**
     * CREATE - Create new default collection for user.
     */
    public function createDefaultCollection($userId): bool
    {
        try {
            $sql = "INSERT INTO collections (u_id, name, description) 
                    VALUES (:u_id, 'My First Collection', 'Your default monster collection')";
            $stmt = $this->db->prepare($sql);
            return (bool) $stmt->execute([':u_id' => $userId]);
        } catch (PDOException $e) {
            error_log("Error creating default collection: " . $e->getMessage());
            return false;
        }
    }

    /**
     * CREATE - Create new collection with custom name/description.
     */
    public function create($userId, $name, $description = ''): int|false
    {
        try {
            $sql = "INSERT INTO collections (u_id, name, description) 
                    VALUES (:u_id, :name, :description)";
            $stmt = $this->db->prepare($sql);
            
            if ($stmt->execute([
                ':u_id' => $userId,
                ':name' => $name,
                ':description' => $description
            ])) {
                return (int) $this->db->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error creating collection: " . $e->getMessage());
            return false;
        }
    }

    /**
     * READ - Get all collections for a user with monster counts.
     */
    public function getByUser($userId): array
    {
        $sql = "SELECT c.*, 
                       COUNT(cm.monster_id) as monster_count
                FROM collections c
                LEFT JOIN collection_monsters cm ON c.collection_id = cm.collection_id
                WHERE c.u_id = :userId
                GROUP BY c.collection_id
                ORDER BY c.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':userId' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * READ - Get single collection by ID (owner verification required).
     */
    public function getById($collectionId, $userId = null): array|false
    {
        $sql = "SELECT * FROM collections WHERE collection_id = :id";
        
        if ($userId !== null) {
            $sql .= " AND u_id = :userId";
        }
        
        $stmt = $this->db->prepare($sql);
        $params = [':id' => $collectionId];
        
        if ($userId !== null) {
            $params[':userId'] = $userId;
        }
        
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * UPDATE - Modify collection name/description.
     */
    public function update($collectionId, $userId, $name, $description = ''): bool
    {
        try {
            $sql = "UPDATE collections 
                    SET name = :name, description = :description 
                    WHERE collection_id = :id AND u_id = :userId";
            
            $stmt = $this->db->prepare($sql);
            return (bool) $stmt->execute([
                ':id' => $collectionId,
                ':userId' => $userId,
                ':name' => $name,
                ':description' => $description
            ]);
        } catch (PDOException $e) {
            error_log("Error updating collection: " . $e->getMessage());
            return false;
        }
    }

    /**
     * DELETE - Remove collection (must be empty).
     */
    public function delete($collectionId, $userId): bool
    {
        try {
            // Check if collection has monsters
            $sql = "SELECT COUNT(*) FROM collection_monsters WHERE collection_id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $collectionId]);
            
            if ($stmt->fetchColumn() > 0) {
                return false;
            }

            // Delete collection
            $sql = "DELETE FROM collections WHERE collection_id = :id AND u_id = :userId";
            $stmt = $this->db->prepare($sql);
            return (bool) $stmt->execute([
                ':id' => $collectionId,
                ':userId' => $userId
            ]);
        } catch (PDOException $e) {
            error_log("Error deleting collection: " . $e->getMessage());
            return false;
        }
    }

    // ===================================================================
    // SECTION 2: COLLECTION MANAGEMENT
    // ===================================================================

    /**
     * Add monster to collection (prevents duplicates).
     */
    public function addMonster($collectionId, $monsterId): bool
    {
        try {
            // Check if already exists
            if ($this->monsterInCollection($collectionId, $monsterId)) {
                return false;
            }

            $sql = "INSERT INTO collection_monsters (collection_id, monster_id) 
                    VALUES (:collection_id, :monster_id)";
            $stmt = $this->db->prepare($sql);
            
            return (bool) $stmt->execute([
                ':collection_id' => $collectionId,
                ':monster_id' => $monsterId
            ]);
        } catch (PDOException $e) {
            error_log("Error adding monster to collection: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove monster from collection.
     */
    public function removeMonster($collectionId, $monsterId): bool
    {
        try {
            $sql = "DELETE FROM collection_monsters 
                    WHERE collection_id = :collection_id AND monster_id = :monster_id";
            $stmt = $this->db->prepare($sql);
            
            return (bool) $stmt->execute([
                ':collection_id' => $collectionId,
                ':monster_id' => $monsterId
            ]);
        } catch (PDOException $e) {
            error_log("Error removing monster from collection: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all monsters in a collection with full monster data.
     */
    public function getMonsters($collectionId): array
    {
        $sql = "SELECT m.* 
                FROM monster m
                JOIN collection_monsters cm ON m.monster_id = cm.monster_id
                WHERE cm.collection_id = :collection_id
                ORDER BY cm.added_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':collection_id' => $collectionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all collections containing a specific monster.
     */
    public function getCollectionsForMonster($monsterId, $userId): array
    {
        $sql = "SELECT c.*, 
                       EXISTS(
                           SELECT 1 FROM collection_monsters cm 
                           WHERE cm.collection_id = c.collection_id 
                           AND cm.monster_id = :monster_id
                       ) as has_monster
                FROM collections c
                WHERE c.u_id = :user_id
                ORDER BY c.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':monster_id' => $monsterId,
            ':user_id' => $userId
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ===================================================================
    // SECTION 3: SHARING FEATURES
    // ===================================================================

    /**
     * Generate unique share token for collection.
     */
    public function generateShareToken($collectionId): string
    {
        try {
            $token = bin2hex(random_bytes(16));
            
            $sql = "UPDATE collections SET share_token = :token WHERE collection_id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':token' => $token,
                ':id' => $collectionId
            ]);
            
            return $token;
        } catch (PDOException $e) {
            error_log("Error generating share token: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Revoke share token (disable sharing).
     */
    public function revokeShareToken($collectionId): bool
    {
        try {
            $sql = "UPDATE collections SET share_token = NULL WHERE collection_id = :id";
            $stmt = $this->db->prepare($sql);
            return (bool) $stmt->execute([':id' => $collectionId]);
        } catch (PDOException $e) {
            error_log("Error revoking share token: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get collection by share token (public access).
     */
    public function getByShareToken($token): array|false
    {
        $sql = "SELECT * FROM collections WHERE share_token = :token";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':token' => $token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get current share token for collection.
     */
    public function getShareToken($collectionId, $userId): string|false
    {
        $sql = "SELECT share_token FROM collections 
                WHERE collection_id = :id AND u_id = :userId";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $collectionId,
            ':userId' => $userId
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['share_token'] ?? false;
    }

    // ===================================================================
    // SECTION 4: HELPER METHODS
    // ===================================================================

    /**
     * Get user's default collection (first created).
     */
    public function getDefaultCollection($userId): array|false
    {
        $sql = "SELECT * FROM collections 
                WHERE u_id = :userId 
                ORDER BY created_at ASC 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':userId' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Check if collection exists and belongs to user.
     */
    public function collectionExists($collectionId, $userId): bool
    {
        $sql = "SELECT COUNT(*) FROM collections 
                WHERE collection_id = :id AND u_id = :userId";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $collectionId,
            ':userId' => $userId
        ]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Check if monster is already in collection.
     */
    private function monsterInCollection($collectionId, $monsterId): bool
    {
        $sql = "SELECT COUNT(*) FROM collection_monsters 
                WHERE collection_id = :collection_id AND monster_id = :monster_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':collection_id' => $collectionId,
            ':monster_id' => $monsterId
        ]);
        return $stmt->fetchColumn() > 0;
    }
}
