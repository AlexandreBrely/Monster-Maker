<?php

namespace App\Models;

use PDO;
use App\Models\Database;

/**
 * Collection Model
 * 
 * WHAT IS A COLLECTION?
 * A collection is like a folder or playlist for organizing monsters.
 * Think of it like:
 * - Music playlists (group songs you like)
 * - Photo albums (group related photos)
 * - Bookmarks folders (organize websites)
 * 
 * In our app, users create collections to organize monsters they want to print or use.
 * 
 * FOR BEGINNERS - DATABASE RELATIONSHIPS:
 * This is a MANY-TO-MANY relationship:
 * - One monster can be in multiple collections (like a song in multiple playlists)
 * - One collection can have multiple monsters (like a playlist with multiple songs)
 * 
 * HOW WE STORE THIS:
 * We use 2 database tables:
 * 1. "collections" table: Stores collection info (name, description, owner)
 * 2. "collection_monsters" table: Links collections to monsters (junction table)
 * 
 * EXAMPLE:
 * Collections table:
 * | collection_id | u_id | collection_name | is_default |
 * |---------------|------|-----------------|------------|
 * | 1             | 5    | To Print        | 1          |
 * | 2             | 5    | My Favorites    | 0          |
 * 
 * Collection_monsters table (junction):
 * | id | collection_id | monster_id |
 * |----|---------------|------------|
 * | 1  | 1             | 10         |  <- Monster 10 is in "To Print"
 * | 2  | 1             | 15         |  <- Monster 15 is in "To Print"
 * | 3  | 2             | 10         |  <- Monster 10 is ALSO in "My Favorites"
 * 
 * KEY FEATURES:
 * - Default "To Print" collection created automatically on user registration
 * - Users can create unlimited custom collections
 * - Cascade deletes: removing collection removes all its monster associations
 * - Ownership verification: users can only manage their own collections
 */
class Collection
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Create default "To Print" collection for a new user
     * 
     * FOR BEGINNERS - WHEN IS THIS CALLED?
     * When a user registers for an account, we automatically create a collection
     * called "To Print" for them. This is their main queue for cards to export as PDF.
     * 
     * WHY AUTO-CREATE?
     * - Better user experience: they can start organizing immediately
     * - Ensures every user has at least one collection
     * - "To Print" is the core feature (printing cards), so we set it up by default
     * 
     * THE is_default FLAG:
     * - is_default = 1 means this is the auto-created "To Print" collection
     * - is_default = 0 means user created it manually
     * - Users cannot delete the default collection (safety measure)
     * 
     * @param int $userId The new user's ID from the users table
     * @return bool True if created successfully, false if creation failed
     */
    public function createDefaultCollection(int $userId): bool
    {
        $query = "INSERT INTO collections (u_id, collection_name, description, is_default) 
                  VALUES (:u_id, 'To Print', 'Default collection for cards ready to print', 1)";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':u_id', $userId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Create a new custom collection
     * 
     * FOR BEGINNERS - HOW CREATING WORKS:
     * 1. User fills out "Create Collection" form
     * 2. Controller validates and calls this method
     * 3. We check if collection name already exists (prevent duplicates)
     * 4. Insert new row into collections table
     * 5. Return the new collection's ID
     * 
     * @param int $userId User ID (owner)
     * @param string $collectionName Collection name (max 100 chars)
     * @param string|null $description Optional description
     * @return int|false Collection ID on success, false on failure
     */
    public function create(int $userId, string $collectionName, ?string $description = null)
    {
        if ($this->collectionExists($userId, $collectionName)) {
            return false;
        }

        $query = "INSERT INTO collections (u_id, collection_name, description, is_default) 
                  VALUES (:u_id, :collection_name, :description, 0)";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':u_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':collection_name', $collectionName, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            return (int) $this->db->lastInsertId();
        }
        
        return false;
    }

    /**
     * Get all collections for a specific user
     * 
     * FOR BEGINNERS - THE LEFT JOIN:
     * We want to show monster count for each collection.
     * Problem: Some collections might have ZERO monsters.
     * Solution: LEFT JOIN (includes collections even if they have no monsters)
     * 
     * INNER JOIN would exclude empty collections
     * LEFT JOIN includes ALL collections, null if no monsters
     * 
     * @param int $userId User ID
     * @return array Array of collections with monster counts
     */
    public function getByUser(int $userId): array
    {
        $query = "SELECT 
                    c.collection_id,
                    c.collection_name,
                    c.description,
                    c.is_default,
                    c.created_at,
                    COUNT(cm.monster_id) as monster_count
                  FROM collections c
                  LEFT JOIN collection_monsters cm ON c.collection_id = cm.collection_id
                  WHERE c.u_id = :u_id
                  GROUP BY c.collection_id
                  ORDER BY c.is_default DESC, c.collection_name ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':u_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get a specific collection by ID
     * 
     * @param int $collectionId Collection ID
     * @return array|false Collection data or false if not found
     */
    public function getById(int $collectionId)
    {
        $query = "SELECT 
                    c.collection_id,
                    c.u_id,
                    c.collection_name,
                    c.description,
                    c.is_default,
                    c.created_at,
                    COUNT(cm.monster_id) as monster_count
                  FROM collections c
                  LEFT JOIN collection_monsters cm ON c.collection_id = cm.collection_id
                  WHERE c.collection_id = :collection_id
                  GROUP BY c.collection_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':collection_id', $collectionId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update collection name and/or description
     * 
     * @param int $collectionId Collection ID
     * @param string $collectionName New name
     * @param string|null $description New description
     * @return bool True on success, false on failure
     */
    public function update(int $collectionId, string $collectionName, ?string $description = null): bool
    {
        $query = "UPDATE collections 
                  SET collection_name = :collection_name, description = :description 
                  WHERE collection_id = :collection_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':collection_id', $collectionId, PDO::PARAM_INT);
        $stmt->bindParam(':collection_name', $collectionName, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        
        return $stmt->execute();
    }

    /**
     * Delete a collection
     * 
     * FOR BEGINNERS - CASCADE DELETE:
     * When we delete a collection, what happens to the monsters in it?
     * Answer: The MONSTERS are NOT deleted (they still exist in monster table)
     * Only the LINKS in collection_monsters table are deleted (foreign key cascade)
     * 
     * Think of it like deleting a playlist:
     * - Playlist is gone
     * - Songs still exist
     * - Only the "this song is in this playlist" records are removed
     * 
     * @param int $collectionId Collection ID to delete
     * @return bool True on success, false on failure
     */
    public function delete(int $collectionId): bool
    {
        $query = "DELETE FROM collections WHERE collection_id = :collection_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':collection_id', $collectionId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Add a monster to a collection
     * 
     * FOR BEGINNERS - HOW THIS WORKS:
     * This creates a link between a collection and a monster in the junction table.
     * Like adding a song to a playlist - the song still exists independently,
     * but now there's a record saying "this song is in this playlist".
     * 
     * EXAMPLE:
     * User clicks "Add to To Print" on a dragon monster
     * This inserts: collection_id=1, monster_id=42 into collection_monsters table
     * Now the dragon appears when viewing the "To Print" collection
     * 
     * DUPLICATE PREVENTION:
     * We check if monster is already in collection before adding.
     * Like Spotify preventing you from adding the same song twice to a playlist.
     * 
     * @param int $collectionId Which collection to add to
     * @param int $monsterId Which monster to add
     * @return bool True if added successfully, false if already exists or error
     */
    public function addMonster(int $collectionId, int $monsterId): bool
    {
        if ($this->monsterInCollection($collectionId, $monsterId)) {
            return false;
        }

        $query = "INSERT INTO collection_monsters (collection_id, monster_id) 
                  VALUES (:collection_id, :monster_id)";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':collection_id', $collectionId, PDO::PARAM_INT);
        $stmt->bindParam(':monster_id', $monsterId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Remove a monster from a collection
     * 
     * FOR BEGINNERS - DELETE vs REMOVE:
     * This REMOVES the link (deletes from collection_monsters table)
     * The monster itself is NOT deleted (still exists in monster table)
     * 
     * Like removing a song from a playlist:
     * - Song still exists in your library
     * - Just no longer in that specific playlist
     * 
     * @param int $collectionId Collection ID
     * @param int $monsterId Monster ID
     * @return bool True on success, false on failure
     */
    public function removeMonster(int $collectionId, int $monsterId): bool
    {
        $query = "DELETE FROM collection_monsters 
                  WHERE collection_id = :collection_id AND monster_id = :monster_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':collection_id', $collectionId, PDO::PARAM_INT);
        $stmt->bindParam(':monster_id', $monsterId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Get all monsters in a collection
     * 
     * FOR BEGINNERS - SQL JOIN EXPLAINED:
     * We need data from 2 tables:
     * - collection_monsters: tells us which monsters are in the collection
     * - monster: has the actual monster data (name, image, stats, etc.)
     * 
     * We use INNER JOIN to combine rows from both tables:
     * "Give me all monsters WHERE their ID matches a row in collection_monsters"
     * 
     * VISUAL EXAMPLE:
     * collection_monsters:        monster:
     * | monster_id |              | monster_id | name     |
     * |------------|              |------------|----------|
     * | 10         |    JOIN →    | 10         | Dragon   |
     * | 15         |              | 15         | Goblin   |
     * 
     * Result: We get full data for Dragon and Goblin
     * 
     * WHY ORDER BY added_at DESC?
     * DESC = descending = newest first
     * Shows most recently added monsters at the top (like Instagram feed)
     * 
     * @param int $collectionId Which collection to get monsters from
     * @return array Array of monster objects with all their data + added_at timestamp
     */
    public function getMonsters(int $collectionId): array
    {
        $query = "SELECT 
                    m.*,
                    cm.added_at
                  FROM collection_monsters cm
                  INNER JOIN monster m ON cm.monster_id = m.monster_id
                  WHERE cm.collection_id = :collection_id
                  ORDER BY cm.added_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':collection_id', $collectionId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all collections that contain a specific monster
     * 
     * Useful for displaying which collections a monster belongs to.
     * 
     * @param int $monsterId Monster ID
     * @param int|null $userId Optional: filter by user ID
     * @return array Array of collection data
     */
    public function getCollectionsForMonster(int $monsterId, ?int $userId = null): array
    {
        $query = "SELECT 
                    c.collection_id,
                    c.collection_name,
                    c.is_default
                  FROM collections c
                  INNER JOIN collection_monsters cm ON c.collection_id = cm.collection_id
                  WHERE cm.monster_id = :monster_id";
        
        if ($userId !== null) {
            $query .= " AND c.u_id = :u_id";
        }
        
        $query .= " ORDER BY c.is_default DESC, c.collection_name ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':monster_id', $monsterId, PDO::PARAM_INT);
        
        if ($userId !== null) {
            $stmt->bindParam(':u_id', $userId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get user's default "To Print" collection
     * 
     * @param int $userId User ID
     * @return array|false Collection data or false if not found
     */
    public function getDefaultCollection(int $userId)
    {
        $query = "SELECT * FROM collections 
                  WHERE u_id = :u_id AND is_default = 1 
                  LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':u_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Check if a collection exists for a user (by name)
     * 
     * Used to prevent duplicate collection names.
     * 
     * @param int $userId User ID
     * @param string $collectionName Collection name
     * @return bool True if exists, false otherwise
     */
    private function collectionExists(int $userId, string $collectionName): bool
    {
        $query = "SELECT COUNT(*) FROM collections 
                  WHERE u_id = :u_id AND collection_name = :collection_name";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':u_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':collection_name', $collectionName, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Check if a monster is already in a collection
     * 
     * Prevents duplicate entries (same monster added twice to one collection).
     * 
     * @param int $collectionId Collection ID
     * @param int $monsterId Monster ID
     * @return bool True if exists, false otherwise
     */
    private function monsterInCollection(int $collectionId, int $monsterId): bool
    {
        $query = "SELECT COUNT(*) FROM collection_monsters 
                  WHERE collection_id = :collection_id AND monster_id = :monster_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':collection_id', $collectionId, PDO::PARAM_INT);
        $stmt->bindParam(':monster_id', $monsterId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
}
