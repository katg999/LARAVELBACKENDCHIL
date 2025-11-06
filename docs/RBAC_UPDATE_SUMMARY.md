# RBAC Update Summary - Personal Email Authentication

## What Changed

### ❌ REMOVED: Entity Email Login
- Health facilities and schools **NO LONGER** login with their entity emails (e.g., `hospital@example.com`)
- VoiceFlow OTP login tokens for health facilities/schools are blocked at `OneTimeLoginController`
- Removed `getOrCreateUserForHealthFacility()` and `getOrCreateUserForSchool()` methods

### ✅ ADDED: Personal Email System

#### 1. **Initial Admin Setup Flow**
- New controller: `InitialAdminSetupController`
- New routes:
  - `GET /initial-admin-setup/health-facility/{facilityId}`
  - `POST /initial-admin-setup/health-facility/{facilityId}`
  - `GET /initial-admin-setup/school/{schoolId}`
  - `POST /initial-admin-setup/school/{schoolId}`
- New views:
  - `resources/views/initial-admin-setup/health-facility.blade.php`
  - `resources/views/initial-admin-setup/school.blade.php`

#### 2. **Updated VoiceFlow OTP Flow**
- `OtpController::verifyOtp()` (schools):
  - Checks if admin exists
  - If no admin: Returns `setup_url` for initial admin setup
  - If admin exists: Directs to login page
  
- `OtpController::verifyHealthFacilityOtp()` (health facilities):
  - Same logic as schools
  - Returns `setup_url` or login redirect

#### 3. **Invitation System** (Already Correct)
- `HealthFacilityInvitationController` - Creates Users with personal emails ✅
- `SchoolInvitationController` - Creates Users with personal emails ✅
- Both properly assign roles and link to entities ✅

## How It Works Now

### Flow 1: First-Time Setup
```
1. VoiceFlow OTP → Verify entity email (hospital@example.com)
2. OTP Response → Returns setup_url
3. Admin Setup → Create account with PERSONAL email (john@gmail.com)
4. Auto-login → Redirected to dashboard
5. Admin Role → Assigned health-facility-admin or school-admin
```

### Flow 2: Inviting Staff
```
1. Admin → Sends invitation to staff personal email (mary@gmail.com)
2. Staff → Clicks invitation link
3. Staff → Creates account with THEIR email (pre-filled from invitation)
4. Role Assigned → Gets role specified by admin
5. Auto-login → Redirected to dashboard
```

### Flow 3: Subsequent Logins
```
1. Staff → Uses personal email to login (/login)
2. Laravel Auth → Validates credentials
3. Middleware → Checks roles
4. Dashboard → Access granted based on permissions
```

## Files Modified

### Controllers
1. ✅ `/app/Http/Controllers/OneTimeLoginController.php`
   - Blocked health_facility and school login attempts
   - Removed User creation methods
   - Added error message directing to invitation system

2. ✅ `/app/Http/Controllers/OtpController.php`
   - Updated `verifyOtp()` to return `setup_url` for schools
   - Updated `verifyHealthFacilityOtp()` to return `setup_url`
   - Checks for existing admin before allowing setup

3. ✅ `/app/Http/Controllers/InitialAdminSetupController.php` (NEW)
   - `showHealthFacilityForm()` - Display admin setup form
   - `createHealthFacilityAdmin()` - Create admin with personal email
   - `showSchoolForm()` - Display admin setup form
   - `createSchoolAdmin()` - Create admin with personal email

### Routes
✅ `/routes/web.php`
- Added 4 new routes for initial admin setup

### Views
✅ `/resources/views/initial-admin-setup/health-facility.blade.php` (NEW)
✅ `/resources/views/initial-admin-setup/school.blade.php` (NEW)

### Documentation
✅ `/RBAC_PERSONAL_EMAILS.md` (NEW) - Comprehensive guide
✅ `/VOICEFLOW_RBAC_INTEGRATION.md` (OUTDATED - needs update)

## Testing Required

### 1. VoiceFlow OTP Flow
- [ ] Send OTP to health facility email
- [ ] Verify OTP returns `setup_url`
- [ ] Visit setup URL shows form
- [ ] Submit form creates admin User with personal email
- [ ] Admin is logged in and has correct role
- [ ] Try VoiceFlow OTP again - should direct to login

### 2. Invitation Flow
- [ ] Admin can access `/health-facility/staff/manage`
- [ ] Admin can send invitation to personal email
- [ ] Staff clicks invitation link
- [ ] Staff creates account successfully
- [ ] Staff is logged in with correct role
- [ ] Staff can access dashboard

### 3. Login Flow
- [ ] Staff can login with personal email at `/login`
- [ ] Dashboard access works
- [ ] Role-based permissions enforced
- [ ] Entity email login is blocked

### 4. Edge Cases
- [ ] Duplicate email handling (invitation for existing User)
- [ ] Expired invitation handling
- [ ] Admin trying to remove themselves
- [ ] User with multiple roles (works at school AND facility)

## API Response Changes

### OLD: VoiceFlow Health Facility OTP Verify
```json
{
  "success": true,
  "message": "OTP verified successfully. Use the login link to access your dashboard.",
  "login_url": "http://localhost:8000/auth/login/abc123...",
  "health_facility_id": 1,
  "health_facility_name": "Central Hospital"
}
```

### NEW: VoiceFlow Health Facility OTP Verify (No Admin)
```json
{
  "success": true,
  "action": "setup_admin",
  "message": "OTP verified successfully. Please set up your admin account using your personal email.",
  "setup_url": "http://localhost:8000/initial-admin-setup/health-facility/1",
  "health_facility_id": 1,
  "health_facility_name": "Central Hospital"
}
```

### NEW: VoiceFlow Health Facility OTP Verify (Admin Exists)
```json
{
  "success": true,
  "action": "redirect_to_login",
  "message": "Your facility already has an admin. Please contact your admin for an invitation link, or login if you already have an account.",
  "health_facility_id": 1,
  "health_facility_name": "Central Hospital"
}
```

## Migration Path

### For New Deployments
1. Everything works out of the box
2. First VoiceFlow OTP creates first admin
3. Admin invites staff
4. Staff use personal emails

### For Existing Deployments
1. **Identify entity-email User records:**
   ```bash
   docker compose exec app php artisan tinker
   ```
   ```php
   $entityUsers = User::whereIn('email', HealthFacility::pluck('email'))
       ->orWhereIn('email', School::pluck('email'))
       ->get();
   ```

2. **Create proper admin accounts:**
   - Contact each entity
   - Have them do VoiceFlow OTP + initial setup
   - OR manually create accounts with personal emails

3. **Delete old entity-email accounts:**
   ```php
   foreach ($entityUsers as $user) {
       $user->delete(); // After confirming new admins exist
   }
   ```

## Security Improvements

✅ **Individual Accountability** - Each staff has own login
✅ **Proper Password Management** - Staff manage own passwords
✅ **Role-Based Access** - Medical personnel can't manage staff
✅ **Audit Trail** - Track who performed each action
✅ **Entity Email Security** - Only used for verification

## Next Steps

1. ✅ Code changes complete
2. ⏳ Update VoiceFlow flows to handle new response format
3. ⏳ Test all flows thoroughly
4. ⏳ Update client-side code to handle `action` field
5. ⏳ Migrate existing entity-email User records
6. ⏳ Update documentation (VOICEFLOW_RBAC_INTEGRATION.md)

## Breaking Changes

⚠️ **VoiceFlow Integration**
- Response format changed (added `action` and `setup_url`)
- Client code must handle different response types
- No more direct dashboard login for entities

⚠️ **Existing Logins**
- Entity email logins will fail
- Users must accept invitations or do initial setup
- Migration required for existing deployments

## Benefits

🎯 **Clear Separation**: Entity email vs. personal email
🎯 **Proper RBAC**: Each person has correct permissions  
🎯 **Better Security**: No shared credentials
🎯 **Audit Trail**: Track individual actions
🎯 **Scalability**: Easy to add/remove staff members
