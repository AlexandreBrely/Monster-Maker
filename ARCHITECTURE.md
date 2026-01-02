# Monster Maker - Architecture

## Overview
Lean MVC PHP app with a Puppeteer microservice for PDFs. This page is the single source for structure, stack, and data flow.

## Stack
- Backend: PHP 8.4 + Apache
- Frontend: HTML, CSS, JS (Fetch/async)
- DB: MySQL 8
- PDF: Node.js + Puppeteer (Chromium) in its own container
- Containers: docker-compose (PHP, MySQL, phpMyAdmin, Puppeteer)

## Directory Map (essential only)
```
public/              # Web root + router (index.php) + AJAX api/*
   css/               # style.css, boss-card.css, small-statblock.css, monster-card-mini.css, monster-form.css, lair-card.css
   js/                # monster-actions.js, monster-form.js, collection-manager.js
   uploads/           # avatars/, monsters/

src/
   controllers/       # AuthController, MonsterController, CollectionController, LairCardController, PagesController, HomeController
   models/            # Database, Monster, Collection, LairCard, MonsterLike, User
   services/          # FileUploadService (uploads)
   views/             # auth/, collection/, dashboard/, home/, lair/, monster/, pages/, templates/, print-templates/

db/init/             # database_structure.sql
docker/              # Dockerfile.puppeteer, Dockerfile.mysql, apache/vhost.conf
config/              # php.ini, xdebug.ini

docker-compose.yml   # Orchestration
README.md            # Quick start
ARCHITECTURE.md      # This file
JURY_PRESENTATION.md # Jury-facing brief
JURY_QUICK_REFERENCE.md # 60-second crib
INDEX.md             # Feature index
```

## MVC Roles
- Controllers: route handling, permission checks, delegating to models.
- Models: DB CRUD with prepared statements; no presentation logic.
- Services: shared logic (currently FileUploadService). Puppeteer is external service, not a PHP service class.
- Views: PHP templates; no business logic.

## PDF Pipeline (high level)
1) Browser hits `?url=monster-pdf&id=X` via fetch.
2) MonsterController validates access, builds print URL, calls Puppeteer service via PrintService.
3) Puppeteer (Node/Chromium) renders clean print template and returns PDF bytes.
4) PHP streams PDF with headers → browser download.

## Data & Security
- Auth: session-based; guarded routes check `$_SESSION['user']`.
- Authorization: monsters/collections enforce owner-or-public rule in controllers.
- DB safety: prepared statements everywhere.
- Uploads: FileUploadService validates MIME/size; stored under public/uploads/.

## Docker Services
- php-apache: app at http://localhost:8000
- mysql: DB (internal)
- phpmyadmin: http://localhost:8080
- pdf-renderer: Puppeteer service (internal only)

## Things intentionally removed
- Old mPDF/TCPDF code and legacy tests.
- Browsershot-era services.
- Redundant documentation and scratch scripts.
Controllers handle:
- HTTP request routing (GET/POST)
- Input validation
- Error handling
- View rendering
- Delegating to models/services

**Key controllers:**
- `MonsterController` - CRUD for monsters, image uploads
- `AuthController` - User login, registration, profile
- `CollectionController` - Monster collections
- `LairCardController` - D&D lair cards
- `HomeController` - Homepage
- `PagesController` - Static pages (Terms, etc)

### Models (Data Access)
Models handle:
- Database CRUD operations
- Data validation
- Query building
- JSON serialization (complex fields)
- File system management (images)

**Key models:**
- `Monster` - Monster CRUD + complex field handling
- `User` - User authentication + profile
- `Collection` - Collection management
- `Database` - PDO connection management
- `MonsterLike` - Like/unlike operations
- `LairCard` - D&D lair cards

### Services (Business Logic)
Services handle:
- File uploads + validation
- Complex operations (e.g., PDF generation - future)
- Shared business logic

**Current services:**
- `FileUploadService` - Image validation + storage

### Views (Presentation)
Views are PHP templates that:
- Render HTML
- Use data from controllers
- Include reusable components
- Don't contain business logic

**View organization:**
- Form pages: `/views/monster/create.php`, `/views/auth/login.php`
- Display pages: `/views/monster/show.php`, `/views/collection/view.php`
- Partials: `/views/templates/` (reusable components)

## CSS Architecture

### Global Styles
- `style.css` - Base typography, layout, utilities

### Component-Specific Styles
- `monster-form.css` - Form section styling
- `boss-card.css` - Full-page boss statblock display
- `small-statblock.css` - Compact monster display
- `monster-card-mini.css` - Mini preview cards
- `lair-card.css` - D&D lair card styling

### CSS for Printing
- Each CSS file includes `@media print` rules
- Ready for print stylesheet integration
- Will be enhanced with `@page` rules for PDF generation

## JavaScript Architecture

### Core Functionality
- `monster-form.js` - Dynamic form sections (actions, traits, etc)
- `monster-actions.js` - AJAX operations (like, collections)
- `collection-manager.js` - Collection UI management

### Bootstrap Integration
- Bootstrap 5.3.8 for grid + components
- Bootstrap Icons for icon set
- No client-side PDF generation (moved to server)

## Database Integration

### Connection Management
- `Database.php` establishes PDO connection
- All models use dependency injection
- Connection pooling via PDO

### Models Use Prepared Statements
- Prevents SQL injection
- All user input is parameterized
- Example: `$stmt->execute([':id' => $id])`

## Security Implementation

### Authentication
- Session-based (PHP $_SESSION)
- User ID stored in session
- Protected routes check authentication

### Authorization
- Ownership verification on CRUD operations
- Examples: User can only edit own monsters/collections

### Input Validation
- All user input validated in models
- HTML-escaped in views (`htmlspecialchars()`)
- File uploads validated (MIME, size, extension)

### File Handling
- Images stored outside web root structure
- File deletion on monster/collection removal
- Upload paths controlled by `FileUploadService`

## Cleanup Status

### ✅ Completed
- Removed deprecated JavaScript (old card-download-v1.js)
- Removed obsolete test files (test-mpdf.php, test PDFs)
- Archived old implementations (PrintService, mPDF code)
- Removed old documentation
- Removed Browsershot PHP dependency
- Footer.php cleaned (removed old PDF script)
- Empty directories removed (html/, old_code/)

### ✅ MVC Structure
- Controllers properly delegate to models
- Models handle all database operations
- Services encapsulate business logic
- Views are presentation-only
- Clear separation of concerns

### 🔄 Ready for Next Phase
- Clean codebase ready for PDF microservice implementation
- Architecture supports multiple rendering strategies
- All dependencies removed that conflicted with Puppeteer approach
- Ready to implement Phase 1: Node.js Puppeteer microservice

## Future Enhancement: PDF Rendering

The cleaned architecture will support:

1. **Puppeteer Microservice** (Node.js)
   - Renders HTML pages to PDF
   - Handles font loading
   - Supports @page CSS rules
   - Returns PDF blob to PHP

2. **New PrintService** (PHP)
   - Calls Puppeteer microservice via cURL
   - Streams PDF to browser
   - Handles errors gracefully

3. **New PDF Rendering URLs** (in controllers)
   - `/monster/{id}/print-preview` - Display printable HTML
   - `/collection/{id}/print-preview` - Collection printable view
   - API endpoints to trigger PDF generation

## Notes for Development

- **Do not add direct PDF libraries to PHP** - Use microservice pattern
- **Keep CSS clean** - @media print rules ready for enhancement
- **Models handle validation** - Controllers just delegate
- **Views are templates** - No business logic in template code
- **Services for shared logic** - FileUploadService is a good example

---

**Cleanup Completed:** December 24, 2025  
**Ready for Phase 1:** Puppeteer Microservice Implementation
