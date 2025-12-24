<?php

namespace App\Models;

use PDO;

/**
 * MonsterLike Model - Database Operations for Like System
 * ========================================================
 * 
 * PURPOSE:
 * Manages the like/unlike functionality for monsters. Tracks which users
 * have liked which monsters and provides like counts.
 * 
 * DATABASE TABLE: monster_likes
 * - like_id (PK): Auto-increment primary key
 * - u_id (FK): User who liked the monster
 * - monster_id (FK): Monster that was liked
 * - created_at: Timestamp of when like was created
 * - UNIQUE(u_id, monster_id): Prevents duplicate likes
 * 
 * KEY CONCEPTS:
 * 
 * 1. MANY-TO-MANY RELATIONSHIP:
 *    - One user can like many monsters
 *    - One monster can be liked by many users
 *    - Junction table (monster_likes) connects them
 * 
 * 2. UNIQUE CONSTRAINT:
 *    - Database enforces one like per user per monster
 *    - Attempting to insert duplicate fails gracefully
 *    - Prevents "like bombing" (spamming likes)
 * 
 * 3. TOGGLE PATTERN:
 *    - Check if like exists → Remove if yes, Add if no
 *    - Single endpoint handles both like and unlike
 *    - Simplifies client-side JavaScript logic
 * 
 * 4. BATCH OPERATIONS:
 *    - getLikeCounts(): Get counts for multiple monsters at once
 *    - getUserLikes(): Check multiple monsters in single query
 *    - Reduces database queries for list pages (performance optimization)
 * 
 * CRUD OPERATIONS:
 * - CREATE: addLike() - Insert new like record
 * - READ: hasLiked(), countLikes(), getLikeCounts(), getUserLikes()
 * - DELETE: removeLike() - Remove like record
 * - TOGGLE: toggleLike() - Add or remove based on current state
 * 
 * SECURITY:
 * - All queries use prepared statements (SQL injection prevention)
 * - Foreign key constraints ensure data integrity
 * - Cascade delete: Remove likes when user or monster deleted
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
     * Add a like to a monster (CREATE operation)
     * 
     * BEHAVIOR:
     * - Inserts new record into monster_likes table
     * - Returns true if successful, false if already liked
     * 
     * ERROR HANDLING:
     * - PDOException caught if UNIQUE constraint violated (duplicate like)
     * - Returns false instead of throwing exception for graceful handling
     * 
     * SECURITY:
     * - Uses prepared statements with named parameters
     * - Type safety: userId and monsterId cast to int before calling
     * 
     * @param int $userId - User who is liking the monster
     * @param int $monsterId - Monster being liked
     * @return bool True if like added, false if already exists or error
     */
    public function addLike($userId, $monsterId)
    {
        try {
            // SQL INSERT: Add new like record to database
            // IMPORTANT: Include created_at with NOW() because database doesn't have DEFAULT
            // Parameter names MUST match column names: :u_id and :monster_id (not :userId)
            $sql = "INSERT INTO monster_likes (u_id, monster_id, created_at) VALUES (:u_id, :monster_id, NOW())";
            
            // STEP 1: Prepare statement (prevents SQL injection)
            $stmt = $this->db->prepare($sql);
            
            // STEP 2: Execute with parameters
            // Returns true if INSERT succeeded, false if failed
            $result = $stmt->execute([':u_id' => $userId, ':monster_id' => $monsterId]);
            
            return $result;
        } catch (\PDOException $e) {
            // DUPLICATE KEY ERROR: User already liked this monster
            // The UNIQUE constraint (u_id, monster_id) prevents duplicate likes
            // Instead of throwing error, return false for graceful handling
            return false;
        }
    }

    /**
     * Remove a like from a monster (DELETE operation)
     * 
     * BEHAVIOR:
     * - Deletes record from monster_likes table
     * - Returns true if removed, false if not found or error
     * 
     * NOTE:
     * - Does not throw error if like doesn't exist
     * - Idempotent: safe to call multiple times
     * 
     * @param int $userId - User who is unliking the monster
     * @param int $monsterId - Monster being unliked
     * @return bool True if like removed, false otherwise
     */
    public function removeLike($userId, $monsterId)
    {
        // SQL DELETE: Remove like record from database
        // Parameter names match column names: :u_id and :monster_id
        $sql = "DELETE FROM monster_likes WHERE u_id = :u_id AND monster_id = :monster_id";
        
        // STEP 1: Prepare statement
        $stmt = $this->db->prepare($sql);
        
        // STEP 2: Execute with parameters
        // Returns true if DELETE succeeded (even if no rows affected)
        return $stmt->execute([':u_id' => $userId, ':monster_id' => $monsterId]);
    }

    /**
     * Toggle like: add if not liked, remove if already liked
     * 
     * TOGGLE PATTERN:
     * This is a common pattern for binary states (on/off, liked/unliked).
     * Instead of separate "like" and "unlike" endpoints, one endpoint
     * checks current state and switches to opposite state.
     * 
     * ADVANTAGES:
     * - Simpler client code: one function instead of two
     * - Prevents race conditions (double-click issues)
     * - Self-correcting: if state is wrong, one click fixes it
     * 
     * WORKFLOW:
     * 1. Check if user has already liked monster (hasLiked)
     * 2. If yes → Remove like (removeLike)
     * 3. If no → Add like (addLike)
     * 4. Return string indicating action taken
     * 
     * RETURN VALUES:
     * - 'added': Like was added (user liked monster)
     * - 'removed': Like was removed (user unliked monster)
     * 
     * USAGE IN CONTROLLER:
     * $action = $likeModel->toggleLike($userId, $monsterId);
     * $newCount = $likeModel->countLikes($monsterId);
     * return json(['success' => true, 'action' => $action, 'count' => $newCount]);
     * 
     * @param int $userId - User toggling the like
     * @param int $monsterId - Monster being liked/unliked
     * @return string 'added' or 'removed'
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
     * Check if a user has liked a specific monster (READ operation)
     * 
     * QUERY STRATEGY:
     * - SELECT 1: Minimal data transfer (only need existence check)
     * - LIMIT 1: Stop after first match (performance optimization)
     * - Returns boolean: Clean interface for if statements
     * 
     * PERFORMANCE:
     * - Index on (u_id, monster_id) makes this query very fast
     * - LIMIT 1 prevents unnecessary scanning
     * 
     * USAGE:
     * if ($likeModel->hasLiked($userId, $monsterId)) {
     *     echo "You liked this!";
     * }
     * 
     * @param int $userId - User to check
     * @param int $monsterId - Monster to check
     * @return bool True if user has liked monster, false otherwise
     */
    public function hasLiked($userId, $monsterId)
    {
        // SQL EXISTENCE CHECK: Does this user-monster combination exist?
        // SELECT 1: We don't need data, just checking if row exists
        // LIMIT 1: Stop searching after first match (performance optimization)
        // Parameter names match columns: :u_id and :monster_id
        $sql = "SELECT 1 FROM monster_likes WHERE u_id = :u_id AND monster_id = :monster_id LIMIT 1";
        
        // STEP 1: Prepare statement
        $stmt = $this->db->prepare($sql);
        
        // STEP 2: Execute with both user ID and monster ID
        $stmt->execute([':u_id' => $userId, ':monster_id' => $monsterId]);
        
        // STEP 3: Check if any row was found
        // fetch() returns row data or false
        // (bool) converts row to true, false stays false
        return (bool)$stmt->fetch();
    }

    /**
     * Count total likes for a monster (AGGREGATE operation)
     * 
     * QUERY STRATEGY:
     * - COUNT(*): Efficient aggregate function
     * - Single monster: For detail pages
     * - Returns integer: Direct use in displays
     * 
     * PERFORMANCE:
     * - Index on monster_id makes COUNT very fast
     * - Counts are cached by database engine
     * 
     * USAGE:
     * $count = $likeModel->countLikes($monsterId);
     * echo "$count users liked this monster";
     * 
     * NOTE:
     * For multiple monsters, use getLikeCounts() instead (batch operation)
     * 
     * @param int $monsterId - Monster to count likes for
     * @return int Number of likes (0 if none)
     */
    public function countLikes($monsterId)
    {
        try {
            // SQL COUNT: Get total number of likes for this monster
            // COUNT(*) is an aggregate function that counts all matching rows
            // Parameter name matches column: :monster_id
            $sql = "SELECT COUNT(*) as count FROM monster_likes WHERE monster_id = :monster_id";
            
            // STEP 1: Prepare the query
            $stmt = $this->db->prepare($sql);
            
            // STEP 2: Execute with monster ID parameter
            $stmt->execute([':monster_id' => $monsterId]);
            
            // STEP 3: Fetch the result as associative array
            // Result will be: ['count' => 5] or ['count' => 0]
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // STEP 4: Return count as integer
            // ?? 0 provides default value if count is null
            return (int)($result['count'] ?? 0);
        } catch (\Exception $e) {
            // If any error occurs, return 0 (graceful degradation)
            return 0;
        }
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
     * Check which monsters a user has liked (BATCH operation)
     * 
     * PERFORMANCE OPTIMIZATION:
     * Problem: Calling hasLiked() in a loop creates N database queries
     * Solution: Single query with IN clause checks all monsters at once
     * 
     * USE CASE:
     * Monster listing page needs to show filled/empty hearts.
     * Instead of checking each monster individually:
     * 1. Get all monster IDs on page
     * 2. Call getUserLikes() once
     * 3. Use in_array() to check if each monster is liked
     * 
     * EXAMPLE USAGE:
     * $monsterIds = array_column($monsters, 'monster_id');
     * $userLikes = $likeModel->getUserLikes($userId, $monsterIds);
     * 
     * foreach ($monsters as $monster) {
     *     $isLiked = in_array($monster['monster_id'], $userLikes);
     *     $icon = $isLiked ? 'bi-heart-fill' : 'bi-heart';
     * }
     * 
     * RETURN FORMAT:
     * Array of monster IDs that user has liked:
     * [1, 5, 7]  // User liked monsters 1, 5, and 7
     * 
     * @param int $userId - User to check likes for
     * @param array $monsterIds - Array of monster IDs to check
     * @return array Array of monster IDs user has liked
     */
    public function getUserLikes($userId, array $monsterIds)
    {
        if (empty($monsterIds)) {
            return [];
        }

        // Build placeholders for IN clause
        $placeholders = implode(',', array_fill(0, count($monsterIds), '?'));
        $sql = "SELECT monster_id FROM monster_likes WHERE u_id = ? AND monster_id IN ($placeholders)";
        
        // Merge userId with monsterIds for parameter binding
        $params = array_merge([$userId], $monsterIds);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        // Extract monster_id column into simple array
        // array_column() converts [['monster_id' => 1], ...] to [1, ...]
        return array_column($stmt->fetchAll(), 'monster_id');
    }
}
