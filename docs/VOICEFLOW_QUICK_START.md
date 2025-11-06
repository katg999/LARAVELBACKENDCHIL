# VoiceFlow Simulation - Quick Start Guide

## TL;DR - Just show me the commands!

### Running Locally

```bash
# Terminal 1: Start Laravel
php artisan serve --host=0.0.0.0 --port=8000

# Terminal 2: Run simulation (all options provided - non-interactive)
php artisan voiceflow:simulate --type=school --email=test@school.com --action=login --auto-verify

# OR: Interactive mode (prompts for input)
php artisan voiceflow:simulate
```

### Running in Docker

```bash
# Start containers (if not already running)
docker-compose up -d

# EASIEST WAY - Use the wrapper script (works exactly like local!)
./voiceflow-docker.sh

# With options
./voiceflow-docker.sh --type=school --email=test@school.com --action=login --auto-verify

# --------- Or use these directly ---------

# Option 1: docker-compose (RECOMMENDED)
docker-compose exec app php artisan voiceflow:simulate

# Option 2: docker exec with -it (interactive mode - REQUIRED for prompts to work!)
docker exec -it laravel_app php artisan voiceflow:simulate

# Option 3: docker exec non-interactive (provide all options)
docker exec laravel_app php artisan voiceflow:simulate --type=school --email=test@school.com --action=login --auto-verify
```

⚠️ **IMPORTANT:** If you want interactive prompts to work in Docker, you MUST use:
- `./voiceflow-docker.sh` (easiest), OR
- `docker-compose exec` (no -it needed), OR  
- `docker exec -it` (with -it flags)

❌ `docker exec laravel_app` (without -it) will NOT work interactively!

## Command Options

```bash
--type=TYPE                User type: school, doctor, health-facility
--email=EMAIL              Email address to test
--action=ACTION            Action: login, register
--auto-verify              Automatically verify OTP (login only)
```

## Examples

### Login as School (auto-verify OTP)
```bash
# Local
php artisan voiceflow:simulate --type=school --email=school@test.com --action=login --auto-verify

# Docker
docker-compose exec app php artisan voiceflow:simulate --type=school --email=school@test.com --action=login --auto-verify
```

### Login as Doctor (manual OTP entry)
```bash
# Local
php artisan voiceflow:simulate --type=doctor --email=doctor@test.com --action=login

# Docker
docker exec -it laravel_app php artisan voiceflow:simulate --type=doctor --email=doctor@test.com --action=login
```

### Register Health Facility
```bash
# Local
php artisan voiceflow:simulate --action=register

# Docker (interactive)
docker exec -it laravel_app php artisan voiceflow:simulate --action=register
```

## Common Errors & Quick Fixes

### ❌ "Email address is required"
**Problem:** Running `docker exec` without `-it` or missing `--email` option

**Fix:**
```bash
# Add -it flags
docker exec -it laravel_app php artisan voiceflow:simulate

# OR provide all options
docker exec laravel_app php artisan voiceflow:simulate --type=school --email=test@school.com --action=login --auto-verify
```

### ❌ "Server is not running"
**Local:**
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

**Docker:**
```bash
docker-compose restart app
```

### ❌ "Non-interactive terminal detected"
**Fix:** Use one of these approaches:
```bash
# Add -it flags
docker exec -it laravel_app php artisan voiceflow:simulate

# OR use docker-compose (handles TTY automatically)
docker-compose exec app php artisan voiceflow:simulate

# OR provide all required options
docker exec laravel_app php artisan voiceflow:simulate --type=school --email=test@school.com --action=login
```

## Test Data Setup

Before running the simulation, make sure you have test data:

```bash
# Create a test school
php artisan tinker
>>> App\Models\School::create(['name' => 'Test School', 'email' => 'school@test.com', 'contact' => '1234567890']);
>>> exit

# Create a test doctor
php artisan tinker
>>> App\Models\Doctor::create(['name' => 'Test Doctor', 'email' => 'doctor@test.com', 'contact' => '1234567890', 'specialization' => 'General']);
>>> exit
```

## Understanding the Output

```
🎤 VoiceFlow Login Simulation
==============================
📍 Using base URL: http://localhost:8000    ← Shows which endpoint it's using

✅ Server is running                         ← Server connectivity check
📧 Testing school login for: test@school.com ← User being tested
📤 Sending OTP...                            ← Sending OTP request
✅ OTP sent successfully!                    ← OTP sent
🔐 OTP Code: 123456                          ← The OTP code (from database)
🔍 Auto-verifying OTP...                     ← Verifying OTP (if --auto-verify)
✅ OTP verified successfully!                ← Success!
🔗 Login URL: https://...                    ← Login URL (if returned)
🎉 VoiceFlow login simulation completed!     ← Done
```

## Pro Tips

1. **Always use `docker-compose exec`** instead of `docker exec` - it handles TTY better
2. **Use `--auto-verify`** to skip manual OTP entry
3. **Check the base URL** in the output to ensure it's using the right endpoint
4. **Provide all options** (`--type`, `--email`, `--action`) for non-interactive execution

## More Info

- Full documentation: `VOICEFLOW_DOCKER_DIFFERENCES.md`
- General guide: `VOICEFLOW_SIMULATION_GUIDE.md`
- Docker setup: `DOCKER.md`
