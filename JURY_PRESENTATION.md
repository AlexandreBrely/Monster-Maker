# Monster Maker - Jury Presentation

## 📸 Code Reference Guide for Screenshots

Throughout this presentation, you'll find links to source code files where you can take screenshots. Here's a quick reference:

**Key Files for Screenshots:**

| Component | File | Use Case |
|-----------|------|----------|
| **Router** | [public/index.php](public/index.php) | URL routing, entry point |
| **Controllers** | [src/controllers/MonsterController.php](src/controllers/MonsterController.php) | Request handling, business logic |
| **Models** | [src/models/MonsterLike.php](src/models/MonsterLike.php), [src/models/FileUploadService.php](src/models/FileUploadService.php) | Database operations, validation |
| **Forms** | [src/views/auth/register.php](src/views/auth/register.php) | HTML forms with validation |
| **JavaScript** | [public/js/monster-form.js](public/js/monster-form.js), [public/js/monster-actions.js](public/js/monster-actions.js) | Complex interactions, AJAX |
| **CSS Print** | [public/css/boss-card.css](public/css/boss-card.css), [public/css/small-statblock.css](public/css/small-statblock.css) | Printable card layouts |
| **Templates** | [src/views/templates/monster-card-mini.php](src/views/templates/monster-card-mini.php) | Reusable UI components |

**How to Use:** Each section below has a "Source Code:" link. Click it to open the file, then take a screenshot of the relevant code lines.

---

## Part 1: Project Overview

### Name, Goal, and Targeted Users

**Project Name:** Monster Maker

**Goal:** Create a comprehensive D&D 5e monster statblock generator and management platform that allows Dungeon Masters to quickly create, customize, and share creatures for their campaigns.

**Targeted Users:**
TTRPG Players
- **Dungeon Masters (DMs):** Primary users who create and manage monsters for their campaigns
- **Game Masters (GMs):** For D&D 5e compatible systems


**Key Features:**
- Create monsters with full D&D 5e statblocks
- Generate printable cards (playing card size, A6 boss cards, landscape lair actions)
- Manage multiple monsters in organized collections
- Share collections via secure token links
- Like/favorite monsters and see popularity metrics
- Full CRUD operations (Create, Read, Update, Delete)

**Live Demo:** http://localhost:8000  
**Database Admin:** http://localhost:8081

---

## Part 2: Architecture & Technology Stack

### Structure Overview

**Project Type:** Full-stack web application using MVC architecture

**Technology Stack:**
- **Backend:** PHP 8.4.14 with PDO (prepared statements, parameterized queries)
- **Database:** MySQL 8.0 with proper foreign keys, constraints, and indexes
- **Frontend:** HTML5, Bootstrap 5.3, JavaScript (fetch API for AJAX)
- **Images:** User-uploaded avatars and monster portraits
- **Containerization:** Docker & Docker Compose (Apache, PHP, MySQL all containerized)

### Design Tools Used

**Database Design:**
- **Looping.exe:** Visual database design tool used to plan schema relationships
- Helped visualize: users → monsters, users → collections, collections → monsters
- Exported SQL from Looping for initial database structure

**UI/UX Design:**
- **Figma:** Wireframing and design mockups
- Designed card layouts (playing card 2.5×3.5", A6 boss 5.8×4.1", lair card landscape)
- Planned responsive layout for desktop/mobile browsing

**Version Control:**
- **Git:** For tracking code changes and project history
- **.gitignore:** Excludes Docker volumes, uploads, node_modules

### Directory Structure

```
Monster_Maker/
├── config/                 # Configuration files
├── db/
│   └── init/
│       └── database_structure.sql    # Initial schema
├── docker/
│   ├── Dockerfile.mysql              # MySQL container config
│   └── apache/vhost.conf             # Apache virtual host
├── docker-compose.yml      # Services: Apache, PHP, MySQL
├── Dockerfile              # PHP/Apache container config
│
├── src/                    # Application code (MVC)
│   ├── controllers/        # Request handlers
│   │   ├── AuthController.php
│   │   ├── MonsterController.php
│   │   ├── CollectionController.php
│   │   ├── LairCardController.php
│   │   ├── HomeController.php
│   │   └── PagesController.php
│   │
│   ├── models/             # Database layer
│   │   ├── Database.php
│   │   ├── User.php
│   │   ├── Monster.php
│   │   ├── Collection.php
│   │   ├── LairCard.php
│   │   ├── MonsterLike.php
│   │   └── FileUploadService.php
│   │
│   └── views/              # HTML templates
│       ├── auth/           # Login, register, profile
│       ├── monster/        # Monster CRUD and display
│       ├── collection/     # Collection CRUD and sharing
│       ├── lair/           # Lair card CRUD
│       ├── dashboard/      # User dashboard
│       ├── templates/      # Reusable components (header, footer, navbar)
│       └── pages/          # Error pages
│
├── public/                 # Web-accessible files
│   ├── index.php           # Router (entry point)
│   ├── api/                # AJAX endpoints
│   │   ├── add-to-collection.php
│   │   ├── create-collection-and-add.php
│   │   ├── get-collections.php
│   │   └── monster-like.php
│   │
│   ├── css/                # Stylesheets
│   │   ├── style.css
│   │   ├── monster-form.css
│   │   ├── monster-card-mini.css
│   │   ├── boss-card.css   # A6 horizontal layout
│   │   ├── small-statblock.css
│   │   └── lair-card.css
│   │
│   ├── js/                 # Client-side JavaScript
│   │   ├── monster-form.js
│   │   ├── monster-actions.js
│   │   └── collection-manager.js
│   │
│   ├── assets/images/svg/  # Icons and graphics
│   │
│   └── uploads/            # User-uploaded files
│       ├── avatars/        # Profile pictures
│       ├── monsters/       # Monster images
│       └── lair_cards/     # Lair card images
│
├── docs/                   # Documentation
│   └── AJAX_EXPLAINED.md
│
└── README.md               # Project documentation
```

---

## Part 3: Router - The Entry Point

### How Requests Flow Through the Application

Every HTTP request to the application goes through **public/index.php**, the router.

**Request Flow:**
```
Browser: GET http://localhost:8000?url=monsters
    ↓
Router (public/index.php)
    ├─ Parses URL: url='monsters'
    ├─ Looks up in routes: 'monsters' → MonsterController::index
    ├─ Creates: new MonsterController()
    └─ Calls: $controller->index()
        ↓
Controller (MonsterController)
    ├─ Validates authorization
    ├─ Calls model methods
    └─ Renders view with data
        ↓
Model (Monster)
    ├─ Queries database
    └─ Returns results
        ↓
View
    ├─ Receives data
    └─ Renders HTML
        ↓
Browser: Displays page
```

### Router Implementation

**Source Code:** [public/index.php](public/index.php)

```php
<?php
// public/index.php - Single entry point for ALL requests

// STEP 1: Initialize
define('ROOT', dirname(__DIR__));  // Project root path
session_start();                   // Enable sessions

// STEP 2: Register autoloader (auto-load classes by namespace)
spl_autoload_register(function ($class) {
    $file = ROOT . '/src/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// STEP 3: Parse URL
$url = $_GET['url'] ?? 'home';
$url = filter_var($url, FILTER_SANITIZE_URL);
$urlParts = explode('/', $url);

// STEP 4: Define routes
$routes = [
    'home'              => ['controller' => 'HomeController', 'action' => 'index'],
    'login'             => ['controller' => 'AuthController', 'action' => 'login'],
    'register'          => ['controller' => 'AuthController', 'action' => 'register'],
    'logout'            => ['controller' => 'AuthController', 'action' => 'logout'],
    'monsters'          => ['controller' => 'MonsterController', 'action' => 'index'],
    'monster-create'    => ['controller' => 'MonsterController', 'action' => 'create'],
    'monster-show'      => ['controller' => 'MonsterController', 'action' => 'show'],
    'monster-edit'      => ['controller' => 'MonsterController', 'action' => 'edit'],
    'collections'       => ['controller' => 'CollectionController', 'action' => 'index'],
    'collection-view'   => ['controller' => 'CollectionController', 'action' => 'view'],
];

// STEP 5: Find and execute route
if (isset($routes[$urlParts[0]])) {
    $controllerName = $routes[$urlParts[0]]['controller'];
    $action = $routes[$urlParts[0]]['action'];
} else {
    $controllerName = 'PagesController';
    $action = 'error404';
}

// STEP 6: Load and execute
$controllerFile = ROOT . '/src/controllers/' . $controllerName . '.php';
if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $controller = new $controllerName();
    if (method_exists($controller, $action)) {
        $controller->$action();
    } else {
        (new PagesController())->error404();
    }
} else {
    echo "Controller not found: $controllerName";
}
?>
```

---

## Part 4: Database Layer

### Database Schema

The application uses MySQL 8.0 with 6 main tables:

#### Users Table
```sql
CREATE TABLE users (
    u_id INT PRIMARY KEY AUTO_INCREMENT,
    u_name VARCHAR(100) UNIQUE NOT NULL,
    u_email VARCHAR(100) UNIQUE NOT NULL,
    u_password VARCHAR(255) NOT NULL,          -- Bcrypt hash (60+ chars)
    u_avatar VARCHAR(255),                     -- Filename
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### Monsters Table
```sql
CREATE TABLE monster (
    monster_id INT PRIMARY KEY AUTO_INCREMENT,
    u_id INT NOT NULL,                         -- Owner (FK to users)
    name VARCHAR(255) NOT NULL,
    
    -- D&D Basic Info
    size VARCHAR(50),                          -- Tiny, Small, Medium, Large, Huge, Gargantuan
    type VARCHAR(100),                         -- Dragon, Beast, Humanoid, etc.
    alignment VARCHAR(100),                    -- Lawful Good, Chaotic Evil, etc.
    
    -- Combat Stats
    ac INT DEFAULT 10,                         -- Armor Class
    hp INT DEFAULT 1,                          -- Hit Points
    hit_dice VARCHAR(100),                     -- e.g., "8d8 + 16"
    speed TEXT,                                -- Movement speeds
    
    -- Ability Scores (STR, DEX, CON, INT, WIS, CHA)
    strength INT DEFAULT 10,
    dexterity INT DEFAULT 10,
    constitution INT DEFAULT 10,
    intelligence INT DEFAULT 10,
    wisdom INT DEFAULT 10,
    charisma INT DEFAULT 10,
    
    -- More Stats
    proficiency_bonus INT DEFAULT 0,
    challenge_rating VARCHAR(50),              -- CR 1/8, 1/4, 1/2, 1, 2, etc.
    xp INT DEFAULT 0,                          -- Experience points
    
    -- Features (stored as JSON for flexibility)
    traits JSON,                               -- Non-combat abilities
    actions JSON,                              -- Attack actions, abilities
    bonus_actions TEXT,                        -- What can be done as bonus action
    reactions JSON,                            -- Reactions (like Parry)
    
    -- Legendary Features (for boss monsters)
    is_legendary BOOLEAN DEFAULT 0,
    legendary_actions JSON,                    -- Actions with action economy cost
    legendary_resistance TEXT,
    lair_actions TEXT,
    
    -- Images
    image_portrait VARCHAR(255),               -- Portrait/headshot
    image_fullbody VARCHAR(255),               -- Full body image
    
    -- Metadata
    is_public BOOLEAN DEFAULT 0,               -- 0=private, 1=public
    like_count INT DEFAULT 0,                  -- Denormalized count for performance
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (u_id) REFERENCES users(u_id) ON DELETE CASCADE
);
```

#### Collections Table
```sql
CREATE TABLE collections (
    collection_id INT PRIMARY KEY AUTO_INCREMENT,
    u_id INT NOT NULL,                         -- Owner (FK to users)
    collection_name VARCHAR(100) NOT NULL,
    share_token CHAR(32) UNIQUE,               -- 32-char hex token for sharing
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (u_id) REFERENCES users(u_id) ON DELETE CASCADE
);
```

#### Collection-Monster Junction Table (Many-to-Many)
```sql
CREATE TABLE collection_monster (
    collection_id INT NOT NULL,
    monster_id INT NOT NULL,
    PRIMARY KEY (collection_id, monster_id),
    
    FOREIGN KEY (collection_id) REFERENCES collections(collection_id) ON DELETE CASCADE,
    FOREIGN KEY (monster_id) REFERENCES monster(monster_id) ON DELETE CASCADE
);
```

#### Likes Table
```sql
CREATE TABLE monster_likes (
    like_id INT PRIMARY KEY AUTO_INCREMENT,
    u_id INT NOT NULL,                         -- User who liked
    monster_id INT NOT NULL,                   -- Monster that was liked
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_user_monster (u_id, monster_id),  -- Prevent duplicate likes
    FOREIGN KEY (u_id) REFERENCES users(u_id) ON DELETE CASCADE,
    FOREIGN KEY (monster_id) REFERENCES monster(monster_id) ON DELETE CASCADE
);
```

#### Lair Cards Table
```sql
CREATE TABLE lair_card (
    lair_id INT PRIMARY KEY AUTO_INCREMENT,
    u_id INT NOT NULL,                         -- Owner
    lair_name VARCHAR(255) NOT NULL,
    lair_actions TEXT,                         -- Lair action descriptions
    image_landscape VARCHAR(255),              -- Landscape image
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (u_id) REFERENCES users(u_id) ON DELETE CASCADE
);
```

### Why This Schema Design?

1. **Foreign Keys:** Maintain referential integrity
   - Can't create monster with non-existent user
   - `ON DELETE CASCADE`: Deleting user also deletes their monsters

2. **UNIQUE Constraints:**
   - `u_email UNIQUE`: Can't register twice with same email
   - `u_username UNIQUE`: Usernames are unique identifiers
   - `unique_user_monster`: Can't like same monster twice

3. **JSON Columns:**
   - `traits`, `actions`, `reactions`, `legendary_actions` are JSON
   - More flexible than normalization for this use case
   - Allows complex nested structures

4. **Denormalization:**
   - `like_count` stored in monster table
   - Why? Prevents slow COUNT() queries on every page load
   - Trade-off: Must update when like added/removed

---

## Part 5: Creating a Monster - CRUD Operations

### The Complete Monster Creation Flow

Creating a monster involves:
1. Display creation form (GET)
2. User fills form and submits (POST)
3. Validate data (server-side)
4. Save to database
5. Redirect to detail view

### Step 1: Show Creation Form

**Source Code:** [src/controllers/MonsterController.php](src/controllers/MonsterController.php) - `create()` method

```php
// src/controllers/MonsterController.php

public function create()
{
    // Check: User must be logged in
    if (!isset($_SESSION['user'])) {
        header('Location: index.php?url=login');
        exit;
    }

    // Handle POST (form submission)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $this->store();  // Go to Step 2
        return;
    }

    // Handle GET (show form)
    require ROOT . '/src/views/monster/create.php';
}
```

### Step 2: Validate and Save Data

**Source Code:** [src/controllers/MonsterController.php](src/controllers/MonsterController.php) - `store()` method

```php
public function store()
{
    // Get data from form
    $data = [
        'name'      => $_POST['name'] ?? '',
        'size'      => $_POST['size'] ?? '',
        'type'      => $_POST['type'] ?? '',
        'ac'        => (int)($_POST['ac'] ?? 10),
        'hp'        => (int)($_POST['hp'] ?? 1),
        'strength'  => (int)($_POST['strength'] ?? 10),
        // ... more fields
    ];

    // Validate
    $errors = $this->monsterModel->validate($data);
    
    if (!empty($errors)) {
        // Show form again with errors
        $_SESSION['errors'] = $errors;
        $_SESSION['old_data'] = $data;
        header('Location: index.php?url=monster-create');
        exit;
    }

    // Add owner ID
    $data['u_id'] = $_SESSION['user']['u_id'];

    // Save to database
    $monsterId = $this->monsterModel->create($data);

    // Redirect to detail view
    header('Location: index.php?url=monster-show&id=' . $monsterId);
    exit;
}
```

### Step 3: Complex Forms with JavaScript

#### Monster Form JavaScript

**Source Code:** [public/js/monster-form.js](public/js/monster-form.js)

The monster creation form is complex because:
- D&D ability scores (6 different stats)
- Automatic modifier calculations
- Dynamic action/trait/reaction builders
- Image uploads with validation

**Key JavaScript Features:**

```javascript
/**
 * Calculate D&D 5e ability modifier from ability score
 * Formula: (score - 10) / 2, rounded down
 * Example: Score 16 → (16-10)/2 = +3 modifier
 */
function calculateModifier(score) {
    return Math.floor((score - 10) / 2);
}

// Update modifier display when ability score changes
document.getElementById('strength').addEventListener('input', function() {
    const mod = calculateModifier(this.value);
    const sign = mod >= 0 ? '+' : '';
    document.getElementById('str-modifier').textContent = sign + mod;
});

/**
 * Add new action row to dynamic form
 * Allows user to add multiple actions without page reload
 */
function addAction() {
    const container = document.getElementById('actions-container');
    const newRow = `
        <div class="action-row">
            <input type="text" name="action_name[]" placeholder="e.g., Multiattack">
            <textarea name="action_desc[]" placeholder="Description..."></textarea>
            <button type="button" onclick="this.parentElement.remove()">Remove</button>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', newRow);
}
```

### Step 4: Image Handling

#### Image Upload Validation

**Source Code:** [src/models/FileUploadService.php](src/models/FileUploadService.php)

Proper image handling requires security checks:

```php
class FileUploadService
{
    private $allowedMimes = ['image/jpeg', 'image/png'];
    private $maxSize = 5 * 1024 * 1024;  // 5 MB
    private $uploadDir;

    public function upload($file, $category = 'monsters')
    {
        // STEP 1: Verify file exists
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception('No file uploaded');
        }

        // STEP 2: Check file size
        if ($file['size'] > $this->maxSize) {
            throw new Exception('File too large (max 5MB)');
        }

        // STEP 3: Verify actual MIME type (not just filename)
        // finfo reads file MAGIC BYTES (first few bytes that identify type)
        // Prevents attacker from renaming shell.php to shell.jpg
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $actualMime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($actualMime, $this->allowedMimes)) {
            throw new Exception('Invalid file type');
        }

        // STEP 4: Generate unique filename
        // Use random filename, not original
        // Prevents: directory traversal (../../config.php), overwriting files
        $uniqueName = bin2hex(random_bytes(8)) . '.' . 
                      pathinfo($file['name'], PATHINFO_EXTENSION);

        // STEP 5: Move file to safe location
        $destination = $this->uploadDir . $category . '/' . $uniqueName;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Failed to save file');
        }

        return $uniqueName;
    }
}
```

#### How to Use in Controller

**Source Code:** [src/controllers/MonsterController.php](src/controllers/MonsterController.php) - `store()` method

```php
public function store()
{
    // ... validate basic data ...

    // Handle image upload
    if (isset($_FILES['portrait']) && $_FILES['portrait']['size'] > 0) {
        try {
            $portraitFile = (new FileUploadService())->upload(
                $_FILES['portrait'],
                'monsters'
            );
            $data['image_portrait'] = $portraitFile;
        } catch (Exception $e) {
            $_SESSION['errors'][] = 'Image: ' . $e->getMessage();
        }
    }

    // Now save to database with image filename
    $monsterId = $this->monsterModel->create($data);
}
```

### Step 5: Retrieve/Update/Delete

**Source Code:** [src/controllers/MonsterController.php](src/controllers/MonsterController.php) - `show()`, `update()`, `delete()` methods

```php
// READ: Show monster details
public function show()
{
    $monsterId = (int)($_GET['id'] ?? 0);
    $monster = $this->monsterModel->getById($monsterId);

    // Check: Monster must be public OR user must own it
    if (!$monster['is_public'] && $monster['u_id'] != ($_SESSION['user']['u_id'] ?? null)) {
        http_response_code(403);
        exit('Not authorized');
    }

    require ROOT . '/src/views/monster/show.php';
}

// UPDATE: Edit monster
public function update()
{
    $monsterId = (int)($_POST['id'] ?? 0);
    $monster = $this->monsterModel->getById($monsterId);

    // Check: Must own the monster to edit
    if ($monster['u_id'] != $_SESSION['user']['u_id']) {
        http_response_code(403);
        exit('Not authorized');
    }

    // Validate new data, update, redirect
}

// DELETE: Remove monster
public function delete()
{
    $monsterId = (int)($_GET['id'] ?? 0);
    $monster = $this->monsterModel->getById($monsterId);

    // Check: Must own the monster to delete
    if ($monster['u_id'] != $_SESSION['user']['u_id']) {
        http_response_code(403);
        exit('Not authorized');
    }

    $this->monsterModel->delete($monsterId);
    header('Location: index.php?url=monster-list');
    exit;
}
```

---

## Part 6: Print Optimization - Download & Complex CSS

### Printable Card Formats

Monster Maker generates three different printable card formats:

#### 1. Playing Card (2.5" × 3.5")
**CSS:** [public/css/small-statblock.css](public/css/small-statblock.css)  
**HTML View:** [src/views/monster/small-statblock.php](src/views/monster/small-statblock.php)

Used for: Printing stat cards that fit in a standard playing card deck

```css
/* Physical playing card dimensions */
@page {
    size: 2.5in 3.5in;
    margin: 0.1in;
}

body {
    width: 2.5in;
    height: 3.5in;
    font-size: 8pt;      /* Small for card size */
    column-count: 1;
    break-inside: avoid;
}

/* Fit all content on one card */
.card {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.card-name {
    font-weight: bold;
    font-size: 10pt;
    border-bottom: 1px solid;
}

.card-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;  /* 2 columns */
    gap: 2px;
    font-size: 7pt;
}

/* Print media query: hide UI elements not needed on print */
@media print {
    .no-print { display: none; }  /* Hide buttons, navigation */
    body { background: white; }     /* No color backgrounds */
}
```

#### 2. A6 Boss Card (5.8" × 4.1" horizontal)
**CSS:** [public/css/boss-card.css](public/css/boss-card.css)  
**HTML View:** [src/views/monster/boss-card.php](src/views/monster/boss-card.php)

Used for: Larger boss monster cards with more space for details

```css
@page {
    size: A6 landscape;  /* 5.8" × 4.1" */
    margin: 0.2in;
}

.boss-card {
    display: grid;
    grid-template-columns: 1fr 1fr;  /* Two-column layout */
    gap: 10px;
    height: 100%;
}

.left-column {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);  /* 3 columns for 6 abilities */
    gap: 4px;
    font-size: 9pt;
}

.ability {
    border: 1px solid;
    padding: 4px;
    text-align: center;
}

.ability-score {
    font-weight: bold;
    font-size: 10pt;
}

.ability-mod {
    color: #666;
    font-size: 8pt;
}
```

#### 3. Lair Card (Landscape 5" × 3.5")
**CSS:** [public/css/lair-card.css](public/css/lair-card.css)  
**HTML View:** [src/views/lair/show.php](src/views/lair/show.php)

Used for: Lair action cards displayed during combat

```css
@page {
    size: 5in 3.5in landscape;
    margin: 0.15in;
}

.lair-card {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    font-size: 10pt;
}

.lair-title {
    border-bottom: 2px solid;
    font-weight: bold;
    margin-bottom: 6px;
}

.lair-actions {
    display: flex;
    flex-direction: column;
    gap: 4px;
    flex-grow: 1;
    overflow: hidden;
}

.action {
    page-break-inside: avoid;
    margin-bottom: 4px;
}
```

### JavaScript for Download/Print

**Source Code:** [public/js/card-download.js](public/js/card-download.js)

```javascript
/**
 * Generate PDF from visible page content using jsPDF library
 * jsPDF is loaded from CDN: https://unpkg.com/jspdf@latest/dist/jspdf.umd.min.js
 */
function downloadPDF(pageTitle, pageSize = 'a6', orientation = 'l') {
    // STEP 1: Hide UI elements (buttons, navigation)
    const printButtons = document.querySelectorAll('.print-hide');
    printButtons.forEach(btn => btn.style.display = 'none');

    // STEP 2: Get the content div
    const element = document.getElementById('printable-content');

    // STEP 3: Create PDF using jsPDF
    const pdf = new jsPDF({
        orientation: orientation,  // 'l' = landscape, 'p' = portrait
        unit: 'in',
        format: pageSize
    });

    // STEP 4: Add HTML content to PDF
    pdf.html(element, {
        callback: function(pdf) {
            // STEP 5: Save PDF with filename
            pdf.save(pageTitle + '.pdf');
        },
        margin: 0.1,
        autoPaging: 'text'
    });

    // STEP 6: Show UI elements again
    printButtons.forEach(btn => btn.style.display = '');
}

// Usage:
// downloadPDF('Goblin_Boss', 'a6', 'l');  // A6 landscape PDF
// downloadPDF('Goblin_Card', 'card', 'p');  // Playing card portrait PDF

/**
 * Browser print dialog (Ctrl+P)
 * Uses @media print CSS rules above
 */
function printCard() {
    window.print();
}
```

### CSS Grid & Flexbox Techniques

The card layouts use modern CSS for responsive printing:

```css
/* Flexible grid that adapts to available space */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(40px, 1fr));
    gap: 4px;
}

/* Absolute positioning for image backgrounds */
.card {
    position: relative;
    background-image: url('parchment.jpg');
    background-size: cover;
}

.card-content {
    position: relative;
    z-index: 1;  /* Appear above background image */
    padding: 8px;
}

/* Flexbox for vertical centering of stats */
.ability {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

/* Prevent content breaks across pages */
@media print {
    .action { page-break-inside: avoid; }
    .trait { page-break-inside: avoid; }
}
```

---

## Part 7: User Authentication & Protection

### User Registration & Security

#### What Protections Are in Place?

1. **Password Security - Bcrypt Hashing**
   ```php
   // Store: bcrypt hash with random salt, not plain password
   $hashed = password_hash($password, PASSWORD_DEFAULT);
   // Result: "$2y$10$..." (60 characters)
   
   // Verify: Compare user input against stored hash
   if (password_verify($password, $hashed)) {
       // Password matches
   }
   ```

2. **SQL Injection Prevention - Prepared Statements**
   ```php
   // WRONG: Vulnerable to SQL injection
   $sql = "SELECT * FROM users WHERE email = '$email'";
   
   // RIGHT: Prepared statements separate code from data
   $sql = "SELECT * FROM users WHERE email = :email";
   $stmt = $this->db->prepare($sql);
   $stmt->execute([':email' => $email]);
   ```

3. **XSS Prevention - Escaping Output**
   ```php
   // WRONG: Can execute JavaScript
   echo $user['username'];  // If username = "<script>alert('hacked')</script>"
   
   // RIGHT: Escape HTML special characters
   echo htmlspecialchars($user['username']);  // Outputs: &lt;script&gt;...
   ```

4. **CSRF Protection - Session Validation**
   ```php
   // Every form submission checked against session
   if ($user['u_id'] != $_SESSION['user']['u_id']) {
       // Request is from different user - rejected
   }
   ```

5. **File Upload Security**
   - Check actual MIME type (not just filename)
   - Verify file size limits
   - Generate random filename (prevents overwrite, traversal)
   - Store outside web root if possible

#### Current Registration Form

**Source Code:** [src/views/auth/register.php](src/views/auth/register.php)

```php
// src/views/auth/register.php

<form method="POST" action="">
    <div>
        <label>Username:</label>
        <input type="text" name="username" required minlength="3" maxlength="100">
        <small>3-100 characters, letters and numbers only</small>
    </div>

    <div>
        <label>Email:</label>
        <input type="email" name="email" required>
        <small>Must be a valid email address</small>
    </div>

    <div>
        <label>Password:</label>
        <input type="password" name="password" required minlength="8">
        <small>Minimum 8 characters (uppercase, lowercase, numbers recommended)</small>
    </div>

    <div>
        <label>Confirm Password:</label>
        <input type="password" name="confirm_password" required minlength="8">
    </div>

    <button type="submit">Register</button>
</form>
```

### What Should Be Added Before Production Release?

#### 1. **CAPTCHA - Prevent Bot Attacks**
```php
// Add Google reCAPTCHA v3 (invisible, no clicking needed)
<script src="https://www.google.com/recaptcha/api.js"></script>

// Server-side verification:
$token = $_POST['g-recaptcha-response'];
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'secret' => RECAPTCHA_SECRET_KEY,
    'response' => $token
]));
$response = json_decode(curl_exec($ch));

if (!$response->success || $response->score < 0.5) {
    // Reject registration - likely bot
}
```

#### 2. **Email Verification - Confirm Address**
```php
// Send verification link to email
$verificationToken = bin2hex(random_bytes(32));
// Store: $verificationToken, $email, $expiresAt = now + 24 hours in database

// Email contains: http://localhost:8000?url=verify-email&token=...
// User clicks link, token verified, account activated
```

#### 3. **Two-Factor Authentication - Extra Security**
```php
// After password login, require code from:
// - Google Authenticator app
// - SMS text message
// - Email link

// Prevents: account takeover even if password leaked
```

#### 4. **Rate Limiting - Prevent Brute Force**
```php
// Track login attempts per IP address
// If > 5 failed attempts in 15 minutes, block temporarily
// Prevents: attackers guessing passwords

// Implementation:
// - Log each login attempt with timestamp
// - Check: COUNT(*) WHERE ip = ... AND timestamp > now() - 15min
// - If > 5, respond with: "Too many attempts, try again later"
```

#### 5. **Email Notifications - Alert of Changes**
```php
// Send email when:
// - Account created
// - Password changed
// - Profile updated
// - Someone tries wrong password (X times)

// Helps user detect unauthorized access
```

#### 6. **Audit Logging - Track Actions**
```php
// Create logs table:
CREATE TABLE audit_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    u_id INT,
    action VARCHAR(100),          -- 'login', 'create_monster', 'delete_collection'
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    timestamp TIMESTAMP
);

// Log important actions:
$this->logAction($_SESSION['user']['u_id'], 'login', $_SERVER['REMOTE_ADDR']);

// Helps detect suspicious activity
```

---

## Part 8: Browsing & Searching - SQL Query Optimization

### Finding & Displaying Monsters

The browsing page shows paginated, filtered list of all public monsters.

#### Challenge: Efficient Query

When you have 10,000+ monsters, loading all of them is slow. Solution: Use LIMIT and OFFSET.

**Source Code:** [src/controllers/MonsterController.php](src/controllers/MonsterController.php) - `index()` method

**Query (with pagination):**
```sql
SELECT 
    m.monster_id,
    m.name,
    m.size,
    m.type,
    m.cr,
    m.image_portrait,
    m.like_count,
    m.u_id,
    u.u_name as creator_name,
    COUNT(l.like_id) as actual_like_count,
    CASE WHEN l.u_id = ? THEN 1 ELSE 0 END as user_liked
FROM monster m
LEFT JOIN users u ON m.u_id = u.u_id
LEFT JOIN monster_likes l ON m.monster_id = l.monster_id
WHERE m.is_public = 1
GROUP BY m.monster_id
ORDER BY m.created_at DESC
LIMIT 10 OFFSET 0;  -- First 10 monsters
```

**PHP Code:**
```php
public function index()
{
    $page = (int)($_GET['page'] ?? 1);
    $perPage = 10;
    $offset = ($page - 1) * $perPage;

    // Get total count for pagination
    $total = $this->monsterModel->countPublic();
    $totalPages = ceil($total / $perPage);

    // Get monsters for current page
    $monsters = $this->monsterModel->getPublic($offset, $perPage);

    // Get current user's likes (if logged in)
    $userLikes = [];
    if (isset($_SESSION['user'])) {
        $userLikes = $this->likeModel->getUserLikes(
            $_SESSION['user']['u_id'],
            array_column($monsters, 'monster_id')
        );
    }

    // Render
    require ROOT . '/src/views/monster/index.php';
}
```

#### Optimizing the Query

**Index on is_public:**
```sql
CREATE INDEX idx_public ON monster(is_public, created_at DESC);
-- Helps database quickly find public monsters and sort them
```

**Index on likes:**
```sql
CREATE INDEX idx_likes_user ON monster_likes(u_id, monster_id);
-- Helps database quickly find monsters a user has liked
```

**Why LEFT JOIN for likes?**
```
LEFT JOIN: Include monster even if NO one has liked it
INNER JOIN: Would exclude monsters with 0 likes (wrong!)

LEFT JOIN keeps all monsters, adds NULL for likes column if no likes exist
```

---

## Part 9: Collection System & Sharing

### Creating Collections

Users can create a collection to organize monsters.

**Source Code:** [src/controllers/CollectionController.php](src/controllers/CollectionController.php) - `createAndAdd()` method

**Atomic Operation Example:**
```php
// CREATE COLLECTION AND ADD MONSTER (all-or-nothing)
public function createAndAdd()
{
    $userId = $_SESSION['user']['u_id'];
    $collectionName = $_POST['name'];
    $monsterId = (int)$_POST['monster_id'];

    try {
        // STEP 1: Create collection
        $collectionId = $this->collectionModel->create($userId, $collectionName);

        // STEP 2: Add monster
        $this->collectionModel->addMonster($collectionId, $monsterId);

        // Success - return collection ID
        http_response_code(200);
        echo json_encode(['success' => true, 'collection_id' => $collectionId]);
    } catch (Exception $e) {
        // STEP 3: ROLLBACK - Delete collection if add fails
        // Prevents orphaned empty collections
        $this->collectionModel->delete($collectionId);

        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
```

### Sharing Collections via Tokens

Users can share their collections with a secure token (not just user ID).

**Source Code:** [src/controllers/CollectionController.php](src/controllers/CollectionController.php) - `share()` and `viewShared()` methods

**Token Generation:**
```php
public function share($collectionId)
{
    // Generate random 32-character hex string
    $token = bin2hex(random_bytes(16));  // 16 bytes = 32 hex chars

    // Store token in database
    $sql = "UPDATE collections SET share_token = :token WHERE collection_id = :id";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':token' => $token, ':id' => $collectionId]);

    // Return: http://localhost:8000?url=collection-share&token=a3f2e8b1c7d4e9f2a5b6c8d1e3f4a5b6
    return [
        'token' => $token,
        'url' => 'http://localhost:8000?url=collection-share&token=' . $token
    ];
}
```

**Why tokens instead of user IDs?**
- Token: `a3f2e8b1c7d4e9f2a5b6c8d1e3f4a5b6` (32^16 possibilities)
- ID: `42` (can guess all IDs from 1 to N)
- Tokens can't be enumerated; IDs can be brute-forced

**View Shared Collection:**
```php
public function viewShared()
{
    $token = $_GET['token'] ?? '';

    // Find collection by token
    $collection = $this->collectionModel->getByToken($token);

    if (!$collection) {
        http_response_code(404);
        exit('Collection not found');
    }

    // Get monsters in collection
    $monsters = $this->collectionModel->getMonsters($collection['collection_id']);

    // Render public view
    require ROOT . '/src/views/collection/public-view.php';
}
```

---

## Part 10: Like System & AJAX

### How Likes Work

**Source Code:** [src/models/MonsterLike.php](src/models/MonsterLike.php) - Complete like system  
**Implementation in Controller:** [src/controllers/MonsterController.php](src/controllers/MonsterController.php) - `toggleLike()` method

**Database Design:**
```sql
CREATE TABLE monster_likes (
    like_id INT PRIMARY KEY AUTO_INCREMENT,
    u_id INT NOT NULL,
    monster_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY (u_id, monster_id)  -- Can't like same monster twice
);
```

### AJAX Flow

When user clicks heart icon:

```
1. User clicks ❤️ heart button
    ↓
2. JavaScript preventDefault() (no page reload)
    ↓
3. fetch() AJAX request to index.php?url=monster-like&id=123
    ↓
4. MonsterController::toggleLike() handles request
    ├─ Check: User logged in?
    ├─ Check: Monster exists and is public?
    ├─ Call: MonsterLike->toggleLike()
    │   ├─ Check: User already liked it?
    │   ├─ If yes: DELETE from database
    │   ├─ If no: INSERT into database
    │   └─ Return: "added" or "removed"
    ├─ Call: MonsterLike->countLikes()
    │   └─ Return: New total like count
    └─ Send JSON response: {success: true, action: "added", count: 5, liked: true}
    ↓
5. JavaScript receives response
    ├─ Parse JSON
    ├─ Update heart icon (fill or empty)
    ├─ Update counter number (5)
    └─ No page reload - instant feedback
    ↓
6. User sees: Heart filled, count = 5
```

### Implementation

**Source Code:**
- **Frontend JavaScript:** [public/js/monster-actions.js](public/js/monster-actions.js) - `toggleLike()` function  
- **Backend PHP Controller:** [src/controllers/MonsterController.php](src/controllers/MonsterController.php) - `toggleLike()` method  
- **Database Model:** [src/models/MonsterLike.php](src/models/MonsterLike.php) - `toggleLike()`, `countLikes()`, `hasLiked()` methods

**Frontend - JavaScript**
```javascript
/**
 * Toggle like when user clicks heart
 * AJAX request - no page reload
 */
function toggleLike(event, monsterId) {
    // STEP 1: Prevent default (no page reload)
    event.preventDefault();

    // STEP 2: Find UI elements
    const btn = event.target.closest('button.like-btn');
    const icon = btn.querySelector('i');
    const countSpan = btn.querySelector('.like-count');

    // STEP 3: Disable button (prevent double-click)
    btn.disabled = true;

    // STEP 4: Send AJAX request
    fetch('index.php?url=monster-like&id=' + monsterId)
        .then(response => response.json())
        .then(data => {
            // STEP 5: Update UI
            if (data.liked) {
                // User just liked it - show filled heart
                icon.classList.remove('bi-heart');
                icon.classList.add('bi-heart-fill');
            } else {
                // User just unliked it - show empty heart
                icon.classList.remove('bi-heart-fill');
                icon.classList.add('bi-heart');
            }
            countSpan.textContent = data.count;
        })
        .catch(error => alert('Error: ' + error))
        .finally(() => {
            // STEP 6: Re-enable button
            btn.disabled = false;
        });
}
```

**Backend - PHP**
```php
public function toggleLike()
{
    // Set response type to JSON
    header('Content-Type: application/json');

    // Check: User logged in?
    if (!isset($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        return;
    }

    // Get parameters
    $userId = $_SESSION['user']['u_id'];
    $monsterId = (int)($_GET['id'] ?? 0);

    // Check: Monster exists and is public?
    $monster = $this->monsterModel->getById($monsterId);
    if (!$monster || !$monster['is_public']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Not allowed']);
        return;
    }

    // Toggle like
    $action = $this->likeModel->toggleLike($userId, $monsterId);
    $count = $this->likeModel->countLikes($monsterId);

    // Return JSON response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'action' => $action,
        'count' => $count,
        'liked' => ($action === 'added')
    ]);
}
```

### Persistence: Showing Liked State on Page Load

On every page, the controller loads user's liked monsters.

**Source Code:**
- **Controller Loading Likes:** [src/controllers/MonsterController.php](src/controllers/MonsterController.php) - `index()` and other action methods  
- **Model Fetching:** [src/models/MonsterLike.php](src/models/MonsterLike.php) - `getUserLikes()` method  
- **View Display Heart:** [src/views/templates/monster-card-mini.php](src/views/templates/monster-card-mini.php) - Heart button rendering

```php
public function index()
{
    // Get monsters
    $monsters = $this->monsterModel->getPublic();

    // Get user's liked monsters (if logged in)
    $userLikes = [];
    if (isset($_SESSION['user'])) {
        $userLikes = $this->likeModel->getUserLikes(
            $_SESSION['user']['u_id'],
            array_column($monsters, 'monster_id')
        );
    }

    // Pass to view
    require ROOT . '/src/views/monster/index.php';
}
```

In the view:
```php
<?php
// Set $isLiked before rendering heart button
$isLiked = in_array($monster['monster_id'], $userLikes ?? []);
?>

<button class="like-btn" onclick="toggleLike(event, <?php echo $monster['monster_id']; ?>)">
    <i class="bi <?php echo $isLiked ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
    <span class="like-count"><?php echo $monster['like_count']; ?></span>
</button>
```

---

## Part 11: Next Steps & Future Development

### Short Term (1-2 weeks)

1. **Spell Cards** - Link monsters to spell database
   - Let DMs see what spells monster can cast
   - Generate spell card printouts

2. **Better User Protection**
   - Add email verification on registration
   - Implement CAPTCHA to prevent bot attacks
   - Add rate limiting to login attempts

3. **Collection Improvements**
   - Add description/notes to collections
   - Add collaborators (share editing permission)
   - Add export to PDF (all monsters in collection)

4. **Print Optimization**
   - Test on various printers
   - Optimize colors for black & white printing
   - Add print margin/spacing options

### Medium Term (1 month)

5. **Mobile Optimization**
   - Responsive design for phone/tablet
   - Touch-friendly button sizes
   - Native app wrapper (progressive web app)

6. **Search & Filtering**
   - Search by name
   - Filter by size, type, CR, abilities
   - Full-text search on descriptions

7. **Encounter Builder**
   - Combine multiple monsters for encounters
   - Calculate total XP difficulty
   - Suggest adjustments (add more monsters, scale HP)

### Long Term (ongoing)

8. **Community Features**
   - User profiles and follower system
   - Comments/reviews on public monsters
   - Trending monsters (most liked, most used)

9. **Admin Dashboard**
   - Moderation tools
   - Usage statistics
   - User management

10. **API for Integrations**
    - RESTful API for third-party apps
    - OAuth for sign-in with Google/Discord
    - Webhook notifications

---

## Part 12: Demonstration

### Key Features to Show

1. **Create a Monster**
   - Walk through form
   - Show ability modifiers calculating automatically
   - Show image upload
   - Save and view the created monster

2. **Public Browse**
   - Show filtering and pagination
   - Click heart to like (AJAX without reload)
   - Show like counter updating

3. **Collections**
   - Create a collection
   - Add monsters to it (AJAX dropdown)
   - Share via token
   - Show public view works

4. **Print Cards**
   - Generate PDF of playing card size
   - Generate boss card PDF
   - Show how they look when printed

5. **User Account**
   - Show registration validation
   - Show login
   - Show profile/avatar upload

---

## Conclusion

Monster Maker demonstrates:

✅ **Full MVC Architecture** - Clear separation of concerns  
✅ **Database Design** - Relationships, constraints, indexing  
✅ **Security Practices** - Prepared statements, password hashing, XSS prevention  
✅ **User Authentication** - Registration, login, session management  
✅ **CRUD Operations** - Create, Read, Update, Delete with validation  
✅ **File Handling** - Secure image uploads with validation  
✅ **AJAX & JSON** - Dynamic interactions without page reload  
✅ **Print Optimization** - Multiple printable card formats with CSS  
✅ **Data Persistence** - Collections, likes, user preferences  
✅ **Authorization** - User ownership verification  

**Technologies:** PHP 8.4, MySQL 8.0, Bootstrap 5.3, JavaScript (fetch API), Docker

**Production Readiness:** Code is clean, well-commented, secure, and extensible for future features.
