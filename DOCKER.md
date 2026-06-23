# Docker Development Environment

This stack is configured for the project target in `.agents/context/project.md`:

- PHP 8.3 FPM for Laravel
- Nginx
- PostgreSQL 16 with `pgvector` and `pg_trgm`
- Redis 7
- Database-backed Laravel queue worker
- Node 22 for Vite

## First Run

Copy the Docker environment template:

```bash
cp .env.docker.example .env
```

Start the services:

```bash
docker compose up -d --build
```

Install dependencies and prepare the app if they are not already present:

```bash
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

Open the app at:

```text
http://localhost:8080
```

Vite runs at:

```text
http://localhost:5173
```

## Useful Commands

```bash
docker compose exec app php artisan test
docker compose exec app php artisan migrate
docker compose exec app php artisan tinker
docker compose exec postgres psql -U charley -d charley
docker compose logs -f app nginx queue
```

## Notes

- The current `composer.json` still requires `laravel/framework:^12.0` and `php:^8.2`. The container uses PHP 8.3 so it is ready for the documented Laravel 13 target, but upgrading the framework dependency should be handled as a separate dependency change.
- `.env.docker.example` uses Docker service names for internal hosts: `postgres` and `redis`.
- PostgreSQL extensions are created idempotently on initial database volume creation via `docker/postgres/init/01-extensions.sql`.
