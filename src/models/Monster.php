<?php

namespace App\Models;

use App\Models\Database;
use PDO;
use PDOException;

/**
 * Class Monster
 * Modèle représentant les opérations CRUD sur la table `monster`.
 *
 * Gère :
 * - Création et modification de monstres
 * - Validation des données avant insertion
 * - Gestion des actions, réactions, et capacités légendaires
 * - Sérialisation/désérialisation des données JSON
 * - Upload et gestion des images
 *
 * Notes générales :
 * - Cette classe attend que Database::getConnection() retourne un objet PDO configuré
 * - Les méthodes retournent des types simples (array, string|false, bool)
 * - Les données JSON (actions, réactions, etc.) sont sérialisées/désérialisées automatiquement
 */
class Monster
{
    /** @var PDO Connexion PDO utilisée par la classe */
    private $db;

    /** Chemin de base pour les uploads d'images */
    const UPLOAD_PATH = __DIR__ . '/../../public/uploads/monsters/';

    /**
     * Constructeur
     * Initialise la connexion à la base de données
     */
    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Récupère tous les monstres publics
     *
     * Retour :
     * - array : tableau associatif de monstres (vide si aucun)
     */
    public function getAll(): array
    {
        $sql = "SELECT * FROM monster WHERE is_public = 1 ORDER BY created_at DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère un monstre par son ID
     *
     * Param :
     * - $id : identifiant du monstre
     *
     * Retour :
     * - array | false : tableau du monstre avec données JSON désérialisées, ou false
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
     * Récupère tous les monstres d'un utilisateur
     *
     * Param :
     * - $userId : identifiant de l'utilisateur propriétaire
     *
     * Retour :
     * - array : tableau de monstres
     */
    public function getByUser($userId): array
    {
        $sql = "SELECT * FROM monster WHERE u_id = :userId ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':userId' => $userId]);
        
        $monsters = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Désérialiser les champs JSON pour chaque monstre
        foreach ($monsters as &$monster) {
            $this->deserializeJsonFields($monster);
        }
        
        return $monsters;
    }

    /**
     * Recherche des monstres par critères
     *
     * Param :
     * - $criteria : tableau associatif (name, type, size, challenge_rating, etc.)
     *
     * Retour :
     * - array : monstres correspondant aux critères
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
     * Crée un nouveau monstre
     *
     * Param :
     * - $data : tableau associatif contenant toutes les données du monstre
     *   Clés attendues : name, size, type, alignment, ac, hp, hit_dice, speed, etc.
     * - $userId : identifiant du créateur (u_id)
     *
     * Retour :
     * - string (lastInsertId) en cas de succès
     * - false en cas d'erreur
     */
    public function create(array $data, $userId)
    {
        try {
            // Validation basique
            if (empty($data['name']) || empty($data['size']) || empty($data['ac']) || empty($data['hp'])) {
                return false;
            }

            // Traitement des actions, réactions, et capacités légendaires
            $data['traits'] = $this->serializeTraits($data['traits'] ?? []);
            $data['actions'] = $this->serializeActions($data['actions'] ?? []);
            $data['bonus_actions'] = $this->serializeBonusActions($data['bonus_actions'] ?? []);
            $data['reactions'] = $this->serializeReactions($data['reactions'] ?? []);
            $data['legendary_actions'] = $this->serializeLegendaryActions($data['legendary_actions'] ?? []);

            // Traitement des images
            $data['image_portrait'] = $data['image_portrait'] ?? null;
            $data['image_fullbody'] = $data['image_fullbody'] ?? null;

            $sql = "
                INSERT INTO monster (
                    name, size, type, alignment, ac, hp, hit_dice, ac_notes,
                    equipment_variants, speed, proficiency_bonus,
                    strength, dexterity, constitution, intelligence, wisdom, charisma,
                    saving_throws, skills, senses, languages, challenge_rating,
                    damage_immunities, condition_immunities, damage_resistances, damage_vulnerabilities,
                    traits, actions, bonus_actions, reactions, legendary_actions,
                    is_legendary, legendary_resistance, legendary_resistance_lair, lair_actions,
                    image_portrait, image_fullbody, card_size, is_public, u_id
                ) VALUES (
                    :name, :size, :type, :alignment, :ac, :hp, :hit_dice, :ac_notes,
                    :equipment_variants, :speed, :proficiency_bonus,
                    :strength, :dexterity, :constitution, :intelligence, :wisdom, :charisma,
                    :saving_throws, :skills, :senses, :languages, :challenge_rating,
                    :damage_immunities, :condition_immunities, :damage_resistances, :damage_vulnerabilities,
                    :traits, :actions, :bonus_actions, :reactions, :legendary_actions,
                    :is_legendary, :legendary_resistance, :legendary_resistance_lair, :lair_actions,
                    :image_portrait, :image_fullbody, :card_size, :is_public, :userId
                )
            ";

            $stmt = $this->db->prepare($sql);

            // Binding des paramètres avec valeurs par défaut
            $stmt->execute([
                ':name' => $data['name'],
                ':size' => $data['size'] ?? '',
                ':type' => $data['type'] ?? '',
                ':alignment' => $data['alignment'] ?? '',
                ':ac' => $data['ac'] ?? 10,
                ':hp' => $data['hp'] ?? 1,
                ':hit_dice' => $data['hit_dice'] ?? '',
                ':ac_notes' => $data['ac_notes'] ?? '',
                ':equipment_variants' => $data['equipment_variants'] ?? '',
                ':speed' => $data['speed'] ?? '',
                ':proficiency_bonus' => $data['proficiency_bonus'] ?? 0,
                ':strength' => $data['strength'] ?? 10,
                ':dexterity' => $data['dexterity'] ?? 10,
                ':constitution' => $data['constitution'] ?? 10,
                ':intelligence' => $data['intelligence'] ?? 10,
                ':wisdom' => $data['wisdom'] ?? 10,
                ':charisma' => $data['charisma'] ?? 10,
                ':saving_throws' => $data['saving_throws'] ?? '',
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
                ':card_size' => $data['card_size'] ?? 1,
                ':is_public' => $data['is_public'] ?? 0,
                ':userId' => $userId
            ]);

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            // En production, logger l'erreur
            error_log('Monster create error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour un monstre existant
     *
     * Param :
     * - $id : identifiant du monstre
     * - $data : tableau associatif des données à mettre à jour
     * - $userId : identifiant du propriétaire (sécurité : vérification propriété)
     *
     * Retour :
     * - bool : true si succès, false sinon
     */
    public function update($id, array $data, $userId): bool
    {
        try {
            // Vérifier que le monstre appartient à l'utilisateur
            $sql = "SELECT monster_id FROM monster WHERE monster_id = :id AND u_id = :userId";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id, ':userId' => $userId]);
            
            if (!$stmt->fetch()) {
                return false; // Monstre inexistant ou ne appartient pas à l'utilisateur
            }

            // Sérialisation des données complexes
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

            // Construction dynamique de la requête UPDATE
            $updates = [];
            $params = [':id' => $id];

            foreach ($data as $key => $value) {
                $updates[] = "$key = :$key";
                $params[":$key"] = $value;
            }

            if (empty($updates)) {
                return true; // Rien à mettre à jour
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
     * Supprime un monstre
     *
     * Param :
     * - $id : identifiant du monstre
     * - $userId : identifiant du propriétaire (sécurité)
     *
     * Retour :
     * - bool : true si suppression réussie
     */
    public function delete($id, $userId): bool
    {
        try {
            // Récupérer les images associées avant suppression
            $sql = "SELECT image_portrait, image_fullbody FROM monster WHERE monster_id = :id AND u_id = :userId";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id, ':userId' => $userId]);
            $monster = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$monster) {
                return false; // Monstre inexistant ou ne appartient pas à l'utilisateur
            }

            // Suppression du monstre
            $sql = "DELETE FROM monster WHERE monster_id = :id AND u_id = :userId";
            $stmt = $this->db->prepare($sql);
            $success = (bool) $stmt->execute([':id' => $id, ':userId' => $userId]);

            // Suppression des fichiers image
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

    /**
     * ===== MÉTHODES DE SÉRIALISATION / DÉSÉRIALISATION =====
     * Convertissent les données JSON et PHP
     */

    /**
     * Sérialise les traits
     */
    private function serializeTraits($traits)
    {
        if (is_string($traits)) {
            return $traits; // Déjà en JSON
        }

        if (!is_array($traits) || empty($traits)) {
            return json_encode([]);
        }

        return json_encode($traits);
    }

    /**
     * Sérialise les actions depuis le formulaire en JSON
     * Les actions arrivent comme tableau PHP, on les stocke en JSON dans la DB
     *
     * Param :
     * - $actions : tableau d'actions du formulaire ou JSON string
     *
     * Retour :
     * - string JSON
     */
    private function serializeActions($actions)
    {
        if (is_string($actions)) {
            return $actions; // Déjà en JSON
        }

        if (!is_array($actions) || empty($actions)) {
            return json_encode([]);
        }

        return json_encode($actions);
    }

    /**
     * Sérialise les bonus actions
     */
    private function serializeBonusActions($actions)
    {
        if (is_string($actions)) {
            return $actions; // Déjà en JSON
        }

        if (!is_array($actions) || empty($actions)) {
            return json_encode([]);
        }

        return json_encode($actions);
    }

    /**
     * Sérialise les réactions
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
     * Sérialise les capacités légendaires
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
     * Désérialise les champs JSON d'un monstre
     * Modifie directement le tableau passé en référence
     *
     * Param :
     * - $monster : tableau du monstre (passé par référence)
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

    /**
     * ===== MÉTHODES DE VALIDATION =====
     */

    /**
     * Valide les données du formulaire avant création/modification
     *
     * Param :
     * - $data : tableau des données du formulaire
     *
     * Retour :
     * - array : tableau des erreurs (vide si valide)
     */
    public function validate(array $data): array
    {
        $errors = [];

        // Validation du nom
        if (empty(trim($data['name'] ?? ''))) {
            $errors['name'] = 'Monster name is required';
        }

        // Validation de la taille
        $validSizes = ['Tiny', 'Small', 'Medium', 'Large', 'Huge', 'Gargantuan'];
        if (empty($data['size']) || !in_array($data['size'], $validSizes)) {
            $errors['size'] = 'Invalid monster size';
        }

        // Validation de l'AC (nombre positif)
        if (!is_numeric($data['ac'] ?? null) || $data['ac'] < 1) {
            $errors['ac'] = 'Armor class must be a positive number';
        }

        // Validation des PV
        if (!is_numeric($data['hp'] ?? null) || $data['hp'] < 1) {
            $errors['hp'] = 'Hit points must be a positive number';
        }

        // Validation des caractéristiques (1-20)
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
     * Construit le HTML des sections actions/réactions depuis les données JSON
     * Utile pour les formulaires d'édition
     *
     * Param :
     * - $actions : tableau décodé des actions
     *
     * Retour :
     * - string HTML
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
            
            // HTML du formulaire pour chaque action existante
            // À développer selon les besoins d'édition
            $html .= "<!-- Action $index: $type -->";
            $index++;
        }

        return $html;
    }
}
