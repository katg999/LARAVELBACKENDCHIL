# RBAC with Admin-Initiated Invitations

## Overview

Health facilities and schools **DO NOT** create their own admin accounts. Instead, the **system administrator** sends invitation links to create the initial admin user.

### Key Principles:
- ✅ **Entity emails** → VoiceFlow OTP verification ONLY (proves ownership)
- ✅ **Personal emails** → Used for all staff dashboard access
- ✅ **System admin** → Sends first invitation to create initial entity admin
- ✅ **Entity admin** → Can then invite additional staff members

## Complete Authentication Flow

### Step 1: Entity Registration & Verification

```
1. Health Facility/School → Registers in system
   - Entity email: hospital@example.com
   - Entity details: name, contact, etc.

2. VoiceFlow OTP Verification (Optional)
   POST /api/voiceflow/send-login-otp
   Body: { "email": "hospital@example.com" }
   
   POST /api/voiceflow/verify-health-facility-otp
   Body: { "email": "hospital@example.com", "otp": "123456" }
   
   Response:
   {
     "success": true,
     "action": "contact_admin",
     "message": "OTP verified. Contact administrator for invitation link.",
     "note": "Entity emails are only for verification. Use personal emails for access."
   }

3. Entity Owner → Contacts system administrator
   - Provides proof of ownership (OTP verified or other means)
   - Requests access to the system
```

### Step 2: System Admin Sends Initial Invitation

```
1. System Admin Logs In
   - URL: /admin/login
   - Credentials: System admin email + password

2. Admin Navigates to Entity Management
   - Health Facilities: /admin/health-facilities
   - Schools: /admin/schools

3. Admin Sends First Invitation
   POST /health-facility/staff/invite
   Headers: { Authorization: "Bearer {admin-token}" }
   Body: {
     "email": "john.smith@gmail.com",  ← Personal email of facility owner
     "role": "health-facility-admin",
     "health_facility_id": 1            ← Required for super admin
   }
   
   Creates invitation with token
   
4. Invitation Email Sent
   - Sent to: john.smith@gmail.com (personal email)
   - Contains: Invitation link with token
   - Link: https://app.com/health-facility/invitation/accept/abc123xyz
```

### Step 3: Initial Admin Accepts Invitation

```
1. Recipient Clicks Invitation Link
   GET /health-facility/invitation/accept/abc123xyz
   
2. Invitation Acceptance Form
   - Name: "Dr. John Smith"
   - Email: john.smith@gmail.com (pre-filled from invitation)
   - Password: ******** (choose password)
   - Password Confirmation: ********

3. User Account Created
   POST /health-facility/invitation/accept/abc123xyz
   Body: {
     "name": "Dr. John Smith",
     "password": "password123",
     "password_confirmation": "password123"
   }
   
   Creates:
   - User record with personal email
   - Assigned role: health-facility-admin
   - Linked to: health_facility_id from invitation
   - Auto-login after creation

4. Redirected to Dashboard
   - URL: /health-facility/dashboard
   - Now has full admin access
```

### Step 4: Entity Admin Invites Additional Staff

```
1. Entity Admin (John) Sends Invitations
   POST /health-facility/staff/invite
   Body: {
     "email": "nurse.mary@gmail.com",  ← Personal email
     "role": "health-facility-medical-personnel"
     # health_facility_id NOT required (uses admin's facility)
   }

2. Staff Member Accepts Invitation
   - Same flow as Step 3
   - Creates User with personal email
   - Assigns specified role
   - Links to same health facility

3. Staff Can Login
   - URL: /login
   - Email: nurse.mary@gmail.com (personal)
   - Password: Their chosen password
```

### Step 5: Ongoing Authentication

```
Everyone logs in with their PERSONAL email:

1. Visit /login

2. Enter Credentials
   - Email: john.smith@gmail.com (NOT hospital@example.com)
   - Password: password123

3. Laravel Auth Validates
   - Checks users table
   - Verifies password
   - Checks roles

4. Dashboard Access
   - Redirected based on role
   - Full RBAC enforcement
   - Actions logged with user ID
```

## Key Differences from Old System

| Aspect | ❌ Old (Self-Setup) | ✅ New (Admin-Initiated) |
|--------|---------------------|--------------------------|
| **Initial Admin** | Self-registers after OTP | Invited by system admin |
| **VoiceFlow OTP** | Creates User account | Only verifies ownership |
| **Entity Email** | Used for login | Never used for login |
| **Personal Email** | Optional | Required for all staff |
| **Setup Process** | Self-serve (/initial-admin-setup) | Admin-controlled |
| **Security** | Less control | Admin approves all access |
| **Audit Trail** | From first login | From invitation creation |

## System Administrator Actions

### Prerequisites
- System admin must have 'admin' role
- Access to admin dashboard at /admin

### Send Initial Health Facility Admin Invitation

```bash
# Via Admin Dashboard UI
1. Login to /admin
2. Navigate to Health Facilities
3. Select facility
4. Click "Send Invitation"
5. Enter:
   - Personal email of facility owner
   - Role: health-facility-admin
6. Click "Send"

# Or via API (for automation)
curl -X POST http://localhost:8000/health-facility/staff/invite \
  -H "Authorization: Bearer {admin-token}" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@personal-email.com",
    "role": "health-facility-admin",
    "health_facility_id": 1
  }'
```

### Send Initial School Admin Invitation

```bash
# Via Admin Dashboard UI
1. Login to /admin
2. Navigate to Schools
3. Select school
4. Click "Send Invitation"
5. Enter:
   - Personal email of school administrator
   - Role: school-admin
6. Click "Send"

# Or via API
curl -X POST http://localhost:8000/school/staff/invite \
  -H "Authorization: Bearer {admin-token}" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@personal-email.com",
    "role": "school-admin",
    "school_id": 1
  }'
```

## Invitation System

### Invitation Flow
```
1. Invitation Created
   - Token generated (unique, expires in 7 days)
   - Email specified (personal email)
   - Role specified
   - Entity linked (health_facility_id or school_id)
   - Invited by tracked

2. Invitation Sent
   - Email to personal address
   - Contains unique link with token
   - Expires in 7 days

3. Invitation Accepted
   - User clicks link
   - Fills form (name, password)
   - Email pre-filled from invitation
   - User account created
   - Role assigned automatically
   - Entity linked automatically

4. Invitation Marked Used
   - Cannot be reused
   - Tracks who accepted
   - Records acceptance date
```

### Invitation Security
- ✅ Tokens are unique and random
- ✅ Invitations expire after 7 days
- ✅ Can only be used once
- ✅ Email is locked to invitation (no changes)
- ✅ Role is locked to invitation
- ✅ Entity is locked to invitation
- ✅ Admins can revoke unused invitations

## API Endpoints

### VoiceFlow Endpoints (Verification Only)
```
POST /api/voiceflow/send-login-otp
Body: { "email": "entity@email.com" }
Response: { "success": true, "message": "OTP sent" }

POST /api/voiceflow/verify-health-facility-otp
Body: { "email": "entity@email.com", "otp": "123456" }
Response: { 
  "success": true, 
  "action": "contact_admin",
  "message": "Contact administrator for invitation"
}

POST /api/voiceflow/verify-otp (schools)
Response: Same as health facility
```

### Invitation Endpoints

#### Send Invitation (Super Admin or Entity Admin)
```
POST /health-facility/staff/invite
Headers: { Authorization: "Bearer {token}" }
Body: {
  "email": "personal@email.com",
  "role": "health-facility-admin|health-facility-medical-personnel",
  "health_facility_id": 1  // Required for super admin, optional for entity admin
}

POST /school/staff/invite
Headers: { Authorization: "Bearer {token}" }
Body: {
  "email": "personal@email.com",
  "role": "school-admin|school-staff",
  "school_id": 1  // Required for super admin, optional for entity admin
}
```

#### Accept Invitation (Public)
```
GET /health-facility/invitation/accept/{token}
Returns: Form to create account

POST /health-facility/invitation/accept/{token}
Body: {
  "name": "Full Name",
  "password": "password123",
  "password_confirmation": "password123"
}
Response: Auto-login and redirect to dashboard

GET /school/invitation/accept/{token}
POST /school/invitation/accept/{token}
Same as health facility
```

#### Manage Invitations (Entity Admin)
```
GET /health-facility/staff/manage
Returns: List of staff and pending invitations

DELETE /health-facility/staff/invitation/{id}
Revokes pending invitation

DELETE /health-facility/staff/{userId}
Removes staff member

Same endpoints for schools
```

## Database Schema

### health_facility_invitations Table
```sql
CREATE TABLE health_facility_invitations (
    id BIGINT PRIMARY KEY,
    health_facility_id BIGINT NOT NULL,
    email VARCHAR(255) NOT NULL,      -- Personal email
    role VARCHAR(255) NOT NULL,        -- Role to assign
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    invited_by BIGINT NOT NULL,        -- User ID of inviter
    accepted BOOLEAN DEFAULT false,
    accepted_by BIGINT NULLABLE,       -- User ID who accepted
    accepted_at TIMESTAMP NULLABLE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (health_facility_id) REFERENCES health_facilities(id),
    FOREIGN KEY (invited_by) REFERENCES users(id),
    FOREIGN KEY (accepted_by) REFERENCES users(id)
);
```

### school_invitations Table
```sql
-- Same structure as health_facility_invitations
-- but with school_id instead of health_facility_id
```

## Testing the Flow

### Test Complete Flow in Docker

```bash
# 1. Verify health facility ownership via VoiceFlow
docker compose exec app php artisan voiceflow:simulate \
  --type=health-facility \
  --email=clinic@test.com \
  --action=login \
  --auto-verify

# Expected: Message to contact administrator

# 2. Login as system admin (setup admin user first if needed)
# Visit: http://localhost:8000/admin/login

# 3. Send invitation to personal email
docker compose exec app php artisan tinker
```

```php
// In tinker
$admin = App\User::where('email', 'admin@system.com')->first();
$facility = App\Models\HealthFacility::first();

// Create invitation
$invitation = App\Models\HealthFacilityInvitation::create([
    'health_facility_id' => $facility->id,
    'email' => 'john.smith@gmail.com',  // Personal email
    'role' => 'health-facility-admin',
    'token' => App\Models\HealthFacilityInvitation::generateToken(),
    'expires_at' => now()->addDays(7),
    'invited_by' => $admin->id,
]);

echo 'Invitation URL: ' . url('/health-facility/invitation/accept/' . $invitation->token);
```

```bash
# 4. Visit invitation URL, fill form, create account

# 5. Login with personal email at /login
```

## Summary

### ✅ Correct Flow
1. **Entity registers** → Entity email recorded
2. **VoiceFlow OTP** → Verifies ownership (optional)
3. **Entity contacts admin** → Requests access
4. **System admin sends invitation** → To personal email
5. **Recipient accepts invitation** → Creates User account
6. **Login with personal email** → Dashboard access

### ❌ Incorrect Flow (Blocked)
1. ~~Entity does VoiceFlow OTP~~
2. ~~System creates User with entity email~~
3. ~~Entity logs in with entity email~~

### 🎯 Key Benefits
- ✅ **Admin control**: System admin approves all access
- ✅ **Security**: Personal emails, individual passwords
- ✅ **Accountability**: Know who invited whom
- ✅ **Audit trail**: Complete history from invitation
- ✅ **Flexibility**: Admins control roles and permissions
- ✅ **Scalability**: Each staff member has own account

This system provides proper administrative oversight while maintaining the convenience of VoiceFlow OTP for entity ownership verification.
