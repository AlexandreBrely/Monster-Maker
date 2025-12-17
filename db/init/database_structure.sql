-- =============================================
-- Monster Maker Database Schema
-- Complete structure for production deployment
-- =============================================

-- ===== USERS TABLE =====
-- Stores user accounts with authentication credentials
CREATE TABLE IF NOT EXISTS users (
    u_id INT PRIMARY KEY AUTO_INCREMENT,
    u_name VARCHAR(50) NOT NULL UNIQUE,
    u_email VARCHAR(50) NOT NULL UNIQUE,
    u_password VARCHAR(255) NOT NULL,
    u_avatar TEXT,
    u_created_at DATETIME NOT NULL,
    INDEX idx_username (u_name),
    INDEX idx_email (u_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== MONSTERS TABLE =====
-- Stores D&D 5e monster statblocks
-- card_size: 1 = Boss (A6 horizontal), 2 = Small (playing card)
-- is_legendary: 0 = normal creature, 1 = legendary/boss monster
CREATE TABLE IF NOT EXISTS monster (
    monster_id INT PRIMARY KEY AUTO_INCREMENT,
    u_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    size VARCHAR(20) NOT NULL,
    type VARCHAR(50) NOT NULL,
    alignment VARCHAR(50),
    ac INT DEFAULT 10,
    ac_notes VARCHAR(255),
    hp INT DEFAULT 1,
    hit_dice VARCHAR(50),
    speed VARCHAR(100),
    initiative INT DEFAULT 0,
    proficiency_bonus INT DEFAULT 0,
    strength INT DEFAULT 10,
    dexterity INT DEFAULT 10,
    constitution INT DEFAULT 10,
    intelligence INT DEFAULT 10,
    wisdom INT DEFAULT 10,
    charisma INT DEFAULT 10,
    saving_throws TEXT,
    skills TEXT,
    senses TEXT,
    languages VARCHAR(255),
    challenge_rating VARCHAR(20) DEFAULT '0',
    damage_immunities TEXT,
    condition_immunities TEXT,
    damage_resistances TEXT,
    damage_vulnerabilities TEXT,
    traits LONGTEXT,
    actions LONGTEXT,
    bonus_actions TEXT,
    reactions LONGTEXT,
    legendary_actions LONGTEXT,
    is_legendary BOOLEAN DEFAULT 0,
    legendary_resistance TEXT,
    legendary_resistance_lair TEXT,
    lair_actions LONGTEXT,
    image_portrait VARCHAR(255),
    image_fullbody VARCHAR(255),
    is_public BOOLEAN DEFAULT 0,
    card_size INT DEFAULT 2,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (u_id) REFERENCES users(u_id) ON DELETE CASCADE,
    INDEX idx_user_id (u_id),
    INDEX idx_is_public (is_public),
    INDEX idx_monster_name (name),
    INDEX idx_monster_user (u_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== LAIR CARDS TABLE =====
-- Stores lair action cards (landscape format)
-- Separate from monster statblocks, used for initiative 20 lair actions
CREATE TABLE IF NOT EXISTS lair_card (
    lair_id INT PRIMARY KEY AUTO_INCREMENT,
    u_id INT NOT NULL,
    monster_name VARCHAR(100) NOT NULL,
    lair_name VARCHAR(100) NOT NULL,
    lair_description TEXT,
    lair_initiative INT DEFAULT 20,
    lair_actions LONGTEXT,
    regional_effects TEXT,
    image_back VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (u_id) REFERENCES users(u_id) ON DELETE CASCADE,
    INDEX idx_lair_user (u_id),
    INDEX idx_lair_monster (monster_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== LIKES TABLE =====
-- Tracks user likes on public monsters
-- Composite unique key ensures one like per user per monster
CREATE TABLE IF NOT EXISTS monster_likes (
    like_id INT PRIMARY KEY AUTO_INCREMENT,
    u_id INT NOT NULL,
    monster_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (u_id) REFERENCES users(u_id) ON DELETE CASCADE,
    FOREIGN KEY (monster_id) REFERENCES monster(monster_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_monster (u_id, monster_id),
    INDEX idx_monster_likes (monster_id),
    INDEX idx_user_likes (u_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== COLLECTIONS TABLE =====
-- Stores user-created collections for organizing monsters and lairs
-- Each user has a default "To Print" collection created automatically
-- Users can create custom collections for organizing content (e.g., "Goblin Patrol", "Kobold Encounter")
CREATE TABLE IF NOT EXISTS collections (
    collection_id INT PRIMARY KEY AUTO_INCREMENT,
    u_id INT NOT NULL,
    collection_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_default BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (u_id) REFERENCES users(u_id) ON DELETE CASCADE,
    UNIQUE KEY unique_collection_name (u_id, collection_name),
    INDEX idx_collection_user (u_id),
    INDEX idx_collection_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== COLLECTION MONSTERS TABLE =====
-- Junction table linking monsters to collections (many-to-many)
-- Allows a monster to be in multiple collections
-- Allows a collection to contain multiple monsters
CREATE TABLE IF NOT EXISTS collection_monsters (
    id INT PRIMARY KEY AUTO_INCREMENT,
    collection_id INT NOT NULL,
    monster_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (collection_id) REFERENCES collections(collection_id) ON DELETE CASCADE,
    FOREIGN KEY (monster_id) REFERENCES monster(monster_id) ON DELETE CASCADE,
    UNIQUE KEY unique_collection_monster (collection_id, monster_id),
    INDEX idx_collection (collection_id),
    INDEX idx_monster (monster_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== SPELL CARDS TABLE =====
-- Stores D&D 5e spell cards for quick reference at the table
-- Users can create custom spells or track favorite spells
-- Future feature: Print spells as cards similar to monster cards
CREATE TABLE IF NOT EXISTS spell_card (
    spell_id INT PRIMARY KEY AUTO_INCREMENT,
    u_id INT NOT NULL,
    spell_name VARCHAR(120) NOT NULL,
    spell_level SMALLINT,
    spell_casting VARCHAR(20),
    spell_range VARCHAR(75),
    spell_components VARCHAR(25),
    spell_duration VARCHAR(25),
    spell_school VARCHAR(50),
    spell_attack VARCHAR(50),
    spell_damage VARCHAR(50),
    spell_description TEXT NOT NULL,
    spell_upcast TEXT,
    spell_tags VARCHAR(50),
    spells_available VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (u_id) REFERENCES users(u_id) ON DELETE CASCADE,
    INDEX idx_spell_user (u_id),
    INDEX idx_spell_level (spell_level),
    INDEX idx_spell_school (spell_school)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
