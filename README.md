# Monster Maker

A comprehensive D&D 5e monster statblock generator and management platform. Create, share, and organize custom monsters and lair cards with built-in printing support and collection management.

## Features

### Core Functionality
- **Monster Creation**: Create Small (playing card) and Boss (A5 sheet) format monsters
- **Lair Cards**: Create landscape lair action and regional effects cards
- **Collections**: Organize monsters into user collections with sharing via secure tokens
- **Like System**: Favorite public monsters and see popularity metrics
- **Card Printing**: Download monsters as PDF or print-ready images
- **File Management**: Upload custom artwork and manage monster images

### User Experience
- **Unified Card Display**: Consistent monster card rendering across all pages
- **AJAX Interactions**: Like/unlike without page reload, smooth collection management
- **Public Browsing**: Filter and search public monsters by category, order by popularity
- **Shared Collections**: Share your collections via direct link with friends
- **Responsive Design**: Mobile-friendly interface with Bootstrap 5

## Tech Stack

- **Backend:** PHP 8.4 with PDO
- **Frontend:** HTML5, CSS3, Bootstrap 5.3
- **Database:** MySQL 8.0
- **Architecture:** MVC (Model-View-Controller)
- **API:** Internal JSON REST API for AJAX endpoints
- **Container:** Docker & Docker Compose

## Quick Start

1. Clone and navigate:
```bash
git clone https://github.com/AlexandreBrely/Monster-Maker.git
cd Monster-Maker
```

2. Start Docker:
```bash
docker-compose up -d
```

3. Access:
- Website: http://localhost:8000
- phpMyAdmin: http://localhost:8081 (Root/root)

## Project Structure

```
Monster_Maker/
├── src/
│   ├── controllers/          # Request handling & business logic
│   │   ├── AuthController.php
│   │   ├── MonsterController.php
│   │   ├── CollectionController.php
│   │   ├── LairCardController.php
│   │   └── PagesController.php
│   ├── models/               # Database operations & validation
│   │   ├── User.php
│   │   ├── Monster.php
│   │   ├── Collection.php
│   │   ├── MonsterLike.php   # Like system
│   │   ├── LairCard.php
│   │   ├── Database.php      # PDO singleton
│   │   └── FileUploadService.php
│   ├── services/             # Business logic services
│   │   └── FileUploadService.php
│   └── views/                # HTML templates
│       ├── auth/             # Login, register, profile
│       ├── monster/          # Monster CRUD & display
│       ├── collection/       # Collection views
│       ├── lair/             # Lair card views
│       ├── dashboard/        # User dashboard
│       ├── templates/        # Reusable components
│       │   └── monster-card-mini.php  # Unified card template
│       └── pages/            # Static pages
├── public/
│   ├── index.php             # Router (URL → Controller)
│   ├── api/                  # Internal JSON API endpoints
│   │   ├── add-to-collection.php
│   │   ├── get-collections.php
│   │   └── create-collection-and-add.php
│   ├── js/                   # JavaScript (AJAX handlers)
│   │   ├── monster-actions.js        # Like/collection AJAX
│   │   ├── collection-manager.js     # Collection UI
│   │   ├── monster-form.js           # Form interactions
│   │   └── card-download.js          # PDF generation
│   ├── css/                  # Stylesheets
│   │   ├── style.css
│   │   ├── monster-card-mini.css
│   │   ├── monster-form.css
│   │   └── ...
│   └── uploads/              # User-uploaded images
│       ├── avatars/
│       └── monsters/
├── db/
│   └── init/
│       └── database_structure.sql
└── docker/                   # Docker configuration
```

## Architecture & Design

### MVC Pattern
```
Request Flow: URL → Router (index.php) → Controller → Model → View
```

**Controller Layer** (`src/controllers/`)
- Receives HTTP requests and URL parameters
- Validates user authentication and permissions
- Orchestrates business logic with models
- Renders views or returns JSON for AJAX

**Model Layer** (`src/models/`)
- Database operations with PDO prepared statements
- Data validation and sanitization
- SQL injection prevention through parameterization
- Business logic and calculations

**View Layer** (`src/views/`)
- HTML templates with PHP server-side rendering
- Reusable components (monster-card-mini.php template)
- Error/success message handling
- Responsive Bootstrap layout

### AJAX & JSON API

**Internal API Endpoints** (`public/api/`)
```
POST  /api/add-to-collection.php              Add monster to collection
POST  /api/create-collection-and-add.php      Create collection & add monster
GET   /api/get-collections.php                Fetch user's collections
GET   index.php?url=monster-like&id=X         Toggle like on monster
```

**JSON Response Format**
```json
{
  "success": true,
  "action": "added",
  "count": 5,
  "liked": true
}
```

**HTTP Status Codes Used**
- `200 OK` - Request successful
- `400 Bad Request` - Invalid input
- `401 Unauthorized` - Not logged in
- `403 Forbidden` - Permission denied
- `405 Method Not Allowed` - Wrong HTTP method
- `409 Conflict` - Duplicate entry
- `500 Internal Server Error` - Server error

### Like System

**Database Schema** (`monster_likes` table)
```sql
CREATE TABLE monster_likes (
  like_id INT PRIMARY KEY AUTO_INCREMENT,
  u_id INT NOT NULL (FK to users),
  monster_id INT NOT NULL (FK to monster),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user_monster (u_id, monster_id)
)
```

**How It Works**
1. User clicks heart icon on monster card
2. `toggleLike(monsterId)` JavaScript function sends AJAX request
3. `MonsterController->toggleLike()` endpoint processes request
4. `MonsterLike->toggleLike()` adds or removes like record
5. Server returns JSON with new count and liked state
6. JavaScript updates UI: icon changes, counter updates
7. Icon fills/empties visually, no page reload

**Persistence**
- Controllers fetch user's liked monsters via `MonsterLike->getUserLikes()`
- Views set `$isLiked` variable before rendering monster-card-mini.php
- Heart icon appears filled on initial page load for liked monsters

### Collection System

**Features**
- Default "To Print" collection created automatically
- Create custom collections with optional descriptions
- Share collections via 32-character secure token
- Public shared collections visible without login
- Prevent duplicate monsters in collections

**Sharing Workflow**
1. User creates collection and monsters
2. User clicks "Share" button, generates token
3. Share link: `index.php?url=collection-public&token=abc123...`
4. Anyone with link can view (no login required)
5. If user is logged in, can see their likes on shared collection

### Key Technologies

**Security**
- **SQL Injection Prevention**: Prepared statements with parameterized queries
- **Password Security**: `password_hash()` (bcrypt) + `password_verify()`
- **Session Authentication**: `$_SESSION['user']` stored in database
- **CSRF Protection**: Token validation (basic implementation)
- **File Upload Security**: 
  - MIME type validation (not filename-based)
  - Random filename generation
  - Directory traversal prevention

**Data Validation**
- **Server-side only** (client validation is unreliable)
- Type casting: `(int)`, `(string)`, `trim()`, `filter_var()`
- Range validation: ability scores 1-30, AC > 0, HP > 0
- Uniqueness checks: email, username, collection names
- Length validation: 1-100 characters for names

**JSON Handling**
- Complex data stored as JSON: actions, reactions, legendary actions
- Serialization: `json_encode($array)` on INSERT
- Deserialization: `json_decode($string, true)` on SELECT
- Frontend/backend easy communication via JSON API endpoints

**File Uploads**
- Maximum size: 5MB
- MIME types: image/jpeg, image/png, image/webp
- Unique filenames: `{random_hash}_{original_name}.{ext}`
- Location: `public/uploads/{avatars,monsters}/`
- Hard delete or soft delete handling

**Database**
- **PDO Connection**: Type-safe database abstraction layer
- **Prepared Statements**: Prevents SQL injection
- **Connection Parameters**: Configured via environment variables
- **Character Encoding**: utf8mb4 for full Unicode support
- **Transaction Safety**: Atomic operations for multi-step processes

## Database Schema

### users table
- `u_id` (PK, AUTO_INCREMENT)
- `u_name` (VARCHAR, UNIQUE)
- `u_email` (VARCHAR, UNIQUE)
- `u_password` (VARCHAR, hashed)
- `u_avatar` (TEXT, filename)
- `u_created_at` (DATETIME)

### monster table
- `monster_id` (PK, AUTO_INCREMENT)
- `u_id` (FK to users)
- `name`, `size`, `type`, `alignment`
- `ac`, `ac_notes`, `hp`, `hit_dice`
- Ability scores: `strength`, `dexterity`, `constitution`, `intelligence`, `wisdom`, `charisma`
- `saving_throws`, `skills`, `senses`, `languages` (TEXT)
- `traits`, `actions`, `bonus_actions`, `reactions`, `legendary_actions` (LONGTEXT JSON)
- `damage_immunities`, `condition_immunities`, `damage_resistances`, `damage_vulnerabilities` (TEXT)
- `is_public` (BOOLEAN)
- `card_size` (INT: 1=Boss, 2=Small)
- `created_at`, `updated_at` (TIMESTAMP)

### collections table
- `collection_id` (PK)
- `u_id` (FK to users)
- `collection_name` (VARCHAR)
- `description` (TEXT)
- `is_default` (BOOLEAN)
- `share_token` (VARCHAR 32, UNIQUE nullable)
- `created_at`, `updated_at` (TIMESTAMP)

### monster_likes table
- `like_id` (PK)
- `u_id` (FK to users)
- `monster_id` (FK to monster)
- `created_at` (TIMESTAMP)
- UNIQUE KEY on (u_id, monster_id)

### lair_card table
- `lair_id` (PK)
- `u_id` (FK to users)
- `monster_name`, `lair_name` (VARCHAR)
- `lair_description` (TEXT)
- `lair_initiative` (INT, default 20)
- `lair_actions`, `regional_effects` (LONGTEXT)
- `image_back` (VARCHAR, filename)
- `created_at`, `updated_at` (TIMESTAMP)

## Installation & Setup

### Prerequisites
- Docker & Docker Compose
- Git

### Setup Steps

1. **Clone Repository**
```bash
git clone https://github.com/AlexandreBrely/Monster-Maker.git
cd Monster-Maker
```

2. **Start Services**
```bash
docker-compose up -d
```

Services will be available at:
- Website: http://localhost:8000
- phpMyAdmin: http://localhost:8081 (username: root, password: root)
- MySQL: localhost:3306

3. **Create User Account**
- Register: http://localhost:8000?url=register
- Login: http://localhost:8000?url=login

4. **Start Creating**
- Create Monster: http://localhost:8000?url=create_select
- Choose Small or Boss format
- Fill in statblock information
- Upload images (optional)
- Publish to make public

## Development Guide

### For Junior Developers

This codebase is designed to be readable and educational:

**Step-by-Step Comments**
Every complex function has numbered step-by-step comments explaining:
- What each section does
- Why it matters
- How it connects to other parts

Example from `monster-actions.js`:
```javascript
// STEP 1: Prevent default behavior
// STEP 2: Find the button element
// STEP 3: Find icon and count elements
// ... (10 more steps)
```

**Consistent Patterns**
- All API endpoints follow same structure: validate → auth → process → respond
- All models use same prepared statement pattern: prepare → execute → fetch
- All views use same template inclusion: set variables → require template

**Well-Documented Files**
Key documentation files:
- [docs/AJAX_EXPLAINED.md](docs/AJAX_EXPLAINED.md) - AJAX request/response cycle
- [public/api/add-to-collection.php](public/api/add-to-collection.php) - JSON API example
- [public/js/monster-actions.js](public/js/monster-actions.js) - Frontend AJAX example
- [src/models/MonsterLike.php](src/models/MonsterLike.php) - Database operations example

**Common Tasks**

Adding a new API endpoint:
1. Create file in `public/api/`
2. Validate authentication and input
3. Create model instance
4. Call model method
5. Return JSON response with proper HTTP status code
6. Write comprehensive docblock explaining JSON format

Adding a new database operation:
1. Create method in relevant model class
2. Use prepared statements with named parameters
3. Handle exceptions gracefully
4. Return appropriate data structure (array, int, bool)
5. Document with docblock and parameter types

Adding a new feature to monster cards:
1. Update [src/views/templates/monster-card-mini.php](src/views/templates/monster-card-mini.php)
2. Add JavaScript function to [public/js/monster-actions.js](public/js/monster-actions.js)
3. Add corresponding AJAX endpoint in `public/api/`
4. Fetch data in relevant controller
5. Pass to view via controller variables

## Code Quality Standards

- **Comments**: Every function has docblock with parameters and return types
- **Naming**: Clear, descriptive variable and function names
- **Indentation**: 4 spaces, consistent formatting
- **Error Handling**: Try-catch for exceptions, graceful error messages
- **Security**: No SQL injection risks, password hashing, session validation
- **DRY**: Reusable components (monster-card-mini.php), shared models

## Performance Optimizations

- **Database Indexing**: Indexes on frequently queried columns (u_id, monster_id, is_public)
- **AJAX Loading**: Collections loaded on-demand, not in every page's HTML
- **Query Optimization**: JOIN for like counts instead of N+1 queries
- **Caching**: Like counts queried once per page load, not per card
- **Image Optimization**: Size limits prevent large uploads

## Troubleshooting

**Docker Issues**
```bash
# View logs
docker logs php-apache-monster-maker
docker logs mysql-db-monster-maker

# Restart services
docker-compose restart

# Full reset
docker-compose down
docker volume prune
docker-compose up -d
```

**Database Issues**
```bash
# Access MySQL
docker exec -it mysql-db-monster-maker mysql -uroot -proot

# View error log
docker exec php-apache-monster-maker tail /var/log/apache2/error.log
```

**Upload Issues**
```bash
# Check directory exists
docker exec php-apache-monster-maker ls -la /var/www/public/uploads/

# Fix permissions
docker exec php-apache-monster-maker chmod 755 /var/www/public/uploads
```

## API Documentation

### GET /index.php?url=monster-like&id={id}

Toggle like on monster (requires authentication)

**Request**
```
GET /index.php?url=monster-like&id=123
```

**Response** (200 OK)
```json
{
  "success": true,
  "action": "added",
  "count": 5,
  "liked": true
}
```

### POST /api/add-to-collection.php

Add monster to existing collection

**Request Body**
```json
{
  "monster_id": 123,
  "collection_id": 456
}
```

**Response** (200 OK)
```json
{
  "success": true,
  "message": "Added 'Goblin' to 'To Print'."
}
```

### POST /api/create-collection-and-add.php

Create collection and add monster (atomic operation)

**Request Body**
```json
{
  "monster_id": 123,
  "collection_name": "Goblin Encounters",
  "description": "Level 1-3 goblin-themed monsters"
}
```

**Response** (200 OK)
```json
{
  "success": true,
  "message": "Created 'Goblin Encounters' and added 'Goblin Warrior'.",
  "collection_id": 15
}
```

### GET /api/get-collections.php

Fetch user's collections (requires authentication)

**Request**
```
GET /api/get-collections.php
```

**Response** (200 OK)
```json
{
  "success": true,
  "collections": [
    {
      "collection_id": 1,
      "collection_name": "To Print",
      "monster_count": 12,
      "is_default": true
    }
  ]
}
```

## License

Educational purposes - © 2025 Alex, LaKobolderie

## Contributing

To contribute to this project:

1. Read the code comments and docblocks first
2. Follow existing patterns for consistency
3. Write clear variable and function names
4. Add step-by-step comments for complex logic
5. Test in Docker environment before committing
6. Update README if changing API or structure


