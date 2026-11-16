# Laravel HR Management System

A full-stack HR platform built with Laravel 12 (PHP 8.4) covering employee management, attendance tracking, leave management, payroll generation, and audit logging, packaged to run entirely with Docker.

## Prerequisites

- [Docker](https://www.docker.com/get-started)
- Docker Compose (bundled with Docker Desktop / `docker compose` plugin)

## Run

```bash
git clone <repository-url>
cd laravelHr
cp .env.example .env

docker compose up -d --build
```

The `app` container runs as the unprivileged `app` user (uid/gid 1000; override with the `UID`/`GID` build args). On every `docker compose up`, the one-shot `app-init` service gives that user ownership of `storage/`, `bootstrap/cache` and `vendor/`. `docker exec` runs as `app` by default, so artisan and composer never create root-owned files. Application setup is still manual. After the containers are up, run:

```bash
docker exec hr_app composer install
docker exec hr_app php artisan key:generate
docker exec hr_app php artisan migrate
docker exec hr_app php artisan db:seed

# Build the CSS (Vite + Tailwind 3.4; output in public/build, not committed)
docker compose run --rm node npm ci
docker compose run --rm node npm run build
```

Run `npm run build` again after changing Blade views, `public/js`, or Tailwind classes in `app/` (enums). Without `public/build/manifest.json`, every page fails with a Vite manifest error.

Before running the app, make sure your `.env` points at the MySQL service (see [Environment](#environment)).

## Services

| Service | Image / Build | Purpose | Port (host:container) | URL |
|---------|---------------|---------|-----------------------|-----|
| `app` (`hr_app`) | Build: `docker/php/Dockerfile` (`php:8.4-fpm`) | PHP-FPM application server | — (exposes 9000 internally) | — |
| `nginx` (`hr_nginx`) | `nginx:1.27-alpine` | Web server / reverse proxy to PHP-FPM | `8080:80` | http://localhost:8080 |
| `mysql` (`hr_mysql`) | `mysql:8.4` | Application database | `127.0.0.1:3306` | — |
| `phpmyadmin` (`hr_pma`) | `phpmyadmin:5.2` | Database admin UI (dev only) | `127.0.0.1:8081` | http://localhost:8081 |
| `mailpit` (`hr_mailpit`) | `axllent/mailpit:v1.20.0` | Dev mail catcher (password-reset emails) | `127.0.0.1:8025` | http://localhost:8025 |
| `app-init` | same image as `app` | One-shot ownership fix, then exits | — | — |
| `node` (profile `tools`) | `node:22-alpine` | Frontend build (`docker compose run --rm node …`) | — | — |

All services share the `hrnet` bridge network. `app` waits for `app-init` to finish and for MySQL's healthcheck. `nginx` has its own healthcheck on `/up`, which only passes when PHP-FPM and the app are working.

## Environment

Copy `.env.example` to `.env`. To match the Docker MySQL service, set the following keys (defaults shown match the `mysql` service credentials in `docker-compose.yml`):

| Key | Value | Notes |
|-----|-------|-------|
| `APP_NAME` | `"HR Management System"` | Display name |
| `APP_URL` | `http://localhost:8080` | Matches the `nginx` host port |
| `DB_CONNECTION` | `mysql` | |
| `DB_HOST` | `mysql` | Docker service name |
| `DB_PORT` | `3306` | |
| `DB_DATABASE` | `laravel_hr` | Matches `MYSQL_DATABASE` |
| `DB_USERNAME` | `hr_user` | Matches `MYSQL_USER` |
| `DB_PASSWORD` | `hr_secret` | Matches `MYSQL_PASSWORD` |
| `SESSION_DRIVER` | `database` | |
| `CACHE_STORE` | `database` | |
| `QUEUE_CONNECTION` | `database` | |
| `MAIL_MAILER` / `MAIL_HOST` / `MAIL_PORT` | `smtp` / `mailpit` / `1025` | Password-reset mail goes to Mailpit. With `log`, the reset link is written to `storage/logs/laravel.log` instead |
| `HR_TIMEZONE` | `UTC` | Company timezone (e.g. `Asia/Manila`). It sets which calendar day a clock-in belongs to, lateness against shift times, and the nightly day close. Stored datetimes stay in UTC |

The `mysql` service is configured via these Compose environment variables: `MYSQL_ROOT_PASSWORD=rootsecret`, `MYSQL_DATABASE=laravel_hr`, `MYSQL_USER=hr_user`, `MYSQL_PASSWORD=hr_secret`. The `phpmyadmin` service uses `PMA_HOST=mysql`, `PMA_PORT=3306`, `UPLOAD_LIMIT=256M`.

Passwords can be overridden via `DB_ROOT_PASSWORD` / `DB_PASSWORD` in `.env` (the defaults above are for local dev only; change them in production). MySQL and phpMyAdmin ports are bound to `127.0.0.1`. Demo data is seeded only in `local`/`testing` environments, so the demo accounts below must not exist in production.

## Volumes & persistence

**Named volumes**
- `mysql_data` → `/var/lib/mysql` — persists the MySQL database across restarts.

**Bind mounts**
- `./` → `/var/www/html` — the project source, mounted into both `app` and `nginx`.
- `./docker/php/php.ini` → `/usr/local/etc/php/conf.d/zz-custom.ini` (read-only) — custom PHP config.
- `./docker/nginx/default.conf` → `/etc/nginx/conf.d/default.conf` (read-only) — Nginx site config.
- `./docker/mysql/my.cnf` → `/etc/mysql/conf.d/my.cnf` (read-only) — MySQL config.

## Common commands

```bash
# View logs (all services)
docker compose logs -f

# Tail Laravel application log
docker exec hr_app tail -f storage/logs/laravel.log

# Stop the stack
docker compose down

# Stop and delete the database volume (all data lost)
docker compose down -v

# Rebuild after Dockerfile / dependency changes
docker compose up -d --build

# Open a shell in the app container
docker exec -it hr_app sh

# Run any artisan command
docker exec hr_app php artisan <command>

# Re-run migrations / seed demo data
docker exec hr_app php artisan migrate
docker exec hr_app php artisan db:seed
```

## Verify

1. Confirm all four containers are running:

   ```bash
   docker compose ps
   ```

2. Open the app at http://localhost:8080 and phpMyAdmin at http://localhost:8081.

3. Log in with the seeded demo accounts:

   | Role | Email | Password |
   |------|-------|----------|
   | HR / Admin | admin@hr.test | password |
   | Employee | employee@hr.test | password |

   Demo accounts are seeded only when `APP_ENV` is `local` or `testing`.

## Tests

Tests run against a separate MySQL database, `laravel_hr_testing` (see `phpunit.xml`). `tests/TestCase.php` refuses to run against any database whose name does not contain `testing`. Create the database once:

```bash
docker exec -i hr_mysql mysql -uroot -p <<'SQL'
CREATE DATABASE IF NOT EXISTS laravel_hr_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL ON laravel_hr_testing.* TO 'hr_user'@'%';
SQL
docker exec hr_app php artisan test
docker exec hr_app vendor/bin/pint --test
```

## License

MIT — see [LICENSE](LICENSE).
