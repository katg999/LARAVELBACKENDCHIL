<?php

namespace Tests\Feature;

use App\Models\HealthFacility;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthFacilityDashboardScopeTest extends TestCase
{
    use RefreshDatabase;

    private function facility(string $name): HealthFacility
    {
        return HealthFacility::create([
            'name' => $name, 'email' => uniqid() . '@test.com', 'contact_number' => '256711111111',
            'contact' => '256711111111', 'location' => 'Kampala', 'type' => 'clinic',
        ]);
    }

    private function patient(HealthFacility $f, ?string $gender): void
    {
        Patient::create([
            'patient_id' => 'P' . random_int(100000, 999999), 'name' => 'Pat', 'gender' => $gender,
            'birth_date' => '1990-01-01', 'health_facility_id' => $f->id,
        ]);
    }

    /** @test */
    public function unspecified_gender_count_only_includes_this_facilitys_patients(): void
    {
        $mine = $this->facility('Mine');
        $other = $this->facility('Other');

        $this->patient($mine, 'female');
        $this->patient($mine, '');       // mine, unspecified
        $this->patient($other, '');      // someone else's, must not be counted
        $this->patient($other, '');

        $response = $this->withSession(['authenticated_user' => [
            'type' => 'health_facility', 'id' => $mine->id, 'name' => 'Mine', 'email' => $mine->email,
        ]])->get('/health-facility/dashboard');

        $response->assertOk();
        $this->assertSame([0, 1, 0, 1], $response->viewData('genderData'));
    }

    /** @test */
    public function header_shows_the_clinic_name_not_a_generic_label(): void
    {
        $mine = $this->facility('Sunrise Clinic');

        $this->withSession(['authenticated_user' => [
            'type' => 'health_facility', 'id' => $mine->id, 'name' => 'Sunrise Clinic', 'email' => $mine->email,
        ]])->get('/health-facility/dashboard')->assertSee('Sunrise Clinic');
    }
}
