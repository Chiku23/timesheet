# TimeSheet Architecture & Implementation Plan

This document outlines the detailed roadmap, technical architecture, database schema, and implemented specifications for the **TimeSheet** web application.

---

## 1. High-Level Requirements & Features

1. **Authentication & Authorization**:
   - Secure login & logout with password hashing via PHP's `password_hash()` and `password_verify()`.
   - Role-based access control: **Admin** and **User** with route guards in `router.php`.
2. **Server-Authoritative Interactive Timer**:
   - Draggable, non-blocking floating window with dedicated grabbable header bar (`cursor: grab` / `cursor: grabbing`).
   - Digital monospace time display (`00:00:00`) with real-time second updates.
   - **Pause / Resume break handling**: Pause work during breaks without generating fragmented log records.
   - **Server-Authoritative Tracking**: Timestamps and segment accumulations calculated strictly server-side in `timer_sessions` table, preventing client console tampering.
   - **Stop & Save**: Consolidates the complete session duration into a single verified time log entry.
   - **Source Differentiation**: Automatic assignment of `entry_type = 'timer'` vs `entry_type = 'manual'`.
3. **Timesheet Views & Logs Management**:
   - **Detailed Log View**: Chronological logs grouped by date with search, project filter, and source filter (`All`, `Timer`, `Manual`).
   - **Weekly Timesheet Matrix**: Weekly grid displaying projects on rows and days (Mon–Sun) on columns with daily sums and total weekly hours.
   - Full CRUD: Add, Edit, and Delete manual logs with real-time calculated duration previews.
4. **Dashboards**:
   - **User Dashboard**: Personal logs, weekly target goal progress bar (40h target), KPI metrics formatted with exact durations (`15m`, `1h 30m`, never `0H`).
   - **Admin Console**: Team oversight, member activity breakdown, company project manager (+ New Project modal), and team audit logs.
5. **Reporting & Analytics**:
   - Date range presets: This Week, This Month, Last Month, Custom Range.
   - Source filter (`All`, `Timer`, `Manual`).
   - Visual daily tracked time bar chart and project distribution progress tracks.
   - Comprehensive Period Audit Table.
6. **Exporting**:
   - Export filtered audit data to **CSV** format.
   - Export professional reports to **PDF** leveraging `pdfmake`.
7. **Design Standards**:
   - **Flat, modern, minimal aesthetic**: Strict prohibition of gradients (`linear-gradient`, `radial-gradient`) in favor of clean solid colors and neutral gray scales.
   - **No Emojis**: Crisp inline SVG vector icons used exclusively across headers, cards, tables, and buttons.
   - **Input Styling**: Cross-browser styling for `date`, `time`, and `datetime-local` inputs with tabular numbers and dark mode picker indicator inversion.

---

## 2. Technical Architecture

### 2.1 Technology Stack
- **Backend**: Native PHP 8.x with Composer PSR-4 Autoloading (`src/`).
- **Database Layer**: `illuminate/database` (Eloquent ORM) with MySQL.
- **Frontend Core**: Vanilla PHP templates, Vanilla JavaScript (ES6+), Vanilla CSS (Custom properties / Variables).
- **Theming**: Light and Dark mode with persistent `localStorage` support (`color-scheme` aligned).
- **Asset Pipeline**: Automatic asset discovery via `loadComponent()` in `index.php`.

### 2.2 Directory Structure
```
TimeSheet/
├── assets/
│   ├── css/
│   │   ├── globalvars.css       # Core design tokens, neutral gray palette, primary colors
│   │   ├── global.css           # Base styles, header, footer, floating timer window, badges
│   │   └── global-dark.css      # Dark theme overrides (color-scheme: dark)
│   └── js/
│       └── global.js            # Server-authoritative timer manager, draggable popup, theme toggle
├── Docs/
│   ├── PLAN.md                  # Project plan and specifications
│   └── ARCHITECTURE_AND_LOGIC.md# Full technical implementation and coding logic guide
├── src/
│   ├── components/
│   │   ├── header/              # Navigation bar, user profile, theme toggle (SVGs)
│   │   └── footer/              # Floating launcher and timer popup modal
│   ├── config/
│   │   ├── database.php         # Eloquent Capsule setup and schema auto-migration
│   │   ├── schema.sql           # Base MySQL database schema DDL
│   │   └── seed.php             # Initial database seed script
│   ├── models/
│   │   ├── User.php             # User Eloquent model with auth helpers
│   │   ├── Project.php          # Project Eloquent model
│   │   ├── TimeLog.php          # TimeLog Eloquent model with getFormattedDurationAttribute()
│   │   ├── TimerSession.php     # Server-authoritative live session model
│   │   └── Setting.php          # Workspace configuration key-value model
│   ├── pages/
│   │   ├── home/                # User dashboard & weekly timesheet matrix
│   │   ├── settings/            # Admin workspace settings & project manager
│   │   ├── reports/             # Analytics, visual charts, audit table, CSV/PDF export
│   │   ├── admin/               # Team oversight, project manager, team audit logs
│   │   ├── login/               # Clean authentication screen with demo account shortcuts
│   │   └── 404/                 # Fallback error page
│   ├── api.php                  # Dedicated JSON REST API endpoints
│   ├── app.php                  # Output buffering layout wrapper
│   └── router.php               # Page routing and authentication guards
├── composer.json                # Composer dependencies and autoloading definitions
├── index.php                    # Application entry point
└── README.md                    # Setup and usage instructions
```

---

## 3. Database Schema

### 3.1 `users` Table
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique user ID |
| `name` | VARCHAR(100) | NOT NULL | User full name |
| `email` | VARCHAR(150) | NOT NULL, UNIQUE | User login email |
| `password` | VARCHAR(255) | NOT NULL | Password hash |
| `role` | ENUM('admin', 'user') | DEFAULT 'user' | Access control level |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Registration timestamp |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last modification timestamp |

### 3.2 `projects` Table
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique project ID |
| `name` | VARCHAR(120) | NOT NULL | Project or client title |
| `color_hex` | VARCHAR(7) | DEFAULT '#4f46e5' | Color accent hex for badges and charts |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |

### 3.3 `time_logs` Table
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique log ID |
| `user_id` | BIGINT UNSIGNED | FOREIGN KEY -> users(id) ON DELETE CASCADE | Associated user ID |
| `project_id` | BIGINT UNSIGNED | NULLABLE, FOREIGN KEY -> projects(id) ON DELETE SET NULL | Associated project ID |
| `task_description` | VARCHAR(255) | NOT NULL | Description of work performed |
| `start_time` | DATETIME | NOT NULL | Start timestamp of work session |
| `end_time` | DATETIME | NULLABLE | End timestamp of work session |
| `duration_seconds` | INT UNSIGNED | DEFAULT 0 | Tracked duration in total seconds |
| `entry_type` | ENUM('timer', 'manual') | DEFAULT 'manual' | Source differentiation (server-verified timer vs manual entry) |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Modification timestamp |

---

## 4. Implementation Status

- [x] **Database Schema & Eloquent Initialization**: Auto-migrates `entry_type`, `timer_sessions`, and `settings` safely.
- [x] **Server-Authoritative Interactive Timer**: Server-calculated durations via `timer_sessions` prevent client console tampering; supports pause break continuity and non-stretching draggable modal window.
- [x] **Admin Workspace Settings & Project Management**: Dynamic weekly target configuration (`/settings`) and full project CRUD with color badges.
- [x] **Source Differentiation**: Clear badges (`Timer` vs `Manual`) in tables and filters.
- [x] **Duration Formatting**: Smart formatting algorithm (`45s`, `15m`, `1h 30m`) preventing small tasks from displaying as `0H`.
- [x] **Weekly Matrix Timesheet**: Interactive week grid showing project rows and day columns with daily/weekly totals.
- [x] **Modern Flat Aesthetic**: Flat solid colors and crisp SVGs without gradients or emojis.
