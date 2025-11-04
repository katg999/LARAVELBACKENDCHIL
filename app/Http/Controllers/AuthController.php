<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\SchoolInvitation;
use App\Models\HealthFacilityInvitation;
use App\User;
use App\Models\School;
use App\Models\HealthFacility;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        $invitationToken = $request->query('invitation_token');
        $invitationType = $request->query('invitation_type');
        
        $invitation = null;
        if ($invitationToken && $invitationType) {
            $invitation = $this->getInvitation($invitationType, $invitationToken);
            
            // If invitation exists and user doesn't exist, redirect to register
            if ($invitation && !\App\User::where('email', $invitation->email)->exists()) {
                return redirect()->route('register', [
                    'invitation_token' => $invitationToken,
                    'invitation_type' => $invitationType
                ]);
            }
        }

        return view('auth.login', compact('invitation', 'invitationToken', 'invitationType'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required']
        ]);

        // Check if this is an invitation acceptance flow
        $invitationToken = $request->input('invitation_token');
        $invitationType = $request->input('invitation_type');
        
        if ($invitationToken && $invitationType) {
            return $this->handleInvitationLogin($request, $credentials, $invitationType, $invitationToken);
        }

        if ($request->expectsJson()) {
            if (Auth::attempt($credentials, $request->boolean('remember'))) {
                $request->session()->regenerate();
                return response()->json(['success' => true, 'redirect' => '/admin']);
            }
            return response()->json(['success' => false, 'errors' => ['email' => 'Invalid credentials']], 422);
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))){
            $request->session()->regenerate();
            return redirect()->intended('/admin');
        }

        return back()->withErrors(['email' => 'Invalid credentials'])->onlyInput('email');
    }

    protected function handleInvitationLogin(Request $request, array $credentials, string $invitationType, string $invitationToken)
    {
        $invitation = $this->getInvitation($invitationType, $invitationToken);

        if (!$invitation) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'errors' => ['invitation' => 'Invalid or expired invitation']], 422);
            }
            return back()->withErrors(['invitation' => 'Invalid or expired invitation'])->withInput();
        }

        if ($invitation->accepted) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'errors' => ['invitation' => 'This invitation has already been accepted']], 422);
            }
            return back()->withErrors(['invitation' => 'This invitation has already been accepted'])->withInput();
        }

        if ($invitation->isExpired()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'errors' => ['invitation' => 'This invitation has expired']], 422);
            }
            return back()->withErrors(['invitation' => 'This invitation has expired'])->withInput();
        }

        // Check if email matches invitation
        if ($credentials['email'] !== $invitation->email) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'errors' => ['email' => 'Email does not match invitation']], 422);
            }
            return back()->withErrors(['email' => 'Email does not match invitation'])->withInput();
        }

        // Try to authenticate existing user
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // Update existing user with entity association
            $user = Auth::user();
            $this->associateUserWithEntity($user, $invitation, $invitationType);

            // Mark invitation as accepted
            $invitation->update([
                'accepted' => true,
                'accepted_by' => $user->id,
                'accepted_at' => now(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'redirect' => $this->getDashboardRoute($invitationType)]);
            }
            return redirect()->intended($this->getDashboardRoute($invitationType));
        }

        // If authentication fails, suggest registration
        $message = 'Invalid credentials. If you don\'t have an account yet, please register first.';
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'errors' => ['email' => $message], 'suggest_registration' => true], 422);
        }
        return back()->withErrors(['email' => $message])->withInput();
    }

    public function showRegistration(Request $request)
    {
        $invitationToken = $request->query('invitation_token');
        $invitationType = $request->query('invitation_type');
        
        $invitation = null;
        if ($invitationToken && $invitationType) {
            $invitation = $this->getInvitation($invitationType, $invitationToken);
        }

        return view('auth.register', compact('invitation', 'invitationToken', 'invitationType'));
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Check if this is an invitation acceptance flow
        $invitationToken = $request->input('invitation_token');
        $invitationType = $request->input('invitation_type');
        
        if ($invitationToken && $invitationType) {
            return $this->handleInvitationRegistration($request, $validated, $invitationType, $invitationToken);
        }

        // Regular registration
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);

        return redirect('/admin');
    }

    protected function handleInvitationRegistration(Request $request, array $validated, string $invitationType, string $invitationToken)
    {
        $invitation = $this->getInvitation($invitationType, $invitationToken);
        
        if (!$invitation) {
            return back()->withErrors(['invitation' => 'Invalid or expired invitation'])->withInput();
        }

        if ($invitation->accepted) {
            return back()->withErrors(['invitation' => 'This invitation has already been accepted'])->withInput();
        }

        if ($invitation->isExpired()) {
            return back()->withErrors(['invitation' => 'This invitation has expired'])->withInput();
        }

        // Check if email matches invitation
        if ($validated['email'] !== $invitation->email) {
            return back()->withErrors(['email' => 'Email does not match invitation'])->withInput();
        }

        // Create new user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Associate user with entity
        $this->associateUserWithEntity($user, $invitation, $invitationType);

        // Mark invitation as accepted
        $invitation->update([
            'accepted' => true,
            'accepted_by' => $user->id,
            'accepted_at' => now(),
        ]);

        Auth::login($user);

        return redirect($this->getDashboardRoute($invitationType));
    }

    protected function getInvitation(string $type, string $token)
    {
        switch ($type) {
            case 'school':
                return SchoolInvitation::with('school')->where('token', $token)->first();
            case 'health-facility':
                return HealthFacilityInvitation::with('healthFacility')->where('token', $token)->first();
            default:
                return null;
        }
    }

    protected function associateUserWithEntity(\App\User $user, $invitation, string $type)
    {
        switch ($type) {
            case 'school':
                $user->school_id = $invitation->school_id;
                $user->assignRole($invitation->role);
                break;
            case 'health-facility':
                $user->health_facility_id = $invitation->health_facility_id;
                $user->assignRole($invitation->role);
                break;
        }
        $user->save();
    }

    protected function getDashboardRoute(string $type)
    {
        switch ($type) {
            case 'school':
                return '/school-dashboard';
            case 'health-facility':
                return '/health-facility/dashboard';
            default:
                return '/admin';
        }
    }

    public function logout(Request $request)
    {
        // Logout from default guard
        try {
            Auth::logout();
        } catch (\Exception $e) {
            // ignore
        }

        // Also attempt to logout doctor guard if present
        try {
            if (Auth::guard('doctor')->check()) {
                Auth::guard('doctor')->logout();
            }
        } catch (\Exception $e) {
            // ignore
        }

        // Clear session-based authentication for entities (schools, health facilities)
        $request->session()->forget('authenticated_user');
        $request->session()->forget('_session_lifetime');

        // Invalidate session and regenerate CSRF token
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Show logout page with countdown instead of redirecting to login
        return view('auth.logout');
    }
}
