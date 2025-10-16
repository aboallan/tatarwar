# Department Task Management System

A lightweight web portal for coordinating departmental work. The interface is built with HTML, CSS, and JavaScript, while PHP powers the backend and connects the application to a MySQL database (compatible with MAMP on macOS).

## Key features
- Dashboard with quick metrics, upcoming deadlines, and reminder history.
- Full CRUD workflow for departmental tasks (create, update status, delete).
- Department directory with optional contact email and automatic seeding of the provided fourteen departments.
- Reminder logging endpoint to record when a follow-up notification is sent.
- Figma-inspired interface with gradient hero header, glassmorphism cards, and responsive insight chips for a polished presentation.

## Requirements
- PHP 8 or newer.
- MySQL server (the default MAMP database works well).
- Web server such as Apache bundled with MAMP.

## Getting started with MAMP
1. Copy the project into your MAMP web directory (typically `/Applications/MAMP/htdocs`).
2. Open phpMyAdmin through `http://localhost/phpMyAdmin`.
3. Import the database schema located at `database/schema.sql`. It creates tables, seeds the fourteen departments, and loads a curated sample data set so dashboards and reminders are populated immediately.
4. Update the connection values in `db.php` if your credentials differ. The defaults are:
   - Username: `root`
   - Password: `root`
   - Database: `task_manager`
5. Start MAMP servers and visit `http://localhost/tatarwar/index.php` (or the folder name you chose) to access the portal.

## Database structure
- `departments`: stores department names and optional email contacts.
- `tasks`: tracks task details, responsible department, priority, status, and due date.
- `notifications`: records reminder messages tied to tasks.

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
├── db.php
├── index.php
├── tasks.php
├── departments.php
└── README.md
```

## Customising the platform
- Update the colour palette or typography in `assets/css/style.css`.
- Extend the reminder logic in `send_reminder.php` to trigger actual emails or integrations.
- Modify the `departments.php` form to capture additional metadata as required.

## Design highlights
- **Hero workspace shell:** layered gradient header with live status chip and quick access to navigation plus a new task shortcut.
- **Insight overview cards:** reusable components show task health, overdue counts, and department activity in a glanceable grid.
- **Elevated data tables:** floating rows, pill badges, and accent chips make task priorities and statuses readable across devices.
