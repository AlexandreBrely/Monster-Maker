<?php

namespace App\Models;

use PDO;

/**
 * Lair Card Model
 * Manages lair action cards (horizontal landscape format)
 * 
 * Lair cards are separate from monster statblocks and contain:
 * - Lair actions (special actions that occur on initiative count 20)
 * - Regional effects (environmental changes around the lair)
 * - Lair description and atmosphere
 * 
 * For beginners:
 * This model handles all database operations for lair cards.
 * Think of it as the "data manager" that knows how to save, retrieve,
 * update, and delete lair cards from the database.
 */
class LairCard
{
    private $db; // Database connection (PDO object)

    public function __construct()
    {
        // Create database connection when model is instantiated
        // This happens automatically when you create a new LairCard()
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Get all lair cards for a specific user
     * 
     * For beginners:
     * This fetches all lair cards that belong to one user.
     * Steps:
     * 1. Prepare SQL query with placeholder (:userId) for security
     * 2. Execute query with actual user ID
     * 3. Convert JSON fields (like lair_actions) from text to PHP arrays
     * 4. Return array of lair cards
     */
    public function getByUser($userId)
    {
        // SQL query: Get all cards for this user, newest first
        $sql = "SELECT * FROM lair_card WHERE u_id = :userId ORDER BY created_at DESC";
        
        // Prepare statement (prevents SQL injection)
        $stmt = $this->db->prepare($sql);
        
        // Execute with actual user ID replacing :userId placeholder
        $stmt->execute([':userId' => $userId]);
        
        // Fetch all results as array
        $cards = $stmt->fetchAll();

        // Convert JSON fields to PHP arrays for each card
        // The & means we modify the actual $card, not a copy
        foreach ($cards as &$card) {
            $this->deserializeJsonFields($card);
        }

        return $cards;
    }

    /**
     * Get a single lair card by ID with deserialized data
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM lair_card WHERE lair_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $card = $stmt->fetch();

        if ($card) {
            $this->deserializeJsonFields($card);
        }

        return $card;
    }

    /**
     * Create a new lair card
     * 
     * For beginners:
     * This saves a new lair card to the database.
     * We convert PHP arrays (lair_actions) to JSON text before storing,
     * because databases can't store arrays directly.
     */
    public function create($data, $userId)
    {
        // SQL INSERT statement with placeholders (:u_id, :monster_name, etc.)
        $sql = "INSERT INTO lair_card (
                    u_id, monster_name, lair_name, lair_description,
                    lair_initiative, lair_actions, regional_effects, image_back
                ) VALUES (
                    :u_id, :monster_name, :lair_name, :lair_description,
                    :lair_initiative, :lair_actions, :regional_effects, :image_back
                )";

        $stmt = $this->db->prepare($sql);

        // Convert lair_actions array to JSON string for storage
        // Example: [{"name":"Quake","description":"..."}] becomes JSON text
        $lairActions = json_encode($data['lair_actions'] ?? []);

        $params = [
            ':u_id' => $userId,
            ':monster_name' => $data['monster_name'],
            ':lair_name' => $data['lair_name'],
            ':lair_description' => $data['lair_description'] ?? '',
            ':lair_initiative' => $data['lair_initiative'] ?? 20,
            ':lair_actions' => $lairActions,
            ':regional_effects' => $data['regional_effects'] ?? '',
            ':image_back' => $data['image_back'] ?? ''
        ];

        return $stmt->execute($params);
    }

    /**
     * Update an existing lair card
     */
    public function update($id, $data, $userId)
    {
        // Verify ownership
        $sql = "SELECT lair_id FROM lair_card WHERE lair_id = :id AND u_id = :userId";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id, ':userId' => $userId]);

        if (!$stmt->fetch()) {
            return false; // Not authorized
        }

        $sql = "UPDATE lair_card SET
                    monster_name = :monster_name,
                    lair_name = :lair_name,
                    lair_description = :lair_description,
                    lair_initiative = :lair_initiative,
                    lair_actions = :lair_actions,
                    regional_effects = :regional_effects,
                    image_back = :image_back
                WHERE lair_id = :id AND u_id = :userId";

        $stmt = $this->db->prepare($sql);

        $lairActions = json_encode($data['lair_actions'] ?? []);

        $params = [
            ':monster_name' => $data['monster_name'],
            ':lair_name' => $data['lair_name'],
            ':lair_description' => $data['lair_description'] ?? '',
            ':lair_initiative' => $data['lair_initiative'] ?? 20,
            ':lair_actions' => $lairActions,
            ':regional_effects' => $data['regional_effects'] ?? '',
            ':image_back' => $data['image_back'] ?? '',
            ':id' => $id,
            ':userId' => $userId
        ];

        return $stmt->execute($params);
    }

    /**
     * Delete a lair card (with ownership verification)
     */
    public function delete($id, $userId)
    {
        // Get image filename before deleting
        $card = $this->getById($id);
        if (!$card || $card['u_id'] != $userId) {
            return false;
        }

        // Delete image file if exists
        if (!empty($card['image_back'])) {
            $imagePath = __DIR__ . '/../../public/uploads/lair/' . $card['image_back'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $sql = "DELETE FROM lair_card WHERE lair_id = :id AND u_id = :userId";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id, ':userId' => $userId]);
    }

    /**
     * Deserialize JSON fields in a lair card record
     * 
     * For beginners:
     * "Deserialize" means converting JSON text back to PHP arrays.
     * The database stores lair_actions as JSON text like:
     * '[{"name":"Quake","description":"Ground shakes"}]'
     * 
     * This method converts it back to a PHP array so we can loop through it:
     * [['name' => 'Quake', 'description' => 'Ground shakes']]
     * 
     * The & before $card means we modify the original array, not a copy.
     */
    private function deserializeJsonFields(&$card)
    {
        if (!empty($card['lair_actions'])) {
            // json_decode converts JSON text to PHP array
            // true = return array (not object)
            $decoded = json_decode($card['lair_actions'], true);
            
            // Use decoded array, or empty array if decode failed
            $card['lair_actions'] = $decoded ?? [];
        } else {
            // No lair actions? Set to empty array
            $card['lair_actions'] = [];
        }
    }

    /**
     * Validate lair card data
     */
    public function validate($data)
    {
        $errors = [];

        if (empty($data['monster_name'])) {
            $errors['monster_name'] = 'Monster name is required';
        }

        if (empty($data['lair_name'])) {
            $errors['lair_name'] = 'Lair name is required';
        }

        if (empty($data['lair_actions']) || count($data['lair_actions']) === 0) {
            $errors['lair_actions'] = 'At least one lair action is required';
        }

        return $errors;
    }
}
