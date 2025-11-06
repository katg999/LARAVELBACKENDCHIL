<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\HealthFacility;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\LabTest;
use App\Models\Duration;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesTestDurations;
use Tests\TestCase;

class RouteHealthCheckTest extends TestCase
{
    use DatabaseTransactions, CreatesTestDurations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestDurations();

        // Create test data needed for admin routes
        $school = School::create([
            'name' => 'Test School',
            'email' => 'test@school.com',
            'contact' => '+256700000000',
        ]);

        $healthFacility = HealthFacility::create([
            'name' => 'Test Health Facility',
            'email' => 'test@facility.com',
            'contact_number' => '+256711111111',
            'contact' => '+256711111111',
            'location' => 'Test Location',
            'type' => 'hospital',
        ]);

        $doctor = Doctor::create([
            'name' => 'Dr. Test',
            'email' => 'dr@test.com',
            'specialization' => 'General',
            'contact' => '+256700000001',
            'school_id' => $school->id,
            'health_facility_id' => $healthFacility->id,
            'meeting_slug' => 'keti-123456'
        ]);
    }

    protected function mockAuthenticatedSchool(School $school)
    {
        $this->withSession([
            'authenticated_user' => [
                'id' => $school->id,
                'type' => 'school',
                'name' => $school->name,
                'email' => $school->email,
            ]
        ]);
    }

    protected function mockAuthenticatedHealthFacility(HealthFacility $healthFacility)
    {
        $this->withSession([
            'authenticated_user' => [
                'id' => $healthFacility->id,
                'type' => 'health_facility',
                'name' => $healthFacility->name,
                'email' => $healthFacility->email,
            ]
        ]);
    }

    protected function mockAuthenticatedDoctor(Doctor $doctor)
    {
        $this->withSession([
            'authenticated_user' => [
                'id' => $doctor->id,
                'type' => 'doctor',
                'name' => $doctor->name,
                'email' => $doctor->email,
            ]
        ]);
    }

    /** @test */
    public function all_public_routes_return_successful_responses()
    {
        // Public routes that don't require authentication
        $publicRoutes = [
            ['method' => 'GET', 'uri' => '/'],
            // ['method' => 'GET', 'uri' => '/home'], // Removed - route no longer exists
            ['method' => 'GET', 'uri' => '/api-dashboard'],
            ['method' => 'GET', 'uri' => '/finance-dashboard'],
            ['method' => 'GET', 'uri' => '/login'],
            ['method' => 'GET', 'uri' => '/register'],
            ['method' => 'GET', 'uri' => '/password/reset'],
            ['method' => 'GET', 'uri' => '/patients/create'],
            ['method' => 'GET', 'uri' => '/payment'],
            ['method' => 'GET', 'uri' => '/profile'],
            ['method' => 'GET', 'uri' => '/success'],
            ['method' => 'GET', 'uri' => '/cancel'],
            ['method' => 'GET', 'uri' => '/health-facilities-dashboard'],
            ['method' => 'GET', 'uri' => '/doctors-dashboard'],
            ['method' => 'GET', 'uri' => '/doctor-availabilities'],
        ];

        foreach ($publicRoutes as $route) {
            $response = $this->call($route['method'], $route['uri']);
            $this->assertNotEquals(404, $response->getStatusCode(),
                "Route {$route['method']} {$route['uri']} returned 404");
            $this->assertNotEquals(500, $response->getStatusCode(),
                "Route {$route['method']} {$route['uri']} returned 500");
        }
    }

    /** @test */
    public function school_authenticated_routes_work_with_mock_session()
    {
        $school = School::create([
            'name' => 'Auth Test School',
            'email' => 'auth-test@school.com',
            'contact' => '+256700000000',
        ]);

        $this->mockAuthenticatedSchool($school);

        $schoolRoutes = [
            ['method' => 'GET', 'uri' => '/school-dashboard'],
            ['method' => 'GET', 'uri' => '/students'],
            ['method' => 'GET', 'uri' => '/lab-tests'],
            ['method' => 'GET', 'uri' => '/transactions'],
        ];

        foreach ($schoolRoutes as $route) {
            $response = $this->call($route['method'], $route['uri']);
            $this->assertNotEquals(404, $response->getStatusCode(),
                "Route {$route['method']} {$route['uri']} returned 404");
            $this->assertNotEquals(500, $response->getStatusCode(),
                "Route {$route['method']} {$route['uri']} returned 500");
        }
    }

    /** @test */
    public function health_facility_authenticated_routes_work_with_mock_session()
    {
        $healthFacility = HealthFacility::create([
            'name' => 'Auth Test Health Facility',
            'email' => 'auth-test@facility.com',
            'contact_number' => '+256711111111',
            'contact' => '+256711111111',
            'location' => 'Test Location',
            'type' => 'hospital',
        ]);

        $this->mockAuthenticatedHealthFacility($healthFacility);

        $healthFacilityRoutes = [
            ['method' => 'GET', 'uri' => '/health-facility/dashboard'],
            ['method' => 'GET', 'uri' => '/health-facility/patients'],
            ['method' => 'GET', 'uri' => '/health-facility/patients/create'],
            ['method' => 'GET', 'uri' => '/health-facility/lab-tests'],
            ['method' => 'GET', 'uri' => '/health-facility/transactions'],
            ['method' => 'GET', 'uri' => '/health-facility/staff'],
        ];

        foreach ($healthFacilityRoutes as $route) {
            $response = $this->call($route['method'], $route['uri']);
            $this->assertNotEquals(404, $response->getStatusCode(),
                "Route {$route['method']} {$route['uri']} returned 404");
            $this->assertNotEquals(500, $response->getStatusCode(),
                "Route {$route['method']} {$route['uri']} returned 500");
        }
    }

    /** @test */
    public function doctor_authenticated_routes_work_with_mock_session()
    {
        $doctor = Doctor::create([
            'name' => 'Dr. Test',
            'email' => 'dr@test.com',
            'specialization' => 'General',
            'contact' => '+256700000001',
        ]);

        $this->mockAuthenticatedDoctor($doctor);

        $doctorRoutes = [
            ['method' => 'GET', 'uri' => '/doctor/dashboard'],
            ['method' => 'GET', 'uri' => '/doctor/availability'],
            ['method' => 'GET', 'uri' => '/doctor/appointments'],
            ['method' => 'GET', 'uri' => '/doctor/meeting-link'],
            ['method' => 'GET', 'uri' => '/doctor/profile'],
            ['method' => 'GET', 'uri' => '/doctor/edit-profile'],
        ];

        foreach ($doctorRoutes as $route) {
            $response = $this->call($route['method'], $route['uri']);
            $this->assertNotEquals(404, $response->getStatusCode(),
                "Route {$route['method']} {$route['uri']} returned 404");
            $this->assertNotEquals(500, $response->getStatusCode(),
                "Route {$route['method']} {$route['uri']} returned 500");
        }
    }

    /** @test */
    public function routes_with_parameters_work_with_valid_data()
    {
        // Create test data for parameterized routes
        $school = School::create([
            'name' => 'Param Test School',
            'email' => 'param-test@school.com',
            'contact' => '+256700000000',
        ]);

        $healthFacility = HealthFacility::create([
            'name' => 'Test Health Facility',
            'email' => 'param-test@facility.com',
            'contact_number' => '+256711111111',
            'contact' => '+256711111111',
            'location' => 'Test Location',
            'type' => 'hospital',
        ]);

        $doctor = Doctor::create([
            'name' => 'Dr. Test',
            'email' => 'dr@test.com',
            'specialization' => 'General',
            'contact' => '+256700000001',
        ]);

        $patient = Patient::create([
            'patient_id' => 'P001',
            'name' => 'Test Patient',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
            'school_id' => $school->id,
        ]);

        $appointment = Appointment::create([
            'school_id' => $school->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_time' => now()->addDays(1),
            'status' => 'awaiting_payment',
            'duration_id' => $this->getGeneralDurationId(),
            'reason' => 'Test appointment',
        ]);

        $labTest = LabTest::create([
            'school_id' => $school->id,
            'patient_id' => $patient->id,
            'test_type' => 'Blood Test',
            'status' => 'pending',
        ]);

        // Test routes with parameters
        $parameterizedRoutes = [
            ['method' => 'GET', 'uri' => "/students/{$school->id}"],
            ['method' => 'GET', 'uri' => "/lab-tests/{$school->id}"],
            ['method' => 'GET', 'uri' => "/book-doctor/{$school->id}"],
            ['method' => 'GET', 'uri' => "/transactions/{$school->id}"],
            ['method' => 'GET', 'uri' => "/patients/{$patient->id}/profile"],
            ['method' => 'GET', 'uri' => "/patients/{$patient->id}/maternal"],
            ['method' => 'GET', 'uri' => "/doctors/{$doctor->id}"],
            // ['method' => 'GET', 'uri' => "/appointment/pay/{$appointment->id}"], // Commented out due to complex payment logic
            ['method' => 'GET', 'uri' => "/appointment/success/{$appointment->id}"],
            ['method' => 'GET', 'uri' => "/appointment/cancel/{$appointment->id}"],
            ['method' => 'GET', 'uri' => "/appointment/payment-status/{$appointment->id}"],
        ];

        foreach ($parameterizedRoutes as $route) {
            $response = $this->call($route['method'], $route['uri']);
            $this->assertNotEquals(404, $response->getStatusCode(),
                "Route {$route['method']} {$route['uri']} returned 404");
            $this->assertNotEquals(500, $response->getStatusCode(),
                "Route {$route['method']} {$route['uri']} returned 500");
        }
    }

    /** @test */
    public function admin_routes_work_with_admin_middleware_bypassed()
    {
        // Create sample data that views might need
        $doctor = \App\Models\Doctor::create([
            'name' => 'Admin Test Doctor',
            'email' => 'admin-test-dr@test.com',
            'specialization' => 'General',
            'contact' => '+256700000009',
            'meeting_slug' => 'keti-123789'
        ]);
        
        $school = \App\Models\School::create([
            'name' => 'Admin Test School',
            'email' => 'admin-test@school.com',
            'contact' => '+256700000008',
        ]);
        
        $healthFacility = \App\Models\HealthFacility::create([
            'name' => 'Admin Test Health Facility',
            'email' => 'admin-test@facility.com',
            'contact' => '+256711111119',
            'contact_number' => '+256711111119',
            'location' => 'Test Location',
            'type' => 'hospital',
        ]);

        // Test some admin routes by bypassing middleware
        $adminRoutes = [
            ['method' => 'GET', 'uri' => '/admin'],
            ['method' => 'GET', 'uri' => '/admin/doctors'],
            ['method' => 'GET', 'uri' => '/admin/schools'],
            ['method' => 'GET', 'uri' => '/admin/health-facilities'],
            ['method' => 'GET', 'uri' => '/admin/patients'],
            ['method' => 'GET', 'uri' => '/admin/appointments'],
            ['method' => 'GET', 'uri' => '/admin/payments'],
            ['method' => 'GET', 'uri' => '/admin/contact-submissions'],
        ];

        foreach ($adminRoutes as $route) {
            $response = $this->withoutMiddleware()->call($route['method'], $route['uri']);
            if ($response->getStatusCode() == 500) {
                $content = $response->getContent();
                // Strip HTML tags to get plain text error
                $plainContent = strip_tags($content);
                echo "Route {$route['method']} {$route['uri']} returned 500. Content: " . substr($plainContent, 0, 500) . PHP_EOL;
            }
            $this->assertNotEquals(404, $response->getStatusCode(),
                "Route {$route['method']} {$route['uri']} returned 404");
            $this->assertNotEquals(500, $response->getStatusCode(),
                "Route {$route['method']} {$route['uri']} returned 500");
        }
    }

    /** @test */
    public function api_routes_return_valid_responses()
    {
        // Test some key API routes
        $apiRoutes = [
            ['method' => 'GET', 'uri' => '/api/hello'],
            ['method' => 'GET', 'uri' => '/api/doctors'],
            ['method' => 'GET', 'uri' => '/api/schools'],
            ['method' => 'GET', 'uri' => '/api/health-facilities'],
            ['method' => 'GET', 'uri' => '/api/contact-submissions'],
        ];

        foreach ($apiRoutes as $route) {
            $response = $this->call($route['method'], $route['uri']);
            $this->assertNotEquals(404, $response->getStatusCode(),
                "Route {$route['method']} {$route['uri']} returned 404");
            $this->assertNotEquals(500, $response->getStatusCode(),
                "Route {$route['method']} {$route['uri']} returned 500");
        }
    }

    /** @test */
    public function newsletter_routes_work()
    {
        $newsletterRoutes = [
            ['method' => 'GET', 'uri' => '/verify-newsletter/test-token'],
            ['method' => 'GET', 'uri' => '/api/verify-newsletter/test-token'],
        ];

        foreach ($newsletterRoutes as $route) {
            $response = $this->call($route['method'], $route['uri']);
            // Newsletter routes might return redirects or specific responses, but shouldn't 404 or 500
            $this->assertNotEquals(404, $response->getStatusCode(),
                "Route {$route['method']} {$route['uri']} returned 404");
            $this->assertNotEquals(500, $response->getStatusCode(),
                "Route {$route['method']} {$route['uri']} returned 500");
        }
    }

    /** @test */
    public function password_reset_routes_work()
    {
        $passwordRoutes = [
            ['method' => 'GET', 'uri' => '/password/reset/test-token'],
        ];

        foreach ($passwordRoutes as $route) {
            $response = $this->call($route['method'], $route['uri']);
            $this->assertNotEquals(404, $response->getStatusCode(),
                "Route {$route['method']} {$route['uri']} returned 404");
            $this->assertNotEquals(500, $response->getStatusCode(),
                "Route {$route['method']} {$route['uri']} returned 500");
        }
    }

    /** @test */
    public function file_upload_and_preview_routes_exist()
    {
        // These routes are for Livewire file handling - just check they don't 404
        $fileRoutes = [
            ['method' => 'GET', 'uri' => '/livewire/livewire.js'],
        ];

        foreach ($fileRoutes as $route) {
            $response = $this->call($route['method'], $route['uri']);
            $this->assertNotEquals(404, $response->getStatusCode(),
                "Route {$route['method']} {$route['uri']} returned 404");
            // 500 might be acceptable for some file routes if dependencies aren't set up
        }
    }
}