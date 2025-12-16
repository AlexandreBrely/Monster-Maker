<?php

namespace App\Models;

use PDO;

/**
 * MonsterLike Model
 * Handles like/unlike actions on monsters
 */
class MonsterLike
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Add a like to a monster
     * Returns true if added, false if already liked or error
     */
    public function addLike($userId, $monsterId)
    {
        try {
            $sql = "INSERT INTO monster_likes (u_id, monster_id) VALUES (:userId, :monsterId)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':userId' => $userId, ':monsterId' => $monsterId]);
        } catch (\PDOException $e) {
            // Duplicate key (already liked) or other error
            return false;
        }
    }

    /**
     * Remove a like from a monster
     * Returns true if removed, false otherwise
     */
    public function removeLike($userId, $monsterId)
    {
        $sql = "DELETE FROM monster_likes WHERE u_id = :userId AND monster_id = :monsterId";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':userId' => $userId, ':monsterId' => $monsterId]);
    }

    /**
     * Toggle like: add if not liked, remove if already liked
     * Returns 'added' or 'removed'
     */
    public function toggleLike($userId, $monsterId)
    {
        if ($this->hasLiked($userId, $monsterId)) {
            $this->removeLike($userId, $monsterId);
            return 'removed';
        } else {
            $this->addLike($userId, $monsterId);
            return 'added';
        }
    }

    /**
     * Check if a user has liked a specific monster
     */
    public function hasLiked($userId, $monsterId)
    {
        $sql = "SELECT 1 FROM monster_likes WHERE u_id = :userId AND monster_id = :monsterId LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':userId' => $userId, ':monsterId' => $monsterId]);
        return (bool)$stmt->fetch();
    }

    /**
     * Count total likes for a monster
     */
    public function countLikes($monsterId)
    {
        $sql = "SELECT COUNT(*) as count FROM monster_likes WHERE monster_id = :monsterId";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':monsterId' => $monsterId]);
        $result = $stmt->fetch();
        return (int)($result['count'] ?? 0);
    }

    /**
     * Get like counts for multiple monsters (batch operation)
     * Returns array: [monster_id => like_count]
     */
    public function getLikeCounts(array $monsterIds)
    {
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
        while ($row = $stmt->fetch()) {
            $counts[$row['monster_id']] = (int)$row['count'];
        }
        
        return $counts;
    }

    /**
     * Check which monsters a user has liked (batch operation)
     * Returns array of monster IDs
     */
    public function getUserLikes($userId, array $monsterIds)
    {
        if (empty($monsterIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($monsterIds), '?'));
        $sql = "SELECT monster_id FROM monster_likes WHERE u_id = ? AND monster_id IN ($placeholders)";
        
        $params = array_merge([$userId], $monsterIds);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return array_column($stmt->fetchAll(), 'monster_id');
    }
}
