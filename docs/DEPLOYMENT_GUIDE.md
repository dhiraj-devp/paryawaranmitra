# Deployment guide

## Requirements

- PHP 8.2 or newer, with `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `intl`, and `zip`.
- Composer 2.
- MySQL 8 or MariaDB 10.11 or newer for a shared or production database.
- A mailer and a queue worker when those features are switched on. Neither sends messages in this phase.

This workstation has PHP 8.2.12 and MariaDB 10.4.32 under XAMPP. Laravel 13 was not installed because it requires PHP 8.3. MariaDB 10.4 is below the production target. Use it only as a temporary local server.

## First local run

```bash
composer install
copy .env.example .env
php artisan key:generate
```

Edit `.env`:

- `APP_URL` should match the address you open, for example `http://127.0.0.1:8000`.
- Quote any password or secret that contains `#`. An unquoted `#` is a comment, and the value is cut off.

Create the database, then:

```bash
php artisan migrate --seed
php artisan serve
```

The seeder loads roles and permissions. It creates the Super Admin and Staff accounts only when both the email and the password for that account are set. Leave both empty to skip the account. Setting only one of them is an error. Running the seeder again does not change an existing password.

Local development on this machine currently uses SQLite (`DB_CONNECTION=sqlite`) because MariaDB was not accepting connections. `.env.example` is set to MySQL database `paryavaran_mitra`. Switch `.env` to that when the server is up.

### MySQL with XAMPP

Start MySQL from the XAMPP control panel. The client is `C:\xampp8.2\mysql\bin\mysql.exe` and is not on `PATH`.

```bash
C:\xampp8.2\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS paryavaran_mitra CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Then set `DB_CONNECTION=mysql` and run `php artisan migrate --seed`.

## Production configuration

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL` is the HTTPS origin
- `APP_KEY` generated on the server and kept out of git
- `SESSION_SECURE_COOKIE=true`
- `SESSION_ENCRYPT=true`
- `ADMIN_TWO_FACTOR=false` until enrollment is implemented. Setting it true blocks every administrator login.
- `LOG_LEVEL=info` or `warning`

Run `php artisan config:cache`, `php artisan route:cache`, and `php artisan view:cache` only after `.env` is final. Rebuild them after any config change.

The web server must not expose `storage/app/private`. The document root is `public/`.

## Queue, cache, and Redis

Defaults are the database drivers, which work without extra services. When the PHP `redis` extension and a Redis server are installed:

```dotenv
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_CLIENT=phpredis
```

Do this as a deliberate cutover. Do not install Predis just to flip the drivers. Keep sessions on one driver. Mixing database sessions and Redis sessions logs everyone out.

A queue worker is required once a real job is dispatched:

```bash
php artisan queue:work
```

Schedule `php artisan schedule:run` every minute when automation rules exist. No business jobs are scheduled now.

## Backups

Take the database and the private files on the same schedule. Daily is the minimum. Keep copies off the application server.

MySQL or MariaDB:

```bash
C:\xampp8.2\mysql\bin\mysqldump.exe --single-transaction --routines --databases paryavaran_mitra > paryavaran_mitra.sql
```

On a server where `mysqldump` is on `PATH`, the same flags apply. `--single-transaction` needs InnoDB, which these migrations use.

Private files:

```bash
# storage/app/private, including documents and any other local disks
```

Copy that directory with the dump. A restore without it loses every uploaded file even though the paths remain in the database.

## Restore

1. Put the application in maintenance mode: `php artisan down`.
2. Restore the database into an empty schema:

```bash
C:\xampp8.2\mysql\bin\mysql.exe -u root paryavaran_mitra < paryavaran_mitra.sql
```

3. Replace `storage/app/private` with the matching file backup. Do not leave newer files that the restored database does not know about, and do not leave database paths whose files are missing.
4. Confirm `APP_KEY` is the key that encrypted session payloads and two-factor secrets in that backup. A new key cannot read those values.
5. `php artisan up`, then sign in and open a known audit row and, once documents exist, an authorized download.

Practice the restore on a copy before it is needed. A backup that has never been restored is unverified.

SQLite local file, if that is what `.env` points at, is `database/database.sqlite`. Copy it while the application is stopped. It is gitignored.

## Updates

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Migrations are forward-only in production. Do not run `migrate:fresh` against a database that holds office data.

## Health

`GET /up` is the framework health route. It does not check Redis, mail, or the document disk. Add those checks when those dependencies are in use.
