# Department TaskMaster Suite

A classic, data-focused portal for coordinating departmental work. The interface uses HTML, CSS, and vanilla JavaScript, while PHP powers the backend against a MySQL database (fully compatible with MAMP on macOS).

## Key features
- Secure authentication with role selection for Presidents, Managers, and Employees.
- Dashboard with concise metrics, upcoming deadlines, and reminder history laid out in a classic two-column view.
- Dedicated task creation workspace plus a streamlined task roster with inline status updates and reminder triggers.
- Calendar view that visualises deadlines across departments with colour-coded priorities and monthly quick stats.
- Department directory with optional contacts and workload summaries, seeded with the fourteen provided departments.
- Reminder management page to create follow-up notices and review the log in a separate hub.

## Requirements
- PHP 8 or newer.
- MySQL server (the default MAMP database works great).
- Web server such as Apache bundled with MAMP.

## Getting started with MAMP
1. Copy the project into your MAMP web directory (typically `/Applications/MAMP/htdocs`).
2. Open phpMyAdmin through `http://localhost/phpMyAdmin`.
3. Import the database schema located at `database/schema.sql`. It creates tables, seeds departments, tasks, calendar events, notifications, and three demo user accounts.
4. Update the connection values in `db.php` if your credentials differ. The defaults are:
   - Username: `root`
   - Password: `root`
   - Database: `task_manager`
5. Start MAMP servers and visit `http://localhost/tatarwar/login.php` (or the folder name you chose).
6. Sign in with one of the seeded accounts or create a new one from the registration page.
   - President: `president@taskmaster.test` / `president123`
   - Manager: `manager@taskmaster.test` / `manager123`
   - Employee: `employee@taskmaster.test` / `employee123`

## Database structure
- `users`: authentication table with role, hashed password, and audit timestamps.
- `departments`: stores department names and optional email contacts.
- `tasks`: tracks task details, responsible department, priority, status, and due date.
- `notifications`: records reminder messages tied to tasks.
- `calendar_events`: stores calendar items that power the monthly schedule view.

## Project structure
```
.
├── assets
│   ├── css
│   │   └── style.css
│   └── js
│       └── app.js
├── database
│   └── schema.sql
├── includes
│   ├── footer.php
│   ├── functions.php
│   └── header.php
├── calendar.php
├── db.php
├── departments.php
├── index.php
├── login.php
├── logout.php
├── reminders.php
├── register.php
├── send_reminder.php
├── task_create.php
├── tasks.php
└── README.md
```

## Classic design accents
- **Gradient shell:** requested linear gradient background paired with frosted surfaces for a timeless control room aesthetic.
- **Summary cards & tables:** reusable cards and elevated tables keep numbers, statuses, and priorities easy to scan.
- **Dedicated hubs:** separate screens for reminders and task creation make navigation straightforward while keeping the layout tidy.
- **Responsive layout:** collapses gracefully on smaller screens while preserving the primary navigation and KPI highlights.

## Customisation tips
- Adjust typography, spacing, or palette inside `assets/css/style.css` to match your brand.
- Extend reminder delivery in `send_reminder.php` to trigger emails, SMS, or chat integrations.
- Capture additional metadata by expanding the forms in `task_create.php` or `departments.php` and updating the schema accordingly.
