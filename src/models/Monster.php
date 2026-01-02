<?php

namespace App\Models;

use App\Models\Database;
use PDO;
use PDOException;

/**
 * Class Monster
 * Represents all database operations (CRUD) for monsters.
 * 
 * This class demonstrates several important patterns:
 * 
 * 1. DEPENDENCY INJECTION: Constructor receives Database, extracts PDO connection
 * 
 * 2. JSON SERIALIZATION:
 *    - Database stores JSON strings in columns like 'traits', 'actions', 'reactions'
 *    - This method handles conversion between PHP arrays (for code) and JSON (for storage)
 *    - Example: PHP array ['name' => 'Fireball'] becomes JSON string '[{"name":"Fireball"}]'
 * 
 * 3. DYNAMIC SQL BUILDING:
 *    - Search and update methods build SQL conditionally based on input
 *    - Only adds WHERE clauses or SET fields that are actually provided
 * 
 * 4. SECURITY CONSIDERATIONS:
 *    - All database queries use prepared statements (prevents SQL injection)
 *    - Owner verification: delete(), update() check user owns the monster
 *    - File handling: delete() cleans up uploaded image files after DB deletion
 * 
 * Responsibilities:
 * - CRUD operations (Create, Read, Update, Delete monsters)
 * - Search and filtering
 * - JSON serialization/deserialization for complex fields
 * - Validation of monster data
 * - Image file management
 */
class Monster
{
    // Property to hold the PDO database connection
    /** @var PDO Database connection for all queries */
    private $db;

    // Directory where uploaded monster images are stored
    /** File system path to monster upload directory */
    const UPLOAD_PATH = __DIR__ . '/../../public/uploads/monsters/';

    /**
     * Constructor: Initialize Monster model with database connection.
     * 
     * Dependency Injection Pattern:
     * - Create a new Database instance
     * - Call getConnection() to get a PDO object
     * - Store PDO in $this->db for all methods to use
     * 
     * Note: In production, it's better to pass the PDO as a parameter
     * ("constructor injection") instead of creating Database here.
     */
    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Get all public monsters from the database.
     * 
     * Public monsters: Only those where is_public = 1 (true)
     * This prevents draft/private monsters from showing in search results.
     * 
     * Ordering: ORDER BY created_at DESC sorts by creation date (newest first)
     * 
     * Note: This method does NOT deserialize JSON fields.
     * JSON fields will still be strings (stored database form).
     * Use getById() if you need the deserialized data.
     * 
     * @return array Array of monster records (empty array if no public monsters exist)
     *               Each element is an associative array with keys like:
     *               ['monster_id' => 1, 'name' => 'Dragon', 'size' => 'Large', ...]
     */
    public function getAll(): array
    {
        $sql = "SELECT * FROM monster WHERE is_public = 1 ORDER BY created_at DESC";
        $stmt = $this->db->query($sql); // query() for simple statements (no parameters)
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Returns array of all results
    }

    /**
     * Get a single monster by its ID with all data deserialized.
     * 
     * Why deserialize here but not in getAll()?
     * - getAll() is for public browsing (quick list, JSON fields not needed yet)
     * - getById() is for detailed view/editing (need actual data, not JSON strings)
     * - It's wasteful to deserialize all 100 monsters if user only views one
     * 
     * The deserialization process:
     * - Takes JSON string: '[{"name":"Fireball"}]'
     * - Converts to PHP array: [['name' => 'Fireball']]
     * - Makes the data usable in PHP code
     * 
     * @param int $id The monster ID to fetch
     * @return array|false Deserialized monster record, or false if not found
     *                      Example: ['monster_id' => 1, 'name' => 'Red Dragon',
     *                               'actions' => [['name' => 'Bite', 'damage' => '2d6+3'], ...], ...]
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM monster WHERE monster_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        // fetch() returns one record or false
        $monster = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Guard clause: If monster not found, return false immediately
        // This prevents trying to deserialize null data
        if ($monster) {
            // Deserialize JSON fields: convert 'actions' from JSON string to PHP array
            $this->deserializeJsonFields($monster);
        }
        
        return $monster;
    }

    /**
     * Get all monsters created by a specific user.
     * 
     * This is important for the user dashboard:
     * - Show only monsters the user owns (WHERE u_id = :userId)
     * - Includes private monsters (is_public can be 0 or 1)
     * - Newest first (ORDER BY created_at DESC)
     * 
     * The foreach loop demonstrates PASS BY REFERENCE:
     * - foreach ($monsters as &$monster) - the & is crucial!
     * - Without &, changes to $monster wouldn't affect the original array
     * - With &, deserializeJsonFields() modifies the actual array elements
     * 
     * @param int $userId The user ID to fetch monsters for
     * @return array Array of deserialized monster records, or empty array if none
     */
    public function getByUser($userId): array
    {
        $sql = "SELECT m.*, COALESCE(like_counts.count, 0) as like_count 
                FROM monster m 
                LEFT JOIN (
                    SELECT monster_id, COUNT(*) as count 
                    FROM monster_likes 
                    GROUP BY monster_id
                ) like_counts ON m.monster_id = like_counts.monster_id 
                WHERE m.u_id = :userId 
                ORDER BY m.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':userId' => $userId]);
        
        $monsters = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Pass by reference: the & in &$monster allows us to modify the original array
        // Without &, we'd be looping over copies, and deserializeJsonFields() wouldn't affect $monsters
        foreach ($monsters as &$monster) {
            $this->deserializeJsonFields($monster);
        }
        
        return $monsters;
    }

    /**
     * Search for public monsters matching given criteria.
     * 
     * This demonstrates DYNAMIC SQL BUILDING:
     * - Start with a base query: "SELECT * FROM monster WHERE is_public = 1"
     * - Build WHERE clause conditionally based on search parameters
     * - Only add criteria if user provided them (empty() checks prevent null/empty filters)
     * 
     * Examples:
     * search(['name' => 'Dragon']) 
     *   → "SELECT * FROM monster WHERE is_public = 1 AND name LIKE :name"
     *   → params: [':name' => '%Dragon%']  (% are SQL wildcards: matches anywhere in the field)
     * 
     * search(['name' => 'Dragon', 'size' => 'Large'])
     *   → "SELECT * FROM monster WHERE is_public = 1 AND name LIKE :name AND size = :size"
     *   → params: [':name' => '%Dragon%', ':size' => 'Large']
     * 
     * Why concatenate instead of just use multiple queries?
     * - One database request is faster than multiple separate requests
     * - Easier to maintain one method that handles all combinations
     * 
     * @param array $criteria Associative array with optional keys:
     *                         ['name' => 'Dragon', 'type' => 'Dragon', 'size' => 'Large', 
     *                          'challenge_rating' => 5, ...]
     * @return array Array of deserialized monsters matching criteria (empty if none match)
     */
    public function search(array $criteria): array
    {
        // Start with base query for public monsters only
        $sql = "SELECT * FROM monster WHERE is_public = 1";
        $params = [];

        // Guard clause: If name was provided and is not empty, add name filter
        if (!empty($criteria['name'])) {
            // LIKE '%' . value . '%' matches the value anywhere in the name
            // Example: 'LIKE %dragon%' matches 'Red Dragon', 'Dragon Kin', 'Pseudo-Dragon', etc.
            $sql .= " AND name LIKE :name";
            $params[':name'] = '%' . $criteria['name'] . '%';
        }

        // Guard clause: If type provided, add exact match filter
        if (!empty($criteria['type'])) {
            $sql .= " AND type = :type";
            $params[':type'] = $criteria['type'];
        }

        // Guard clause: If size provided, add exact match filter
        if (!empty($criteria['size'])) {
            $sql .= " AND size = :size";
            $params[':size'] = $criteria['size'];
        }

        // Guard clause: If challenge rating provided, add exact match filter
        if (!empty($criteria['challenge_rating'])) {
            $sql .= " AND challenge_rating = :cr";
            $params[':cr'] = $criteria['challenge_rating'];
        }

        // Order results by newest first
        $sql .= " ORDER BY created_at DESC";

        // Execute the dynamically-built query
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $monsters = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Deserialize JSON fields for each result
        foreach ($monsters as &$monster) {
            $this->deserializeJsonFields($monster);
        }
        
        return $monsters;
    }

    /**
     * Get all public monsters with filters and sorting
     * 
     * @param string $orderBy Sort type: 'random', 'newest', 'oldest', 'most_liked'
     * @param int|null $userId Filter by specific user ID (null = all public)
     * @param string|null $search Search by monster name
     * @param string|null $size Filter by size
     * @param string|null $type Filter by type
     * @return array Array of monsters with like_count included
     */
    public function getAllFiltered($orderBy = 'newest', $userId = null, $search = null, $size = null, $type = null): array
    {
        $sql = "SELECT m.*, COALESCE(like_counts.count, 0) as like_count 
                FROM monster m 
                LEFT JOIN (
                    SELECT monster_id, COUNT(*) as count 
                    FROM monster_likes 
                    GROUP BY monster_id
                ) like_counts ON m.monster_id = like_counts.monster_id 
                WHERE m.is_public = 1";
        
        $params = [];
        
        // Filter by name search
        if ($search !== null && $search !== '') {
            $sql .= " AND m.name LIKE :search";
            $params[':search'] = '%' . $search . '%';
        }
        
        // Filter by size
        if ($size !== null && $size !== '') {
            $sql .= " AND m.size = :size";
            $params[':size'] = $size;
        }
        
        // Filter by type
        if ($type !== null && $type !== '') {
            $sql .= " AND m.type = :type";
            $params[':type'] = $type;
        }
        
        // Filter by user if specified
        if ($userId !== null) {
            $sql .= " AND m.u_id = :userId";
            $params[':userId'] = $userId;
        }
        
        // Apply ordering
        switch ($orderBy) {
            case 'random':
                $sql .= " ORDER BY RAND()";
                break;
            case 'oldest':
                $sql .= " ORDER BY m.created_at ASC";
                break;
            case 'most_liked':
                $sql .= " ORDER BY like_count DESC, m.created_at DESC";
                break;
            case 'newest':
            default:
                $sql .= " ORDER BY m.created_at DESC";
                break;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new monster in the database.
     * 
     * This is the largest method in the Monster class and demonstrates several patterns:
     * 
     * 1. GUARD CLAUSES:
     *    - Validate required fields immediately
     *    - Return false early if validation fails (don't waste time building SQL)
     * 
     * 2. NULL COALESCING OPERATOR (??):
     *    - $data['traits'] ?? [] means: use traits if they exist, otherwise use empty array
     *    - This prevents errors when array keys don't exist
     * 
     * 3. JSON SERIALIZATION:
     *    - traits, actions, reactions arrive as PHP arrays from the form
     *    - Serialize methods convert them to JSON strings for database storage
     * 
     * 4. MASSIVE PREPARED STATEMENT:
     *    - 40+ placeholders (:name, :size, :ac, etc.)
     *    - Each corresponds to a database column
     *    - Prevents SQL injection attacks
     * 
     * 5. DEFAULT VALUES:
     *    - Many fields use ?? 'defaultValue' in the execute() array
     *    - Example: ':strength' => $data['strength'] ?? 10
     *    - If form doesn't include strength, default to 10
     * 
     * Return values:
     * - Numeric string: The new monster's ID (from lastInsertId())
     * - false: Insert failed (validation error or database error)
     * 
     * @param array $data Associative array with monster data from the form
     *                     Required keys: 'name', 'size', 'ac', 'hp'
     *                     Optional keys: 'type', 'alignment', 'strength', 'dexterity', etc.
     * @param int $userId The ID of the user creating this monster
     * @return string|false The new monster ID on success, false on failure
     */
    public function create(array $data, $userId)
    {
        try {
            // GUARD CLAUSE: Validate that all required fields are present
            // If any required field is missing or empty, return false immediately
            // Don't proceed with database operation if data is incomplete
            if (empty($data['name']) || empty($data['size']) || empty($data['ac']) || empty($data['hp'])) {
                return false;
            }

            // SERIALIZATION: Convert PHP arrays to JSON strings for database storage
            // If field doesn't exist in $data, the ?? [] operator provides an empty array
            // serializeTraits() then converts that array to a JSON string
            $data['traits'] = $this->serializeTraits($data['traits'] ?? []);
            $data['actions'] = $this->serializeActions($data['actions'] ?? []);
            $data['bonus_actions'] = $this->serializeBonusActions($data['bonus_actions'] ?? []);
            $data['reactions'] = $this->serializeReactions($data['reactions'] ?? []);
            $data['legendary_actions'] = $this->serializeLegendaryActions($data['legendary_actions'] ?? []);

            // IMAGE HANDLING: Assign image fields with null as default if not provided
            // null in database means "no image uploaded for this monster"
            $data['image_portrait'] = $data['image_portrait'] ?? null;
            $data['image_fullbody'] = $data['image_fullbody'] ?? null;

            $sql = "
                INSERT INTO monster (
                    name, size, type, alignment, ac, ac_notes, hp, hit_dice,
                    speed, proficiency_bonus, saving_throws,
                    strength, dexterity, constitution, intelligence, wisdom, charisma,
                    skills, senses, languages, challenge_rating,
                    damage_immunities, condition_immunities, damage_resistances, damage_vulnerabilities,
                    traits, actions, bonus_actions, reactions, legendary_actions,
                    is_legendary, legendary_resistance, legendary_resistance_lair, lair_actions,
                    image_portrait, image_fullbody, card_size, is_public, u_id
                ) VALUES (
                    :name, :size, :type, :alignment, :ac, :ac_notes, :hp, :hit_dice,
                    :speed, :proficiency_bonus, :saving_throws,
                    :strength, :dexterity, :constitution, :intelligence, :wisdom, :charisma,
                    :skills, :senses, :languages, :challenge_rating,
                    :damage_immunities, :condition_immunities, :damage_resistances, :damage_vulnerabilities,
                    :traits, :actions, :bonus_actions, :reactions, :legendary_actions,
                    :is_legendary, :legendary_resistance, :legendary_resistance_lair, :lair_actions,
                    :image_portrait, :image_fullbody, :card_size, :is_public, :u_id
                )
            ";

            $stmt = $this->db->prepare($sql);

            // Bind parameters with default values
            $stmt->execute([
                ':name' => $data['name'],
                ':size' => $data['size'] ?? '',
                ':type' => $data['type'] ?? '',
                ':alignment' => $data['alignment'] ?? '',
                ':ac' => $data['ac'] ?? 10,
                ':ac_notes' => $data['ac_notes'] ?? '',
                ':hp' => $data['hp'] ?? 1,
                ':hit_dice' => $data['hit_dice'] ?? '',
                ':speed' => $data['speed'] ?? '',
                ':proficiency_bonus' => $data['proficiency_bonus'] ?? 0,
                ':saving_throws' => $data['saving_throws'] ?? '',
                ':strength' => $data['strength'] ?? 10,
                ':dexterity' => $data['dexterity'] ?? 10,
                ':constitution' => $data['constitution'] ?? 10,
                ':intelligence' => $data['intelligence'] ?? 10,
                ':wisdom' => $data['wisdom'] ?? 10,
                ':charisma' => $data['charisma'] ?? 10,
                ':skills' => $data['skills'] ?? '',
                ':senses' => $data['senses'] ?? '',
                ':languages' => $data['languages'] ?? '',
                ':challenge_rating' => $data['challenge_rating'] ?? '0',
                ':damage_immunities' => $data['damage_immunities'] ?? '',
                ':condition_immunities' => $data['condition_immunities'] ?? '',
                ':damage_resistances' => $data['damage_resistances'] ?? '',
                ':damage_vulnerabilities' => $data['damage_vulnerabilities'] ?? '',
                ':traits' => $data['traits'] ?? '',
                ':actions' => $data['actions'],
                ':bonus_actions' => $data['bonus_actions'] ?? '',
                ':reactions' => $data['reactions'],
                ':legendary_actions' => $data['legendary_actions'],
                ':is_legendary' => $data['is_legendary'] ?? 0,
                ':legendary_resistance' => $data['legendary_resistance'] ?? '',
                ':legendary_resistance_lair' => $data['legendary_resistance_lair'] ?? '',
                ':lair_actions' => $data['lair_actions'] ?? '',
                ':image_portrait' => $data['image_portrait'],
                ':image_fullbody' => $data['image_fullbody'],
                ':card_size' => $data['card_size'] ?? 2,
                ':is_public' => $data['is_public'] ?? 0,
                ':u_id' => $userId
            ]);

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            // Log the full error message for debugging
            error_log('Monster create error: ' . $e->getMessage());
            error_log('Monster create SQL state: ' . $e->getCode());
            error_log('Monster create error trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Update an existing monster.
     * 
     * SECURITY: This method verifies ownership before allowing updates.
     * Users can only update their own monsters, not others'.
     * 
     * Process:
     * 1. Check if monster exists AND belongs to the user
     * 2. If not, return false immediately (fail-fast principle)
     * 3. Serialize any complex fields (actions, traits, etc.)
     * 4. Build dynamic UPDATE statement (only updates provided fields)
     * 5. Execute and return success/failure
     * 
     * Dynamic SQL Building:
     * - Iterate through $data array
     * - For each key/value pair, create "key = :key" clause
     * - Example: ['name' => 'Dragon'] becomes ["name = :name"] and params[':name'] = 'Dragon'
     * - implode(', ', updates) joins them: "name = :name, ac = :ac"
     * 
     * @param int $id The monster ID to update
     * @param array $data Associative array of fields to update (any subset of columns)
     * @param int $userId The user ID (must own the monster to update it)
     * @return bool true on success, false if not found or update fails
     */
    public function update($id, array $data, $userId): bool
    {
        try {
            // SECURITY CHECK: Verify monster exists AND belongs to this user
            // This prevents users from modifying other users' monsters
            $sql = "SELECT monster_id FROM monster WHERE monster_id = :id AND u_id = :userId";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id, ':userId' => $userId]);
            
            // Guard clause: If monster doesn't exist or doesn't belong to user, return false
            if (!$stmt->fetch()) {
                return false;
            }

            // SERIALIZATION: If complex fields are being updated, serialize them first
            // Only serialize fields that are actually being updated (if isset)
            if (isset($data['traits'])) {
                $data['traits'] = $this->serializeTraits($data['traits']);
            }
            if (isset($data['actions'])) {
                $data['actions'] = $this->serializeActions($data['actions']);
            }
            if (isset($data['bonus_actions'])) {
                $data['bonus_actions'] = $this->serializeBonusActions($data['bonus_actions']);
            }
            if (isset($data['reactions'])) {
                $data['reactions'] = $this->serializeReactions($data['reactions']);
            }
            if (isset($data['legendary_actions'])) {
                $data['legendary_actions'] = $this->serializeLegendaryActions($data['legendary_actions']);
            }

            // DYNAMIC SQL BUILDING: Build SET clause from provided fields
            // Example: ['name' => 'Dragon', 'ac' => 15] becomes "name = :name, ac = :ac"
            $updates = [];
            $params = [':id' => $id];

            // Iterate through data: build "column = :column" for each field
            foreach ($data as $key => $value) {
                $updates[] = "$key = :$key";
                $params[":$key"] = $value;
            }

            // Guard clause: If no fields to update, nothing to do, return true
            if (empty($updates)) {
                return true;
            }

            // Build final UPDATE statement and execute
            $sql = "UPDATE monster SET " . implode(', ', $updates) . " WHERE monster_id = :id";
            $stmt = $this->db->prepare($sql);

            // (bool) cast converts the result to boolean (true if successful, false otherwise)
            return (bool) $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('Monster update error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a monster and its associated files.
     * 
     * This demonstrates file system cleanup alongside database deletion:
     * 1. Fetch monster's image filenames from database
     * 2. Delete the database record
     * 3. Delete physical image files from disk (if deletion succeeded)
     * 
     * Security: Only the monster owner can delete their monsters.
     * 
     * File deletion:
     * - @unlink() suppresses error messages if files don't exist
     * - unlink() is the PHP function to delete files
     * - Useful for cleaning up when database records are deleted
     * 
     * @param int $id The monster ID to delete
     * @param int $userId The user ID (must own the monster to delete it)
     * @return bool true if deletion succeeded, false otherwise
     */
    public function delete($id, $userId): bool
    {
        try {
            // Step 1: Fetch the monster record to get image filenames
            // Only fetch if this user owns the monster (WHERE u_id = :userId)
            $sql = "SELECT image_portrait, image_fullbody FROM monster WHERE monster_id = :id AND u_id = :userId";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id, ':userId' => $userId]);
            $monster = $stmt->fetch(PDO::FETCH_ASSOC);

            // Guard clause: If monster doesn't exist or doesn't belong to user, can't delete
            if (!$monster) {
                return false;
            }

            // Step 2: Delete the monster from database
            $sql = "DELETE FROM monster WHERE monster_id = :id AND u_id = :userId";
            $stmt = $this->db->prepare($sql);
            // (bool) cast converts statement result to true/false
            $success = (bool) $stmt->execute([':id' => $id, ':userId' => $userId]);

            // Step 3: If database deletion succeeded, delete the physical image files
            // Only proceed if $success is true (prevents trying to delete files for failed DB deletes)
            if ($success) {
                // Guard: If portrait image exists, delete it from disk
                if (!empty($monster['image_portrait'])) {
                    // @ suppresses warnings if the file doesn't exist (don't crash if file missing)
                    @unlink(self::UPLOAD_PATH . $monster['image_portrait']);
                }
                // Guard: If fullbody image exists, delete it from disk
                if (!empty($monster['image_fullbody'])) {
                    @unlink(self::UPLOAD_PATH . $monster['image_fullbody']);
                }
            }

            return $success;
        } catch (PDOException $e) {
            error_log('Monster delete error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ===== SERIALIZATION / DESERIALIZATION METHODS =====
     * These methods convert between PHP arrays and JSON strings.
     * 
     * Why serialization matters:
     * - Database stores JSON in columns like 'traits', 'actions', 'reactions'
     * - PHP code works with arrays (easier to loop/access)
     * - Need to convert back and forth between these two formats
     * 
     * SERIALIZATION (PHP array → JSON string):
     * - Used in create(), update() before inserting into database
     * - Converts: ['name' => 'Fireball'] to JSON: '[{"name":"Fireball"}]'
     * 
     * DESERIALIZATION (JSON string → PHP array):
     * - Used in getById(), getByUser(), search() after retrieving from database
     * - Converts: '[{"name":"Fireball"}]' to array: ['name' => 'Fireball']
     */

    /**
     * Serialize traits array to JSON string.
     * 
     * Guard clauses:
     * 1. If already a JSON string, return as-is (already serialized)
     * 2. If not an array or empty, return empty JSON array '[]'
     * 3. Otherwise, encode PHP array to JSON
     * 
     * Why the string check? Sometimes data comes pre-serialized from database queries.
     * 
     * @param array|string $traits Traits array or JSON string
     * @return string JSON-encoded traits
     */
    private function serializeTraits($traits)
    {
        // Guard: If it's already a JSON string, don't re-encode
        if (is_string($traits)) {
            return $traits;
        }

        // Guard: If not array or empty, return empty JSON array
        if (!is_array($traits) || empty($traits)) {
            return json_encode([]);
        }

        // Convert PHP array to JSON string
        return json_encode($traits);
    }

    /**
     * Serialize actions array to JSON string.
     * 
     * Actions are the main attack/ability actions a monster can take.
     * Same pattern as serializeTraits().
     * 
     * @param array|string $actions Actions array or JSON string
     * @return string JSON-encoded actions
     */
    private function serializeActions($actions)
    {
        if (is_string($actions)) {
            return $actions;
        }

        if (!is_array($actions) || empty($actions)) {
            return json_encode([]);
        }

        return json_encode($actions);
    }

    /**
     * Serialize bonus actions array to JSON string.
     * 
     * @param array|string $actions Bonus actions array or JSON string
     * @return string JSON-encoded bonus actions
     */
    private function serializeBonusActions($actions)
    {
        if (is_string($actions)) {
            return $actions;
        }

        if (!is_array($actions) || empty($actions)) {
            return json_encode([]);
        }

        return json_encode($actions);
    }

    /**
     * Serialize reactions array to JSON string.
     * 
     * Reactions are defensive abilities triggered by enemy actions.
     * 
     * @param array|string $reactions Reactions array or JSON string
     * @return string JSON-encoded reactions
     */
    private function serializeReactions($reactions)
    {
        if (is_string($reactions)) {
            return $reactions;
        }

        if (!is_array($reactions) || empty($reactions)) {
            return json_encode([]);
        }

        return json_encode($reactions);
    }

    /**
     * Serialize legendary actions array to JSON string.
     * 
     * Legendary actions are special powers legendary monsters can use on other turns.
     * 
     * @param array|string $actions Legendary actions array or JSON string
     * @return string JSON-encoded legendary actions
     */
    private function serializeLegendaryActions($actions)
    {
        if (is_string($actions)) {
            return $actions;
        }

        if (!is_array($actions) || empty($actions)) {
            return json_encode([]);
        }

        return json_encode($actions);
    }

    /**
     * Deserialize JSON fields in a monster record.
     * 
     * This is called after fetching from database.
     * Modifies the $monster array by reference (pass by reference: &$monster).
     * 
     * Process:
     * - Takes JSON string from database: '[{"name":"Fireball"}]'
     * - Decodes to PHP array: [['name' => 'Fireball']]
     * - Stores back in $monster['actions'] = [['name' => 'Fireball']]
     * - If empty or null, sets to empty array []
     * 
     * Pass by reference (&):
     * - Allows this method to modify the original $monster array
     * - Without &, changes would only affect a copy
     * 
     * @param array &$monster The monster record (passed by reference for modification)
     */
    private function deserializeJsonFields(&$monster)
    {
        // List of fields that contain JSON in the database
        $jsonFields = ['traits', 'actions', 'bonus_actions', 'reactions', 'legendary_actions'];

        // Deserialize each JSON field
        foreach ($jsonFields as $field) {
            // Guard: If field exists and is not empty
            if (!empty($monster[$field])) {
                // json_decode($string, true) converts JSON to PHP array
                // Second parameter true = return as associative array (not object)
                $decoded = json_decode($monster[$field], true);
                // Set to decoded array, or empty array if decode failed
                $monster[$field] = $decoded ?? [];
            } else {
                // If field is empty or missing, set to empty array
                $monster[$field] = [];
            }
        }
    }

    /**
     * ===== VALIDATION METHODS =====
     */

    /**
     * Validate monster form data before creating or updating.
     * 
     * Returns an array of error messages (empty if all validation passes).
     * This allows users to see ALL errors at once, not just the first one.
     * 
     * Validation rules:
     * - name: Required, non-empty
     * - size: Must be one of the valid D&D sizes
     * - ac: Positive number (armor class starts at 1)
     * - hp: Positive number (monsters need at least 1 hit point)
     * - abilities: Between 1 and 30 (D&D standard)
     * 
     * @param array $data Form data to validate
     * @return array Array of errors: ['field' => 'Error message'] (empty if no errors)
     */
    public function validate(array $data): array
    {
        $errors = [];

        // Guard: Check monster name exists and is not empty
        if (empty(trim($data['name'] ?? ''))) {
            $errors['name'] = 'Monster name is required';
        }

        // Guard: Check size is one of the valid D&D sizes
        // These are the standard D&D size categories
        $validSizes = ['Tiny', 'Small', 'Medium', 'Large', 'Huge', 'Gargantuan'];
        if (empty($data['size']) || !in_array($data['size'], $validSizes)) {
            $errors['size'] = 'Invalid monster size';
        }

        // Guard: Check armor class is a positive number
        // is_numeric() returns true for numbers and numeric strings
        if (!is_numeric($data['ac'] ?? null) || $data['ac'] < 1) {
            $errors['ac'] = 'Armor class must be a positive number';
        }

        // Guard: Check hit points is a positive number
        if (!is_numeric($data['hp'] ?? null) || $data['hp'] < 1) {
            $errors['hp'] = 'Hit points must be a positive number';
        }

        // Guard: Validate all six ability scores (STR, DEX, CON, INT, WIS, CHA)
        // D&D abilities range from 1 (very weak) to 30 (god-like)
        $abilities = ['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'];
        foreach ($abilities as $ability) {
            // If not provided, use default of 10 (average human)
            $val = $data[$ability] ?? 10;
            if (!is_numeric($val) || $val < 1 || $val > 30) {
                $errors[$ability] = 'Ability score must be between 1 and 30';
            }
        }

        return $errors;
    }

    /**
     * Build HTML form fields for editing existing actions.
     * 
     * This is a helper method for the edit form.
     * Takes deserialized action data and generates HTML input fields.
     * 
     * Current implementation is minimal.
     * In production, this would generate full form fields for each action:
     * - Name input: <input name="actions[0][name]" value="...">
     * - Damage input: <input name="actions[0][damage]" value="...">
     * - Description textarea: <textarea name="actions[0][description]">...</textarea>
     * 
     * @param array $actions Deserialized actions array from database
     * @return string HTML for action form fields
     */
    public function buildActionsHTML($actions): string
    {
        // Guard: If no actions provided, return empty string
        if (empty($actions)) {
            return '';
        }

        $html = '';
        $index = 0;

        // Loop through each action and generate form fields
        foreach ($actions as $action) {
            // Get action type (description, attack, etc.)
            $type = $action['type'] ?? 'description';
            // htmlspecialchars() prevents XSS attacks by escaping HTML characters
            // Example: <script> becomes &lt;script&gt; (harmless text)
            $name = htmlspecialchars($action['name'] ?? '');
            
            // HTML form fields for this action
            // TODO: Expand this to include all action properties
            $html .= "<!-- Action $index: $type -->";
            $index++;
        }

        return $html;
    }
}
