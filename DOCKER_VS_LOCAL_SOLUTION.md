# Making VoiceFlow Simulation Work Identically in Docker and Local

## The Problem You Had

When running `docker exec laravel_app php artisan voiceflow:simulate`, the command failed with "❌ Email address is required" because Docker wasn't allocating a TTY (pseudo-terminal), making interactive prompts fail.

## The Solution

You now have **three ways** to run the command in Docker that work exactly like local:

### 1. ✅ Use the Wrapper Script (EASIEST!)

```bash
./voiceflow-docker.sh
```

This wrapper script:
- Automatically uses the correct docker command with TTY support
- Checks if Docker/containers are running
- Works exactly like running locally
- Passes through all command options

### 2. ✅ Use docker-compose exec

```bash
docker-compose exec app php artisan voiceflow:simulate
```

`docker-compose exec` automatically handles TTY allocation.

### 3. ✅ Use docker exec with -it flags

```bash
docker exec -it laravel_app php artisan voiceflow:simulate
```

The `-it` flags tell Docker to:
- `-i` = Keep STDIN open (interactive)
- `-t` = Allocate a pseudo-TTY (terminal)

## Side-by-Side Comparison

### Local (works perfectly) ✅
```bash
php artisan voiceflow:simulate
🎤 VoiceFlow Login Simulation
==============================
📍 Using base URL: http://localhost:8000

✅ Server is running

 What would you like to do? [login]:
  [0] login
  [1] register
 > █
```

### Docker with wrapper (works exactly the same!) ✅
```bash
./voiceflow-docker.sh
✓ Using docker-compose (recommended)
🎤 VoiceFlow Login Simulation
==============================
📍 Using base URL: http://localhost:80

✅ Server is running

 What would you like to do? [login]:
  [0] login
  [1] register
 > █
```

### Docker WITHOUT -it flags (FAILS) ❌
```bash
docker exec laravel_app php artisan voiceflow:simulate
🎤 VoiceFlow Login Simulation
==============================
📍 Using base URL: http://localhost:80

✅ Server is running

 What would you like to do? [login]:
  [0] login
  [1] register
 > ❌ Email address is required  # <-- Prompt failed silently!
```

## Quick Usage Examples

### Interactive Mode (prompts for everything)

**Local:**
```bash
php artisan voiceflow:simulate
```

**Docker (any of these work):**
```bash
./voiceflow-docker.sh
# OR
docker-compose exec app php artisan voiceflow:simulate
# OR
docker exec -it laravel_app php artisan voiceflow:simulate
```

### With Options (skip prompts)

**Local:**
```bash
php artisan voiceflow:simulate --type=school --email=test@school.com --action=login --auto-verify
```

**Docker:**
```bash
./voiceflow-docker.sh --type=school --email=test@school.com --action=login --auto-verify
# OR
docker-compose exec app php artisan voiceflow:simulate --type=school --email=test@school.com --action=login --auto-verify
# OR
docker exec -it laravel_app php artisan voiceflow:simulate --type=school --email=test@school.com --action=login --auto-verify
# OR even without -it (since options are provided)
docker exec laravel_app php artisan voiceflow:simulate --type=school --email=test@school.com --action=login --auto-verify
```

## Why This Matters

### TTY (Terminal) Allocation

Interactive commands need a TTY to:
- Display prompts correctly
- Read user input
- Handle cursor positioning
- Show interactive menus

### What docker exec Does

| Command | TTY | Interactive | Result |
|---------|-----|-------------|--------|
| `docker exec` | ❌ No | ❌ No | Prompts fail silently |
| `docker exec -t` | ✅ Yes | ❌ No | Prompts display but can't read input |
| `docker exec -i` | ❌ No | ✅ Yes | Can read input but prompts broken |
| `docker exec -it` | ✅ Yes | ✅ Yes | **Works perfectly!** |
| `docker-compose exec` | ✅ Auto | ✅ Auto | **Works perfectly!** |

## Technical Details

### How the Fix Works

1. **Wrapper Script** (`voiceflow-docker.sh`):
   - Automatically detects docker-compose availability
   - Always uses commands that allocate TTY
   - Provides helpful error messages

2. **Command Detection**:
   - Checks if STDIN is a TTY using `posix_isatty(STDIN)`
   - Provides helpful error messages when TTY is missing
   - Shows the correct command to use

3. **Base URL Detection**:
   - Checks for `/.dockerenv` file (Docker container marker)
   - Checks `WEBROOT` environment variable
   - Uses `http://localhost:80` in Docker (internal nginx port)
   - Uses `http://localhost:8000` locally (dev server)

## Common Scenarios

### Scenario 1: First time running in Docker
```bash
# This won't work interactively
docker exec laravel_app php artisan voiceflow:simulate
# Error: "❌ Email address is required"

# Solution: Use the wrapper
./voiceflow-docker.sh
# Works! ✅
```

### Scenario 2: Running in CI/CD (non-interactive)
```bash
# Provide all options, no TTY needed
docker exec laravel_app php artisan voiceflow:simulate \
  --type=school \
  --email=test@school.com \
  --action=login \
  --auto-verify
```

### Scenario 3: Development workflow
```bash
# Use the wrapper - it's smart!
./voiceflow-docker.sh
# Automatically picks the best method
```

## Files Modified

1. **`app/Console/Commands/VoiceFlowSimulation.php`**:
   - Enhanced environment detection
   - Better error messages for non-TTY scenarios
   - Shows base URL in output

2. **`voiceflow-docker.sh`** (NEW):
   - Wrapper script that handles TTY automatically
   - Checks container status
   - Uses best available method

3. **Documentation**:
   - `VOICEFLOW_QUICK_START.md` - Quick reference
   - `VOICEFLOW_DOCKER_DIFFERENCES.md` - Detailed explanation
   - `DOCKER_VS_LOCAL_SOLUTION.md` - This file!

## Recommendation

**Use the wrapper script:** `./voiceflow-docker.sh`

It's the simplest solution and works in all scenarios. Just remember:
- Use `php artisan voiceflow:simulate` locally
- Use `./voiceflow-docker.sh` in Docker

They'll work exactly the same! 🎉
