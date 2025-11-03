# RBAC Implementation for Health Facilities and Schools

## Overview
This implementation adds Role-Based Access Control (RBAC) for both health facilities and schools, allowing:
- **Health Facility Admin**: Full access including staff management
- **Health Facility Medical Personnel**: Access to patient care features
- **School Admin**: Full access including staff management  
- **School Staff**: Access to school management features

## What Was Implemented

### 1. Database Migrations
Created the following migrations in `database/migrations/`:

- **`2025_11_03_000001_add_entity_relations_to_users_table.php`**
  - Adds `school_id` and `health_facility_id` columns to users table
  - Links users to their respective organizations

- **`2025_11_03_000002_create_health_facility_invitations_table.php`**
  - Stores invitation data for health facility staff
  - Tracks invitation status, expiration, and acceptance

- **`2025_11_03_000003_create_school_invitations_table.php`**
  - Stores invitation data for school staff
  - Same structure as health facility invitations

### 2. Models
Created/Updated the following models:

- **`app/Models/HealthFacilityInvitation.php`**
  - Manages health facility invitation lifecycle
  - Methods: `generateToken()`, `isExpired()`, `isValid()`

- **`app/Models/SchoolInvitation.php`**
  - Manages school invitation lifecycle
  - Same methods as HealthFacilityInvitation

- **Updated `app/Models/HealthFacility.php`**
  - Added relationships: `users()`, `invitations()`, `pendingInvitations()`, `admins()`, `medicalPersonnel()`

- **Updated `app/Models/School.php`**
  - Added relationships: `users()`, `invitations()`, `pendingInvitations()`, `admins()`, `staff()`

- **Updated `app/User.php`**
  - Added `school_id` and `health_facility_id` to fillable
  - Added relationships: `school()`, `healthFacility()`

### 3. Controllers
Created the following controllers:

- **`app/Http/Controllers/HealthFacilityInvitationController.php`**
  - `index()` - View staff and invitations
  - `sendInvitation()` - Send invitation to new staff
  - `showAcceptForm()` - Display invitation acceptance form
  - `acceptInvitation()` - Process invitation acceptance
  - `revokeInvitation()` - Cancel pending invitation
  - `removeStaffMember()` - Remove staff member

- **`app/Http/Controllers/SchoolInvitationController.php`**
  - Same methods as HealthFacilityInvitationController for schools

### 4. Routes
Added the following routes in `routes/web.php`:

**Health Facility Routes:**
```php
// Public invitation acceptance
GET  /health-facility/invitation/accept/{token}
POST /health-facility/invitation/accept/{token}

// Admin-only staff management (requires health-facility-admin role)
GET    /health-facility/staff/manage
POST   /health-facility/staff/invite
DELETE /health-facility/staff/invitation/{id}
DELETE /health-facility/staff/{userId}
```

**School Routes:**
```php
// Public invitation acceptance
GET  /school/invitation/accept/{token}
POST /school/invitation/accept/{token}

// Admin-only staff management (requires school-admin role)
GET    /school/staff/manage
POST   /school/staff/invite
DELETE /school/staff/invitation/{id}
DELETE /school/staff/{userId}
```

### 5. Updated RBAC Seeder
Updated `database/seeders/RbacSeeder.php` with new roles and permissions:

**New Permissions:**
- `manage-school-staff` - For school admins to manage staff
- `manage-health-facility-staff` - For health facility admins to manage staff

**New/Updated Roles:**
- `school-admin` - Full school access + staff management
- `school-staff` - School features without staff management
- `health-facility-admin` - Full health facility access + staff management
- `health-facility-medical-personnel` - Patient care features without staff management

### 6. Middleware Update
Updated `app/Http/Middleware/RoleMiddleware.php`:
- Now supports multiple roles per route (comma-separated)
- Checks if user has ANY of the specified roles

### 7. Views
Created the following views:

**Health Facility Views:**
- `resources/views/health-facility/staff/index.blade.php` - Staff management dashboard
- `resources/views/health-facility/invitations/accept.blade.php` - Accept invitation form

**School Views:**
- `resources/views/school/staff/index.blade.php` - Staff management dashboard
- `resources/views/school/invitations/accept.blade.php` - Accept invitation form

**Shared Views:**
- `resources/views/invitations/expired.blade.php` - Expired invitation message
- `resources/views/invitations/already-accepted.blade.php` - Already accepted message

## How to Use

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Seed RBAC Data
```bash
php artisan db:seed --class=RbacSeeder
```

### 3. For Health Facility Admins

1. Log in with a health-facility-admin role
2. Navigate to the staff management page
3. Click "Send Invitation"
4. Enter the email and select role:
   - **Admin** - Can manage other staff members
   - **Medical Personnel** - Can access patient care features
5. Copy the invitation link and send it to the invitee
6. The invitee clicks the link, creates their account, and is automatically assigned to your health facility

### 4. For School Admins

1. Log in with a school-admin role
2. Navigate to the staff management page
3. Click "Send Invitation"
4. Enter the email and select role:
   - **Admin** - Can manage other staff members
   - **Staff** - Can access school features
5. Copy the invitation link and send it to the invitee
6. The invitee clicks the link, creates their account, and is automatically assigned to your school

## Key Features

### Security
- Invitations expire after 7 days
- Unique tokens prevent unauthorized access
- Only admins can send invitations
- Users cannot remove themselves
- Role-based access control enforced throughout

### Flexibility
- Supports multiple roles per organization
- Can have both admin and regular staff
- Tracks who sent invitations
- Shows invitation history

### User Experience
- Clear role descriptions
- Visual status indicators (pending, expired, accepted)
- Confirmation dialogs for destructive actions
- Success/error messages for all operations

## Next Steps (Optional Enhancements)

1. **Email Integration**: Connect the invitation system to actual email sending (currently shows link in success message)
2. **Resend Invitations**: Add ability to resend expired invitations
3. **Bulk Invitations**: Allow sending multiple invitations at once
4. **Role Updates**: Allow admins to change existing staff member roles
5. **Activity Logs**: Track staff actions and changes
6. **Permissions Granularity**: Add more fine-grained permissions for specific features

## Testing

To test the implementation:

1. Create a test health facility/school in the database
2. Create a test user and assign them the admin role for that facility
3. Log in as that user
4. Access the staff management page
5. Send an invitation
6. Open the invitation link in an incognito window
7. Complete the signup process
8. Verify the new user is added to the staff list

## Important Notes

- The `health-facility-staff` role still exists for backward compatibility but new facilities should use `health-facility-admin` or `health-facility-medical-personnel`
- Similarly, schools should use `school-admin` or `school-staff` instead of the generic `school-staff` role
- Update any existing middleware route definitions to use the new role names
- The invitation token is stored in plain text - consider hashing if security is critical
