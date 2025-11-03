<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HealthFacilityInvitation;
use App\Models\HealthFacility;
use App\User;
use App\Models\Role;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Mail\HealthFacilityInvitationMail;

class HealthFacilityInvitationController extends Controller
{
    /**
     * Display a listing of invitations for a health facility
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        if (!$user->hasPermission('manage-health-facility-staff')) {
            abort(403, 'Unauthorized to manage health facility staff');
        }

        $healthFacility = HealthFacility::findOrFail($user->health_facility_id);
        
        $invitations = $healthFacility->invitations()
            ->with('inviter', 'acceptedByUser')
            ->latest()
            ->paginate(15);

        $staffMembers = $healthFacility->users()->with('roles')->get();

        return view('health-facility.staff.index', compact('healthFacility', 'invitations', 'staffMembers'));
    }

    /**
     * Send an invitation to join the health facility
     * Can be called by facility admin OR super admin (for initial setup)
     */
    public function sendInvitation(Request $request)
    {
        $user = $request->user();
        
        \Log::info('Health Facility Invitation: Starting invitation process', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_roles' => $user->roles->pluck('slug')->toArray()
        ]);
        
        // Check if user has permission (existing admin) OR is a super admin
        $isSuperAdmin = $user->hasRole('admin'); // System admin role
        $hasPermission = $user->hasPermission('manage-health-facility-staff');
        
        \Log::info('Health Facility Invitation: Permission check', [
            'is_super_admin' => $isSuperAdmin,
            'has_permission' => $hasPermission
        ]);
        
        if (!$hasPermission && !$isSuperAdmin) {
            \Log::warning('Health Facility Invitation: Unauthorized access attempt', ['user_id' => $user->id]);
            abort(403, 'Unauthorized to manage health facility staff');
        }

        $validated = $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:health-facility-admin,health-facility-medical-personnel',
            'health_facility_id' => $isSuperAdmin ? 'required|exists:health_facilities,id' : 'nullable',
        ]);
        
        \Log::info('Health Facility Invitation: Validation passed', $validated);

        // Get health facility ID
        if ($isSuperAdmin && isset($validated['health_facility_id'])) {
            // Super admin can invite to any facility
            $healthFacilityId = $validated['health_facility_id'];
        } else {
            // Regular admin can only invite to their own facility
            $healthFacilityId = $user->health_facility_id;
        }
        
        \Log::info('Health Facility Invitation: Facility ID determined', ['health_facility_id' => $healthFacilityId]);
        
        $healthFacility = HealthFacility::findOrFail($healthFacilityId);
        
        \Log::info('Health Facility Invitation: Facility found', [
            'facility_id' => $healthFacility->id,
            'facility_name' => $healthFacility->name,
            'facility_email' => $healthFacility->email
        ]);

        // Check if user already exists and is part of this health facility
        $existingUser = User::where('email', $validated['email'])
            ->where('health_facility_id', $healthFacility->id)
            ->first();

        if ($existingUser) {
            \Log::warning('Health Facility Invitation: User already exists', [
                'email' => $validated['email'],
                'facility_id' => $healthFacility->id
            ]);
            return back()->with('error', 'This user is already a member of your health facility.');
        }

        // Check if there's already a pending invitation
        $pendingInvitation = HealthFacilityInvitation::where('email', $validated['email'])
            ->where('health_facility_id', $healthFacility->id)
            ->where('accepted', false)
            ->where('expires_at', '>', now())
            ->first();

        if ($pendingInvitation) {
            \Log::warning('Health Facility Invitation: Pending invitation exists', [
                'email' => $validated['email'],
                'invitation_id' => $pendingInvitation->id,
                'expires_at' => $pendingInvitation->expires_at
            ]);
            return back()->with('error', 'An invitation has already been sent to this email address.');
        }

        // Create the invitation
        $invitation = HealthFacilityInvitation::create([
            'health_facility_id' => $healthFacility->id,
            'email' => $validated['email'],
            'role' => $validated['role'],
            'token' => HealthFacilityInvitation::generateToken(),
            'expires_at' => Carbon::now()->addDays(7),
            'invited_by' => $user->id,
        ]);
        
        \Log::info('Health Facility Invitation: Created successfully', [
            'invitation_id' => $invitation->id,
            'email' => $invitation->email,
            'role' => $invitation->role,
            'token' => $invitation->token,
            'expires_at' => $invitation->expires_at
        ]);

        // Send invitation email
        $invitationUrl = url('/health-facility/invitation/accept/' . $invitation->token);
        
        \Log::info('Health Facility Invitation: URL generated', [
            'invitation_url' => $invitationUrl,
            'recipient_email' => $validated['email']
        ]);
        
        // Send invitation email
        try {
            Mail::to($validated['email'])->send(new HealthFacilityInvitationMail($invitation, $invitationUrl));
            
            \Log::info('Health Facility Invitation: Email sent successfully', [
                'recipient' => $validated['email'],
                'invitation_url' => $invitationUrl,
                'health_facility_id' => $healthFacility->id,
                'role' => $validated['role']
            ]);
            
            return back()->with('success', 'Invitation sent successfully to ' . $validated['email'] . '! They will receive an email with instructions to accept the invitation.');
        } catch (\Exception $e) {
            \Log::error('Health Facility Invitation: Failed to send email', [
                'error' => $e->getMessage(),
                'recipient' => $validated['email'],
                'invitation_url' => $invitationUrl
            ]);
            
            return back()->with('error', 'Invitation created but email could not be sent. Please check mail configuration. You can manually share this link: ' . $invitationUrl);
        }
    }

    /**
     * Show the invitation acceptance form
     */
    public function showAcceptForm($token)
    {
        $invitation = HealthFacilityInvitation::where('token', $token)->firstOrFail();

        if (!$invitation->isValid()) {
            if ($invitation->accepted) {
                return view('invitations.already-accepted');
            } else {
                return view('invitations.expired');
            }
        }

        return view('health-facility.invitations.accept', compact('invitation'));
    }

    /**
     * Accept an invitation
     */
    public function acceptInvitation(Request $request, $token)
    {
        $invitation = HealthFacilityInvitation::where('token', $token)->firstOrFail();

        if (!$invitation->isValid()) {
            return redirect()->route('login')->with('error', 'This invitation is no longer valid.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Check if user already exists
        $user = User::where('email', $invitation->email)->first();

        if (!$user) {
            // Create new user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $invitation->email,
                'password' => Hash::make($validated['password']),
                'health_facility_id' => $invitation->health_facility_id,
            ]);
        } else {
            // Update existing user
            $user->health_facility_id = $invitation->health_facility_id;
            $user->save();
        }

        // Assign role
        $user->assignRole($invitation->role);

        // Mark invitation as accepted
        $invitation->update([
            'accepted' => true,
            'accepted_by' => $user->id,
            'accepted_at' => now(),
        ]);

        // Log the user in
        auth()->login($user);

        return redirect()->route('health-facility.dashboard')->with('success', 'Welcome to ' . $invitation->healthFacility->name . '!');
    }

    /**
     * Revoke an invitation
     */
    public function revokeInvitation(Request $request, $id)
    {
        $user = $request->user();
        
        if (!$user->hasPermission('manage-health-facility-staff')) {
            abort(403, 'Unauthorized to manage health facility staff');
        }

        $invitation = HealthFacilityInvitation::findOrFail($id);

        // Verify the invitation belongs to the user's health facility
        if ($invitation->health_facility_id !== $user->health_facility_id) {
            abort(403, 'Unauthorized action.');
        }

        $invitation->delete();

        return back()->with('success', 'Invitation revoked successfully.');
    }

    /**
     * Remove a staff member
     */
    public function removeStaffMember(Request $request, $userId)
    {
        $user = $request->user();
        
        if (!$user->hasPermission('manage-health-facility-staff')) {
            abort(403, 'Unauthorized to manage health facility staff');
        }

        $staffMember = User::findOrFail($userId);

        // Verify the staff member belongs to the user's health facility
        if ($staffMember->health_facility_id !== $user->health_facility_id) {
            abort(403, 'Unauthorized action.');
        }

        // Don't allow removing yourself
        if ($staffMember->id === $user->id) {
            return back()->with('error', 'You cannot remove yourself.');
        }

        // Remove health facility association and roles
        $staffMember->health_facility_id = null;
        $staffMember->removeRole('health-facility-admin');
        $staffMember->removeRole('health-facility-medical-personnel');
        $staffMember->save();

        return back()->with('success', 'Staff member removed successfully.');
    }
}
