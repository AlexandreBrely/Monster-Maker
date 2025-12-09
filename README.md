# Monster Maker

A D&D monster generator web application with user authentication, CRUD operations, and file uploads.

## Tech Stack

- **Backend:** PHP 8.4
- **Frontend:** HTML5, CSS3, Bootstrap 5.3
- **Database:** MySQL 8.0
- **Architecture:** MVC (Model-View-Controller)
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
src/
├── Controllers/
│   ├── AuthController.php      # User auth (register, login, profile)
│   ├── MonsterController.php   # Monster CRUD operations
│   ├── HomeController.php      # Home page
│   └── PagesController.php     # Static pages
├── Models/
│   ├── User.php                # User model + validation
│   ├── Monster.php             # Monster CRUD + validation
│   └── Database.php            # PDO connection
└── Views/
    ├── auth/                   # Login, register, profile
    ├── monster/                # Monster CRUD views
    ├── templates/              # Header, navbar, footer
    └── pages/                  # Static pages
public/
├── index.php                   # Router (URL → Controller)
├── uploads/                    # User images (avatars, monsters)
├── css/                        # Stylesheets
└── js/                         # JavaScript (if needed)
```

## How It Works

### Architecture Pattern
- **Router** (`public/index.php`): Maps URLs to controller methods
- **Controllers**: Handle requests, call models, display views
- **Models**: Database operations, validation, business logic
- **Views**: HTML templates with error/success handling

### User Flow

**Registration:**
```
User fills form → Controller validates → Model checks uniqueness 
→ Password hashed → Inserted to DB → Redirects to login
```

**Authentication:**
```
User submits credentials → Controller finds user by email 
→ Verifies password hash → Session created → Logged in
```

**Monster Creation:**
```
User fills form → Controller validates → Images uploaded 
→ Model serializes actions/reactions to JSON → Saved to DB 
→ Redirects to monster view
```

**Authorization:**
```
User attempts edit/delete → Controller checks u_id matches 
→ If owner: allowed / If not owner: 403 error shown
```

### Database Schema

**users table:**
- u_id (PK)
- u_username (UNIQUE)
- u_email (UNIQUE)
- u_password (hashed)
- u_avatar (filename)

**monster table:**
- monster_id (PK)
- u_id (FK to users)
- name, size, type, alignment
- ac, hp, hit_dice
- ability scores (STR, DEX, CON, INT, WIS, CHA)
- actions (JSON array)
- reactions (JSON array)
- legendary_actions (JSON array)
- image_portrait, image_fullbody
- is_public (0/1)
- created_at, updated_at

### Key Technologies

**PDO (PHP Data Objects):**
- Prepared statements prevent SQL injection
- Parameterized queries with `:param` notation
- Works with any database driver

**Password Security:**
- `password_hash()` with default algorithm (PASSWORD_DEFAULT = bcrypt)
- `password_verify()` for login verification
- No plaintext passwords stored

**File Uploads:**
- MIME type validation (not filename-based)
- Size limit: 5MB
- Unique filenames: `{random_bytes}_{original_name}.{ext}`
- Prevents directory traversal attacks

**Form Validation:**
- Server-side only (client validation is unreliable)
- Type casting: `(int)`, `(string)`, `trim()`, etc.
- Uniqueness checks: email, username
- Range checks: ability scores 1-30, AC > 0

**JSON Handling:**
- Complex data (actions, reactions) stored as JSON in database
- Automatic serialization on insert: `json_encode()`
- Automatic deserialization on retrieval: `json_decode()`
- Queryable via SQL JSON functions if needed

## Installation & Setup

1. **Database Import:**
   - phpMyAdmin: http://localhost:8081
   - Import schema from `db/init/` folder
   - Creates: users, monster tables

2. **Directory Permissions:**
   ```bash
   mkdir -p public/uploads/{avatars,monsters}
   chmod 755 public/uploads
   ```

3. **Test the System:**
   - Register at http://localhost:8000?url=register
   - Login at http://localhost:8000?url=login
   - Create monster at http://localhost:8000?url=create

## Author

© 2025 Alex, LaKobolderie

## License

Educational purposes

