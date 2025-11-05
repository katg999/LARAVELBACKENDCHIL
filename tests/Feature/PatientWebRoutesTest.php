<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\School;
use App\Models\HealthFacility;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\LabTest;
use App\Models\Duration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesTestDurations;
use Tests\TestCase;

class PatientWebRoutesTest extends TestCase
{
    use RefreshDatabase, CreatesTestDurations;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure durations exist for all tests
        $this->createTestDurations();
    }

    protected function mockAuthenticatedSchool(School $school)
    {
        // Mock the session to have an authenticated school user
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
        // Mock the session to have an authenticated health facility user
        $this->withSession([
            'authenticated_user' => [
                'id' => $healthFacility->id,
                'type' => 'health_facility',
                'name' => $healthFacility->name,
                'email' => $healthFacility->email,
            ]
        ]);
    }

    /** @test */
    public function it_can_display_students_page()
    {
        $school = School::create([
            'name' => 'Test School',
            'email' => 'test@school.com',
            'contact' => '+256700000000',
        ]);

        $student1 = Patient::create([
            'patient_id' => 'P001',
            'name' => 'Student 1',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
            'school_id' => $school->id,
            'grade' => 'Grade 5',
        ]);

        $student2 = Patient::create([
            'patient_id' => 'P002',
            'name' => 'Student 2',
            'birth_date' => '2010-02-01',
            'gender' => 'female',
            'school_id' => $school->id,
            'grade' => 'Grade 4',
        ]);

        $response = $this->get("/students/{$school->id}");

        $response->assertStatus(200)
                ->assertViewIs('school.students')
                ->assertViewHas('school', $school)
                ->assertViewHas('students');

        $students = $response->viewData('students');
        $this->assertCount(2, $students);
        $this->assertEquals('Student 1', $students->first()->name);
    }

    /** @test */
    public function it_can_create_student_via_web_form()
    {
        $school = School::create([
            'name' => 'Test School',
            'email' => 'test@school.com',
            'contact' => '+256700000000',
        ]);

        $data = [
            'patient_type' => 'new',
            'school_id' => $school->id,
            'name' => 'Web Student',
            'gender' => 'male',
            'grade' => 'Grade 3',
            'birth_date' => '2012-03-15',
            'parent_contact' => '+256700000003',
        ];

        $response = $this->withoutMiddleware()->post('/students/create', $data);

        $response->assertRedirect("/students?school={$school->id}");

        $this->assertDatabaseHas('patients', [
            'name' => 'Web Student',
            'school_id' => $school->id,
            'grade' => 'Grade 3',
            'gender' => 'male',
        ]);

        $student = Patient::where('name', 'Web Student')->first();
        $this->assertNotNull($student->patient_id);
    }

    /** @test */
    public function it_validates_web_student_creation()
    {
        $school = School::create([
            'name' => 'Test School',
            'email' => 'test@school.com',
            'contact' => '+256700000000',
        ]);

        $response = $this->withoutMiddleware()->post('/students/create', [
            'patient_type' => 'new',
            'school_id' => $school->id, // Use the created school's ID
            'name' => '',
            'gender' => 'invalid',
            'birth_date' => 'not-a-date',
        ]);

        $response->assertRedirect()
                ->assertSessionHasErrors([
                    'name' => 'The name field is required.',
                    'gender' => 'The selected gender is invalid.',
                    'grade' => 'The grade field is required.',
                    'parent_contact' => 'The parent contact field is required.',
                    'birth_date' => 'The birth date is not a valid date.',
                ]);
    }

    /** @test */
    public function it_can_delete_student_via_web_route()
    {
        $school = School::create([
            'name' => 'Test School',
            'email' => 'test@school.com',
            'contact' => '+256700000000',
        ]);

        $student = Patient::create([
            'patient_id' => 'P001',
            'name' => 'Student to Delete',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
            'school_id' => $school->id,
        ]);

        $response = $this->withoutMiddleware()->delete("/students/{$school->id}/{$student->id}/delete");

        $response->assertRedirect("/students?school={$school->id}")
                ->assertSessionHas('success', 'Student deleted successfully.');

        $this->assertDatabaseMissing('patients', ['id' => $student->id]);
    }

    /** @test */
    public function it_prevents_deleting_student_with_appointments()
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

        $student = Patient::create([
            'patient_id' => 'P001',
            'name' => 'Student with Appointment',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
            'school_id' => $school->id,
        ]);

        // Create an appointment
        Appointment::create([
            'school_id' => $school->id,
            'patient_id' => $student->id,
            'doctor_id' => $doctor->id,
            'appointment_time' => now()->addDays(1),
            'status' => 'pending',
            'duration_id' => $this->getGeneralDurationId(),
            'reason' => 'Medical checkup',
        ]);

        $response = $this->withoutMiddleware()->delete("/students/{$school->id}/{$student->id}/delete");

        $response->assertRedirect("/students?school={$school->id}")
                ->assertSessionHas('error', 'Cannot delete student with existing appointments.');

        $this->assertDatabaseHas('patients', ['id' => $student->id]);
    }

    /** @test */
    /** @skip Authentication system needs refactoring */
    public function it_can_display_school_dashboard_with_correct_counts()
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
            'school_id' => $school->id,
        ]);

        // Create students
        $student1 = Patient::create([
            'patient_id' => 'P001',
            'name' => 'Student 1',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
            'school_id' => $school->id,
        ]);

        $student2 = Patient::create([
            'patient_id' => 'P002',
            'name' => 'Student 2',
            'birth_date' => '2010-02-01',
            'gender' => 'female',
            'school_id' => $school->id,
        ]);

        // Create appointments
        Appointment::create([
            'school_id' => $school->id,
            'patient_id' => $student1->id,
            'doctor_id' => $doctor->id,
            'appointment_time' => now()->addDays(1),
            'status' => 'pending',
            'duration_id' => $this->getGeneralDurationId(),
            'reason' => 'Medical checkup',
        ]);

        Appointment::create([
            'school_id' => $school->id,
            'patient_id' => $student2->id,
            'doctor_id' => $doctor->id,
            'appointment_time' => now()->addDays(2),
            'status' => 'completed',
            'duration_id' => $this->getGeneralDurationId(),
            'reason' => 'Follow-up checkup',
        ]);

        // Create lab tests
        LabTest::create([
            'school_id' => $school->id,
            'patient_id' => $student1->id,
            'test_type' => 'Blood Test',
            'status' => 'pending',
        ]);

        LabTest::create([
            'school_id' => $school->id,
            'patient_id' => $student2->id,
            'test_type' => 'Urine Test',
            'status' => 'completed',
        ]);

        $this->mockAuthenticatedSchool($school);

        $response = $this->get("/school-dashboard");

        $response->assertStatus(200)
                ->assertViewHas('school', $school)
                ->assertViewHas('studentsCount', 2)
                ->assertViewHas('appointmentsCount', 2)
                ->assertViewHas('labTestsCount', 2)
                ->assertViewHas('doctorsCount', 1);
    }

    /** @test */
    public function it_can_display_lab_tests_page_with_patient_relationships()
    {
        $school = School::create([
            'name' => 'Test School',
            'email' => 'test@school.com',
            'contact' => '+256700000000',
        ]);

        $student = Patient::create([
            'patient_id' => 'P001',
            'name' => 'Test Student',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
            'school_id' => $school->id,
        ]);

        $labTest = LabTest::create([
            'school_id' => $school->id,
            'patient_id' => $student->id,
            'test_type' => 'Blood Test',
            'status' => 'pending',
            'notes' => 'Routine checkup',
        ]);

        $response = $this->get("/lab-tests/{$school->id}");

        $response->assertStatus(200)
                ->assertViewHas('school', $school)
                ->assertViewHas('labTests')
                ->assertViewHas('students');

        $labTests = $response->viewData('labTests');
        $this->assertCount(1, $labTests);
        $this->assertEquals($student->id, $labTests->first()->patient->id);
        $this->assertEquals('Test Student', $labTests->first()->patient->name);
    }

    /** @test */
    public function it_can_display_book_doctor_page_with_patient_data()
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

        $student = Patient::create([
            'patient_id' => 'P001',
            'name' => 'Test Student',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
            'school_id' => $school->id,
        ]);

        $appointment = Appointment::create([
            'school_id' => $school->id,
            'patient_id' => $student->id,
            'doctor_id' => $doctor->id,
            'appointment_time' => now()->addDays(1),
            'status' => 'pending',
            'duration_id' => $this->getGeneralDurationId(),
            'reason' => 'Medical consultation',
        ]);

        $response = $this->get("/book-doctor/{$school->id}");

        $response->assertStatus(200)
                ->assertViewHas('school', $school)
                ->assertViewHas('appointments')
                ->assertViewHas('patients')
                ->assertViewHas('doctors');

        $appointments = $response->viewData('appointments');
        $patients = $response->viewData('patients');
        $doctors = $response->viewData('doctors');

        $this->assertCount(1, $appointments);
        $this->assertCount(1, $patients);
        $this->assertCount(1, $doctors);

        $this->assertEquals($student->id, $appointments->first()->patient->id);
        $this->assertEquals($student->id, $patients->first()->id);
        $this->assertEquals($doctor->id, $doctors->first()->id);
    }

    /** @test */
    /** @skip Authentication system needs refactoring */
    public function it_can_display_health_facility_patients_page()
    {
        $healthFacility = HealthFacility::create([
            'name' => 'Test Health Facility',
            'email' => 'test@facility.com',
            'contact_number' => '+256711111111',
            'contact' => '+256711111111',
            'location' => 'Test Location',
            'type' => 'hospital',
        ]);

        $patient1 = Patient::create([
            'patient_id' => 'P001',
            'name' => 'Patient 1',
            'birth_date' => '1980-01-01',
            'gender' => 'male',
            'health_facility_id' => $healthFacility->id,
            'medical_history' => 'Hypertension',
        ]);

        $patient2 = Patient::create([
            'patient_id' => 'P002',
            'name' => 'Patient 2',
            'birth_date' => '1985-01-01',
            'gender' => 'female',
            'health_facility_id' => $healthFacility->id,
            'medical_history' => 'Diabetes',
        ]);

        $this->mockAuthenticatedHealthFacility($healthFacility);

        $response = $this->get("/health-facility/patients");

        $response->assertStatus(200)
                ->assertViewHas('healthFacility', $healthFacility)
                ->assertViewHas('patients');

        $patients = $response->viewData('patients');
        $this->assertCount(2, $patients);
    }

    /** @test */
    /** @skip Authentication system needs refactoring */
    public function it_can_create_health_facility_patient_via_web_route()
    {
        $healthFacility = HealthFacility::create([
            'name' => 'Test Health Facility',
            'email' => 'test@facility.com',
            'contact_number' => '+256711111111',
            'contact' => '+256711111111',
            'location' => 'Test Location',
            'type' => 'hospital',
        ]);

        $data = [
            'patient_type' => 'new',
            'health_facility_id' => $healthFacility->id,
            'name' => 'Web HF Patient',
            'gender' => 'female',
            'birth_date' => '1975-08-20',
            'contact_number' => '+256711111113',
        ];

        $response = $this->withoutMiddleware()->post('/patients/create', $data);

        $response->assertRedirect("/health-facility/patients");

        $this->assertDatabaseHas('patients', [
            'name' => 'Web HF Patient',
            'health_facility_id' => $healthFacility->id,
        ]);
    }

    /** @test */
    public function it_can_display_patient_profile_page()
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

        $doctor = Doctor::create([
            'name' => 'Dr. Test',
            'email' => 'dr@test.com',
            'specialization' => 'General',
            'contact' => '+256700000001',
        ]);

        $patient = Patient::create([
            'patient_id' => 'P001',
            'name' => 'Profile Test Patient',
            'gender' => 'female',
            'birth_date' => '1990-05-15',
            'contact_number' => '+256711111114',
            'parent_contact' => '+256700000002',
            'grade' => 'Grade 10',
            'school_id' => $school->id,
            'health_facility_id' => $healthFacility->id,
        ]);

        // Create medical history
        $medicalHistory = \App\Models\MedicalHistory::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'content' => 'Previous surgery for appendicitis',
            'recorded_date' => '2023-06-15',
        ]);

        // Create appointment
        $appointment = Appointment::create([
            'school_id' => $school->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_time' => now()->addDays(1),
            'status' => 'completed',
            'duration_id' => $this->getGeneralDurationId(),
            'reason' => 'Follow-up checkup',
            'notes' => 'Patient recovering well',
        ]);

        // Create lab test
        $labTest = LabTest::create([
            'school_id' => $school->id,
            'patient_id' => $patient->id,
            'test_type' => 'Blood Test',
            'status' => 'completed',
            'results' => 'All values within normal range',
        ]);

        // Create maternal document
        $maternalDocument = \App\Models\MaternalDocument::create([
            'patient_id' => $patient->id,
            'health_facility_id' => $healthFacility->id,
            'original_filename' => 'antenatal_record.pdf',
            's3_path' => 'documents/antenatal_record.pdf',
            'document_type' => 'antenatal_care',
            'confidence' => 0.95,
        ]);

        $response = $this->get("/patients/{$patient->id}/profile");

        $response->assertStatus(200)
                ->assertViewIs('patients.profile')
                ->assertViewHas('patient');

        $viewPatient = $response->viewData('patient');

        // Check basic patient information
        $this->assertEquals($patient->id, $viewPatient->id);
        $this->assertEquals('Profile Test Patient', $viewPatient->name);
        $this->assertEquals('female', $viewPatient->gender);
        $this->assertEquals('1990-05-15', $viewPatient->birth_date->format('Y-m-d'));
        $this->assertEquals('+256711111114', $viewPatient->contact_number);
        $this->assertEquals('+256700000002', $viewPatient->parent_contact);
        $this->assertEquals('Grade 10', $viewPatient->grade);

        // Check relationships are loaded
        $this->assertTrue($viewPatient->relationLoaded('school'));
        $this->assertTrue($viewPatient->relationLoaded('healthFacility'));
        $this->assertTrue($viewPatient->relationLoaded('appointments'));
        $this->assertTrue($viewPatient->relationLoaded('labTests'));
        $this->assertTrue($viewPatient->relationLoaded('maternalDocuments'));
        $this->assertTrue($viewPatient->relationLoaded('medicalHistories'));

        // Check school and health facility associations
        $this->assertEquals($school->id, $viewPatient->school->id);
        $this->assertEquals($healthFacility->id, $viewPatient->healthFacility->id);

        // Check medical history
        $this->assertCount(1, $viewPatient->medicalHistories);
        $this->assertEquals('Previous surgery for appendicitis', $viewPatient->medicalHistories->first()->content);
        $this->assertEquals($doctor->name, $viewPatient->medicalHistories->first()->doctor->name);

        // Check appointments
        $this->assertCount(1, $viewPatient->appointments);
        $this->assertEquals($appointment->id, $viewPatient->appointments->first()->id);
        $this->assertEquals($doctor->name, $viewPatient->appointments->first()->doctor->name);

        // Check lab tests
        $this->assertCount(1, $viewPatient->labTests);
        $this->assertEquals($labTest->id, $viewPatient->labTests->first()->id);

        // Check maternal documents
        $this->assertCount(1, $viewPatient->maternalDocuments);
        $this->assertEquals($maternalDocument->id, $viewPatient->maternalDocuments->first()->id);
        $this->assertEquals('antenatal_care', $viewPatient->maternalDocuments->first()->document_type);
    }

    /** @test */
    public function it_returns_404_for_non_existent_patient_profile()
    {
        $response = $this->get('/patients/999/profile');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_displays_patient_profile_with_minimal_data()
    {
        $patient = Patient::create([
            'patient_id' => 'P002',
            'name' => 'Minimal Patient',
            'gender' => 'male',
            'birth_date' => '1985-01-01',
        ]);

        $response = $this->get("/patients/{$patient->id}/profile");

        $response->assertStatus(200)
                ->assertViewIs('patients.profile')
                ->assertViewHas('patient');

        $viewPatient = $response->viewData('patient');

        // Check basic information
        $this->assertEquals('Minimal Patient', $viewPatient->name);
        $this->assertEquals('male', $viewPatient->gender);
        $this->assertNull($viewPatient->contact_number);
        $this->assertNull($viewPatient->school);
        $this->assertNull($viewPatient->healthFacility);

        // Check empty relationships
        $this->assertCount(0, $viewPatient->appointments);
        $this->assertCount(0, $viewPatient->labTests);
        $this->assertCount(0, $viewPatient->maternalDocuments);
        $this->assertCount(0, $viewPatient->medicalHistories);
    }

    /** @test */
    public function it_displays_patient_profile_with_only_school_association()
    {
        $school = School::create([
            'name' => 'School Only',
            'email' => 'school@test.com',
            'contact' => '+256700000000',
        ]);

        $patient = Patient::create([
            'patient_id' => 'P003',
            'name' => 'School Patient',
            'gender' => 'female',
            'birth_date' => '2010-03-15',
            'grade' => 'Grade 7',
            'parent_contact' => '+256700000003',
            'school_id' => $school->id,
        ]);

        $response = $this->get("/patients/{$patient->id}/profile");

        $response->assertStatus(200);

        $viewPatient = $response->viewData('patient');

        $this->assertEquals($school->id, $viewPatient->school->id);
        $this->assertEquals('School Only', $viewPatient->school->name);
        $this->assertNull($viewPatient->healthFacility);
        $this->assertEquals('Grade 7', $viewPatient->grade);
        $this->assertEquals('+256700000003', $viewPatient->parent_contact);
    }

    /** @test */
    public function it_displays_patient_profile_with_only_health_facility_association()
    {
        $healthFacility = HealthFacility::create([
            'name' => 'HF Only',
            'email' => 'hf@test.com',
            'contact_number' => '+256711111111',
            'contact' => '+256711111111',
            'location' => 'Test Location',
            'type' => 'clinic',
        ]);

        $patient = Patient::create([
            'patient_id' => 'P004',
            'name' => 'HF Patient',
            'gender' => 'male',
            'birth_date' => '1975-12-10',
            'contact_number' => '+256711111115',
            'health_facility_id' => $healthFacility->id,
        ]);

        // Create doctor for medical history
        $doctor = Doctor::create([
            'name' => 'Dr. HF',
            'email' => 'drhf@test.com',
            'specialization' => 'Cardiology',
            'contact' => '+256700000004',
        ]);

        // Create medical history
        $medicalHistory = \App\Models\MedicalHistory::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'content' => 'Hypertension diagnosis',
            'recorded_date' => '2023-08-20',
        ]);

        $response = $this->get("/patients/{$patient->id}/profile");

        $response->assertStatus(200);

        $viewPatient = $response->viewData('patient');

        $this->assertEquals($healthFacility->id, $viewPatient->healthFacility->id);
        $this->assertEquals('HF Only', $viewPatient->healthFacility->name);
        $this->assertNull($viewPatient->school);
        $this->assertNull($viewPatient->grade);
        $this->assertNull($viewPatient->parent_contact);

        // Check medical history
        $this->assertCount(1, $viewPatient->medicalHistories);
        $this->assertEquals('Hypertension diagnosis', $viewPatient->medicalHistories->first()->content);
        $this->assertEquals($doctor->name, $viewPatient->medicalHistories->first()->doctor->name);
    }

    /** @test */
    public function it_displays_patient_profile_with_multiple_appointments_and_lab_requests()
    {
        $school = School::create([
            'name' => 'Multi Data School',
            'email' => 'multi@test.com',
            'contact' => '+256700000000',
        ]);

        $doctor1 = Doctor::create([
            'name' => 'Dr. First',
            'email' => 'dr1@test.com',
            'specialization' => 'Pediatrics',
            'contact' => '+256700000001',
        ]);

        $doctor2 = Doctor::create([
            'name' => 'Dr. Second',
            'email' => 'dr2@test.com',
            'specialization' => 'General',
            'contact' => '+256700000002',
        ]);

        $patient = Patient::create([
            'patient_id' => 'P005',
            'name' => 'Multi Data Patient',
            'gender' => 'female',
            'birth_date' => '2008-08-20',
            'school_id' => $school->id,
            'grade' => 'Grade 6',
        ]);

        // Create multiple appointments
        $appointment1 = Appointment::create([
            'school_id' => $school->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor1->id,
            'appointment_time' => now()->addDays(1),
            'status' => 'completed',
            'duration_id' => $this->getGeneralDurationId(),
            'reason' => 'Annual checkup',
        ]);

        $appointment2 = Appointment::create([
            'school_id' => $school->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor2->id,
            'appointment_time' => now()->addDays(7),
            'status' => 'pending',
            'duration_id' => $this->getSpecialistDurationId(),
            'reason' => 'Follow-up consultation',
        ]);

        // Create multiple lab tests
        $labTest1 = LabTest::create([
            'school_id' => $school->id,
            'patient_id' => $patient->id,
            'test_type' => 'Blood Test',
            'status' => 'completed',
            'results' => 'Normal results',
        ]);

        $labTest2 = LabTest::create([
            'school_id' => $school->id,
            'patient_id' => $patient->id,
            'test_type' => 'Urine Test',
            'status' => 'pending',
        ]);

        $response = $this->get("/patients/{$patient->id}/profile");

        $response->assertStatus(200);

        $viewPatient = $response->viewData('patient');

        // Check multiple appointments
        $this->assertCount(2, $viewPatient->appointments);
        $appointmentStatuses = $viewPatient->appointments->pluck('status')->sort()->values();
        $this->assertEquals(['completed', 'pending'], $appointmentStatuses->toArray());

        // Check multiple lab tests
        $this->assertCount(2, $viewPatient->labTests);
        $labStatuses = $viewPatient->labTests->pluck('status')->sort()->values();
        $this->assertEquals(['completed', 'pending'], $labStatuses->toArray());
    }

    /** @test */
    public function it_displays_patient_profile_with_maternal_documents()
    {
        $healthFacility = HealthFacility::create([
            'name' => 'Maternal Health Facility',
            'email' => 'maternal@test.com',
            'contact_number' => '+256711111111',
            'contact' => '+256711111111',
            'location' => 'Test Location',
            'type' => 'hospital',
        ]);

        $patient = Patient::create([
            'patient_id' => 'P006',
            'name' => 'Maternal Patient',
            'gender' => 'female',
            'birth_date' => '1992-06-10',
            'health_facility_id' => $healthFacility->id,
        ]);

        // Create multiple maternal documents
        $document1 = \App\Models\MaternalDocument::create([
            'patient_id' => $patient->id,
            'health_facility_id' => $healthFacility->id,
            'original_filename' => 'antenatal_visit_1.pdf',
            's3_path' => 'documents/antenatal_visit_1.pdf',
            'document_type' => 'antenatal_care',
            'confidence' => 0.92,
        ]);

        $document2 = \App\Models\MaternalDocument::create([
            'patient_id' => $patient->id,
            'health_facility_id' => $healthFacility->id,
            'original_filename' => 'delivery_record.pdf',
            's3_path' => 'documents/delivery_record.pdf',
            'document_type' => 'delivery_record',
            'confidence' => 0.88,
        ]);

        $response = $this->get("/patients/{$patient->id}/profile");

        $response->assertStatus(200);

        $viewPatient = $response->viewData('patient');

        // Check maternal documents
        $this->assertCount(2, $viewPatient->maternalDocuments);
        $documentTypes = $viewPatient->maternalDocuments->pluck('document_type')->sort()->values();
        $this->assertEquals(['antenatal_care', 'delivery_record'], $documentTypes->toArray());

        // Check document details
        $antenatalDoc = $viewPatient->maternalDocuments->where('document_type', 'antenatal_care')->first();
        $this->assertEquals('antenatal_visit_1.pdf', $antenatalDoc->original_filename);
        $this->assertEquals(0.92, $antenatalDoc->confidence);
    }

    /** @test */
    public function it_calculates_patient_age_correctly_in_profile()
    {
        $patient = Patient::create([
            'patient_id' => 'P007',
            'name' => 'Age Test Patient',
            'gender' => 'male',
            'birth_date' => '2000-10-13', // Born on current date in 2000
        ]);

        $response = $this->get("/patients/{$patient->id}/profile");

        $response->assertStatus(200);

        $viewPatient = $response->viewData('patient');

        // Since we're in October 2025 and patient was born in 2000, they should be 25
        $this->assertEquals(25, $viewPatient->age);
    }
}