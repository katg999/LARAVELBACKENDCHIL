# VoiceFlow RBAC Integration

## Overview
The VoiceFlow authentication system has been updated to work seamlessly with the new RBAC (Role-Based Access Control) system for both health facilities and schools.

## What Changed

### Updated Files
- `app/Http/Controllers/OneTimeLoginController.php`

### Key Updates

#### 1. **Health Facility Authentication** (`getOrCreateUserForHealthFacility`)
When a health facility logs in via VoiceFlow for the first time:
- A `User` record is created/updated
- The user is linked to the health facility via `health_facility_id`
- **Automatically assigned `health-facility-admin` role** on first login
- This allows them to manage the facility and invite other staff

#### 2. **School Authentication** (`getOrCreateUserForSchool`)
When a school logs in via VoiceFlow for the first time:
- A `User` record is created/updated  
- The user is linked to the school via `school_id`
- **Automatically assigned `school-admin` role** on first login
- This allows them to manage the school and invite other staff

#### 3. **Enhanced Session Management**
- Both health facilities and schools now use Laravel's Auth system
- Proper authentication through `Auth::login($user)`
- Maintains backward compatibility with session-based auth
- 12-hour session lifetime

## How It Works

### First-Time Login Flow

1. **User requests OTP via VoiceFlow**
   - Endpoint: `/api/voiceflow/send-login-otp`
   - Email is validated against schools, health facilities, or doctors

2. **User verifies OTP**
   - Endpoint: `/api/voiceflow/verify-otp` (schools)
   - Endpoint: `/api/voiceflow/verify-health-facility-otp` (health facilities)
   - Endpoint: `/api/voiceflow/verify-doctor-otp` (doctors)
   - Returns a one-time login URL

3. **User clicks login link**
   - Endpoint: `/auth/login/{token}`
   - **For Health Facilities:**
     - User record created with `health_facility_id`
     - Assigned `health-facility-admin` role
     - Logged in via Laravel Auth
     - Redirected to `/health-facility/dashboard`
   
   - **For Schools:**
     - User record created with `school_id`
     - Assigned `school-admin` role
     - Logged in via Laravel Auth
     - Redirected to `/school-dashboard`
   
   - **For Doctors:**
     - Session-based auth (no User record)
     - Redirected to `/doctor/dashboard`

### Subsequent Logins

When the same email logs in again:
- Existing `User` record is found
- User is linked to the entity if not already linked
- **Existing roles are preserved** (no role changes)
- User is logged in with their current permissions

## Role Assignment Logic

### Health Facilities
```php
// First-time login
if (!$user->hasRole('health-facility-admin') && 
    !$user->hasRole('health-facility-medical-personnel') &&
    !$user->hasRole('health-facility-staff')) {
    $user->assignRole('health-facility-admin'); // Default to admin
}

// Subsequent logins - no role changes
```

### Schools
```php
// First-time login
if (!$user->hasRole('school-admin') && !$user->hasRole('school-staff')) {
    $user->assignRole('school-admin'); // Default to admin
}

// Subsequent logins - no role changes
```

## Benefits

### 1. **Automatic Admin Assignment**
- First user logging in via VoiceFlow becomes an admin
- They can immediately access staff management features
- Can invite additional admins or regular staff

### 2. **Seamless Integration**
- Works with existing VoiceFlow flows
- No changes needed to VoiceFlow configuration
- Backward compatible with existing sessions

### 3. **Security**
- Uses Laravel's built-in authentication
- Role-based access control enforced
- One-time login tokens expire after 10 minutes

### 4. **Flexibility**
- Admins can invite users with specific roles
- Invited users get appropriate permissions
- VoiceFlow users start as admins, invited users get assigned roles

## Testing VoiceFlow RBAC Integration

### Using Docker

```bash
# Test health facility login
docker compose exec app php artisan voiceflow:simulate \
  --type=health-facility \
  --email=facility@test.com \
  --action=login \
  --auto-verify

# Test school login
docker compose exec app php artisan voiceflow:simulate \
  --type=school \
  --email=school@test.com \
  --action=login \
  --auto-verify
```

### Verify Role Assignment

After logging in via VoiceFlow, verify the user and role were created:

```bash
# Check if user was created
docker compose exec db psql -U laravel_user -d laravel_db -c \
  "SELECT id, name, email, school_id, health_facility_id FROM users WHERE email='facility@test.com';"

# Check assigned roles
docker compose exec db psql -U laravel_user -d laravel_db -c \
  "SELECT u.email, r.name, r.slug 
   FROM users u 
   JOIN role_user ru ON u.id = ru.user_id 
   JOIN roles r ON ru.role_id = r.id 
   WHERE u.email='facility@test.com';"
```

### Test Staff Management

1. Log in via VoiceFlow (becomes admin)
2. Navigate to staff management:
   - Health Facility: `/health-facility/staff/manage`
   - School: `/school/staff/manage`
3. Send invitation to another email
4. Accept invitation and verify role assignment

## Differences from Manual User Creation

| Aspect | VoiceFlow Login | Invitation System |
|--------|----------------|-------------------|
| **First User** | Automatically admin | Invited by existing admin |
| **Role Selection** | Always admin | Chosen by inviter |
| **User Creation** | Automatic on first login | Explicit invitation |
| **Password** | Random (OTP-based) | Set by invitee |
| **Use Case** | Initial facility/school setup | Adding team members |

## Migration Path

### For Existing VoiceFlow Users

If you have users who logged in via VoiceFlow before the RBAC update:

1. **They will be automatically upgraded on next login:**
   - User record linked to their entity
   - Assigned admin role (if no role exists)
   - Full access to staff management

2. **No action needed** - it happens automatically

### For New Deployments

1. Health facility/school logs in via VoiceFlow
2. Becomes admin automatically
3. Can immediately invite other staff members
4. Each invited user gets appropriate role

## Troubleshooting

### User has no role after VoiceFlow login

Check logs:
```bash
docker compose logs app | grep "Assigned.*role"
```

Manually assign role:
```bash
docker compose exec app php artisan tinker
```

```php
$user = App\User::where('email', 'user@test.com')->first();
$user->assignRole('health-facility-admin');
// or
$user->assignRole('school-admin');
```

### User linked to wrong entity

```php
$user = App\User::where('email', 'user@test.com')->first();
$user->health_facility_id = 1; // Correct ID
$user->save();
```

### Multiple roles assigned

This is normal - users can have multiple roles if they work at multiple entities or were invited to different roles.

## API Response Examples

### Successful VoiceFlow Login (Health Facility)

```json
{
  "success": true,
  "message": "OTP verified successfully. Use the login link to access your dashboard.",
  "login_url": "http://localhost:8000/auth/login/abc123xyz...",
  "health_facility_id": 1,
  "health_facility_name": "Central Hospital"
}
```

After clicking the login link:
- User record created with `health_facility_id: 1`
- Assigned `health-facility-admin` role
- Redirected to `/health-facility/dashboard`
- Can access `/health-facility/staff/manage`

## Summary

✅ **VoiceFlow authentication now fully integrated with RBAC**
✅ **First-time logins automatically become admins**
✅ **Admins can invite additional staff with specific roles**
✅ **Backward compatible with existing sessions**
✅ **Works seamlessly in Docker environment**

The VoiceFlow integration provides a smooth onboarding experience where the first user from an organization becomes an admin and can then invite their team with appropriate permissions.
