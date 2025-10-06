# LDOE Base Designer

Design and share Last Day on Earth base layouts in your browser. This repository contains the PHP backend, database seeding scripts, and static assets that power the interactive designer.

## Features

- Interactive base designer (`index.html`) with drag-and-drop tiles, furniture, storage, and decorations.
- REST endpoints (`getItems.php`, `gridAPI.php`) backed by MySQL for fetching catalog data and saving grids.
- One-click database bootstrap with optional production seed data via `setup_database.php`.
- Upload-friendly admin panel (`admin.php`) for managing catalog items, including image uploads to `uploads/`.
- Emoji-aware UI with utf8mb4 charset, ready for hosting on any PHP-capable server.

## Requirements

| Component      | Recommended | Notes |
| -------------- | ----------- | ----- |
| PHP            | 8.0+        | Extensions: `mysqli`, `json`, `mbstring` (default in XAMPP/WAMP/LAMP). |
| Web server     | Apache/Nginx, or PHP built-in server for dev | XAMPP/WAMP/MAMP bundles work out of the box. |
| MySQL/MariaDB  | 5.7+ / 10.4+ | User must have permission to create tables. |
| Git (optional) | Latest      | For cloning the repository. |

> **Heads up:** The app reads item thumbnails from the `uploads/` directory. Ensure this directory exists and is writable by the web server user.

## Quick Start (Windows + XAMPP)

1. **Install XAMPP**
   - Download from [https://www.apachefriends.org](https://www.apachefriends.org) and install Apache + MySQL + PHP.
2. **Clone or copy the project**
   - Place the folder inside `C:\xampp\htdocs\LDOE-base-designer`. If you downloaded a ZIP, extract it there.
3. **Create the uploads folder (if missing)**
   - The repo already ships with `uploads/`. Verify it exists and is writable (`Right click → Properties → Security`).
4. **Configure database credentials**
   - Edit `dbcon.php` and set `$DB_HOST`, `$DB_USER`, `$DB_PASS`, `$DB_NAME` to match your MySQL setup.
   - Default XAMPP MySQL credentials are usually `root` / *(empty password)*.
5. **Start Apache and MySQL**
   - Open the XAMPP Control Panel and click *Start* for Apache and MySQL.
6. **Create the database and tables**
   - Visit `http://localhost/LDOE-base-designer/setup_database.php?seed=1`.
   - This will create the database (if needed), build tables, and populate them with starter data. Omit `?seed=1` if you prefer an empty catalog.
7. **Open the designer**
   - Navigate to `http://localhost/LDOE-base-designer/index.html` to start building bases.
8. **Manage catalog items (optional)**
   - Visit `http://localhost/LDOE-base-designer/admin.php` to add/edit tiles, storage, decorations, etc. Uploaded images land in `uploads/` automatically.

## Quick Start (macOS/Linux)

1. Install a PHP + MySQL bundle (MAMP, LAMP stack) or use Homebrew/Apt packages.
2. Clone the repo into the document root your web server serves.
3. Create an empty MySQL database (e.g., `base_designer`).
4. Update credentials in `dbcon.php`.
5. Run the setup script:
   ```bash
   php setup_database.php --seed
   ```
6. Serve the project (Apache/Nginx, or for quick local testing):
   ```bash
   php -S 127.0.0.1:8000
   ```
7. Browse to `http://127.0.0.1:8000/index.html`.

> When using the PHP built-in server, make sure you launch it from the project root so the PHP endpoints (`getItems.php`, `gridAPI.php`, etc.) resolve correctly.

## Deploying to a Hosting Provider

1. **Upload files** to your hosting account (e.g., via SFTP) keeping the directory structure intact.
2. **Create a MySQL database** using your host's control panel.
3. **Update `dbcon.php`** with the hosting provider's MySQL credentials.
4. **Set directory permissions**
   - Ensure `uploads/` is writable (`chmod 755 uploads` or `chmod 775 uploads` depending on the host).
5. **Run the setup script** by visiting `https://yourdomain.com/setup_database.php?seed=1`.
   - Remove `?seed=1` if you will populate data manually.
6. **Secure admin access**
   - If you are hosting publicly, consider restoring authentication inside `admin.php` or protecting it with HTTP auth at the server level.
7. **Test the app** at `https://yourdomain.com/index.html`.

## Project Structure

```
LDOE-base-designer/
├── admin.php           # Catalog CRUD dashboard (no login by default)
├── dbcon.php           # Central MySQL connection settings
├── functions.js        # Front-end logic for the designer
├── getItems.php        # REST endpoint returning catalog data
├── gridAPI.php         # REST endpoint for saving/loading user grids
├── index.html          # Main designer UI
├── setup_database.php  # Database/table creation + seeding helper
├── style.css           # UI styling
└── uploads/            # Item thumbnail images (user uploads & seeds)
```

## Admin Panel Tips

- Uploading a file replaces the existing image path with something like `uploads/abc123.webp`.
- Leaving the upload blank and entering an emoji lets you create emoji-based items.
- Deleting an item removes it from MySQL but **does not** delete the image file. Remove unused files manually if needed.

## Database Management

- Re-run `setup_database.php` anytime you need to ensure tables exist. It's idempotent.
- Add `?seed=1` or `--seed` to refill tables only when they are empty; existing rows are preserved.
- To reset everything, drop the database manually (via phpMyAdmin or CLI) and re-run the script.

## Troubleshooting

| Symptom | Fix |
| ------- | --- |
| `Database not initialized` message | Check `dbcon.php` credentials and ensure MySQL is running. Then rerun `setup_database.php`. |
| Missing images | Confirm the path stored in MySQL points to an existing file in `uploads/`. Set write permissions for the web server. |
| Upload errors in admin panel | Ensure PHP `file_uploads` is enabled and `uploads/` is writable. Default max file size is controlled by `upload_max_filesize` in `php.ini`. |
| "Table not yet created" from `gridAPI.php` | The `grid_items` table is not part of the current schema. Update your code or reintroduce the table if your fork requires it. |
| UTF-8/emoji rendering issues | Make sure your MySQL database uses `utf8mb4` (handled by the setup script). |

## Useful Commands

```powershell
# Clone the repository
git clone https://github.com/brzezinski-grzegorz/LDOE-base-designer.git

# Run the setup script with seed data (Windows PowerShell)
php setup_database.php --seed

# Start PHP0built-in server for quick testing
docker run --rm -it -v ${PWD}:/app -w /app php:8.2-cli php -S 0.0.0.0:8000
```

## Contributing

1. Fork the repository.
2. Create a feature branch: `git checkout -b feature/amazing`.
3. Commit your changes and open a Pull Request.

## License

This project is licensed under the MIT License. See [LICENSE](LICENSE) (add one if missing) for details.
