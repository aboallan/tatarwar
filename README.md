# Municipal Project Command Center

This PHP application delivers a professional, widescreen command environment for municipal tasking, project portfolio control, and risk monitoring. It now ships as a multi-page experience so each workflow lives on its own screen—perfect for MacBook presentations or MAMP-based demos.

## Application map

| Page | Purpose |
| --- | --- |
| `login.php` / `register.php` | Authentication portal for coordinators and department leads. |
| `dashboard.php` | Executive overview with portfolio metrics, due-date alerts, and recent progress notes. |
| `projects.php` | Register and review programmes with sponsors, timelines, and completion tracking. |
| `tasks.php` | Create, filter, update, and retire assignments per department and project. |
| `departments.php` | Monitor workload distribution, risk exposure, and upcoming deadlines for every directorate. |
| `updates.php` | Log structured progress reports with risk/blocker commentary and review the latest activity feed. |

Each screen reads and writes to the same SQLite database so data stays in sync as you move between services.

## Key capabilities

- **Authentication:** Built-in registration, login, and logout flows so only authorized coordinators can manage assignments.
- **Portfolio management:** Register strategic projects/programs with sponsors, timelines, and auto-calculated completion metrics.
- **Task engine:** Assign work to departments, tag it to projects, track risk, impact, effort, status, priority, and due dates with rich filtering.
- **Progress logging:** Capture structured progress updates, risks, and blockers while automatically nudging task status as work advances.
- **Executive insights:** Portfolio snapshot, risk distribution, departmental workload heatmap, and deadline alerts surface the most important signals at a glance.

## Running locally with MAMP (macOS)

1. Clone or copy this repository into your MAMP `htdocs` folder (for example `~/Applications/MAMP/htdocs/tatarwar`).
2. Launch MAMP and start the Apache and MySQL servers (SQLite is used, so MySQL is optional).
3. Visit [http://localhost:8888/tatarwar](http://localhost:8888/tatarwar) in your browser. The login screen will appear immediately.
4. Register your first account, sign in, and navigate between the dedicated pages using the top navigation bar.

> The bundled SQLite database file (`database/app.sqlite`) is created automatically the first time you load any page.

### Alternative: PHP built-in web server

If you prefer the PHP development server:

```bash
php -S localhost:8000
```

Then browse to [http://localhost:8000](http://localhost:8000) (the router will send you to the login page or dashboard depending on your session).

## Database layout

The system uses an embedded **SQLite** database. No external server is required—the application creates and migrates the schema for you.

- **Database file:** `database/app.sqlite`
- **Schema source:** `database/schema.sql`
- **Core tables:** `departments`, `projects`, `tasks`, `task_updates`, `users`

When you run the dashboard for the first time the file is created automatically inside `database/`. Delete the file at any time to reset the demo data and the app will rebuild it on the next load.

## Inspecting the database on macOS with VS Code

If you are working on a MacBook, the easiest way to browse or edit the bundled SQLite database is directly from Visual Studio Code:

1. Open the project folder in VS Code.
2. Install the extension **"SQLite" by alexcvzz** (search for "SQLite" in the Extensions sidebar) and reload VS Code if prompted.
3. After installation, open the command palette (`⇧⌘P`) and run **"SQLite: Open Database"**.
4. Choose the file `database/app.sqlite`. The extension will add the file to the **SQLite Explorer** view in the Activity Bar (usually a cylinder icon).
5. Expand the database entry from the SQLite Explorer to browse tables, view rows, or run custom SQL queries.

> 💡 Double-clicking `app.sqlite` in the normal VS Code file explorer will only show a "binary file" warning because SQLite databases are not plain text. Always open the file through the SQLite extension (or the CLI below) to inspect its contents.

### Using the built-in sqlite3 CLI (optional)

macOS ships with the `sqlite3` command-line tool. You can inspect the same file from the terminal:

```bash
cd /path/to/your/project
sqlite3 database/app.sqlite
```

Once inside the prompt you can list the tables with `.tables` or quit with `.exit`. This is handy if you prefer working from the terminal instead of a VS Code extension.
