# Docker Setup Guide

This Laravel application can be run using Docker Compose, which provides a complete development environment with PHP, Nginx, PostgreSQL, Redis, and Mailhog.

## Prerequisites

- Docker Desktop installed on your machine
- Docker Compose (included with Docker Desktop)

## Quick Start

1. **Copy the environment file:**
   ```bash
   cp .env.docker .env
   ```

2. **Generate application key:**
   ```bash
   docker-compose run --rm app php artisan key:generate
   ```

3. **Start the containers:**
   ```bash
   docker-compose up -d
   ```

4. **Install dependencies:**
   ```bash
   docker-compose exec app composer install
   ```

5. **Run migrations:**
   ```bash
   docker-compose exec app php artisan migrate
   ```

6. **Access the application:**
   - Application: http://localhost:8000
   - Mailhog (Email testing): http://localhost:8025

## Services

The docker-compose setup includes:

- **app**: Laravel application (PHP + Nginx)
- **db**: PostgreSQL 15 database
- **redis**: Redis cache server
- **mailhog**: Email testing tool

## Common Commands

### Start containers
```bash
docker-compose up -d
```

### Stop containers
```bash
docker-compose down
```

### View logs
```bash
docker-compose logs -f app
```

### Run artisan commands
```bash
docker-compose exec app php artisan [command]
```

### Run composer commands
```bash
docker-compose exec app composer [command]
```

### Access Laravel Tinker
```bash
docker-compose exec app php artisan tinker
```

### Run migrations
```bash
docker-compose exec app php artisan migrate
```

### Seed database
```bash
docker-compose exec app php artisan db:seed
```

### Clear cache
```bash
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
```

### Run tests
```bash
docker-compose exec app php artisan test
```

### Access database
```bash
docker-compose exec db psql -U laravel_user -d laravel_db
```

### Rebuild containers
```bash
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

## Database Configuration

The PostgreSQL database is configured with:
- **Host**: db (internal) or localhost:5432 (external)
- **Database**: laravel_db
- **Username**: laravel_user
- **Password**: laravel_password

## Storage Permissions

If you encounter permission issues with storage, run:
```bash
docker-compose exec app chmod -R 777 storage bootstrap/cache
```

## Troubleshooting

### Port Already in Use
If port 8000, 5432, or 6379 is already in use, edit `docker-compose.yml` and change the port mapping:
```yaml
ports:
  - "8080:80"  # Changed from 8000:80
```

### Database Connection Issues
Make sure the database container is fully started before running migrations:
```bash
docker-compose ps
```

### Permission Issues
```bash
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

## Development Workflow

1. Make changes to your code
2. The changes are automatically reflected (volume mounted)
3. For config changes, clear cache:
   ```bash
   docker-compose exec app php artisan config:clear
   ```

## Production Note

This docker-compose setup is for **development only**. For production deployment, use the `Dockerfile` with proper environment variables and security configurations.
