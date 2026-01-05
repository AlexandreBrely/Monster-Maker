<?php

namespace App\Models;

use App\Models\Database;
use PDO;
use PDOException;

/**
 * MonsterLike Model
 * Manages the like/unlike system for monsters.
 * 
 * ORGANIZATION:
 * 1. Constructor
 * 2. CRUD Operations (Add/Remove Likes)
 * 3. Query Operations (Check Status, Count Likes)
 */
class MonsterLike
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
     * CREATE - Add like to monster.
     */
    public function addLike($monsterId, $userId): bool
    {
        try {
            // Check if already liked
            if ($this->hasLiked($monsterId, $userId)) {
                return false;
            }

            $sql = "INSERT INTO monster_likes (monster_id, u_id) VALUES (:monster_id, :u_id)";
            $stmt = $this->db->prepare($sql);
            
            return (bool) $stmt->execute([
                ':monster_id' => $monsterId,
                ':u_id' => $userId
            ]);
        } catch (PDOException $e) {
            error_log("Error adding like: " . $e->getMessage());
            return false;
        }
    }

    /**
     * DELETE - Remove like from monster.
     */
    public function removeLike($monsterId, $userId): bool
    {
        try {
            $sql = "DELETE FROM monster_likes WHERE monster_id = :monster_id AND u_id = :u_id";
            $stmt = $this->db->prepare($sql);
            
            return (bool) $stmt->execute([
                ':monster_id' => $monsterId,
                ':u_id' => $userId
            ]);
        } catch (PDOException $e) {
            error_log("Error removing like: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Toggle like status (like if not liked, unlike if liked).
     */
    public function toggleLike($monsterId, $userId): array
    {
        try {
            if ($this->hasLiked($monsterId, $userId)) {
                $this->removeLike($monsterId, $userId);
                $liked = false;
            } else {
                $this->addLike($monsterId, $userId);
                $liked = true;
            }

            $count = $this->countLikes($monsterId);

            return [
                'success' => true,
                'liked' => $liked,
                'count' => $count
            ];
        } catch (PDOException $e) {
            error_log("Error toggling like: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Database error'
            ];
        }
    }

    // ===================================================================
    // SECTION 2: QUERY OPERATIONS
    // ===================================================================

    /**
     * Check if user has liked a specific monster.
     */
    public function hasLiked($monsterId, $userId): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM monster_likes WHERE monster_id = :monster_id AND u_id = :u_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':monster_id' => $monsterId,
                ':u_id' => $userId
            ]);
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error checking like status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Count total likes for a monster.
     */
    public function countLikes($monsterId): int
    {
        try {
            $sql = "SELECT COUNT(*) FROM monster_likes WHERE monster_id = :monster_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':monster_id' => $monsterId]);
            
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error counting likes: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get like counts for multiple monsters (batch query).
     */
    public function getLikeCounts(array $monsterIds): array
    {
        try {
            if (empty($monsterIds)) {
                return [];
            }

            $placeholders = implode(',', array_fill(0, count($monsterIds), '?'));
            $sql = "SELECT monster_id, COUNT(*) as count 
                    FROM monster_likes 
                    WHERE monster_id IN ($placeholders) 
                    GROUP BY monster_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($monsterIds);
            
            $counts = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $counts[$row['monster_id']] = (int) $row['count'];
            }
            
            return $counts;
        } catch (PDOException $e) {
            error_log("Error getting like counts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all monsters liked by a user.
     */
    public function getUserLikes($userId): array
    {
        try {
            $sql = "SELECT m.*, ml.created_at as liked_at 
                    FROM monster m
                    JOIN monster_likes ml ON m.monster_id = ml.monster_id
                    WHERE ml.u_id = :u_id
                    ORDER BY ml.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':u_id' => $userId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting user likes: " . $e->getMessage());
            return [];
        }
    }
}
