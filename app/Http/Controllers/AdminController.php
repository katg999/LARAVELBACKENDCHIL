<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\SchoolInvitation;
use App\Models\HealthFacilityInvitation;
use App\Models\School;
use App\Models\HealthFacility;
use Illuminate\Support\Facades\Mail;
use App\Mail\SchoolInvitationMail;
use App\Mail\HealthFacilityInvitationMail;

class AdminController extends Controller
{
    public function index()
    {
        // Get statistics for dashboard
        $stats = [
            'doctors' => \App\Models\Doctor::count(),
            'schools' => \App\Models\School::count(),
            'health_facilities' => \App\Models\HealthFacility::count(),
            'revenue' => \App\Models\Transaction::where('status', 'successful')->sum('amount'),
            'patients_male' => \App\Models\Patient::where('gender', 'male')->count(),
            'patients_female' => \App\Models\Patient::where('gender', 'female')->count(),
            'independent_doctors' => \App\Models\Doctor::whereNull('school_id')->whereNull('health_facility_id')->count(),
            'doctors_by_location' => [
                'schools' => \App\Models\Doctor::whereNotNull('school_id')->count(),
                'health_facilities' => \App\Models\Doctor::whereNotNull('health_facility_id')->count(),
                'independent' => \App\Models\Doctor::whereNull('school_id')->whereNull('health_facility_id')->count()
            ]
        ];

        // Get monthly data for the current year
        $currentYear = date('Y');
        $monthlyData = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthName = date('M', mktime(0, 0, 0, $month, 1));
            $startDate = Carbon::create($currentYear, $month, 1)->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::create($currentYear, $month, 1)->endOfMonth()->format('Y-m-d');
            // Count appointments for this month
            $appointmentsCount = \App\Models\Appointment::whereBetween('appointment_time', [$startDate, $endDate])->count();

            // Sum revenue for this month from successful payments
            $monthlyRevenue = \App\Models\Payment::where('status', 'completed')
                ->whereHas('appointment', function($query) use ($startDate, $endDate) {
                    $query->whereBetween('appointment_time', [$startDate, $endDate]);
                })
                ->sum('amount');

            $monthlyData[] = [
                'month' => $monthName,
                'appointments' => $appointmentsCount,
                'revenue' => (float) $monthlyRevenue
            ];
        }

        // Get locations for map - Schools, Health Facilities, and Independent Doctors
        $locations = [];

        // Ugandan cities for sample locations
        $ugandanCities = [
            ['Kampala', 0.3476, 32.5825],
            ['Entebbe', 0.0562, 32.4794],
            ['Jinja', 0.4244, 33.2041],
            ['Mbale', 1.0820, 34.1750],
            ['Mbarara', -0.6072, 30.6545],
            ['Masaka', -0.3317, 31.7341],
            ['Gulu', 2.7746, 32.2990],
            ['Lira', 2.2499, 32.8998],
            ['Arua', 3.0201, 30.9111],
            ['Fort Portal', 0.6617, 30.2748],
            ['Soroti', 1.7146, 33.6111],
            ['Hoima', 1.4347, 31.3524],
            ['Mukono', 0.3533, 32.7553],
            ['Wakiso', 0.4044, 32.4784],
            ['Tororo', 0.6928, 34.1809]
        ];

        $cityIndex = 0;

        // Add Schools
        $schools = \App\Models\School::all();
        foreach ($schools as $school) {
            $cityData = $ugandanCities[$cityIndex % count($ugandanCities)];
            $cityIndex++;

            $locations[] = [
                $school->name,     // location name
                $cityData[1],      // latitude
                $cityData[2],      // longitude
                'school',          // type
                'School',          // entity type
                $cityData[0]       // city name
            ];
        }

        // Add Health Facilities
        $healthFacilities = \App\Models\HealthFacility::all();
        foreach ($healthFacilities as $facility) {
            $cityData = $ugandanCities[$cityIndex % count($ugandanCities)];
            $cityIndex++;

            $locations[] = [
                $facility->name,   // location name
                $cityData[1],      // latitude
                $cityData[2],      // longitude
                'health_facility', // type
                'Health Facility', // entity type
                $cityData[0]       // city name
            ];
        }

        // Add Independent Doctors (doctors not affiliated with schools or health facilities)
        $independentDoctors = \App\Models\Doctor::whereNull('school_id')->whereNull('health_facility_id')->get();
        foreach ($independentDoctors as $doctor) {
            $cityData = $ugandanCities[$cityIndex % count($ugandanCities)];
            $cityIndex++;

            $locations[] = [
                $doctor->name,     // location name
                $cityData[1],      // latitude
                $cityData[2],      // longitude
                'doctor',          // type
                'Independent Doctor', // entity type
                $cityData[0]       // city name
            ];
        }

        // Simple list of models we support in the admin panel
        $models = [
            'doctors' => 'Doctors',
            'appointments' => 'Appointments',
            'students' => 'Students',
            'schools' => 'Schools',
            'health-facilities' => 'Health Facilities',
            'patients' => 'Patients',
        ];

        return view('admin.index', compact('models', 'stats', 'monthlyData', 'locations'));
    }

    /**
     * Display all invitations (schools and health facilities)
     */
    public function invitations(Request $request)
    {
        \Log::info('Admin Invitations: Viewing invitations page', [
            'user_id' => auth()->id(),
            'filters' => $request->all()
        ]);

        $status = $request->get('status', 'all'); // all, pending, accepted, expired
        $type = $request->get('type', 'all'); // all, school, health-facility

        // Get school invitations
        $schoolInvitationsQuery = SchoolInvitation::with(['school', 'inviter', 'acceptedByUser']);
        
        if ($status === 'pending') {
            $schoolInvitationsQuery->where('accepted', false)
                ->where('expires_at', '>', now());
        } elseif ($status === 'accepted') {
            $schoolInvitationsQuery->where('accepted', true);
        } elseif ($status === 'expired') {
            $schoolInvitationsQuery->where('accepted', false)
                ->where('expires_at', '<=', now());
        }

        $schoolInvitations = ($type === 'all' || $type === 'school') 
            ? $schoolInvitationsQuery->latest()->get() 
            : collect();

        // Get health facility invitations
        $facilityInvitationsQuery = HealthFacilityInvitation::with(['healthFacility', 'inviter', 'acceptedByUser']);
        
        if ($status === 'pending') {
            $facilityInvitationsQuery->where('accepted', false)
                ->where('expires_at', '>', now());
        } elseif ($status === 'accepted') {
            $facilityInvitationsQuery->where('accepted', true);
        } elseif ($status === 'expired') {
            $facilityInvitationsQuery->where('accepted', false)
                ->where('expires_at', '<=', now());
        }

        $facilityInvitations = ($type === 'all' || $type === 'health-facility') 
            ? $facilityInvitationsQuery->latest()->get() 
            : collect();

        // Combine and transform invitations
        $invitations = collect();

        foreach ($schoolInvitations as $inv) {
            $invitations->push([
                'id' => $inv->id,
                'type' => 'school',
                'type_label' => 'School',
                'entity_name' => $inv->school->name ?? 'N/A',
                'email' => $inv->email,
                'role' => $inv->role,
                'role_label' => ucwords(str_replace('-', ' ', $inv->role)),
                'invited_by' => $inv->inviter->name ?? 'N/A',
                'invited_at' => $inv->created_at,
                'expires_at' => $inv->expires_at,
                'accepted' => $inv->accepted,
                'accepted_at' => $inv->accepted_at,
                'accepted_by' => $inv->acceptedByUser->name ?? 'N/A',
                'is_expired' => $inv->isExpired(),
                'is_valid' => $inv->isValid(),
                'url' => url('/school/invitation/accept/' . $inv->token),
                'model' => $inv
            ]);
        }

        foreach ($facilityInvitations as $inv) {
            $invitations->push([
                'id' => $inv->id,
                'type' => 'health-facility',
                'type_label' => 'Health Facility',
                'entity_name' => $inv->healthFacility->name ?? 'N/A',
                'email' => $inv->email,
                'role' => $inv->role,
                'role_label' => ucwords(str_replace('-', ' ', $inv->role)),
                'invited_by' => $inv->inviter->name ?? 'N/A',
                'invited_at' => $inv->created_at,
                'expires_at' => $inv->expires_at,
                'accepted' => $inv->accepted,
                'accepted_at' => $inv->accepted_at,
                'accepted_by' => $inv->acceptedByUser->name ?? 'N/A',
                'is_expired' => $inv->isExpired(),
                'is_valid' => $inv->isValid(),
                'url' => url('/health-facility/invitation/accept/' . $inv->token),
                'model' => $inv
            ]);
        }

        // Sort by created_at descending
        $invitations = $invitations->sortByDesc('invited_at');

        \Log::info('Admin Invitations: Retrieved invitations', [
            'total' => $invitations->count(),
            'schools' => $schoolInvitations->count(),
            'facilities' => $facilityInvitations->count()
        ]);

        return view('admin.invitations.index', compact('invitations', 'status', 'type'));
    }

    /**
     * Resend an invitation email
     */
    public function resendInvitation(Request $request, $type, $id)
    {
        \Log::info('Admin Invitations: Resending invitation', [
            'type' => $type,
            'invitation_id' => $id,
            'user_id' => auth()->id()
        ]);

        if ($type === 'school') {
            $invitation = SchoolInvitation::findOrFail($id);
            
            if ($invitation->accepted) {
                return back()->with('error', 'This invitation has already been accepted.');
            }

            if ($invitation->isExpired()) {
                // Extend expiration
                $invitation->expires_at = Carbon::now()->addDays(7);
                $invitation->save();
            }

            $invitationUrl = url('/school/invitation/accept/' . $invitation->token);
            
            // Send invitation email
            try {
                Mail::to($invitation->email)->send(new SchoolInvitationMail($invitation, $invitationUrl));
                
                \Log::info('Admin Invitations: School invitation email resent successfully', [
                    'invitation_id' => $id,
                    'email' => $invitation->email,
                    'url' => $invitationUrl
                ]);

                return back()->with('success', 'Invitation resent to ' . $invitation->email . '! Expiration extended to ' . $invitation->expires_at->format('M d, Y'));
            } catch (\Exception $e) {
                \Log::error('Admin Invitations: Failed to resend school invitation email', [
                    'invitation_id' => $id,
                    'email' => $invitation->email,
                    'error' => $e->getMessage()
                ]);
                
                return back()->with('error', 'Failed to send email. Please check mail configuration. Manual link: ' . $invitationUrl);
            }
        } else {
            $invitation = HealthFacilityInvitation::findOrFail($id);
            
            if ($invitation->accepted) {
                return back()->with('error', 'This invitation has already been accepted.');
            }

            if ($invitation->isExpired()) {
                // Extend expiration
                $invitation->expires_at = Carbon::now()->addDays(7);
                $invitation->save();
            }

            $invitationUrl = url('/health-facility/invitation/accept/' . $invitation->token);
            
            // Send invitation email
            try {
                Mail::to($invitation->email)->send(new HealthFacilityInvitationMail($invitation, $invitationUrl));
                
                \Log::info('Admin Invitations: Health facility invitation email resent successfully', [
                    'invitation_id' => $id,
                    'email' => $invitation->email,
                    'url' => $invitationUrl
                ]);

                return back()->with('success', 'Invitation resent to ' . $invitation->email . '! Expiration extended to ' . $invitation->expires_at->format('M d, Y'));
            } catch (\Exception $e) {
                \Log::error('Admin Invitations: Failed to resend health facility invitation email', [
                    'invitation_id' => $id,
                    'email' => $invitation->email,
                    'error' => $e->getMessage()
                ]);
                
                return back()->with('error', 'Failed to send email. Please check mail configuration. Manual link: ' . $invitationUrl);
            }
        }
    }

    /**
     * Revoke/delete an invitation
     */
    public function revokeInvitation(Request $request, $type, $id)
    {
        \Log::info('Admin Invitations: Revoking invitation', [
            'type' => $type,
            'invitation_id' => $id,
            'user_id' => auth()->id()
        ]);

        if ($type === 'school') {
            $invitation = SchoolInvitation::findOrFail($id);
            
            if ($invitation->accepted) {
                return back()->with('error', 'Cannot revoke an accepted invitation.');
            }

            $email = $invitation->email;
            $invitation->delete();

            \Log::info('Admin Invitations: School invitation revoked', [
                'invitation_id' => $id,
                'email' => $email
            ]);

            return back()->with('success', 'Invitation to ' . $email . ' has been revoked.');
        } else {
            $invitation = HealthFacilityInvitation::findOrFail($id);
            
            if ($invitation->accepted) {
                return back()->with('error', 'Cannot revoke an accepted invitation.');
            }

            $email = $invitation->email;
            $invitation->delete();

            \Log::info('Admin Invitations: Health facility invitation revoked', [
                'invitation_id' => $id,
                'email' => $email
            ]);

            return back()->with('success', 'Invitation to ' . $email . ' has been revoked.');
        }
    }
}
