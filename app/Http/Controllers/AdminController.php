<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

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
}
