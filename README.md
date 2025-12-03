# Monster Maker

A web application for creating, sharing, and printing custom monsters for tabletop RPGs (D&D, Pathfinder, etc.).

## Features

- Create custom monsters with D&D-style stats
- Browse monsters created by the community
- Export monsters as PDF (business card size or A5 format)
- User authentication and profile management

## Tech Stack

- **Backend:** PHP 8.4
- **Frontend:** HTML, CSS, Bootstrap 5.3
- **Database:** MySQL 8.0
- **Architecture:** MVC (Model-View-Controller)
- **Container:** Docker & Docker Compose

## Installation

1. Clone the repository:
```bash
git clone https://github.com/AlexandreBrely/Monster-Maker.git
cd Monster-Maker
```

2. Start Docker containers:
```bash
docker-compose up -d
```

3. Access the application:
- Website: http://localhost:8000
- phpMyAdmin: http://localhost:8081

## Project Structure

```
Monster_Maker/
├── public/              # Web root
│   ├── index.php       # Entry point & router
│   ├── css/            # Stylesheets
│   ├── js/             # JavaScript files
│   └── assets/         # Images and other assets
├── src/
│   ├── controllers/    # Application controllers
│   ├── models/         # Data models
│   └── views/          # View templates
│       └── templates/  # Reusable templates (header, navbar, footer)
├── config/             # Configuration files
├── docker/             # Docker configuration files
├── php/                # PHP configuration
└── db/                 # Database initialization scripts
```

## Author

© 2025 Alex, LaKobolderie

## License

This project is for educational purposes.
