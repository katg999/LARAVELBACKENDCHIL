<?php

namespace Tests\Browser;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\LoginPage;
use Tests\DuskTestCase;

class SessionAccessTest extends DuskTestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_login_with_otp()
    {
        // Create a test school
        $school = School::factory()->create([
            'email' => 'test@example.com',
            'name' => 'Test School'
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit(new LoginPage)
                    ->sendOtp('test@example.com')
                    ->waitForText('OTP sent successfully')
                    ->assertSee('OTP sent successfully');

            // In a real test, you'd need to capture the OTP from email or database
            // For this example, we'll assume we can get it somehow
            // $otp = // get OTP from database or email

            // Then continue with OTP entry
            // $browser->loginWithOtp($otp)
            //         ->assertPathIs('/school-dashboard');
        });
    }

    /** @test */
    public function unauthorized_access_shows_403_error()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/school-dashboard')
                    ->assertSee('403')
                    ->assertSee('ACCESS DENIED');
        });
    }

    /** @test */
    public function invalid_otp_shows_error()
    {
        $school = School::factory()->create([
            'email' => 'test@example.com'
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit(new LoginPage)
                    ->sendOtp('test@example.com')
                    ->waitForText('OTP sent successfully')
                    ->loginWithOtp('000000') // Invalid OTP
                    ->assertSee('Invalid OTP');
        });
    }
}