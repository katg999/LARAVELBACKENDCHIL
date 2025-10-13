<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\School;
use App\Models\HealthFacility;
use App\Models\LabTest;
use App\Models\LabRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Duration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesTestDurations;
use Tests\TestCase;

class UnifiedPatientManagementTest extends TestCase
{
    use RefreshDatabase, CreatesTestDurations;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->createTestDurations();
    }

    /** @test */
    public function it_can_find_or_create_patient_for_school()
    {
        $school = School::create([
            'name' => 'Test School',
            'email' => 'test@school.com',
            'contact' => '+256700000000',
        ]);

        // Create a patient for the school
        $patientData = [
            'name' => 'John Doe',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
            'parent_contact' => '+256700000000',
        ];

        $patient = Patient::findOrCreate($patientData, [
            'school_id' => $school->id,
            'grade' => 'Grade 5',
        ]);

        $this->assertEquals('John Doe', $patient->name);
        $this->assertEquals($school->id, $patient->school_id);
        $this->assertEquals('Grade 5', $patient->grade);
        $this->assertNotNull($patient->patient_id);

        // Try to find or create the same patient again
        $samePatient = Patient::findOrCreate($patientData, [
            'school_id' => $school->id,
            'grade' => 'Grade 6', // Different grade
        ]);

        // Should return the same patient
        $this->assertEquals($patient->id, $samePatient->id);
        $this->assertEquals('Grade 5', $samePatient->grade); // Should keep original grade
    }

    /** @test */
    public function it_can_find_or_create_patient_for_health_facility()
    {
        $healthFacility = HealthFacility::create([
            'name' => 'Test Health Facility',
            'email' => 'test@facility.com',
            'contact_number' => '+256711111111',
            'contact' => '+256711111111',
            'location' => 'Test Location',
            'type' => 'hospital',
        ]);

        // Create a patient for the health facility
        $patientData = [
            'name' => 'Jane Smith',
            'birth_date' => '1985-05-15',
            'gender' => 'female',
            'contact_number' => '+256711111111',
        ];

        $patient = Patient::findOrCreate($patientData, [
            'health_facility_id' => $healthFacility->id,
            'medical_history' => 'No known allergies',
        ]);

        $this->assertEquals('Jane Smith', $patient->name);
        $this->assertEquals($healthFacility->id, $patient->health_facility_id);
        $this->assertEquals('No known allergies', $patient->medical_history);
        $this->assertNotNull($patient->patient_id);

        // Try to find or create the same patient again
        $samePatient = Patient::findOrCreate($patientData, [
            'health_facility_id' => $healthFacility->id,
            'medical_history' => 'Updated history',
        ]);

        // Should return the same patient
        $this->assertEquals($patient->id, $samePatient->id);
        $this->assertEquals('No known allergies', $samePatient->medical_history); // Should keep original
    }

    /** @test */
    public function it_generates_unique_patient_ids()
    {
        $school = School::create([
            'name' => 'Test School',
            'email' => 'test@school.com',
            'contact' => '+256700000000',
        ]);

        $patient1 = Patient::findOrCreate([
            'name' => 'Patient One',
            'birth_date' => '2000-01-01',
            'gender' => 'male',
        ], ['school_id' => $school->id]);

        $patient2 = Patient::findOrCreate([
            'name' => 'Patient Two',
            'birth_date' => '2000-01-01',
            'gender' => 'female',
        ], ['school_id' => $school->id]);

        $this->assertNotEquals($patient1->patient_id, $patient2->patient_id);
        $this->assertMatchesRegularExpression('/^P\d{6}$/', $patient1->patient_id);
        $this->assertMatchesRegularExpression('/^P\d{6}$/', $patient2->patient_id);
    }

    /** @test */
    public function it_updates_patient_associations_when_found()
    {
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

        // Create patient for school
        $patient = Patient::findOrCreate([
            'name' => 'John Doe',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
        ], ['school_id' => $school->id]);

        $this->assertEquals($school->id, $patient->school_id);
        $this->assertNull($patient->health_facility_id);

        // Find same patient and associate with health facility
        $samePatient = Patient::findOrCreate([
            'name' => 'John Doe',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
        ], ['health_facility_id' => $healthFacility->id]);

        // Should be the same patient with both associations
        $this->assertEquals($patient->id, $samePatient->id);
        $this->assertEquals($school->id, $samePatient->school_id);
        $this->assertEquals($healthFacility->id, $samePatient->health_facility_id);
    }

    /** @test */
    public function it_handles_patient_scopes_correctly()
    {
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

        // Create school patients
        $student1 = Patient::findOrCreate([
            'name' => 'Student 1',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
        ], ['school_id' => $school->id]);

        $student2 = Patient::findOrCreate([
            'name' => 'Student 2',
            'birth_date' => '2010-02-01',
            'gender' => 'female',
        ], ['school_id' => $school->id]);

        // Create health facility patients
        $patient1 = Patient::findOrCreate([
            'name' => 'Patient 1',
            'birth_date' => '1980-01-01',
            'gender' => 'male',
        ], ['health_facility_id' => $healthFacility->id]);

        $patient2 = Patient::findOrCreate([
            'name' => 'Patient 2',
            'birth_date' => '1985-01-01',
            'gender' => 'female',
        ], ['health_facility_id' => $healthFacility->id]);

        // Test scopes
        $this->assertCount(2, Patient::students()->get());
        $this->assertCount(2, Patient::patients()->get());
        $this->assertCount(2, Patient::forSchool($school->id)->get());
        $this->assertCount(2, Patient::forHealthFacility($healthFacility->id)->get());
    }

    /** @test */
    public function it_handles_patient_attributes_correctly()
    {
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

        $student = Patient::findOrCreate([
            'name' => 'Young Student',
            'birth_date' => '2015-01-01', // 10 years old
            'gender' => 'male',
        ], ['school_id' => $school->id]);

        $adultPatient = Patient::findOrCreate([
            'name' => 'Adult Patient',
            'birth_date' => '1980-01-01', // 45 years old
            'gender' => 'female',
        ], ['health_facility_id' => $healthFacility->id]);

        // Test computed attributes
        $this->assertTrue($student->is_student);
        $this->assertFalse($student->is_health_facility_patient);
        $this->assertEquals($school->id, $student->institution->id);

        $this->assertFalse($adultPatient->is_student);
        $this->assertTrue($adultPatient->is_health_facility_patient);
        $this->assertEquals($healthFacility->id, $adultPatient->institution->id);

        // Test age calculation (approximate)
        $this->assertEquals(10, $student->age);
        $this->assertEquals(45, $adultPatient->age);
    }

    /** @test */
    public function it_handles_lab_tests_relationships_correctly()
    {
        $school = School::create([
            'name' => 'Test School',
            'email' => 'test@school.com',
            'contact' => '+256700000000',
        ]);

        $patient = Patient::findOrCreate([
            'name' => 'Test Patient',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
        ], ['school_id' => $school->id]);

        $labTest = LabTest::create([
            'school_id' => $school->id,
            'patient_id' => $patient->id,
            'test_type' => 'Blood Test',
            'notes' => 'Routine checkup',
            'status' => 'pending',
        ]);

        // Test relationships
        $this->assertCount(1, $patient->labTests);
        $this->assertEquals($labTest->id, $patient->labTests->first()->id);
        $this->assertEquals($patient->id, $labTest->patient->id);
    }

    /** @test */
    public function it_handles_appointments_relationships_correctly()
    {
        $school = School::create([
            'name' => 'Test School',
            'email' => 'test@school.com',
            'contact' => '+256700000000',
        ]);

        $doctor = Doctor::create([
            'name' => 'Dr. Test',
            'email' => 'dr@test.com',
            'specialization' => 'General',
            'contact' => '+256700000001',
        ]);

        $patient = Patient::findOrCreate([
            'name' => 'Test Patient',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
        ], ['school_id' => $school->id]);

        $appointment = Appointment::create([
            'school_id' => $school->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_time' => now()->addDays(1),
            'reason' => 'Regular checkup',
            'status' => 'pending',
            'duration_id' => $this->getGeneralDurationId(),
        ]);

        // Test relationships
        $this->assertCount(1, $patient->appointments);
        $this->assertEquals($appointment->id, $patient->appointments->first()->id);
        $this->assertEquals($patient->id, $appointment->patient->id);
    }

    /** @test */
    public function it_can_create_patient_via_api_for_school()
    {
        $school = School::create([
            'name' => 'Test School',
            'email' => 'test@school.com',
            'contact' => '+256700000000',
        ]);

        $patientData = [
            'patient_type' => 'new',
            'school_id' => $school->id,
            'name' => 'API Student',
            'gender' => 'male',
            'grade' => 'Grade 4',
            'birth_date' => '2011-01-01',
            'parent_contact' => '+256700000001',
        ];

        $response = $this->postJson('/api/students', $patientData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Student created successfully.',
                    'patient' => [
                        'name' => 'API Student',
                        'gender' => 'male',
                        'grade' => 'Grade 4',
                        'school_id' => $school->id,
                    ]
                ]);

        $this->assertDatabaseHas('patients', [
            'name' => 'API Student',
            'school_id' => $school->id,
        ]);
    }

    /** @test */
    public function it_can_create_patient_via_api_for_health_facility()
    {
        $healthFacility = HealthFacility::create([
            'name' => 'Test Health Facility',
            'email' => 'test@facility.com',
            'contact_number' => '+256711111111',
            'contact' => '+256711111111',
            'location' => 'Test Location',
            'type' => 'hospital',
        ]);

        $patientData = [
            'patient_type' => 'new',
            'health_facility_id' => $healthFacility->id,
            'name' => 'API Patient',
            'gender' => 'female',
            'birth_date' => '1985-01-01',
            'contact_number' => '+256711111112',
            'medical_history' => 'Hypertension',
        ];

        $response = $this->postJson('/api/patients', $patientData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Patient created successfully.',
                    'patient' => [
                        'name' => 'API Patient',
                        'gender' => 'female',
                        'health_facility_id' => $healthFacility->id,
                    ]
                ]);

        $this->assertDatabaseHas('patients', [
            'name' => 'API Patient',
            'health_facility_id' => $healthFacility->id,
        ]);
    }

    /** @test */
    public function it_validates_patient_creation_via_api()
    {
        $response = $this->postJson('/api/students', [
            'name' => '', // Invalid: empty name
            'gender' => 'invalid_gender', // Invalid: not in allowed values
            'birth_date' => 'invalid-date', // Invalid: not a date
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Validation failed.',
                ])
                ->assertJsonStructure([
                    'errors' => [
                        'patient_type',
                        'school_id',
                    ]
                ]);
    }

    /** @test */
    public function it_can_create_patient_via_web_route()
    {
        $school = School::create([
            'name' => 'Test School',
            'email' => 'test@school.com',
            'contact' => '+256700000000',
        ]);

        $patientData = [
            'patient_type' => 'new',
            'school_id' => $school->id,
            'name' => 'Web Student',
            'gender' => 'female',
            'grade' => 'Grade 3',
            'birth_date' => '2012-01-01',
            'parent_contact' => '+256700000002',
        ];

        $response = $this->withoutMiddleware()->post('/students/create', $patientData);

        $response->assertRedirect(route('students', ['school' => $school->id]));

        $this->assertDatabaseHas('patients', [
            'name' => 'Web Student',
            'school_id' => $school->id,
            'grade' => 'Grade 3',
        ]);
    }

    /** @test */
    public function it_can_delete_patient_via_web_route()
    {
        $school = School::create([
            'name' => 'Test School',
            'email' => 'test@school.com',
            'contact' => '+256700000000',
        ]);

        $patient = Patient::findOrCreate([
            'name' => 'Student to Delete',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
        ], ['school_id' => $school->id]);

        $response = $this->withoutMiddleware()->delete("/students/{$patient->id}/delete");

        $response->assertRedirect(route('students', ['school' => $school->id]))
                ->assertSessionHas('success', 'Student deleted successfully.');

        $this->assertDatabaseMissing('patients', ['id' => $patient->id]);
    }

    /** @test */
    public function it_prevents_deleting_patient_with_appointments()
    {
        $school = School::create([
            'name' => 'Test School',
            'email' => 'test@school.com',
            'contact' => '+256700000000',
        ]);

        $doctor = Doctor::create([
            'name' => 'Dr. Test',
            'email' => 'dr@test.com',
            'specialization' => 'General',
            'contact' => '+256700000001',
        ]);

        $patient = Patient::findOrCreate([
            'name' => 'Student with Appointment',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
        ], ['school_id' => $school->id]);

        // Create an appointment for the patient
        Appointment::create([
            'school_id' => $school->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_time' => now()->addDays(1),
            'reason' => 'Medical consultation',
            'status' => 'pending',
            'duration_id' => $this->getGeneralDurationId(),
        ]);

        $response = $this->withoutMiddleware()->delete("/students/{$patient->id}/delete");

        $response->assertRedirect(route('students', ['school' => $school->id]))
                ->assertSessionHas('error', 'Cannot delete student with existing appointments.');

        $this->assertDatabaseHas('patients', ['id' => $patient->id]);
    }

    /** @test */
    public function it_can_display_students_for_school()
    {
        $school = School::create([
            'name' => 'Test School',
            'email' => 'test@school.com',
            'contact' => '+256700000000',
        ]);

        $patient1 = Patient::findOrCreate([
            'name' => 'Student 1',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
        ], ['school_id' => $school->id, 'grade' => 'Grade 5']);

        $patient2 = Patient::findOrCreate([
            'name' => 'Student 2',
            'birth_date' => '2010-02-01',
            'gender' => 'female',
        ], ['school_id' => $school->id, 'grade' => 'Grade 4']);

        $response = $this->get("/students/{$school->id}");

        $response->assertStatus(200)
                ->assertViewHas('school', $school)
                ->assertViewHas('students');

        $students = $response->viewData('students');
        $this->assertCount(2, $students);
        $this->assertEquals($patient1->id, $students->first()->id);
    }

    /** @test */
    public function it_can_display_school_dashboard_with_patient_data()
    {
        $school = School::create([
            'name' => 'Test School',
            'email' => 'test@school.com',
            'contact' => '+256700000000',
        ]);

        $doctor = Doctor::create([
            'name' => 'Dr. Test',
            'email' => 'dr@test.com',
            'specialization' => 'General',
            'contact' => '+256700000001',
        ]);

        $patient = Patient::findOrCreate([
            'name' => 'Dashboard Student',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
        ], ['school_id' => $school->id]);

        // Create appointment and lab test
        Appointment::create([
            'school_id' => $school->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_time' => now()->addDays(1),
            'reason' => 'Regular checkup',
            'status' => 'pending',
            'duration_id' => $this->getGeneralDurationId(),
        ]);

        LabTest::create([
            'school_id' => $school->id,
            'patient_id' => $patient->id,
            'test_type' => 'Blood Test',
            'status' => 'pending',
        ]);

        $response = $this->get("/school-dashboard/{$school->id}");

        $response->assertStatus(200)
                ->assertViewHas('school', $school)
                ->assertViewHas('studentsCount', 1)
                ->assertViewHas('appointmentsCount', 1)
                ->assertViewHas('labTestsCount', 1)
                ->assertViewHas('students')
                ->assertViewHas('appointments')
                ->assertViewHas('labTests');
    }

    /** @test */
    public function it_can_associate_existing_patient_with_school_via_web_form()
    {
        $school = School::create([
            'name' => 'Test School',
            'email' => 'test@school.com',
            'contact' => '+256700000000',
        ]);

        // Create an existing patient
        $existingPatient = Patient::create([
            'patient_id' => 'P123456',
            'name' => 'Existing Patient',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
        ]);

        $response = $this->withoutMiddleware()->post('/students/create', [
            'patient_type' => 'existing',
            'patient_id' => 'P123456',
            'school_id' => $school->id,
            'grade' => 'Grade 5',
        ]);

        $response->assertRedirect("/students/{$school->id}");

        // Check that the patient is now associated with the school
        $existingPatient->refresh();
        $this->assertEquals($school->id, $existingPatient->school_id);
        $this->assertEquals('Grade 5', $existingPatient->grade);
    }

    /** @test */
    public function it_can_associate_existing_patient_with_health_facility_via_web_form()
    {
        $healthFacility = HealthFacility::create([
            'name' => 'Test Health Facility',
            'email' => 'test@facility.com',
            'contact_number' => '+256711111111',
            'contact' => '+256711111111',
            'location' => 'Test Location',
            'type' => 'hospital',
        ]);

        // Create an existing patient
        $existingPatient = Patient::create([
            'patient_id' => 'P654321',
            'name' => 'Existing HF Patient',
            'birth_date' => '1985-05-15',
            'gender' => 'female',
        ]);

        $response = $this->withoutMiddleware()->post('/patients/create', [
            'patient_type' => 'existing',
            'patient_id' => 'P654321',
            'health_facility_id' => $healthFacility->id,
        ]);

        $response->assertRedirect("/health-facility/{$healthFacility->id}/patients");

        // Check that the patient is now associated with the health facility
        $existingPatient->refresh();
        $this->assertEquals($healthFacility->id, $existingPatient->health_facility_id);
    }

    /** @test */
    public function it_validates_patient_id_exists_when_associating_existing_patient()
    {
        $school = School::create([
            'name' => 'Test School',
            'email' => 'test@school.com',
            'contact' => '+256700000000',
        ]);

        $response = $this->withoutMiddleware()->post('/students/create', [
            'patient_type' => 'existing',
            'patient_id' => 'P999999', // Non-existent patient ID
            'school_id' => $school->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('patient_id');
    }

    /** @test */
    public function it_can_associate_existing_patient_with_school_via_api()
    {
        $school = School::create([
            'name' => 'Test School',
            'email' => 'test@school.com',
            'contact' => '+256700000000',
        ]);

        // Create an existing patient
        $existingPatient = Patient::create([
            'patient_id' => 'P123456',
            'name' => 'Existing API Patient',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
        ]);

        $response = $this->postJson('/api/students', [
            'patient_type' => 'existing',
            'patient_id' => 'P123456',
            'school_id' => $school->id,
            'grade' => 'Grade 5',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Existing patient associated with school successfully.',
                    'patient' => [
                        'patient_id' => 'P123456',
                        'name' => 'Existing API Patient',
                        'school_id' => $school->id,
                        'grade' => 'Grade 5',
                    ]
                ]);

        // Check that the patient is now associated with the school
        $existingPatient->refresh();
        $this->assertEquals($school->id, $existingPatient->school_id);
        $this->assertEquals('Grade 5', $existingPatient->grade);
    }

    /** @test */
    public function it_can_associate_existing_patient_with_health_facility_via_api()
    {
        $healthFacility = HealthFacility::create([
            'name' => 'Test Health Facility',
            'email' => 'test@facility.com',
            'contact_number' => '+256711111111',
            'contact' => '+256711111111',
            'location' => 'Test Location',
            'type' => 'hospital',
        ]);

        // Create an existing patient
        $existingPatient = Patient::create([
            'patient_id' => 'P654321',
            'name' => 'Existing API HF Patient',
            'birth_date' => '1985-05-15',
            'gender' => 'female',
        ]);

        $response = $this->postJson('/api/patients', [
            'patient_type' => 'existing',
            'patient_id' => 'P654321',
            'health_facility_id' => $healthFacility->id,
            'medical_history' => 'Previous allergies',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Existing patient associated with health facility successfully.',
                    'patient' => [
                        'patient_id' => 'P654321',
                        'name' => 'Existing API HF Patient',
                        'health_facility_id' => $healthFacility->id,
                        'medical_history' => 'Previous allergies',
                    ]
                ]);

        // Check that the patient is now associated with the health facility
        $existingPatient->refresh();
        $this->assertEquals($healthFacility->id, $existingPatient->health_facility_id);
        $this->assertEquals('Previous allergies', $existingPatient->medical_history);
    }

    /** @test */
    public function it_validates_patient_id_exists_when_associating_existing_patient_via_api()
    {
        $school = School::create([
            'name' => 'Test School',
            'email' => 'test@school.com',
            'contact' => '+256700000000',
        ]);

        $response = $this->postJson('/api/students', [
            'patient_type' => 'existing',
            'patient_id' => 'P999999', // Non-existent patient ID
            'school_id' => $school->id,
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Validation failed.',
                ])
                ->assertJsonStructure([
                    'errors' => [
                        'patient_id',
                    ]
                ]);
    }

    /** @test */
    public function it_can_display_general_patient_creation_form()
    {
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

        $response = $this->get('/patients/create');

        $response->assertStatus(200)
                ->assertViewHas('schools')
                ->assertViewHas('healthFacilities');

        $schools = $response->viewData('schools');
        $healthFacilities = $response->viewData('healthFacilities');

        $this->assertCount(1, $schools);
        $this->assertCount(1, $healthFacilities);
    }

    /** @test */
    public function it_can_create_general_patient_via_web_form()
    {
        $response = $this->withoutMiddleware()->post('/patients/general', [
            'patient_type' => 'new',
            'name' => 'General Patient',
            'gender' => 'female',
            'birth_date' => '1990-01-01',
            'contact_number' => '+256711111112',
            'medical_history' => 'No known allergies',
        ]);

        $response->assertRedirect(route('home'));

        $this->assertDatabaseHas('patients', [
            'name' => 'General Patient',
            'gender' => 'female',
            'contact_number' => '+256711111112',
            'medical_history' => 'No known allergies',
            'school_id' => null,
            'health_facility_id' => null,
        ]);
    }

    /** @test */
    public function it_can_create_general_patient_with_institution_association_via_web_form()
    {
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

        $response = $this->withoutMiddleware()->post('/patients/general', [
            'patient_type' => 'new',
            'name' => 'Associated Patient',
            'gender' => 'male',
            'birth_date' => '2005-05-15',
            'contact_number' => '+256711111113',
            'school_id' => $school->id,
            'grade' => 'Grade 8',
            'parent_contact' => '+256700000001',
        ]);

        $response->assertRedirect(route('home'));

        $this->assertDatabaseHas('patients', [
            'name' => 'Associated Patient',
            'gender' => 'male',
            'school_id' => $school->id,
            'grade' => 'Grade 8',
            'parent_contact' => '+256700000001',
            'health_facility_id' => null,
        ]);
    }

    /** @test */
    public function it_can_associate_existing_patient_with_institution_via_general_form()
    {
        $school = School::create([
            'name' => 'Test School',
            'email' => 'test@school.com',
            'contact' => '+256700000000',
        ]);

        // Create an existing patient
        $existingPatient = Patient::create([
            'patient_id' => 'P777777',
            'name' => 'Existing General Patient',
            'birth_date' => '1995-10-10',
            'gender' => 'female',
        ]);

        $response = $this->withoutMiddleware()->post('/patients/general', [
            'patient_type' => 'existing',
            'patient_id' => 'P777777',
            'school_id' => $school->id,
            'grade' => 'Grade 10',
        ]);

        $response->assertRedirect(route('home'));

        // Check that the patient is now associated with the school
        $existingPatient->refresh();
        $this->assertEquals($school->id, $existingPatient->school_id);
        $this->assertEquals('Grade 10', $existingPatient->grade);
    }

    /** @test */
    public function it_validates_general_patient_creation_form()
    {
        $response = $this->withoutMiddleware()->post('/patients/general', [
            'patient_type' => 'new',
            'name' => '', // Invalid: empty name
            'gender' => 'invalid_gender', // Invalid: not in allowed values
            'birth_date' => 'not-a-date', // Invalid: not a date
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'name' => 'The name field is required.',
            'gender' => 'The selected gender is invalid.',
            'birth_date' => 'The birth date is not a valid date.',
        ]);
    }
}
