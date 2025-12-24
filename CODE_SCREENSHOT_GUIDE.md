# Code Reference Guide - Screenshots for Jury Presentation

## Overview

Your JURY_PRESENTATION.md now includes **clickable links** to every major code section. When presenting, you can:

1. Open the presentation file in your browser/editor
2. Click any "Source Code:" link
3. Take a screenshot of the relevant code

## Quick Reference: What to Screenshot

### Part 3: Router - The Entry Point
- **File:** [public/index.php](public/index.php)
- **What to Show:** URL parsing, route mapping, controller instantiation
- **Key Lines:** Entire routing logic (spl_autoload_register, $routes array, controller execution)

### Part 5: Creating a Monster - CRUD Operations

#### Step 1: Show Creation Form
- **File:** [src/controllers/MonsterController.php](src/controllers/MonsterController.php) - `create()` method
- **What to Show:** Form display logic
- **Talking Point:** Checking authentication before showing form

#### Step 2: Validate and Save Data
- **File:** [src/controllers/MonsterController.php](src/controllers/MonsterController.php) - `store()` method
- **What to Show:** Data extraction, validation, database save, redirect
- **Talking Point:** Error handling and user feedback

#### Step 3: Complex Forms with JavaScript
- **File:** [public/js/monster-form.js](public/js/monster-form.js)
- **What to Show:** Ability modifier calculations, dynamic form builders
- **Talking Point:** Real-time validation without page reload

#### Step 4: Image Handling
- **File:** [src/models/FileUploadService.php](src/models/FileUploadService.php)
- **What to Show:** File type validation, MIME checking, secure naming
- **Talking Point:** Security: preventing malicious file uploads

#### Step 5: Retrieve/Update/Delete
- **File:** [src/controllers/MonsterController.php](src/controllers/MonsterController.php)
- **Methods:** `show()`, `update()`, `delete()`
- **Talking Point:** Authorization checks - only owner can edit/delete

### Part 6: Print Optimization - Download & Complex CSS

#### Playing Card Layout
- **CSS:** [public/css/small-statblock.css](public/css/small-statblock.css)
- **HTML:** [src/views/monster/small-statblock.php](src/views/monster/small-statblock.php)
- **What to Show:** @page sizing, column layouts, print media queries
- **Talking Point:** Responsive design for printing

#### Boss Card Layout
- **CSS:** [public/css/boss-card.css](public/css/boss-card.css)
- **HTML:** [src/views/monster/boss-card.php](src/views/monster/boss-card.php)
- **What to Show:** CSS Grid for two-column layout, ability grid
- **Talking Point:** Professional card design with print-ready sizing

#### Lair Card Layout
- **CSS:** [public/css/lair-card.css](public/css/lair-card.css)
- **HTML:** [src/views/lair/show.php](src/views/lair/show.php)
- **What to Show:** Landscape orientation, action layout
- **Talking Point:** Domain-specific card format for lair actions

#### JavaScript Download
- **File:** [public/js/card-download.js](public/js/card-download.js)
- **What to Show:** jsPDF integration, hiding UI elements for print
- **Talking Point:** Converting HTML to PDF automatically

### Part 7: User Authentication & Protection

#### Registration Form
- **File:** [src/views/auth/register.php](src/views/auth/register.php)
- **What to Show:** Form validation, password requirements, field types
- **Talking Point:** Client-side UX + server-side validation

#### Security Protections
- **Files to Reference:**
  - [src/controllers/AuthController.php](src/controllers/AuthController.php) - Bcrypt hashing
  - [src/models/User.php](src/models/User.php) - Prepared statements

### Part 8: Browsing & Searching

#### Pagination and Query Optimization
- **File:** [src/controllers/MonsterController.php](src/controllers/MonsterController.php) - `index()` method
- **What to Show:** LIMIT/OFFSET calculation, pagination logic
- **Talking Point:** Database performance optimization

### Part 9: Collection System & Sharing

#### Atomic Operations
- **File:** [src/controllers/CollectionController.php](src/controllers/CollectionController.php) - `createAndAdd()` method
- **What to Show:** Create then add pattern, rollback on failure
- **Talking Point:** All-or-nothing transactions

#### Token-Based Sharing
- **File:** [src/controllers/CollectionController.php](src/controllers/CollectionController.php) - `share()` and `viewShared()` methods
- **What to Show:** Random token generation, public viewing
- **Talking Point:** Secure sharing without user ID enumeration

### Part 10: Like System & AJAX

#### Frontend JavaScript
- **File:** [public/js/monster-actions.js](public/js/monster-actions.js) - `toggleLike()` function
- **What to Show:** fetch() API, JSON parsing, UI updates
- **Talking Point:** Modern AJAX without page reload

#### Backend Controller
- **File:** [src/controllers/MonsterController.php](src/controllers/MonsterController.php) - `toggleLike()` method
- **What to Show:** Authentication, authorization, JSON response
- **Talking Point:** API endpoint pattern

#### Database Model
- **File:** [src/models/MonsterLike.php](src/models/MonsterLike.php)
- **What to Show:** `toggleLike()`, `countLikes()`, `hasLiked()` methods
- **Talking Point:** Database operations and constraint checking

#### Persistence on Page Load
- **Files:**
  - [src/controllers/MonsterController.php](src/controllers/MonsterController.php) - Loading `$userLikes`
  - [src/models/MonsterLike.php](src/models/MonsterLike.php) - `getUserLikes()` method
  - [src/views/templates/monster-card-mini.php](src/views/templates/monster-card-mini.php) - Rendering heart state
- **What to Show:** Three-part system: load → fetch → display
- **Talking Point:** Data persistence across page views

---

## Tips for Taking Screenshots

1. **Use Light Theme:** Easier to see in presentation
2. **Hide Line Numbers Optional:** Choose what's clearest
3. **Highlight Key Lines:** Use editor to highlight important code
4. **Screenshot Just Enough:** Focus on 8-15 lines per screenshot
5. **Zoom In:** Make code readable from audience distance
6. **Multiple Views:** Show the flow (e.g., form → controller → model → database)

## Presentation Flow Suggestion

When presenting each feature:

1. **Show the HTML/Form** (user perspective)
2. **Show the Controller** (business logic)
3. **Show the Model** (database operations)
4. **Show the Result** (what user sees)

This three-part flow helps jury understand the complete architecture.

---

## File Locations Quick Summary

```
public/
├── index.php                        # Router entry point
├── css/
│   ├── small-statblock.css         # Playing card print layout
│   ├── boss-card.css               # A6 boss card layout
│   └── lair-card.css               # Landscape lair card layout
└── js/
    ├── monster-form.js              # Complex form interactions
    ├── monster-actions.js           # AJAX like system
    └── card-download.js             # PDF generation

src/
├── controllers/
│   ├── MonsterController.php        # Monster CRUD
│   ├── CollectionController.php     # Collection operations
│   └── AuthController.php           # Registration/Login
├── models/
│   ├── MonsterLike.php              # Like system database
│   ├── FileUploadService.php        # Image security
│   └── Monster.php                  # Monster database
└── views/
    ├── auth/
    │   └── register.php              # Registration form
    ├── monster/
    │   ├── small-statblock.php      # Playing card view
    │   └── boss-card.php            # Boss card view
    ├── lair/
    │   └── show.php                 # Lair card view
    └── templates/
        └── monster-card-mini.php    # Reusable card component
```

All files are clickable links in your JURY_PRESENTATION.md!
