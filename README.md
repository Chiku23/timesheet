# TimeSheet

A clean, modern, and lightweight Time-Tracking Web Application built with **PHP 8**, **Eloquent ORM (MySQL)**, and vanilla **HTML5/CSS3/JavaScript**.

---

## Key Features

- **Server-Authoritative Floating Timer**: Draggable, non-blocking floating stopwatch launcher backed by `timer_sessions` table in MySQL. Sessions survive page refreshes, tab closures, and multi-device access.
- **Dynamic Theme Engine**: Smooth dark and light mode toggle with flat, high-contrast design tokens and zero-flash `localStorage` persistence.
- **Dual Timezone Engine**: Instant switching between **Local Browser Timezone** and **UTC (Universal Time)** across all dashboards, time ranges, and analytical charts.
- **Authentication & User Registration**:
  - Secure `/login` and `/signup` self-service registration.
  - Role-based access control (**Admin** vs **User**).
  - Clean authentication pages with extraneous navigation and footers suppressed.
- **Dashboard & Timesheet Logging**:
  - **Weekly Matrix View**: Daily breakdown with project totals and weekly target progress.
  - **Paginated Detailed Logs Table**: Search by task description, filter by project and source (Timer vs Manual), with configurable page sizing (10, 25, 50).
  - **Author-Only Deletion Safeguard**: Logs use soft-deletes (`deleted_at`), and employees can only edit/delete their own entries.
- **Reports & Analytics**:
  - Filterable by presets (This Week, This Month, Last Month) or custom date ranges.
  - Daily tracked time bar chart with responsive horizontal scrolling for extended periods.
  - Time by project breakdown with color indicators.
  - Paginated Period Time Logs table.
- **CSV Data Export**: One-click download of filtered timesheet data directly formatted as CSV.
- **Admin Panel & Dedicated Management Views**:
  - Team member oversight, activity metrics, and role management.
  - Dedicated full-page user editor (`/admin/user-edit?id=X`) replacing disruptive popups.
- **Self-Service Account Settings**: Profile modal for name, email, and password updates with validation.
- **Global Confirmation Modal**: Centralized custom modal dialogs replacing native browser `window.confirm()`.

---

## Architecture & Technology Stack

### 1. Architecture Patterns
- **Component-Driven Pipeline (`loadComponent()`)**: Dynamically discovers and auto-registers matching `.css` and `.js` assets for each page or component on demand.
- **Micro-Router (`src/router.php`)**: Handles HTML page routing, route authorization guards, and admin privileges.
- **RESTful API Router (`src/api.php`)**: Independent API routing terminating before the HTML layout pipeline, returning JSON responses.
- **Database Layer**: Powered by `illuminate/database` (Eloquent ORM) with MySQL. Models include `User`, `TimeLog`, `Project`, `TimerSession`, and `Setting`.
- **Zero Heavy Frontend Dependencies**: Built entirely with clean vanilla JavaScript and modular CSS variables.

### 2. Directory Structure
```text
TimeSheet/
├── assets/
│   ├── css/
│   │   ├── globalvars.css       # Palette and design system tokens
│   │   ├── global.css           # Layout, header, sticky footer, timer styling
│   │   └── global-dark.css      # Dark theme overrides
│   ├── images/
│   │   └── logo.png             # Lightweight 64x64 TS brand logo
│   └── js/
│       └── global.js            # Timer state machine, timezone engine, modal system
├── Docs/
│   ├── PLAN.md                  # Comprehensive project specification
│   └── ARCHITECTURE_AND_LOGIC.md# Technical architecture and API documentation
├── src/
│   ├── components/              # Header, footer, and navigation components
│   ├── config/
│   │   └── database.php         # Eloquent MySQL connection bootstrap
│   ├── models/                  # Eloquent models (User, TimeLog, Project, etc.)
│   ├── pages/                   # Routable views (home, login, signup, admin, reports, settings, profile)
│   ├── api.php                  # API router and endpoints
│   ├── app.php                  # Application buffer wrapper
│   └── router.php               # Page route registry and guards
├── composer.json                # Dependencies (illuminate/database)
├── index.php                    # Development server router entry
└── README.md                    # Project documentation
```

---

## Getting Started

### Prerequisites
- **PHP 8.1+** (CLI & PDO MySQL enabled)
- **MySQL / MariaDB** (via XAMPP or standalone service)
- **Composer**

### Setup & Running

1. **Clone the repository**:
   ```bash
   git clone https://github.com/Chiku23/timesheet.git
   cd timesheet
   ```

2. **Install Composer dependencies**:
   ```bash
   composer install
   ```

3. **Configure Database**:
   Verify your MySQL database credentials in `src/config/database.php` (defaults to host `127.0.0.1`, database `timesheet`, username `root`, password empty).

4. **Start the Development Server**:
   ```bash
   php -S 127.0.0.1:8081 index.php
   ```

5. **Access Application**:
   Open [http://127.0.0.1:8081](http://127.0.0.1:8081) in your browser.
