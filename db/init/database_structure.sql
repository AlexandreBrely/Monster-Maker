-- Monster Maker Database Schema
-- This file documents the actual database structure

-- ===== USERS TABLE =====
CREATE TABLE IF NOT EXISTS users (
    u_id INT PRIMARY KEY AUTO_INCREMENT,
    u_name VARCHAR(50) NOT NULL UNIQUE,
    u_email VARCHAR(50) NOT NULL UNIQUE,
    u_password VARCHAR(255) NOT NULL,
    u_avatar TEXT,
    u_created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== MONSTERS TABLE =====
CREATE TABLE IF NOT EXISTS monster (
    monster_id INT PRIMARY KEY AUTO_INCREMENT,
    u_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    size VARCHAR(20) NOT NULL,
    type VARCHAR(50) NOT NULL,
    alignment VARCHAR(50),
    ac INT DEFAULT 10,
    hp INT DEFAULT 1,
    hit_dice VARCHAR(50),
    ac_notes VARCHAR(255),
    equipment_variants TEXT,
    speed VARCHAR(100),
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
    card_size INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (u_id) REFERENCES users(u_id) ON DELETE CASCADE,
    INDEX idx_user_id (u_id),
    INDEX idx_is_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== CREATE INDEXES =====
CREATE INDEX idx_username ON users(u_username);
CREATE INDEX idx_email ON users(u_email);
CREATE INDEX idx_monster_name ON monster(name);
CREATE INDEX idx_monster_user ON monster(u_id, created_at);
