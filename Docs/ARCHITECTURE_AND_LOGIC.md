# TimeSheet: Architecture & Implementation Logic Guide

A comprehensive technical reference detailing the system architecture, design patterns, coding logic, and database design implemented in the **TimeSheet** web application.

---

## 1. Executive Summary & Architecture

The **TimeSheet** platform is built on modern PHP 8.x principles, utilizing an elegant component-driven architecture without the overhead of heavy frameworks like Laravel or Symfony, while still taking advantage of industry-standard ORM capabilities via `illuminate/database` (Eloquent).

```
                      ┌──────────────────────────────────────┐
                      │              index.php               │
                      │  (Session, Autoload, Database Init)  │
                      └──────────────────┬───────────────────┘
                                         │
                 ┌───────────────────────┴───────────────────────┐
                 ▼                                               ▼
       ┌────────────────────┐                          ┌────────────────────┐
       │     api.php        │                          │      app.php       │
       │ (REST JSON Router) │                          │  (Layout Wrapper)  │
       └────────────────────┘                          └─────────┬──────────┘
                                                                 │
                                                       ┌─────────┴──────────┐
                                                       ▼                    ▼
                                              ┌─────────────────┐  ┌─────────────────┐
                                              │   router.php    │  │ loadComponent() │
                                              │  (Page Guards)  │  │ (Header/Footer) │
                                              └────────┬────────┘  └─────────────────┘
                                                       │
                                        ┌──────────────┼──────────────┐
                                        ▼              ▼              ▼
                                  Dashboard         Reports         Admin
                                 (/home.php)     (/reports.php)  (/admin.php)
```

### 1.1 Core Components
1. **Entry Point (`index.php`)**:
   - Boots native PHP sessions (`session_start()`).
   - Loads Composer autoloader (`vendor/autoload.php`).
   - Initializes Eloquent Capsule (`src/config/database.php`).
   - Registers the dynamic component loader function `loadComponent($name, $data, $baseDir)`.
   - Dispatches API requests directly from `src/api.php` before any HTML is rendered.
2. **Layout Pipeline (`src/app.php`)**:
   - Implements **two-phase output buffering** (`ob_start()` -> `ob_get_clean()`).
   - First, the requested page is executed inside the buffer via `router.php`. This allows the page and its child components to register necessary CSS and JS files into the global arrays (`$cssFiles`, `$jsFiles`).
   - Next, `loadComponent('header')` outputs the `<head>` tag with all discovered CSS stylesheets.
   - The captured `$mainContent` is rendered.
   - Finally, `loadComponent('footer')` renders before `</body>` with all discovered JS scripts.
3. **Routing & Guards (`src/router.php`)**:
   - Strict URI matching with authentication guards (`auth => true`) and role-based guards (`adminOnly => true`).
   - Unauthenticated visitors are routed to `/login`. Non-admin users attempting to access `/admin` receive a flash error and are redirected to `/`.

---

## 2. Server-Authoritative Timer Mechanics & State Machine

In enterprise timesheet architecture, time tracking is fundamentally different from a simple client stopwatch:
1. **Work is non-linear**: A user begins working, pauses for a break, a coffee, or a meeting, and then resumes tracking the *same* continuous task.
2. **Zero log fragmentation**: Pausing must **never** create a separate database entry. A session must commit exactly **one** consolidated time log upon completion.
3. **Server-authoritative integrity**: Elapsed seconds and segment timestamps are maintained strictly on the server (`timer_sessions` database table). The client browser cannot spoof or inflate duration in the JavaScript console.
4. **Source identification**: The system definitively marks whether time was tracked via live server timer (`entry_type = 'timer'`) or keyed in manually (`entry_type = 'manual'`).

### 2.1 Finite State Machine

```
              ┌─────────┐
              │  IDLE   │
              └────┬────┘
                   │
                   │ [Start Timer]
                   ▼
              ┌─────────┐
   ┌─────────►│ RUNNING │◄────────┐
   │          └────┬────┘         │
   │               │              │
[Resume]       [Pause]        [Resume]
   │               │              │
   │               ▼              │
   └──────────┌─────────┐─────────┘
              │ PAUSED  │
              └────┬────┘
                   │
    [Stop & Save]  │  [Stop & Save]
         ┌─────────┴─────────┐
         ▼                   ▼
    ┌─────────────────────────────┐
    │   Commit Consolidated Log   │
    │     (entry_type = 'timer')  │
    └─────────────────────────────┘
```

### 2.2 Time Accumulation Algorithm

The timer logic in [global.js](file:///c:/Projects/timesheet/assets/js/global.js) prevents clock drift and preserves accuracy even if the browser tab goes to sleep or the user navigates between pages:

```javascript
// Current live seconds formula:
function getCurrentTotalSeconds() {
    if (timerStatus === 'running' && runSegmentStartTime) {
        // Calculate seconds elapsed in the CURRENT active segment
        const currentSegment = Math.max(0, Math.floor((Date.now() - runSegmentStartTime) / 1000));
        // Add to previously accumulated seconds from prior segments
        return accumulatedSeconds + currentSegment;
    }
    return accumulatedSeconds;
}
```

#### Break Mechanics Step-by-Step:
1. **Starting**:
   - `timerStatus = 'running'`
   - `accumulatedSeconds = 0`
   - `runSegmentStartTime = Date.now()`
   - `sessionStartIso = new Date().toISOString()`
2. **Pausing (Taking a Break)**:
   - When the user clicks **Pause**, the elapsed time in the current segment is calculated:
     $$\Delta t = \lfloor (\text{Date.now()} - \text{runSegmentStartTime}) / 1000 \rfloor$$
   - This value is added to `accumulatedSeconds`:
     $$\text{accumulatedSeconds} \leftarrow \text{accumulatedSeconds} + \Delta t$$
   - Dispatches `POST /api/timer/pause`, allowing the server to calculate and commit the segment elapsed seconds to `timer_sessions`.
   - The UI interval timer is cleared (`clearInterval`), freezing the displayed digits.
3. **Resuming (Returning from Break)**:
   - When the user clicks **Resume**, dispatches `POST /api/timer/resume`.
   - Server updates `segment_started_at` to the current server timestamp.
   - The UI 1-second interval timer resumes, calculating from the synchronized `accumulatedSeconds`.
4. **Stopping & Saving**:
   - Dispatches `POST /api/timer/stop` with task metadata:
     ```json
     {
       "task_description": "Sprint Planning",
       "project_id": 2
     }
     ```
   - **Server Calculation**: The server retrieves the active `TimerSession`, calculates verified duration strictly from server timestamps and `accumulated_seconds`, commits the verified log (`entry_type = 'timer'`), and deletes the active session. The client cannot spoof or bypass duration.
   - On response success, resets state to `idle`, and triggers a custom DOM event (`timesheet:log-updated`) which immediately reloads active tables.

### 2.3 Persistence Across Browser Reloads

All state variables are synchronized to `localStorage` under namespaced keys:
- `ts_timer_status`: `'idle' | 'running' | 'paused'`
- `ts_timer_accumulated`: integer seconds
- `ts_timer_segment_start`: epoch timestamp (ms) of the active segment
- `ts_timer_session_start`: ISO timestamp of the initial session start
- `ts_timer_task`: task description text
- `ts_timer_project`: project dropdown value

On page load, `recoverTimer()` checks these keys. If `running`, it calculates the elapsed time that occurred while the page was unloaded and resumes ticking smoothly without loss of tracked time.

---

## 3. Source Differentiation (`timer` vs `manual`)

In [schema.sql](file:///c:/Projects/timesheet/src/config/schema.sql) and the `time_logs` table, an `entry_type` column defines the record provenance:

```sql
`entry_type` ENUM('timer', 'manual') NOT NULL DEFAULT 'manual'
```

### 3.1 Database Auto-Migration
In [database.php](file:///c:/Projects/timesheet/src/config/database.php), an auto-migration check runs on boot to guarantee backwards compatibility:

```php
try {
    if ($capsule->schema()->hasTable('time_logs') && !$capsule->schema()->hasColumn('time_logs', 'entry_type')) {
        $capsule->schema()->table('time_logs', function ($table) {
            $table->string('entry_type', 20)->default('manual')->after('duration_seconds');
        });
    }
} catch (\Throwable $e) {
    // Graceful fallback
}
```

### 3.2 Visual Badge Representation
In the UI, time entries render with distinct badges:
- **Timer Entries**: Indigo accent badge with a stopwatch vector icon:
  `<span class="entry-badge entry-timer"><svg ...></svg>Timer</span>`
- **Manual Entries**: Neutral slate badge with an edit pen vector icon:
  `<span class="entry-badge entry-manual"><svg ...></svg>Manual</span>`

Both the Dashboard and Admin views provide dedicated dropdown filters allowing users and managers to inspect `All Sources`, `Timer Tracked Only`, or `Manual Entries Only`.

---

## 4. Smart Duration Formatting Algorithm (Fixing the "0H" Bug)

### The Problem
Previously, raw durations were converted by dividing by 3600 and rounding or using integer division without handling small increments. A task that took 15 minutes ($900\text{s}$) or 45 seconds resulted in:
$$\text{intdiv}(900, 3600) = 0 \longrightarrow \text{"0h" or "0.0h"}$$
This caused users to perceive short, high-frequency tasks as unregistered or lost.

### The Solution
A unified duration formatting algorithm was implemented across both backend PHP and frontend JavaScript:

```
                          Total Seconds
                               │
                       ┌───────┴───────┐
                   ≤ 0 │               │ > 0
                       ▼               │
                     "0m"      ┌───────┴───────┐
                           < 60│               │ ≥ 60
                               ▼               │
                            "XXs"              ▼
                                        Hours = intdiv(total, 3600)
                                        Mins  = intdiv(total % 3600, 60)
                                               │
                                 ┌─────────────┼─────────────┐
                                 │             │             │
                             Hours > 0     Hours > 0     Hours == 0
                             Mins > 0      Mins == 0         │
                                 │             │             ▼
                                 ▼             ▼           "Ym"
                             "Xh Ym"         "Xh"       (e.g. 15m)
```

#### Backend Implementation ([TimeLog.php](file:///c:/Projects/timesheet/src/models/TimeLog.php)):
```php
public function getFormattedDurationAttribute(): string
{
    $total = (int) $this->duration_seconds;
    if ($total <= 0) return '0m';
    if ($total < 60) return "{$total}s";

    $hours = intdiv($total, 3600);
    $minutes = intdiv($total % 3600, 60);

    if ($hours > 0 && $minutes > 0) {
        return "{$hours}h {$minutes}m";
    } elseif ($hours > 0) {
        return "{$hours}h";
    } else {
        return "{$minutes}m";
    }
}
```

---

## 5. Timesheet Views & Page Specialization

To eliminate repetitive layouts across the application, each page has been architected with distinct, specialized capabilities:

### 5.1 Dashboard (`/` -> `home.php`)
- **Weekly Target Bar**: Displays current progress against a standard 40-hour work week:
  $$\text{Progress} = \min\left(100, \left\lfloor \frac{\text{Week Seconds}}{144,000} \times 100 \right\rfloor\right)$$
- **View Switcher Tabs**:
  1. **Detailed Log View**: Chronological table grouped by day (`Today`, `Yesterday`, etc.), with live search, project filter, source badge (`Timer` vs `Manual`), and Edit/Delete controls.
  2. **Weekly Matrix Timesheet**: Projects arranged on rows, Monday through Sunday columns, with daily totals along the footer and row totals on the right. Includes a week navigation widget (`< Prev`, `Next >`, `Current Week`).
- **Manual Log Modal**:
  - Date input (`input[type="date"]`)
  - Start & End time inputs (`input[type="time"]`)
  - **Live Duration Preview Card**: Real-time calculation showing the exact duration (e.g. `Calculated: 45m`) before the user clicks save. Validates that the end time is strictly greater than the start time.

### 5.2 Reports (`/reports` -> `reports.php`)
- **Analytics KPIs**: Total Tracked Time, Daily Average, Active Days, and Source Distribution (% Timer vs % Manual).
- **Interactive Visualizations**:
  - Daily bar chart with formatted duration tooltips (`45m`, `2h 15m`).
  - Project distribution progress bars with proportional percentage fills.
- **Period Audit Log**: Dedicated analytical table showing Date, Project, Task, Source, and Duration.
- **Data Export**:
  - **CSV Export**: Direct HTTP stream download (`Content-Type: text/csv`).
  - **PDF Export**: Client-side document generation using `pdfmake`.

### 5.3 Administration Console (`/admin` -> `admin.php`)
- Accessible strictly to users with `role = 'admin'`.
- **Team Members Management**: Renders active team members, system roles, total logged entries, and total time.
- **Company Project Manager**: Allows administrators to view all active client projects, their accumulated hours, and create new projects via an inline modal (`POST /api/projects/add`).
- **Organization Audit Trail**: Displays team-wide time logs with **actual member names** (resolving the previous numeric ID display), project badges, source indicators, and admin deletion privileges.

---

## 6. Design System & CSS Architecture

### 6.1 Flat, Modern Aesthetic (Zero Gradients)
In adherence to modern UI guidelines, all decorative gradients have been removed in favor of a clean, high-contrast, flat color architecture based on a neutral gray scale:

```css
:root {
    --primary: #4f46e5;         /* Solid Indigo */
    --primary-hover: #4338ca;
    --success: #10b981;         /* Solid Emerald */
    --warning: #f59e0b;         /* Solid Amber */
    --danger: #e11d48;          /* Solid Rose */

    --surface: #ffffff;
    --surface-hover: #f9fafb;
    --bg: #f3f4f6;
    --text-main: #111827;
    --text-muted: #6b7280;
    --border-light: #e5e7eb;
}
```

### 6.2 Dark Theme (`.theme-dark`)
Dark mode uses deep slate surfaces (`#111318` background, `#1a1c24` cards) with explicit browser color scheme declaration:
```css
.theme-dark {
    color-scheme: dark;
}
```
This ensures native form controls (such as the calendar and clock pickers in date and time inputs) render with dark backgrounds instead of unstyled white browser defaults.

### 6.3 Input Styling & Picker Icons
Browsers often render native `input[type="date"]` and `input[type="time"]` inconsistently. The global styling standardizes them:
```css
input[type="date"],
input[type="time"],
input[type="datetime-local"] {
    font-family: var(--font-family);
    font-variant-numeric: tabular-nums;
    color-scheme: inherit;
    font-size: 0.85rem;
    padding: 8px 10px;
    border: 1px solid var(--border-light);
    border-radius: 6px;
    background-color: var(--bg);
    color: var(--text-main);
    box-sizing: border-box;
}

.theme-dark input[type="date"]::-webkit-calendar-picker-indicator,
.theme-dark input[type="time"]::-webkit-calendar-picker-indicator {
    filter: invert(0.85);
}
```

### 6.4 Clean SVG Vector Iconography (Zero Emojis)
All emojis (`⏱`, `👋`, `📅`, `📊`, `🔥`, `📝`, `⚠️`, `📥`, `📄`, `🏷️`, `👥`, `📋`, `▶`, `■`) have been eliminated and replaced with crisp, inline SVG vector graphics:
- Stopwatch / Timer: `<svg viewBox="0 0 24 24"><circle cx="12" cy="13" r="8"></circle><path d="M12 9v4l2.5 2.5"></path><path d="M10 2h4"></path></svg>`
- Calendar: `<svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line></svg>`
- Controls: SVG Play (`polygon points="5 3 19 12 5 21 5 3"`), Pause (`rect x="6" ... rect x="14"`), and Stop (`rect x="4" ...`).

---

## 7. REST API Endpoint Reference

| Method | Endpoint | Access | Description |
|---|---|---|---|
| `POST` | `/api/login` | Public | Authenticates credentials and starts PHP session |
| `GET` | `/logout` | Authenticated | Destroys session and redirects to `/login` |
| `GET` | `/api/user/profile` | Authenticated | Retrieves current authenticated user's profile details |
| `POST` | `/api/user/profile` | Authenticated | Updates display name, email (with uniqueness check), and password |
| `GET` | `/api/logs` | Authenticated | Retrieves logs. By default, returns personal logs for the authenticated user (including admins). Admins can view team logs using `?all=1` or `?user_id=X`. Supports `?entry_type=timer\|manual`, `?project_id=`, `?date_from=`, `?date_to=` |
| `POST` | `/api/logs/add` | Authenticated | Saves manual entry with `entry_type = 'manual'` |
| `POST` | `/api/logs/update`| Authenticated | Updates log details (Admin or log owner); marks as `'manual'` |
| `POST` | `/api/logs/delete`| Authenticated | Deletes log entry (Admin or log owner) |
| `GET` | `/api/timer/active`| Authenticated | Fetches active live server timer session for user |
| `POST` | `/api/timer/start` | Authenticated | Starts or resets active server timer session with server timestamp |
| `POST` | `/api/timer/pause` | Authenticated | Pauses timer and calculates elapsed seconds strictly on server |
| `POST` | `/api/timer/resume`| Authenticated | Resumes timer with new segment start timestamp |
| `POST` | `/api/timer/stop`  | Authenticated | Finalizes session, computes verified duration, commits `entry_type = 'timer'`, deletes session |
| `POST` | `/api/timer/discard`| Authenticated | Discards active timer session without saving |
| `GET` | `/api/projects` | Authenticated | Lists all projects with cumulative tracked seconds |
| `POST` | `/api/projects/add`| Admin Only | Creates a new project with name and color hex |
| `POST` | `/api/projects/update`| Admin Only | Updates project name and color hex |
| `POST` | `/api/projects/delete`| Admin Only | Deletes project |
| `GET` | `/api/settings` | Authenticated | Retrieves workspace settings (weekly_goal_hours, company_name) |
| `POST` | `/api/settings/update`| Admin Only | Updates workspace settings (weekly_goal_hours, company_name) |
| `GET` | `/api/reports` | Authenticated | Aggregates daily & project metrics with source splits |
| `GET` | `/api/admin/stats`| Admin Only | Company-wide KPIs, active users, and member breakdown |
| `GET` | `/api/export/csv` | Authenticated | Streams downloadable CSV timesheet export |

---

## 8. Timezone Architecture & Tamper-Proof Mechanics

### 8.1 UTC Backend Standardization & Dynamic Timezone Switcher
To guarantee consistency across distributed teams, database storage, and external reporting:
1. **Server Core (`index.php`)**:
   - Explicitly sets `date_default_timezone_set('UTC')`.
   - All database records (`start_time`, `end_time`, `created_at`, `updated_at`, `session_start`) are stored in strict UTC.
2. **API Serialization (`src/api.php`)**:
   - All datetime values emitted to the client are serialized in ISO 8601 format with explicit `Z` UTC indicators (e.g. `2026-10-02T19:14:01Z`).
   - This eliminates browser parsing ambiguity across different user locales.
3. **UI Dynamic Timezone Switcher**:
   - A toggle pill (`Local` / `UTC`) is positioned in the application header.
   - **Default Behavior**: Defaults to **Local** browser timezone, using `Intl.DateTimeFormat().resolvedOptions().timeZone` or the user's localized time.
   - **Instant Re-rendering**: When the user switches to `UTC` or `Local`, preference is stored in `localStorage` and a global custom event `timesheet:timezone-changed` is dispatched. The dashboard, reports audit tables, and calendar matrices re-format their timestamps and date groupings immediately without requiring a full page refresh.

### 8.2 Client Breakpoint & Script-Pause Tamper Protection
When tracking time in client-heavy applications, pausing script execution in browser developer tools (or network latency delays) during a Stop or Pause action could introduce race conditions or artificially inflate recorded minutes:

1. **Synchronous Click-Time Capture**:
   - In [global.js](file:///c:/Projects/timesheet/assets/js/global.js), `pauseTimer()` and `stopAndSaveTimer()` synchronously capture:
     ```javascript
     const clientRecordedDuration = getCurrentTotalSeconds();
     ```
   - This reading is taken on the first tick of the event loop, before any asynchronous network promises or developer tool breakpoints can execute.
2. **Server-Authoritative Duration Clamping**:
   - The server computes `$serverMaxSeconds` based strictly on its own clock (`$session->current_seconds` plus elapsed seconds since `$session->segment_start`).
   - If a user deliberately pauses the script in DevTools for several minutes before resuming the request, `$clientDuration` reflects the moment the button was clicked.
   - If `$clientDuration <= $serverMaxSeconds + 3`, the server uses `$clientDuration` as the exact stop duration, avoiding phantom minutes.
   - If a malicious client attempts to spoof a higher duration in the browser console, the server clamps duration to `$serverMaxSeconds`.
3. **Consistent End-Time Derivation**:
   - The session's `end_time` is calculated as `$startDateTime + $finalSeconds` rather than `date('Y-m-d H:i:s')` at request arrival time.
   - This ensures that deliberate breakpoint pauses between clicking stop and network dispatch never push the recorded end timestamp into the future.

---

## 9. Payroll Protection, Soft Deletes & User Profile

### 9.1 Wage & Payroll Integrity via Soft Deletes
In enterprise timesheet solutions, deleting logged work hours directly impacts employee compensation and labor law compliance:
1. **Permanent Soft Deletes (`deleted_at`)**:
   - The `time_logs` table incorporates Eloquent's `SoftDeletes` trait.
   - Calling delete does not wipe records from the database; it assigns a `deleted_at` timestamp. Historical audit trails and dispute resolution records are preserved indefinitely.
2. **Author-Only Deletion Safeguard**:
   - In `POST /api/logs/delete`, only the user who created and worked on the entry (`$log->user_id === $_SESSION['user_id']`) is permitted to remove it.
   - Administrators cannot delete an employee's work records arbitrarily, eliminating wage tampering and unauthorized timesheet alterations.

### 9.2 Global Custom Modal System (Zero Native JS Confirm)
Browser-native confirmation popups (`window.confirm()`) are non-customizable, blocking, and visually inconsistent across platforms.
- A centralized custom dialog (`#globalConfirmModal`) is wired via `window.showConfirmDialog()` in [global.js](file:///c:/Projects/timesheet/assets/js/global.js).
- Returns a Promise resolving to `true` or `false`, providing uniform animations, SVG warning icons, and full dark/light theme support.

### 9.3 Self-Service Account & Profile Settings
All authenticated users can manage their credentials from any view:
- **Profile Modal (`#accountModal`)**: Accessible via the user avatar pill in the top navigation bar.
- **Name & Email Editing**: With uniqueness validation to prevent collisions.
- **Secure Password Changes**: Requires current password verification and confirmation matching with minimum complexity checks.


