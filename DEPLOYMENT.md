# Hostinger deployment

## Requirements

- Hostinger shared hosting with PHP 8.1 or later.
- MySQL with the PDO MySQL and `mbstring` extensions enabled.
- An HTTPS-enabled domain.

## Deploy the application

1. In hPanel, create a MySQL database and database user. Grant that user access to the database.
2. Upload the contents of this project (including `index.php`, `backend/`, `frontend/`, and the root `.htaccess`) into `public_html`. Do not upload `.git` or your local `backend/database/config.local.php`.
3. Copy `backend/database/config.local.php.example` to `backend/database/config.local.php` on the server. Fill in the database host, database name, username, password, and port from hPanel. This config file is excluded from Git and its directory is blocked from direct web access.
4. Export the existing `final_nooma` database from local phpMyAdmin and import it into the Hostinger database. Database exports are not included in this repository; keep exports and real customer data out of Git.
5. Enable HTTPS for the domain in hPanel, then test login, reservations, table management, and user management.

The application detects whether it is installed at the domain root or in a subdirectory and builds internal URLs accordingly. If automatic detection does not match your setup, set `APP_BASE_PATH` to the URL path (for example, `/restaurant`); use an empty value when installed directly in `public_html`.

For deployments that use environment variables instead of `config.local.php`, configure `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, and optionally `DB_PORT` (defaults to `3306`). Environment variables take precedence over the local config file.

## Before going live

- Use a unique, strong database password and never commit `config.local.php` or database exports.
- Create or verify an active administrator account before removing access to the local database.
- Confirm PHP error display is disabled in production and review the hosting error log if the site returns an error.
- Keep a private backup of the database and application files before updates.
- For local XAMPP, create `backend/database/config.local.php` with your local database settings; keep this machine-specific file out of Git and do not upload it to production.
