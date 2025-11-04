<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\User;
// use Illuminate\Foundation\Auth\RegistersUsers; // Commented out due to Laravel 11 changes

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    // use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Show the application registration form.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $token
     * @return \Illuminate\View\View
     */
    public function showRegistrationForm(Request $request, $token = null)
    {
        $invite = null;
        $email = null;
        $invitation = null;
        $invitationToken = null;
        $invitationType = null;
        $existingUser = false;

        // Check for new invitation system (school/health-facility invitations)
        $invitationToken = $request->query('invitation_token');
        $invitationType = $request->query('invitation_type');

        if ($invitationToken && $invitationType) {
            $invitation = $this->getInvitation($invitationType, $invitationToken);

            if (!$invitation) {
                abort(404, 'Invalid invitation token.');
            }

            if ($invitation->accepted) {
                abort(403, 'This invitation has already been accepted.');
            }

            if ($invitation->isExpired()) {
                abort(403, 'This invitation has expired.');
            }

            $email = $invitation->email;
        }
        // Legacy admin invite system
        elseif ($token) {
            $invite = \App\Models\AdminInvite::where('token', $token)->first();

            if (!$invite) {
                abort(404, 'Invalid invitation token.');
            }

            if (!$invite->isValid()) {
                abort(403, 'This invitation has expired or has already been used.');
            }

            $email = $invite->email;
            $existingUser = \App\User::where('email', $email)->exists();
        }

        return view('auth.register', compact('email', 'invite', 'invitation', 'invitationToken', 'invitationType', 'existingUser'));
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            $this->validator($request->all())->validate();

            // Check for new invitation system
            $invitationToken = $request->input('invitation_token');
            $invitationType = $request->input('invitation_type');

            if ($invitationToken && $invitationType) {
                return $this->handleInvitationRegistration($request, $invitationType, $invitationToken);
            }

            // Legacy admin invite system
            $user = $this->createOrLogin($request->all());

            // Mark the invite as used if it exists
            if ($request->has('invite_token')) {
                $invite = \App\Models\AdminInvite::where('token', $request->invite_token)->first();
                if ($invite) {
                    $invite->markAsUsed();
                }
            }

            // Log the user in
            auth()->login($user);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Registration successful! Welcome to the admin panel.',
                    'redirect' => '/'
                ], 201);
            }

            return redirect('/');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registration failed. Please try again.'
                ], 500);
            }
            throw $e;
        }
    }

    /**
     * Get the post register / login redirect path.
     *
     * @return string
     */
    public function redirectPath()
    {
        return $this->redirectTo;
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $rules = [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ];

        // Name is required only for new users, not for existing users accepting admin invites
        if (!$this->isAdminRegistration() || !\App\User::where('email', $data['email'] ?? '')->exists()) {
            $rules['name'] = ['required', 'string', 'max:255'];
        }

        // For admin invites, email doesn't need to be unique since existing users can accept
        if (!$this->isAdminRegistration()) {
            $rules['email'][] = 'unique:users';
        }

        // For existing users accepting admin invites, don't require password confirmation
        if (!$this->isAdminRegistration() || !\App\User::where('email', $data['email'] ?? '')->exists()) {
            $rules['password'][] = 'confirmed';
        }

        return Validator::make($data, $rules);
    }

    /**
     * Create a new user instance after a valid registration, or login existing user.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function createOrLogin(array $data)
    {
        $user = User::where('email', $data['email'])->first();

        if ($user) {
            // User exists, check password and login
            if (!Hash::check($data['password'], $user->password)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'password' => ['The provided password does not match our records.']
                ]);
            }
            // Assign admin role if not already has it
            if (!$user->hasRole('admin')) {
                $user->assignRole('admin');
                $user->is_admin = true;
                $user->save();
            }
            return $user;
        } else {
            // Create new user
            return User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_admin' => true,
            ]);
        }
    }

    /**
     * Handle invitation-based registration
     */
    protected function handleInvitationRegistration(Request $request, string $invitationType, string $invitationToken)
    {
        $invitation = $this->getInvitation($invitationType, $invitationToken);

        if (!$invitation) {
            return back()->withErrors(['invitation' => 'Invalid invitation'])->withInput();
        }

        if ($invitation->accepted) {
            return back()->withErrors(['invitation' => 'This invitation has already been accepted'])->withInput();
        }

        if ($invitation->isExpired()) {
            return back()->withErrors(['invitation' => 'This invitation has expired'])->withInput();
        }

        // Check if email matches invitation
        if ($request->email !== $invitation->email) {
            return back()->withErrors(['email' => 'Email does not match invitation'])->withInput();
        }

        // Create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Associate user with entity
        $this->associateUserWithEntity($user, $invitation, $invitationType);

        // Mark invitation as accepted
        $invitation->update([
            'accepted' => true,
            'accepted_by' => $user->id,
            'accepted_at' => now(),
        ]);

        // Only login for admin invitations, not for school/health-facility
        if ($invitationType !== 'school' && $invitationType !== 'health-facility') {
            auth()->login($user);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Registration successful! Welcome to your dashboard.',
                'redirect' => $this->getDashboardRoute($invitationType)
            ], 201);
        }

        return redirect($this->getDashboardRoute($invitationType));
    }

    /**
     * Get invitation by type and token
     */
    protected function getInvitation(string $type, string $token)
    {
        switch ($type) {
            case 'school':
                return \App\Models\SchoolInvitation::with('school')->where('token', $token)->first();
            case 'health-facility':
                return \App\Models\HealthFacilityInvitation::with('healthFacility')->where('token', $token)->first();
            default:
                return null;
        }
    }

    /**
     * Associate user with entity
     */
    protected function associateUserWithEntity($user, $invitation, string $type)
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

    /**
     * Get dashboard route for invitation type
     */
    protected function getDashboardRoute(string $type)
    {
        return '/';
    }

    /**
     * Check if this is an admin registration
     *
     * @return bool
     */
    protected function isAdminRegistration()
    {
        return request()->has('invite_token');
    }
}
