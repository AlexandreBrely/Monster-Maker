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
 */
class LairCard
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Get all lair cards for a specific user
     */
    public function getByUser($userId)
    {
        $sql = "SELECT * FROM lair_card WHERE u_id = :userId ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':userId' => $userId]);
        $cards = $stmt->fetchAll();

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
     */
    public function create($data, $userId)
    {
        $sql = "INSERT INTO lair_card (
                    u_id, monster_name, lair_name, lair_description,
                    lair_initiative, lair_actions, regional_effects, image_back
                ) VALUES (
                    :u_id, :monster_name, :lair_name, :lair_description,
                    :lair_initiative, :lair_actions, :regional_effects, :image_back
                )";

        $stmt = $this->db->prepare($sql);

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
     */
    private function deserializeJsonFields(&$card)
    {
        if (!empty($card['lair_actions'])) {
            $decoded = json_decode($card['lair_actions'], true);
            $card['lair_actions'] = $decoded ?? [];
        } else {
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
