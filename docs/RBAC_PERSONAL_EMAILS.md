# RBAC Authentication with Personal Emails

## Overview
Health facilities and schools **NO LONGER** login using their entity email addresses. Instead:

1. **Entity Email** → Used only for VoiceFlow OTP verification (proves ownership)
2. **Personal Emails** → Used by staff members for actual dashboard access

## Why This Change?

### ❌ OLD PROBLEM:
- Entity email (e.g., `hospital@example.com`) was used to create User accounts
- Single User account per health facility/school
- No way to distinguish between different staff members
- Security risk: multiple people sharing the same login credentials

### ✅ NEW SOLUTION:
- Entity email verifies ownership via VoiceFlow OTP
- Each staff member uses their own personal email
- Proper role-based access control (RBAC)
- Audit trail of who did what

## Authentication Flows

### Flow 1: Initial Setup (First-Time Registration)

```
1. Health Facility/School → Registers with entity email (hospital@example.com)
   
2. VoiceFlow OTP Request → Sends OTP to entity email
   POST /api/voiceflow/send-login-otp
   Body: { "email": "hospital@example.com" }

3. VoiceFlow OTP Verification → Verifies entity ownership
   POST /api/voiceflow/verify-health-facility-otp
   Body: { "email": "hospital@example.com", "otp": "123456" }
   
   Response:
   {
     "success": true,
     "action": "setup_admin",
     "setup_url": "https://app.com/initial-admin-setup/health-facility/1",
     "message": "Please set up your admin account using your personal email."
   }

4. Initial Admin Setup → Create first admin with PERSONAL email
   GET /initial-admin-setup/health-facility/1
   
   Form submits:
   - name: "Dr. John Smith"
   - email: "john.smith@gmail.com" ← PERSONAL EMAIL
   - password: "********"
   - password_confirmation: "********"

5. Admin Account Created → Logged in automatically
   - User created with email: john.smith@gmail.com
   - Assigned role: health-facility-admin
   - Linked to health_facility_id: 1
   - Redirected to dashboard

6. Admin Can Now Invite Other Staff
   - Sends invitations to other personal emails
   - Each invitation specifies a role (admin or medical-personnel)
```

### Flow 2: Inviting Additional Staff

```
1. Admin Sends Invitation
   POST /health-facility/staff/invite
   Body: { 
     "email": "nurse.mary@gmail.com",  ← Personal email of staff member
     "role": "health-facility-medical-personnel"
   }
   
   Creates invitation with token

2. Staff Member Receives Invitation Link
   Example: https://app.com/health-facility/invitation/accept/abc123xyz

3. Staff Member Accepts Invitation
   GET /health-facility/invitation/accept/abc123xyz
   
   Form submits:
   - name: "Mary Johnson"
   - password: "********"
   - password_confirmation: "********"
   
   Email is pre-filled from invitation

4. User Account Created
   - User created with email from invitation
   - Assigned role from invitation
   - Linked to health_facility_id from invitation
   - Logged in automatically
   - Redirected to dashboard
```

### Flow 3: Subsequent Logins

```
1. Staff Member Logs In with Personal Email
   GET /login
   
   Form submits:
   - email: "john.smith@gmail.com" ← Personal email
   - password: "********"

2. Laravel Auth Validates Credentials
   - Checks users table
   - Verifies password hash
   - Checks health_facility_id or school_id link

3. Middleware Checks Roles
   - Route middleware: role:health-facility-admin
   - Verifies user has required role
   - Grants/denies access accordingly

4. Dashboard Access Granted
   - User sees dashboard for their linked entity
   - Actions logged with their personal user ID
   - Full audit trail maintained
```

### Flow 4: Entity Already Has Admin (VoiceFlow Attempt)

```
1. Someone Tries VoiceFlow OTP with Entity Email
   POST /api/voiceflow/verify-health-facility-otp
   Body: { "email": "hospital@example.com", "otp": "123456" }

2. System Checks for Existing Admin
   - Finds existing User with health_facility_id and admin role
   
3. Response Directs to Login
   {
     "success": true,
     "action": "redirect_to_login",
     "message": "Your facility already has an admin. Please contact your admin for an invitation link, or login if you already have an account."
   }

4. User Must:
   - Contact existing admin for invitation link, OR
   - Login with their personal email if they already have an account
```

## Key Differences

| Aspect | Old System (Entity Email) | New System (Personal Email) |
|--------|--------------------------|------------------------------|
| **Login Email** | hospital@example.com | john.smith@gmail.com |
| **User Records** | 1 per entity | 1 per staff member |
| **Role Assignment** | Single role per entity | Multiple staff, different roles |
| **Security** | Shared credentials | Individual credentials |
| **Audit Trail** | Entity-level only | Per-user actions |
| **VoiceFlow OTP** | For dashboard login | For ownership verification only |
| **Password Reset** | Single password for all staff | Each staff has own password |
| **Staff Management** | Not possible | Full RBAC with invitations |

## Database Structure

### Users Table
```sql
CREATE TABLE users (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    email VARCHAR(255) UNIQUE,  -- Personal email (john.smith@gmail.com)
    password VARCHAR(255),
    school_id BIGINT NULLABLE,  -- Links to school
    health_facility_id BIGINT NULLABLE,  -- Links to health facility
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Role Assignment
```sql
CREATE TABLE role_user (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,  -- Links to users.id
    role_id BIGINT,  -- Links to roles.id (health-facility-admin, etc.)
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Example Data

**Health Facility:**
- id: 1
- name: "Central Hospital"
- email: "hospital@example.com" ← Only used for VoiceFlow verification

**Users (Staff):**
1. User ID 1:
   - name: "Dr. John Smith"
   - email: "john.smith@gmail.com" ← Personal email for login
   - health_facility_id: 1
   - roles: [health-facility-admin]

2. User ID 2:
   - name: "Nurse Mary Johnson"
   - email: "mary.j@gmail.com" ← Personal email for login
   - health_facility_id: 1
   - roles: [health-facility-medical-personnel]

## Routes

### Public Routes (No Authentication)
```php
// Initial admin setup after VoiceFlow verification
GET  /initial-admin-setup/health-facility/{facilityId}
POST /initial-admin-setup/health-facility/{facilityId}
GET  /initial-admin-setup/school/{schoolId}
POST /initial-admin-setup/school/{schoolId}

// Staff invitation acceptance
GET  /health-facility/invitation/accept/{token}
POST /health-facility/invitation/accept/{token}
GET  /school/invitation/accept/{token}
POST /school/invitation/accept/{token}
```

### Protected Routes (Requires Auth + Role)
```php
// Health facility staff management (admin only)
GET    /health-facility/staff/manage         role:health-facility-admin
POST   /health-facility/staff/invite         role:health-facility-admin
DELETE /health-facility/staff/invitation/{id} role:health-facility-admin
DELETE /health-facility/staff/{userId}       role:health-facility-admin

// Health facility dashboard (all staff)
GET /health-facility/dashboard  role:health-facility-admin,health-facility-medical-personnel
GET /health-facility/patients   role:health-facility-admin,health-facility-medical-personnel
GET /health-facility/lab-tests  role:health-facility-admin,health-facility-medical-personnel
// etc.

// School staff management (admin only)
GET    /school/staff/manage         role:school-admin
POST   /school/staff/invite         role:school-admin
DELETE /school/staff/invitation/{id} role:school-admin
DELETE /school/staff/{userId}       role:school-admin

// School dashboard (all staff)
GET /school-dashboard  role:school-admin,school-staff
GET /school/patients   role:school-admin,school-staff
// etc.
```

## Migration Guide

### For Existing Health Facilities/Schools

If you have entities that used VoiceFlow OTP before this change:

1. **Old User records exist with entity emails**
   - These are now invalid for login
   - Staff must go through invitation process

2. **Migration Steps:**
   ```bash
   # Option 1: Let admins self-register
   # Admin goes to VoiceFlow, verifies entity email, sets up personal account
   
   # Option 2: Manually create admin users
   docker compose exec app php artisan tinker
   ```

   ```php
   // In tinker
   $facility = HealthFacility::find(1);
   $user = User::create([
       'name' => 'Admin Name',
       'email' => 'admin@personal-email.com',
       'password' => Hash::make('password'),
       'health_facility_id' => $facility->id
   ]);
   $user->assignRole('health-facility-admin');
   ```

3. **Cleanup old entity-email User records:**
   ```php
   // Find users with entity emails
   $entityUsers = User::whereIn('email', HealthFacility::pluck('email'))
       ->orWhereIn('email', School::pluck('email'))
       ->get();
   
   // Delete them (after confirming new admins are set up)
   foreach ($entityUsers as $user) {
       $user->delete();
   }
   ```

## Security Considerations

### ✅ Improvements
- Individual accountability (each staff has own login)
- Proper password management (staff can reset own passwords)
- Role-based permissions (medical personnel can't manage staff)
- Audit trail (know exactly who performed each action)
- Entity email stays secure (only used for verification)

### 🔒 Best Practices
1. **Never share personal email logins**
2. **Admin should only invite trusted staff members**
3. **Use strong passwords** (enforced: min 8 characters)
4. **Regularly review staff list** and remove departed staff
5. **Entity email** should be secured (only administrators have access)

## Testing

### Test Initial Setup Flow
```bash
# 1. Send OTP to health facility email
curl -X POST http://localhost:8000/api/voiceflow/send-login-otp \
  -H "Content-Type: application/json" \
  -d '{"email":"facility@test.com"}'

# 2. Verify OTP
curl -X POST http://localhost:8000/api/voiceflow/verify-health-facility-otp \
  -H "Content-Type: application/json" \
  -d '{"email":"facility@test.com","otp":"123456"}'

# Response should include setup_url

# 3. Visit setup URL in browser, create admin with personal email

# 4. Login with personal email
# Visit /login, use personal email and password
```

### Test Invitation Flow
```bash
# 1. Login as admin (use personal email)

# 2. Navigate to /health-facility/staff/manage

# 3. Send invitation to staff member's personal email

# 4. Staff member clicks invitation link

# 5. Staff member creates account with their personal email (pre-filled)

# 6. Staff member can now login with their personal email
```

## Common Issues & Solutions

### Issue: "I can't login with the facility email"
**Solution:** That's correct! Use your personal email that was used when you accepted an invitation or during initial setup.

### Issue: "I'm the first admin, how do I create an account?"
**Solution:** Use VoiceFlow OTP with the facility/school email, then follow the setup link to create your admin account with your personal email.

### Issue: "I lost access to my personal email"
**Solution:** Contact another admin to remove your old account and send a new invitation to your new email address.

### Issue: "VoiceFlow OTP says admin already exists"
**Solution:** This is correct. The first admin has already been set up. Contact them for an invitation link or login if you already have an account.

### Issue: "Can I change my personal email?"
**Solution:** Currently not supported through UI. Contact system admin to manually update the email in the database.

## Summary

✅ **Entity emails** → VoiceFlow OTP verification only
✅ **Personal emails** → Actual dashboard login
✅ **First admin** → Self-registers via initial setup flow
✅ **Additional staff** → Invited by admin with specific roles
✅ **RBAC enforced** → Every action has proper authorization
✅ **Audit trail** → Know exactly who did what

This system provides proper security, accountability, and role-based access control while maintaining the convenience of VoiceFlow OTP for entity verification.
