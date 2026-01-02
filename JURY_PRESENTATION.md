# Monster Maker – Jury Presentation

## 1) What It Is (30s)
- Web app to create, manage, and print D&D 5e monster cards.
- Core loop: build monsters → organize collections → download/print PDFs.

## 2) How to Run (60s)
```bash
docker-compose up --build
# App: http://localhost:8000
# DB Admin: http://localhost:8080
```
- Services: php-apache, mysql, phpmyadmin, pdf-renderer (Puppeteer, internal only).

## 3) Architecture Snapshot (60s)
- MVC PHP (controllers/models/views) + MySQL.
- Node.js + Puppeteer microservice renders print HTML → PDF (Chromium).
- Docker network for service-to-service calls (no external exposure for Puppeteer).
- See ARCHITECTURE.md for the tree and roles.

## 4) Demo Script (5–7 min)
1) Create/edit monster with image.
2) Like + add to collection (AJAX, no reload).
3) Download print-ready PDF (shows Puppeteer path).
4) Toggle public/private and show access control.

## 5) Why Puppeteer (story in one breath)
mPDF/TCPDF broke modern CSS (flex, grid, fonts). Real Chrome via Puppeteer renders exactly what users see, then prints to PDF. Microservice avoids blocking PHP and keeps Chrome isolated.

## 6) Files to Show (2–3 key opens)
- Router/entry: public/index.php
- Controller: src/controllers/MonsterController.php (permissions + PDF call)
- JS interactions: public/js/monster-actions.js (fetch/async for like, collection, PDF download)
- Print template: src/views/print-templates/print-wrapper.php
- Docker: docker-compose.yml, docker/Dockerfile.puppeteer

## 7) Safety / QA Talking Points
- Permissions: owner-or-public enforced in controllers.
- Security: sessions, prepared statements, upload validation (MIME/size).
- Resilience: Puppeteer isolated; PHP returns JSON errors if renderer fails.
- Data: prepared statements everywhere; uploads stored under controlled path.

## 8) Avoid / Out of Scope
- Removed scratch scripts and legacy docs (not part of demo).
- Older mPDF/TCPDF attempts (just mention they failed on CSS).
