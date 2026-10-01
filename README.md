# TimeSheet

A clean, modern, and lightweight Time-Tracking Web Application built with **PHP 8**, **Eloquent ORM**, and vanilla **HTML/CSS/JavaScript**.

---

## Features Planned & In Progress

- **Movable Floating Timer**: Pure CSS watch dial with real-time tracking and draggable modal popup (no external SVG required).
- **Dynamic Theme Engine**: Light and Dark mode with solid flat color design tokens and `localStorage` persistence.
- **Authentication & Roles**: Secure login system with distinct **Admin** and **User** access levels.
- **Logs Management**: View, add, edit, and delete daily time logs.
- **Dashboards**:
  - **User Dashboard**: Individual timesheet entries and daily progress.
  - **Admin Dashboard**: Team-wide time oversight, metrics, and user management.
- **Reporting & Filtering**: Filter logged hours across Daily, Weekly, and Monthly windows.
- **Export Engine**: Export time records to **CSV / Excel** and printable **PDF**.

---

## How It Is Built

### 1. Architecture & Design Patterns
- **Component-Driven Core**: Uses an auto-discovering component system (`loadComponent()`) in `index.php` that dynamically registers matching `.css` and `.js` files for each component on demand.
- **Micro-Router (`src/router.php`)**: Handles clean URL path matching (`/`, `/login`, `/admin`, etc.) with fallback to a 404 handler and built-in CLI static file passthrough.
- **Output Buffering Layout (`src/app.php`)**: Captures routed view output into buffer, cleanly inserting discovered stylesheets in the `<head>` and scripts before `</body>`.
- **Database Layer**: Powered by `illuminate/database` (Eloquent ORM) for clean database modeling and query execution.
- **Zero-Dependency Frontend**: Pure CSS variables (`assets/css/globalvars.css`, `assets/css/global-dark.css`) and modular vanilla JavaScript.

### 2. Project Directory Structure
```text
TimeSheet/
├── assets/
│   ├── css/
│   │   ├── globalvars.css       # Color palettes and theme tokens
│   │   ├── global.css           # Layout, header, sticky footer, timer styling
│   │   └── global-dark.css      # Dark theme overrides
│   └── js/
│       └── global.js            # Theme toggling and timer counter logic
├── Docs/
│   └── PLAN.md                  # Comprehensive technical specification & roadmap
├── src/
│   ├── components/              # Reusable UI components (header, footer, modal)
│   ├── pages/                   # Routable views (home, etc.)
│   ├── app.php                  # Application wrapper & buffering
│   └── router.php               # Route registry
├── composer.json                # Composer dependencies and PSR-4 namespace
├── index.php                    # Development server router entry
└── README.md                    # Project documentation
```

---

## Getting Started / How to Run

### Prerequisites
- **PHP 8.1+** installed (e.g. from XAMPP or standalone). Verify with:
  ```powershell
  php -v
  ```
- **Composer** installed. Verify with:
  ```powershell
  composer -v
  ```

### Installation

1. **Clone or navigate to the project directory**:
   ```powershell
   cd c:\Projects\TimeSheet
   ```

2. **Install PHP dependencies**:
   ```powershell
   composer install
   ```

3. **Start the local development server**:
   ```powershell
   php -S 127.0.0.1:8080 index.php
   ```

4. **Open in your browser**:
   Navigate to:
   ```text
   http://127.0.0.1:8080
   ```

---

## Roadmap & Documentation

For the complete architectural design, database schemas, and phase-by-phase implementation plan, see:
- [Docs/PLAN.md](file:///c:/Projects/TimeSheet/Docs/PLAN.md)
