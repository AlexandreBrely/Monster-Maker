<?php

namespace App\Models;

use App\Models\Database;
use PDO;
use PDOException;

/**
 * Monster Model
 * Handles all database operations for monsters.
 * 
 * ORGANIZATION:
 * 1. Constructor
 * 2. CRUD Operations (Create, Read, Update, Delete)
 * 3. Search & Filtering
 * 4. Serialization (JSON conversion for database storage)
 * 5. Deserialization (JSON to PHP arrays)
 * 6. Validation
 */
class Monster
{
    private $db;
    const UPLOAD_PATH = __DIR__ . '/../../public/uploads/monsters/';

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // ===================================================================
    // SECTION 1: CRUD OPERATIONS
    // ===================================================================

    /**
     * CREATE - Insert new monster into database.
     * Serializes complex fields (traits, actions) before storage.
     */
    public function create(array $data, $userId)
    {
        try {
            if (empty($data['name']) || empty($data['size']) || empty($data['ac']) || empty($data['hp'])) {
                return false;
            }

            // Serialize complex fields
            $data['traits'] = $this->serializeTraits($data['traits'] ?? []);
            $data['actions'] = $this->serializeActions($data['actions'] ?? []);
            $data['bonus_actions'] = $this->serializeBonusActions($data['bonus_actions'] ?? []);
            $data['reactions'] = $this->serializeReactions($data['reactions'] ?? []);
            $data['legendary_actions'] = $this->serializeLegendaryActions($data['legendary_actions'] ?? []);

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
            error_log('Monster create error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * READ - Get single monster by ID with deserialized data.
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM monster WHERE monster_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $monster = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($monster) {
            $this->deserializeJsonFields($monster);
        }
        
        return $monster;
    }

    /**
     * READ - Get all public monsters (basic query, no deserialization).
     */
    public function getAll(): array
    {
        $sql = "SELECT * FROM monster WHERE is_public = 1 ORDER BY created_at DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * READ - Get all monsters for a specific user.
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
        
        foreach ($monsters as &$monster) {
            $this->deserializeJsonFields($monster);
        }
        
        return $monsters;
    }

    /**
     * UPDATE - Modify existing monster (owner verification required).
     */
    public function update($id, array $data, $userId): bool
    {
        try {
            // Verify ownership
            $sql = "SELECT monster_id FROM monster WHERE monster_id = :id AND u_id = :userId";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id, ':userId' => $userId]);
            
            if (!$stmt->fetch()) {
                return false;
            }

            // Serialize complex fields if provided
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

            // Build dynamic SET clause
            $updates = [];
            $params = [':id' => $id];

            foreach ($data as $key => $value) {
                $updates[] = "$key = :$key";
                $params[":$key"] = $value;
            }

            if (empty($updates)) {
                return true;
            }

            $sql = "UPDATE monster SET " . implode(', ', $updates) . " WHERE monster_id = :id";
            $stmt = $this->db->prepare($sql);

            return (bool) $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('Monster update error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * DELETE - Remove monster and associated image files.
     */
    public function delete($id, $userId): bool
    {
        try {
            // Fetch monster to get image filenames
            $sql = "SELECT image_portrait, image_fullbody FROM monster WHERE monster_id = :id AND u_id = :userId";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id, ':userId' => $userId]);
            $monster = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$monster) {
                return false;
            }

            // Delete from database
            $sql = "DELETE FROM monster WHERE monster_id = :id AND u_id = :userId";
            $stmt = $this->db->prepare($sql);
            $success = (bool) $stmt->execute([':id' => $id, ':userId' => $userId]);

            // Delete physical files if deletion succeeded
            if ($success) {
                if (!empty($monster['image_portrait'])) {
                    @unlink(self::UPLOAD_PATH . $monster['image_portrait']);
                }
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

    // ===================================================================
    // SECTION 2: SEARCH & FILTERING
    // ===================================================================

    /**
     * Search monsters by multiple criteria with dynamic SQL building.
     */
    public function search(array $criteria): array
    {
        $sql = "SELECT * FROM monster WHERE is_public = 1";
        $params = [];

        if (!empty($criteria['name'])) {
            $sql .= " AND name LIKE :name";
            $params[':name'] = '%' . $criteria['name'] . '%';
        }

        if (!empty($criteria['type'])) {
            $sql .= " AND type = :type";
            $params[':type'] = $criteria['type'];
        }

        if (!empty($criteria['size'])) {
            $sql .= " AND size = :size";
            $params[':size'] = $criteria['size'];
        }

        if (!empty($criteria['challenge_rating'])) {
            $sql .= " AND challenge_rating = :cr";
            $params[':cr'] = $criteria['challenge_rating'];
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $monsters = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($monsters as &$monster) {
            $this->deserializeJsonFields($monster);
        }
        
        return $monsters;
    }

    /**
     * Get all public monsters with filters and sorting.
     * Includes like counts via subquery.
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
        
        if ($search !== null && $search !== '') {
            $sql .= " AND m.name LIKE :search";
            $params[':search'] = '%' . $search . '%';
        }
        
        if ($size !== null && $size !== '') {
            $sql .= " AND m.size = :size";
            $params[':size'] = $size;
        }
        
        if ($type !== null && $type !== '') {
            $sql .= " AND m.type = :type";
            $params[':type'] = $type;
        }
        
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

    // ===================================================================
    // SECTION 3: SERIALIZATION (PHP arrays → JSON strings)
    // ===================================================================

    private function serializeTraits($traits)
    {
        if (is_string($traits)) return $traits;
        if (!is_array($traits) || empty($traits)) return json_encode([]);
        return json_encode($traits);
    }

    private function serializeActions($actions)
    {
        if (is_string($actions)) return $actions;
        if (!is_array($actions) || empty($actions)) return json_encode([]);
        return json_encode($actions);
    }

    private function serializeBonusActions($actions)
    {
        if (is_string($actions)) return $actions;
        if (!is_array($actions) || empty($actions)) return json_encode([]);
        return json_encode($actions);
    }

    private function serializeReactions($reactions)
    {
        if (is_string($reactions)) return $reactions;
        if (!is_array($reactions) || empty($reactions)) return json_encode([]);
        return json_encode($reactions);
    }

    private function serializeLegendaryActions($actions)
    {
        if (is_string($actions)) return $actions;
        if (!is_array($actions) || empty($actions)) return json_encode([]);
        return json_encode($actions);
    }

    // ===================================================================
    // SECTION 4: DESERIALIZATION (JSON strings → PHP arrays)
    // ===================================================================

    /**
     * Convert JSON fields to PHP arrays for easier use in views.
     * Modifies $monster array by reference.
     */
    private function deserializeJsonFields(&$monster)
    {
        $jsonFields = ['traits', 'actions', 'bonus_actions', 'reactions', 'legendary_actions'];

        foreach ($jsonFields as $field) {
            if (!empty($monster[$field])) {
                $decoded = json_decode($monster[$field], true);
                $monster[$field] = $decoded ?? [];
            } else {
                $monster[$field] = [];
            }
        }
    }

    // ===================================================================
    // SECTION 5: VALIDATION
    // ===================================================================

    /**
     * Validate monster data before create/update operations.
     * Returns array of errors (empty if valid).
     */
    public function validate(array $data): array
    {
        $errors = [];

        if (empty(trim($data['name'] ?? ''))) {
            $errors['name'] = 'Monster name is required';
        }

        $validSizes = ['Tiny', 'Small', 'Medium', 'Large', 'Huge', 'Gargantuan'];
        if (empty($data['size']) || !in_array($data['size'], $validSizes)) {
            $errors['size'] = 'Invalid monster size';
        }

        if (!is_numeric($data['ac'] ?? null) || $data['ac'] < 1) {
            $errors['ac'] = 'Armor class must be a positive number';
        }

        if (!is_numeric($data['hp'] ?? null) || $data['hp'] < 1) {
            $errors['hp'] = 'Hit points must be a positive number';
        }

        $abilities = ['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'];
        foreach ($abilities as $ability) {
            $val = $data[$ability] ?? 10;
            if (!is_numeric($val) || $val < 1 || $val > 30) {
                $errors[$ability] = 'Ability score must be between 1 and 30';
            }
        }

        return $errors;
    }

    /**
     * Build HTML form fields for editing actions (minimal implementation).
     */
    public function buildActionsHTML($actions): string
    {
        if (empty($actions)) {
            return '';
        }

        $html = '';
        $index = 0;

        foreach ($actions as $action) {
            $type = $action['type'] ?? 'description';
            $name = htmlspecialchars($action['name'] ?? '');
            $html .= "<!-- Action $index: $type -->";
            $index++;
        }

        return $html;
    }
}
