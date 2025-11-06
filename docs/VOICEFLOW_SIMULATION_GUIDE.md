# VoiceFlow Simulation Command Guide

The `voiceflow:simulate` command allows you to test the VoiceFlow authentication process (login/register) directly from the command line.

## 🎯 Usage Modes

### **Option 1: Quick Test (Auto-Mode)**

The simplest way to test - automatically uses the first available account:

```bash
docker exec laravel_app php artisan voiceflow:simulate --auto-verify
```

Or without the auto-verify flag:
```bash
docker exec laravel_app php artisan voiceflow:simulate
```

This will:
- ✅ Default to login action
- ✅ Automatically find the first available account (school, doctor, or health-facility)
- ✅ Use that account's email
- ✅ With `--auto-verify`: automatically verify the OTP and show the login URL

---

### **Option 2: Interactive Mode (For Manual Selection)**

Use the `-i` flag with `docker exec` and pipe your choices:

```bash
echo -e "0\n0\nschool@test.com\n" | docker exec -i laravel_app php artisan voiceflow:simulate --auto-verify
```

This will prompt you step-by-step for:
1. Action (0=login, 1=register)
2. User type (0=school, 1=doctor, 2=health-facility)
3. Email address

---

### **Option 3: Non-Interactive Mode (For Scripts/Automation)**

Provide all required options via command-line flags:

#### **Login Examples:**

**School Login:**
```bash
docker exec laravel_app php artisan voiceflow:simulate \
  --action=login \
  --type=school \
  --email=school@test.com \
  --auto-verify
```

**Doctor Login:**
```bash
docker exec laravel_app php artisan voiceflow:simulate \
  --action=login \
  --type=doctor \
  --email=doctor@test.com \
  --auto-verify
```

**Health Facility Login:**
```bash
docker exec laravel_app php artisan voiceflow:simulate \
  --action=login \
  --type=health-facility \
  --email=facility@test.com \
  --auto-verify
```

**Without Auto-Verify (manual OTP entry):**
```bash
docker exec laravel_app php artisan voiceflow:simulate \
  --action=login \
  --type=school \
  --email=school@test.com
```

#### **Registration Example:**
```bash
docker exec laravel_app php artisan voiceflow:simulate \
  --action=register \
  --type=school
```

---

## 📋 Command Options

| Option | Required | Values | Description |
|--------|----------|--------|-------------|
| `--action` | Yes* | `login`, `register` | Action to perform (*required in non-interactive mode) |
| `--type` | For login | `school`, `doctor`, `health-facility` | Type of user account |
| `--email` | For login | Valid email | Email address to test |
| `--auto-verify` | No | N/A | Automatically verify OTP after sending |

---

## 📝 Output Examples

### Successful Login:
```
🎤 VoiceFlow Simulation
==============================
✅ Server is running
📧 Testing school login for: school@test.com
📤 Sending OTP...
✅ OTP sent successfully!
🔐 OTP Code: 123456
🔍 Auto-verifying OTP...
✅ OTP verified successfully!
🔗 Login URL: http://localhost/auth/login/ABC123...
🎉 VoiceFlow login simulation completed!
```

### Error (No Options in Non-Interactive Mode):
```
🎤 VoiceFlow Simulation
==============================
✅ Server is running
❌ Unable to get input in non-interactive mode.
💡 For interactive mode, use: docker exec -it laravel_app php artisan voiceflow:simulate
💡 Or provide options for non-interactive mode:
  Login:    docker exec laravel_app php artisan voiceflow:simulate --action=login --type=school --email=school@test.com --auto-verify
```

---

## 🔧 How It Works

1. **Environment Detection**: Automatically detects if running in Docker and uses the correct base URL
   - Docker: `http://localhost:80`
   - Local: `http://localhost:8000`

2. **Server Check**: Verifies the Laravel server is running before proceeding

3. **OTP Generation**: Sends OTP via the VoiceFlow API endpoint

4. **Auto-Verification**: Optionally auto-verifies the OTP and returns login URL

5. **Database Query**: Retrieves the OTP directly from the database for testing

---

## 💡 Tips

- Always use `-it` flags for interactive mode: `docker exec -it`
- Use `--auto-verify` for quick testing
- Check `--help` for all available options: `docker exec laravel_app php artisan voiceflow:simulate --help`
- The OTP code is retrieved directly from the database for testing purposes
- Login URLs are single-use tokens for accessing the dashboard

---

## 🐛 Troubleshooting

**Error: "Unable to get input in non-interactive mode"**
- Solution: Add `-it` flags OR provide all required options (`--action`, `--type`, `--email`)

**Error: "Laravel server is not running"**
- Solution: Make sure the Docker container is running: `docker ps | grep laravel_app`

**Error: "Email is not associated with any [type]"**
- Solution: Verify the email exists in the database for the specified user type
- Check with: `docker exec laravel_app php artisan tinker --execute="App\Models\School::pluck('email')"`

---

## 📚 Related Commands

```bash
# List all schools
docker exec laravel_app php artisan tinker --execute="App\Models\School::all(['id', 'name', 'email'])"

# List all doctors
docker exec laravel_app php artisan tinker --execute="App\Models\Doctor::all(['id', 'name', 'email'])"

# List all health facilities
docker exec laravel_app php artisan tinker --execute="App\Models\HealthFacility::all(['id', 'name', 'email'])"

# Check OTPs in database
docker exec laravel_app php artisan tinker --execute="DB::table('otps')->latest()->limit(5)->get()"
```
