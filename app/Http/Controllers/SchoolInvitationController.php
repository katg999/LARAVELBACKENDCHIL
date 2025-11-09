<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SchoolInvitation;
use App\Models\School;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Mail\SchoolInvitationMail;

class SchoolInvitationController extends Controller
{
    /**
     * Display a listing of invitations for a school
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Only school admins can manage staff
        if (!$user->hasRole('school-admin')) {
            abort(403, 'Unauthorized to manage school staff');
        }

        $school = School::findOrFail($user->school_id);

        $staffMembers = $school->users()->with('roles')->get();

        return view('school.staff.index', compact('school', 'staffMembers'));
    }

    /**
     * Display only invitations for a school
     */
    public function invitations(Request $request)
    {
        $user = $request->user();
        
        // Only school admins can manage staff
        if (!$user->hasRole('school-admin')) {
            abort(403, 'Unauthorized to manage school staff');
        }

        $school = School::findOrFail($user->school_id);
        
        $invitations = $school->invitations()
            ->with('inviter', 'acceptedByUser')
            ->latest()
            ->paginate(15);

        return view('school.invitations.index', compact('school', 'invitations'));
    }

    /**
     * Send an invitation to join the school
     * Can be called by school admin OR super admin (for initial setup)
     */
    public function sendInvitation(Request $request)
    {
        $user = $request->user();
        
        \Log::info('School Invitation: Starting invitation process', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_roles' => $user->roles->pluck('slug')->toArray()
        ]);
        
        // Check if user is school admin OR super admin
        $isSuperAdmin = $user->hasRole('admin'); // System admin role
        $isSchoolAdmin = $user->hasRole('school-admin');
        
        \Log::info('School Invitation: Permission check', [
            'is_super_admin' => $isSuperAdmin,
            'is_school_admin' => $isSchoolAdmin
        ]);
        
        if (!$isSchoolAdmin && !$isSuperAdmin) {
            \Log::warning('School Invitation: Unauthorized access attempt', ['user_id' => $user->id]);
            abort(403, 'Unauthorized to manage school staff');
        }

        $validated = $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:school-admin,school-staff',
            'school_id' => $isSuperAdmin ? 'required|exists:schools,id' : 'nullable',
        ]);
        
        \Log::info('School Invitation: Validation passed', $validated);

        // Get school ID
        if ($isSuperAdmin && isset($validated['school_id'])) {
            // Super admin can invite to any school
            $schoolId = $validated['school_id'];
        } else {
            // Regular admin can only invite to their own school
            $schoolId = $user->school_id;
        }
        
        \Log::info('School Invitation: School ID determined', ['school_id' => $schoolId]);
        
        $school = School::findOrFail($schoolId);
        
        \Log::info('School Invitation: School found', [
            'school_id' => $school->id,
            'school_name' => $school->name,
            'school_email' => $school->email
        ]);

        // Check if user already exists and is part of this school
        $existingUser = User::where('email', $validated['email'])
            ->where('school_id', $school->id)
            ->first();

        if ($existingUser) {
            \Log::warning('School Invitation: User already exists', [
                'email' => $validated['email'],
                'school_id' => $school->id
            ]);
            
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'This user is already a member of your school.'
                ], 422);
            }
            
            return back()->with('error', 'This user is already a member of your school.');
        }

        // Check if there's already a pending invitation
        $pendingInvitation = SchoolInvitation::where('email', $validated['email'])
            ->where('school_id', $school->id)
            ->where('accepted', false)
            ->where('expires_at', '>', now())
            ->first();

        if ($pendingInvitation) {
            \Log::warning('School Invitation: Pending invitation exists', [
                'email' => $validated['email'],
                'invitation_id' => $pendingInvitation->id,
                'expires_at' => $pendingInvitation->expires_at
            ]);
            
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'An invitation has already been sent to this email address.'
                ], 422);
            }
            
            return back()->with('error', 'An invitation has already been sent to this email address.');
        }

        // Create the invitation
        $invitation = SchoolInvitation::create([
            'school_id' => $school->id,
            'email' => $validated['email'],
            'role' => $validated['role'],
            'token' => SchoolInvitation::generateToken(),
            'expires_at' => Carbon::now()->addDays(7),
            'invited_by' => $user->id,
        ]);
        
        \Log::info('School Invitation: Created successfully', [
            'invitation_id' => $invitation->id,
            'email' => $invitation->email,
            'role' => $invitation->role,
            'token' => $invitation->token,
            'expires_at' => $invitation->expires_at
        ]);

        // Send invitation email
        $invitationUrl = url('/school/invitation/accept/' . $invitation->token);
        
        \Log::info('School Invitation: URL generated', [
            'invitation_url' => $invitationUrl,
            'recipient_email' => $validated['email']
        ]);
        
        // Send invitation email
        try {
            Mail::to($validated['email'])->send(new SchoolInvitationMail($invitation, $invitationUrl));
            
            \Log::info('School Invitation: Email sent successfully', [
                'recipient' => $validated['email'],
                'invitation_id' => $invitation->id
            ]);
            
            // Check if this is an AJAX request
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Invitation sent successfully to ' . $validated['email'] . '! They will receive an email with instructions to accept the invitation.',
                    'invitation' => $invitation
                ]);
            }
            
            return back()->with('success', 'Invitation sent successfully to ' . $validated['email'] . '! They will receive an email with instructions to accept the invitation.');
        } catch (\Exception $e) {
            \Log::error('School Invitation: Failed to send email', [
                'recipient' => $validated['email'],
                'error' => $e->getMessage(),
                'invitation_url' => $invitationUrl
            ]);
            
            // Check if this is an AJAX request
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invitation created but email could not be sent. Please check mail configuration. You can copy the link from the Manage Invitations page.',
                    'invitation' => $invitation
                ], 500);
            }
            
            return back()->with('error', 'Invitation created but email could not be sent. Please check mail configuration. You can copy the link from the Manage Invitations page.');
        }
    }

    /**
     * Show the invitation acceptance form
     */
    public function showAcceptForm($token)
    {
        $invitation = SchoolInvitation::where('token', $token)->firstOrFail();

        if (!$invitation->isValid()) {
            if ($invitation->accepted) {
                return view('invitations.already-accepted');
            } else {
                return view('invitations.expired');
            }
        }

        return view('school.invitations.accept', compact('invitation'));
    }

    /**
     * Accept an invitation
     */
    public function acceptInvitation(Request $request, $token)
    {
        $invitation = SchoolInvitation::where('token', $token)->firstOrFail();

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
                'school_id' => $invitation->school_id,
            ]);
        } else {
            // Update existing user
            $user->school_id = $invitation->school_id;
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

        return redirect()->route('school.dashboard')->with('success', 'Welcome to ' . $invitation->school->name . '!');
    }

    /**
     * Revoke an invitation
     */
    public function revokeInvitation(Request $request, $id)
    {
        $user = $request->user();
        
        // Only school admins can manage staff
        if (!$user->hasRole('school-admin')) {
            abort(403, 'Unauthorized to manage school staff');
        }

        $invitation = SchoolInvitation::findOrFail($id);

        // Verify the invitation belongs to the user's school
        if ($invitation->school_id !== $user->school_id) {
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
        
        // Only school admins can manage staff
        if (!$user->hasRole('school-admin')) {
            abort(403, 'Unauthorized to manage school staff');
        }

        $staffMember = User::findOrFail($userId);

        // Verify the staff member belongs to the user's school
        if ($staffMember->school_id !== $user->school_id) {
            abort(403, 'Unauthorized action.');
        }

        // Don't allow removing yourself
        if ($staffMember->id === $user->id) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'You cannot remove yourself.'
                ], 422);
            }
            return back()->with('error', 'You cannot remove yourself.');
        }

        // Remove school association and roles
        $staffMember->school_id = null;
        $staffMember->removeRole('school-admin');
        $staffMember->removeRole('school-staff');
        $staffMember->save();

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Staff member removed successfully.'
            ]);
        }

        return back()->with('success', 'Staff member removed successfully.');
    }
}
