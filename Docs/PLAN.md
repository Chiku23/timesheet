# TimeSheet Architecture & Implementation Plan

This document outlines the detailed roadmap, technical architecture, database schema, and step-by-step implementation plan for the **TimeSheet** web application.

---

## 1. High-Level Requirements

1. **Authentication & Authorization**:
   - Secure login & logout.
   - Role-based access control: **Admin** and **User**.
2. **Interactive Timer**:
   - Movable/draggable popup clock with custom CSS watch dial (no SVG).
   - Real-time time recording with session continuity.
3. **Logs Management**:
   - Tabular view of time records.
   - Full CRUD: Add, Edit, and Delete log entries.
4. **Dashboards**:
   - **User Dashboard**: Personal logs, active timer, weekly summary.
   - **Admin Dashboard**: Team-wide time metrics, user activity, management.
5. **Reporting & Analytics**:
   - Filter by timeframe: Daily, Weekly, Monthly, or Custom Range.
   - Total hours and project breakdown.
6. **Exporting**:
   - Export reports to **CSV / Excel** format.
   - Export visual reports to **PDF** (leveraging `html2canvas` & `pdfmake`).

---

## 2. Technical Architecture

### 2.1 Technology Stack
- **Backend**: Native PHP 8.x with PSR-4 Autoloading (`src/`).
- **Database Layer**: `illuminate/database` (Eloquent ORM) with MySQL or SQLite.
- **Frontend Core**: Vanilla PHP component templates, Vanilla JavaScript (ES6+), Vanilla CSS (Custom properties / Variables).
- **Asset Pipeline**: Automatic asset discovery via `loadComponent()` in `index.php`.
- **Theming**: System-wide Light and Dark mode with persistent `localStorage` support.

### 2.2 Directory Structure
```
TimeSheet/
├── assets/
│   ├── css/
│   │   ├── globalvars.css       # Core design tokens, colors & themes
│   │   ├── global.css           # Base styles, header, footer, floating timer
│   │   └── global-dark.css      # Dark theme overrides
│   └── js/
│       └── global.js            # Theme toggle, timer logic, draggable modal
├── Docs/
│   └── PLAN.md                  # Project plan and specifications
├── src/
│   ├── components/              # Shared UI components (header, footer, modal)
│   ├── config/                  # Database and app configuration
│   ├── models/                  # Eloquent database models (User, TimeLog, Project)
│   ├── pages/                   # Routable views (home, login, admin, reports)
│   ├── app.php                  # Master layout wrapper with output buffering
│   └── router.php               # URL route handler & role guards
├── composer.json                # PHP dependencies & autoloader config
├── index.php                    # Development server entry point & static router
└── README.md                    # Project documentation & run guide
```

---

## 3. Database Schema Design

### 3.1 `users` Table
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| `name` | VARCHAR(100) | NOT NULL | User full name |
| `email` | VARCHAR(150) | NOT NULL, UNIQUE | User login email |
| `password` | VARCHAR(255) | NOT NULL | BCRYPT password hash |
| `role` | ENUM('admin', 'user') | DEFAULT 'user' | Access level |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Registration date |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update date |

### 3.2 `projects` Table
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| `name` | VARCHAR(120) | NOT NULL | Project or client name |
| `color_hex` | VARCHAR(7) | DEFAULT '#13162C' | Identification badge color |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Created date |

### 3.3 `time_logs` Table
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| `user_id` | BIGINT | FOREIGN KEY -> users(id) | Associated user |
| `project_id` | BIGINT | NULLABLE, FOREIGN KEY -> projects(id) | Associated project |
| `task_description` | VARCHAR(255) | NOT NULL | Summary of work done |
| `start_time` | DATETIME | NOT NULL | Start timestamp |
| `end_time` | DATETIME | NULLABLE | End timestamp |
| `duration_seconds`| INT | DEFAULT 0 | Total tracked duration in seconds |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Created date |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Updated date |

---

## 4. Step-by-Step Implementation Roadmap

### Phase 1: Database Setup & Eloquent Initialization
- [ ] Create `src/config/database.php` configuring Eloquent Capsule.
- [ ] Create initial migration script / SQL schema file for `users`, `projects`, and `time_logs`.
- [ ] Seed default admin account (`admin@timesheet.local`) and standard user account.
- [ ] Create Eloquent models: `User.php`, `TimeLog.php`, and `Project.php`.

### Phase 2: Authentication & Route Protection
- [ ] Build `/login` and `/logout` pages with session authentication.
- [ ] Update `src/router.php` with an auth guard (redirect unauthenticated users to `/login`).
- [ ] Update Header navigation to show logged-in user profile, role badge, and functional logout link.

### Phase 3: Movable Timer & Auto-Save
- [ ] Implement draggable physics on the `.timer-modal` header in `assets/js/global.js`.
- [ ] Implement timer persistence (`localStorage` start time recovery so timer survives reloads).
- [ ] On timer stop: automatically prompt or submit log entry to database via an API endpoint (`/api/logs/save`).

### Phase 4: Logs Management (User Dashboard)
- [ ] Display list of user's personal logs grouped by date (Today, Yesterday, Older).
- [ ] Implement **Add Manual Log** modal.
- [ ] Implement **Edit Log** and **Delete Log** endpoints and UI actions.

### Phase 5: Admin Dashboard
- [ ] Create `/admin` view accessible only by users with `role = 'admin'`.
- [ ] Render team KPI cards: Total Hours Tracked, Active Members, Active Projects.
- [ ] Team-wide log inspector with user and date filters.

### Phase 6: Reporting & Export Engine
- [ ] Create `/reports` page with date selector (Daily, Weekly, Monthly, Custom Range).
- [ ] Render summary totals: hours per day and hours per project.
- [ ] Add **Export to CSV** endpoint (sets `Content-Type: text/csv` for native download).
- [ ] Add **Export to PDF** button utilizing client-side `pdfmake`.
