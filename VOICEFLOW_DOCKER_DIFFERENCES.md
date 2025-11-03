# VoiceFlow Simulation: Docker vs Local Differences

## The Problem

The `php artisan voiceflow:simulate` command behaves differently when run locally vs in Docker due to different network configurations and port mappings.

## How It Works

### Local Environment
- **Command**: `php artisan voiceflow:simulate`
- **Base URL**: `http://localhost:8000`
- **How it runs**: Connects to Laravel development server (`php artisan serve`)

### Docker Environment
- **Command**: `docker-compose exec app php artisan voiceflow:simulate`
- **Base URL**: `http://localhost:80` (internal container port)
- **How it runs**: Connects to nginx inside the container on port 80

## The Fix

The command now:
1. **Detects the environment automatically** using these checks:
   - `/.dockerenv` file exists (Docker marker)
   - `WEBROOT` environment variable is set to `/var/www/html/public`
2. **Allows manual override** via `API_BASE_URL` environment variable
3. **Shows the detected URL** in the command output for debugging

## Usage Examples

### Running Locally

```bash
# Start local dev server first
php artisan serve --host=0.0.0.0 --port=8000

# In another terminal
php artisan voiceflow:simulate --type=school --email=school@example.com --action=login --auto-verify
```

### Running in Docker

```bash
# Make sure Docker containers are up
docker-compose up -d

# Option 1: Use docker-compose (recommended - handles TTY automatically)
docker-compose exec app php artisan voiceflow:simulate --type=school --email=school@example.com --action=login --auto-verify

# Option 2: Use docker exec with -it flags for interactive mode
docker exec -it laravel_app php artisan voiceflow:simulate --type=school --email=school@example.com --action=login --auto-verify

# IMPORTANT: Without -it flags or all options, the command will fail in Docker
# docker exec laravel_app php artisan voiceflow:simulate  ❌ This will NOT work interactively
```

### Manual Override (Advanced)

If the auto-detection doesn't work, you can manually specify the base URL:

```bash
# Local
API_BASE_URL=http://localhost:8000 php artisan voiceflow:simulate

# Docker
docker-compose exec -e API_BASE_URL=http://localhost:80 app php artisan voiceflow:simulate

# Docker with host machine port (if needed)
docker-compose exec -e API_BASE_URL=http://host.docker.internal:8000 app php artisan voiceflow:simulate
```

## Common Issues & Solutions

### Issue 1: "Email address is required" in Docker

**Symptom:**
```
❌ Email address is required
```

**Cause:** Running `docker exec` without `-it` flags makes the terminal non-interactive, so prompts fail.

**Solutions:**

**Option A - Use docker-compose (recommended):**
```bash
docker-compose exec app php artisan voiceflow:simulate --type=school --email=test@school.com --action=login --auto-verify
```

**Option B - Use docker exec with -it flags:**
```bash
docker exec -it laravel_app php artisan voiceflow:simulate --type=school --email=test@school.com --action=login --auto-verify
```

**Option C - Provide all options (non-interactive):**
```bash
docker exec laravel_app php artisan voiceflow:simulate --type=school --email=test@school.com --action=login --auto-verify
```

### Issue 2: "Server is not running"

**Local:**
```bash
# Start the dev server
php artisan serve --host=0.0.0.0 --port=8000
```

**Docker:**
```bash
# Check containers are up
docker-compose ps

# Restart if needed
docker-compose restart app
```

### Issue 3: Wrong port detected

Check the command output - it will show:
```
📍 Using base URL: http://localhost:XXXX
```

If this is wrong, use the `API_BASE_URL` environment variable override.

### Issue 4: Database connection issues in Docker

The command needs to connect to the database. Make sure:
```bash
# Check DB container is running
docker-compose ps db

# Check DB connection from app container
docker-compose exec app php artisan db:show
```

### Issue 5: Redis connection issues

If using Redis for sessions/cache:
```bash
# Check Redis container
docker-compose ps redis

# Test Redis connection
docker-compose exec app php artisan tinker
>>> Redis::ping()
```

## Environment Configuration

### Local .env
```env
APP_URL=http://localhost:8000
DB_HOST=127.0.0.1
DB_PORT=5432
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

### Docker .env
```env
APP_URL=http://localhost:8000  # External URL
DB_HOST=db                      # Docker service name
DB_PORT=5432
REDIS_HOST=redis                # Docker service name
REDIS_PORT=6379
```

## Testing the Fix

1. **Test Local**:
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   # In another terminal:
   php artisan voiceflow:simulate --type=school --email=test@school.com --action=login
   ```

2. **Test Docker**:
   ```bash
   docker-compose up -d
   docker-compose exec app php artisan voiceflow:simulate --type=school --email=test@school.com --action=login
   ```

3. **Verify Output**: Both should show:
   ```
   🎤 VoiceFlow Login Simulation
   ==============================
   📍 Using base URL: http://localhost:XXXX
   
   ✅ Server is running
   ```

## Architecture Notes

### Port Mapping
- **Docker Compose**: Maps container port 80 → host port 8000
- **Inside container**: Use `http://localhost:80`
- **From host machine**: Use `http://localhost:8000`

### Network Flow

**Local:**
```
Command → http://localhost:8000 → Laravel Dev Server
```

**Docker:**
```
Command (inside container) → http://localhost:80 → Nginx → PHP-FPM → Laravel
```

**Docker (from host):**
```
Command (on host) → http://localhost:8000 → Docker Port Mapping → Nginx (port 80) → Laravel
```

## Debugging Tips

1. **Check which environment is detected**:
   ```bash
   php artisan voiceflow:simulate
   # Look for: "📍 Using base URL: ..."
   ```

2. **Test server connectivity**:
   ```bash
   # Local
   curl http://localhost:8000
   
   # Docker (from inside container)
   docker-compose exec app curl http://localhost:80
   
   # Docker (from host)
   curl http://localhost:8000
   ```

3. **Check Docker environment**:
   ```bash
   docker-compose exec app env | grep -E "WEBROOT|API_BASE_URL"
   docker-compose exec app test -f /.dockerenv && echo "Inside Docker" || echo "Not in Docker"
   ```

4. **Test API endpoints directly**:
   ```bash
   # Send OTP
   curl -X POST http://localhost:8000/api/voiceflow/send-login-otp \
     -H "Content-Type: application/json" \
     -d '{"email":"test@school.com"}'
   ```

## Additional Resources

- See `VOICEFLOW_SIMULATION_GUIDE.md` for general usage
- See `DOCKER.md` for Docker setup instructions
- See `APPLY_DOCKER_GUIDE.md` for Docker deployment
