# Task Management Dashboard Setup

This project ships with a file-based **SQLite** database, so you do not need to create
or configure a separate server. The application automatically looks for a database file
named `app.sqlite` inside the `database/` directory at the project root.

When you run the application for the first time, it will create `database/app.sqlite`
and execute the schema found in `database/schema.sql`. You can delete the file if you want
to reset the data and let the app rebuild a fresh database on the next launch.

To summarize:

- **Database type:** SQLite (bundled with PHP via PDO)
- **Database file path / name:** `database/app.sqlite`

Make sure the `database/` directory is writable by PHP so the file can be created.

## Inspecting the database on macOS with VS Code

If you are working on a MacBook, the easiest way to browse or edit the bundled
SQLite database is directly from Visual Studio Code:

1. Open the project folder in VS Code.
2. Install the extension **"SQLite" by alexcvzz** (search for "SQLite" in the
   Extensions sidebar) and reload VS Code if prompted.
3. After installation, open the command palette (`⇧⌘P`) and run **"SQLite: Open
   Database"**.
4. Choose the file `database/app.sqlite`. The extension will add the file to the
   **SQLite Explorer** view in the Activity Bar (usually a cylinder icon).
5. Expand the database entry from the SQLite Explorer to browse tables, view
   rows, or run custom SQL queries.

> 💡 Double-clicking `app.sqlite` in the normal VS Code file explorer will only
> show a "binary file" warning because SQLite databases are not plain text.
> Always open the file through the SQLite extension (or the CLI below) to
> inspect its contents.

### Using the built-in sqlite3 CLI (optional)

macOS ships with the `sqlite3` command-line tool. You can inspect the same file
from the terminal:

```bash
cd /path/to/your/project
sqlite3 database/app.sqlite
```

Once inside the prompt you can list the tables with `.tables` or quit with
`.exit`. This is handy if you prefer working from the terminal instead of a VS
Code extension.
