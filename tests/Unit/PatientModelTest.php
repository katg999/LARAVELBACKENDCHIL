<?php

namespace Tests\Unit;

use App\Models\Patient;
use App\Models\School;
use App\Models\HealthFacility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_generates_unique_patient_ids_on_creation()
    {
        $patient = new Patient([
            'name' => 'Test Patient',
            'birth_date' => '2000-01-01',
            'gender' => 'male',
        ]);

        $patient->save();

        $this->assertNotNull($patient->patient_id);
        $this->assertMatchesRegularExpression('/^P\d{6}$/', $patient->patient_id);
    }

    /** @test */
    public function it_does_not_regenerate_patient_id_if_already_set()
    {
        $existingId = 'P123456';

        $patient = new Patient([
            'patient_id' => $existingId,
            'name' => 'Test Patient',
            'birth_date' => '2000-01-01',
            'gender' => 'male',
        ]);

        $patient->save();

        $this->assertEquals($existingId, $patient->patient_id);
    }

    /** @test */
    public function it_finds_existing_patient_by_patient_id()
    {
        $school = School::create([
            'name' => 'Test School',
            'email' => 'test@school.com',
            'contact' => '+256700000000',
        ]);

        $existingPatient = Patient::create([
            'patient_id' => 'P123456',
            'name' => 'Existing Patient',
            'birth_date' => '2000-01-01',
            'gender' => 'male',
            'school_id' => $school->id,
        ]);

        $foundPatient = Patient::findOrCreate([
            'patient_id' => 'P123456',
            'name' => 'Different Name', // This should be ignored
        ]);

        $this->assertEquals($existingPatient->id, $foundPatient->id);
        $this->assertEquals('Existing Patient', $foundPatient->name); // Original name preserved
    }

    /** @test */
    public function it_finds_existing_patient_by_identification_criteria()
    {
        $school = School::create([
            'name' => 'Test School',
            'email' => 'test@school.com',
            'contact' => '+256700000000',
        ]);

        $existingPatient = Patient::create([
            'name' => 'John Doe',
            'birth_date' => '2000-01-01',
            'gender' => 'male',
            'contact_number' => '+256700000001',
            'school_id' => $school->id,
        ]);

        // Find by name, birth_date, gender
        $foundPatient = Patient::findOrCreate([
            'name' => 'John Doe',
            'birth_date' => '2000-01-01',
            'gender' => 'male',
        ]);

        $this->assertEquals($existingPatient->id, $foundPatient->id);

        // Find by name, birth_date, gender, and contact
        $foundPatient2 = Patient::findOrCreate([
            'name' => 'John Doe',
            'birth_date' => '2000-01-01',
            'gender' => 'male',
            'contact_number' => '+256700000001',
        ]);

        $this->assertEquals($existingPatient->id, $foundPatient2->id);
    }

    /** @test */
    public function it_updates_empty_fields_when_finding_existing_patient()
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

        $existingPatient = Patient::create([
            'name' => 'John Doe',
            'birth_date' => '2000-01-01',
            'gender' => 'male',
            'school_id' => $school->id,
            // Missing fields that should be updated
        ]);

        $foundPatient = Patient::findOrCreate([
            'name' => 'John Doe',
            'birth_date' => '2000-01-01',
            'gender' => 'male',
        ], [
            'health_facility_id' => $healthFacility->id,
            'contact_number' => '+256700000002',
            'grade' => 'Grade 10',
        ]);

        $this->assertEquals($existingPatient->id, $foundPatient->id);
        $this->assertEquals($school->id, $foundPatient->school_id); // Original preserved
        $this->assertEquals($healthFacility->id, $foundPatient->health_facility_id); // Added
        $this->assertEquals('+256700000002', $foundPatient->contact_number); // Added
        $this->assertEquals('Grade 10', $foundPatient->grade); // Added
    }

    /** @test */
    public function it_creates_new_patient_when_no_match_found()
    {
        $patient = Patient::findOrCreate([
            'name' => 'New Patient',
            'birth_date' => '1990-01-01',
            'gender' => 'female',
        ]);

        $this->assertDatabaseHas('patients', [
            'name' => 'New Patient',
            'birth_date' => '1990-01-01 00:00:00',
            'gender' => 'female',
        ]);

        $this->assertNotNull($patient->patient_id);
    }

    /** @test */
    public function it_calculates_age_correctly()
    {
        $youngPatient = new Patient([
            'birth_date' => now()->subYears(10)->subMonths(6), // 10 years and 6 months ago
        ]);

        $oldPatient = new Patient([
            'birth_date' => now()->subYears(45)->subMonths(3), // 45 years and 3 months ago
        ]);

        $this->assertEquals(10, $youngPatient->age);
        $this->assertEquals(45, $oldPatient->age);
    }

    /** @test */
    public function it_returns_null_age_for_missing_birth_date()
    {
        $patient = new Patient();

        $this->assertNull($patient->age);
    }

    /** @test */
    public function it_identifies_student_patients_correctly()
    {
        $school = School::create([
            'name' => 'Test School',
            'email' => 'test@school.com',
            'contact' => '+256700000000',
        ]);

        $student = new Patient(['school_id' => $school->id]);
        $nonStudent = new Patient(['health_facility_id' => 1]);

        $this->assertTrue($student->is_student);
        $this->assertFalse($nonStudent->is_student);
    }

    /** @test */
    public function it_identifies_health_facility_patients_correctly()
    {
        $healthFacility = HealthFacility::create([
            'name' => 'Test Health Facility',
            'email' => 'test@facility.com',
            'contact_number' => '+256711111111',
            'contact' => '+256711111111',
            'location' => 'Test Location',
            'type' => 'hospital',
        ]);

        $hfPatient = new Patient(['health_facility_id' => $healthFacility->id]);
        $nonHfPatient = new Patient(['school_id' => 1]);

        $this->assertTrue($hfPatient->is_health_facility_patient);
        $this->assertFalse($nonHfPatient->is_health_facility_patient);
    }

    /** @test */
    public function it_returns_correct_institution_relationship()
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

        $student = new Patient(['school_id' => $school->id]);
        $hfPatient = new Patient(['health_facility_id' => $healthFacility->id]);

        $this->assertEquals($school->id, $student->institution->id);
        $this->assertEquals($healthFacility->id, $hfPatient->institution->id);
    }

    /** @test */
    public function it_applies_scopes_correctly()
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

        // Create test patients
        Patient::create([
            'patient_id' => 'P001',
            'name' => 'School Student',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
            'school_id' => $school->id,
        ]);

        Patient::create([
            'patient_id' => 'P002',
            'name' => 'HF Patient',
            'birth_date' => '1980-01-01',
            'gender' => 'female',
            'health_facility_id' => $healthFacility->id,
        ]);

        Patient::create([
            'patient_id' => 'P003',
            'name' => 'Dual Patient',
            'birth_date' => '1990-01-01',
            'gender' => 'other',
            'school_id' => $school->id,
            'health_facility_id' => $healthFacility->id,
        ]);

        // Test scopes
        $this->assertCount(2, Patient::students()->get()); // School Student + Dual Patient
        $this->assertCount(2, Patient::patients()->get()); // HF Patient + Dual Patient
        $this->assertCount(2, Patient::forSchool($school->id)->get()); // School Student + Dual Patient
        $this->assertCount(2, Patient::forHealthFacility($healthFacility->id)->get()); // HF Patient + Dual Patient
    }

    /** @test */
    public function generate_unique_patient_id_creates_valid_format()
    {
        $id = Patient::generateUniquePatientId();

        $this->assertMatchesRegularExpression('/^P\d{6}$/', $id);
        $this->assertGreaterThanOrEqual(0, (int)substr($id, 1));
        $this->assertLessThanOrEqual(999999, (int)substr($id, 1));
    }

    /** @test */
    public function generate_unique_patient_id_creates_unique_values()
    {
        $ids = [];

        // Generate multiple IDs
        for ($i = 0; $i < 10; $i++) {
            $ids[] = Patient::generateUniquePatientId();
        }

        // Check all are unique
        $this->assertCount(10, array_unique($ids));
    }

    /** @test */
    public function it_handles_relationships_correctly()
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

        $patient = Patient::create([
            'patient_id' => 'P001',
            'name' => 'Test Patient',
            'birth_date' => '2000-01-01',
            'gender' => 'male',
            'school_id' => $school->id,
            'health_facility_id' => $healthFacility->id,
        ]);

        // Test relationships
        $this->assertInstanceOf(School::class, $patient->school);
        $this->assertInstanceOf(HealthFacility::class, $patient->healthFacility);
        $this->assertEquals($school->id, $patient->school->id);
        $this->assertEquals($healthFacility->id, $patient->healthFacility->id);

        // Test reverse relationships
        $this->assertCount(1, $school->students);
        $this->assertCount(1, $healthFacility->patients);
        $this->assertEquals($patient->id, $school->students->first()->id);
        $this->assertEquals($patient->id, $healthFacility->patients->first()->id);
    }
}