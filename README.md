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

The `app` container's entrypoint only ensures Laravel's `storage/` and `bootstrap/cache` directories are writable. Application setup is still manual — after the containers are up, run:

```bash
docker exec hr_app composer install
docker exec hr_app php artisan key:generate
docker exec hr_app php artisan migrate
docker exec hr_app php artisan db:seed
```

Before running the app, make sure your `.env` points at the MySQL service (see [Environment](#environment)).

## Services

| Service | Image / Build | Purpose | Port (host:container) | URL |
|---------|---------------|---------|-----------------------|-----|
| `app` (`hr_app`) | Build: `docker/php/Dockerfile` (`php:8.4-fpm`) | PHP-FPM application server | — (exposes 9000 internally) | — |
| `nginx` (`hr_nginx`) | `nginx:1.27-alpine` | Web server / reverse proxy to PHP-FPM | `8080:80` | http://localhost:8080 |
| `mysql` (`hr_mysql`) | `mysql:8.4` | Application database | `3306:3306` | — |
| `phpmyadmin` (`hr_pma`) | `phpmyadmin:5.2` | Database admin UI | `8081:80` | http://localhost:8081 |

All services share the `hrnet` bridge network. `nginx`, `mysql`, and `phpmyadmin` wait for MySQL to pass its healthcheck (`app` and `phpmyadmin` via `service_healthy`, `nginx` via `app`).

## Environment

Copy `.env.example` to `.env`. To match the Docker MySQL service, set the following keys (defaults shown match the `mysql` service credentials in `docker-compose.yml`):

| Key | Value | Notes |
|-----|-------|-------|
| `APP_NAME` | `"HR Management System"` | Display name |
| `APP_URL` | `http://localhost:8080` | Matches the `nginx` host port |
| `DB_CONNECTION` | `mysql` | `.env.example` ships as `sqlite`; change it |
| `DB_HOST` | `mysql` | Docker service name |
| `DB_PORT` | `3306` | |
| `DB_DATABASE` | `laravel_hr` | Matches `MYSQL_DATABASE` |
| `DB_USERNAME` | `hr_user` | Matches `MYSQL_USER` |
| `DB_PASSWORD` | `hr_secret` | Matches `MYSQL_PASSWORD` |
| `SESSION_DRIVER` | `database` | |
| `CACHE_STORE` | `database` | |
| `QUEUE_CONNECTION` | `database` | |

The `mysql` service is configured via these Compose environment variables: `MYSQL_ROOT_PASSWORD=rootsecret`, `MYSQL_DATABASE=laravel_hr`, `MYSQL_USER=hr_user`, `MYSQL_PASSWORD=hr_secret`. The `phpmyadmin` service uses `PMA_HOST=mysql`, `PMA_PORT=3306`, `UPLOAD_LIMIT=256M`.

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
