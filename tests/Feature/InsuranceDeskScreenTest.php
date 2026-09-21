<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\HealthFacility;
use App\Models\Insurer;
use App\Models\MemberPolicy;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsuranceDeskScreenTest extends TestCase
{
    use RefreshDatabase;

    private function facility(): HealthFacility
    {
        return HealthFacility::create(['name' => 'Clinic ' . uniqid(), 'email' => uniqid() . '@t.com', 'contact_number' => '1', 'contact' => '1', 'location' => 'K', 'type' => 'clinic']);
    }

    private function as(HealthFacility $f)
    {
        return $this->withSession(['authenticated_user' => ['type' => 'health_facility', 'id' => $f->id, 'name' => $f->name, 'email' => $f->email]]);
    }

    private function patient(HealthFacility $f, string $name): Patient
    {
        return Patient::create(['patient_id' => 'P' . random_int(100000, 999999), 'name' => $name, 'gender' => 'female', 'birth_date' => '1990-01-01', 'health_facility_id' => $f->id]);
    }

    /** @test */
    public function the_desk_shows_my_clinics_work_and_none_of_another_clinics(): void
    {
        $mine = $this->facility();
        $other = $this->facility();
        $insurer = Insurer::create(['name' => 'Jubilee', 'code' => 'jubilee']);
        $grace = $this->patient($mine, 'Grace Mine');
        $stranger = $this->patient($other, 'Stranger Other');
        MemberPolicy::create(['patient_id' => $grace->id, 'insurer_id' => $insurer->id, 'member_number' => 'GRACE-1', 'status' => 'pending_review', 'submitted_by' => 'patient']);
        MemberPolicy::create(['patient_id' => $stranger->id, 'insurer_id' => $insurer->id, 'member_number' => 'OTHER-1', 'status' => 'pending_review', 'submitted_by' => 'patient']);
        Prescription::create(['patient_id' => $grace->id, 'source' => 'issued', 'status' => 'issued'])->items()->create(['name' => 'Amoxicillin', 'quantity' => 3]);
        Prescription::create(['patient_id' => $stranger->id, 'source' => 'issued', 'status' => 'issued'])->items()->create(['name' => 'Hidden drug', 'quantity' => 1]);

        $this->as($mine)->get('/insurance/desk')->assertOk()
            ->assertSee('Insurance desk')->assertSee('Grace Mine')->assertSee('GRACE-1')->assertSee('Amoxicillin')
            ->assertSee('Confirm')->assertSee('Price it')
            ->assertDontSee('Stranger Other')->assertDontSee('OTHER-1')->assertDontSee('Hidden drug');
    }

    /** @test */
    public function only_clinic_and_doctor_staff_can_open_the_desk(): void
    {
        $this->withSession(['authenticated_user' => ['type' => 'school', 'id' => 1, 'name' => 'S', 'email' => 's@t.com']])
            ->get('/insurance/desk')->assertForbidden();

        $this->flushSession();
        $this->get('/insurance/desk')->assertOk()->assertDontSee('Grace');      // not signed in: the logged-out page, never data
    }

    /** @test */
    public function the_desk_lets_staff_book_an_insured_visit_only_for_patients_with_confirmed_cover(): void
    {
        $f = $this->facility();
        Doctor::create(['name' => 'Desk Doctor', 'email' => 'dd@t.com', 'specialization' => 'GP', 'contact' => '1', 'meeting_slug' => 'dd']);
        $insurer = Insurer::create(['name' => 'Jubilee', 'code' => 'jubilee']);
        $ready = $this->patient($f, 'Ready Patient');
        $pending = $this->patient($f, 'Pending Patient');
        MemberPolicy::create(['patient_id' => $ready->id, 'insurer_id' => $insurer->id, 'member_number' => 'R-1', 'status' => 'active']);
        MemberPolicy::create(['patient_id' => $pending->id, 'insurer_id' => $insurer->id, 'member_number' => 'P-1', 'status' => 'pending_review']);

        $body = $this->as($f)->get('/insurance/desk')->assertOk()->assertSee('Book an insured visit')->getContent();

        $this->assertStringContainsString('Ready Patient', $body);
        $this->assertStringNotContainsString('Pending Patient · Jubilee', $body);
    }
}
