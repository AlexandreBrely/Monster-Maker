# Monster Maker – MVC Tree (Screenshot Ready)

```
Monster_Maker/
├─ public/                     # Front controller + public assets
│  ├─ index.php                # Router / front controller
│  ├─ api/                     # JSON endpoints (AJAX)
│  │  ├─ add-to-collection.php
│  │  ├─ create-collection-and-add.php
│  │  └─ get-collections.php
│  ├─ css/                     # Presentation layer (cards, forms, layout)
│  │  ├─ boss-card.css
│  │  ├─ lair-card.css
│  │  ├─ monster-card-mini.css
│  │  ├─ monster-form.css
│  │  ├─ small-statblock.css
│  │  └─ style.css
│  ├─ js/                      # Client-side behavior (AJAX, forms, PDF)
│  │  ├─ card-download.js
│  │  ├─ collection-manager.js
│  │  ├─ monster-actions.js
│  │  └─ monster-form.js
│  └─ uploads/                 # User assets
│     ├─ avatars/
│     └─ monsters/
│
├─ src/                        # Application code (MVC)
│  ├─ controllers/             # C = Controllers (routing targets)
│  │  ├─ AuthController.php
│  │  ├─ CollectionController.php
│  │  ├─ HomeController.php
│  │  ├─ LairCardController.php
│  │  ├─ MonsterController.php
│  │  └─ PagesController.php
│  ├─ models/                  # M = Models (data + business rules)
│  │  ├─ Collection.php
│  │  ├─ Database.php
│  │  ├─ LairCard.php
│  │  ├─ Monster.php
│  │  ├─ MonsterLike.php
│  │  ├─ User.php
│  │  └─ FileUploadService.php
│  ├─ services/                # Shared services (upload, printing)
│  │  └─ FileUploadService.php
│  └─ views/                   # V = Views (server-rendered UI)
│     ├─ auth/
│     │  ├─ edit-profile.php
│     │  ├─ login.php
│     │  ├─ register.php
│     │  └─ settings.php
│     ├─ collection/
│     │  ├─ create.php
│     │  ├─ edit.php
│     │  ├─ index.php
│     │  ├─ public-view.php
│     │  └─ view.php
│     ├─ dashboard/
│     │  └─ my-cards.php
│     ├─ home/
│     │  └─ index.php
│     ├─ lair/
│     │  ├─ create.php
│     │  ├─ my-lair-cards.php
│     │  └─ show.php
│     ├─ monster/
│     │  ├─ boss-card.php
│     │  ├─ create.php
│     │  ├─ create_select.php
│     │  ├─ create_small.php
│     │  ├─ edit.php
│     │  ├─ index.php
│     │  ├─ my-monsters.php
│     │  ├─ show.php
│     │  ├─ small-statblock.php
│     │  └─ partials/
│     │     ├─ actions.php
│     │     ├─ bonus_actions.php
│     │     ├─ legendary_actions.php
│     │     ├─ reactions.php
│     │     └─ traits.php
│     └─ templates/
│        ├─ action-buttons.php
│        ├─ footer.php
│        ├─ header.php
│        ├─ lair-card-mini.php
│        ├─ monster-card-mini.php
│        └─ navbar.php
│
├─ db/
│  └─ init/
│     └─ database_structure.sql   # Full schema
│
├─ docker/                        # Containerization
│  ├─ Dockerfile.mysql
│  └─ apache/
│     └─ vhost.conf
├─ config/
│  └─ db/ init scripts (if any)
├─ html/                          # Legacy HTML (if retained)
├─ php/                           # Legacy PHP config (if retained)
└─ Dockerfile                     # PHP-Apache image
```

**Caption for screenshot:** "Monster Maker project tree—clear MVC separation with public assets, controllers/models/views, and containerized infrastructure (Docker + MySQL + Puppeteer)."