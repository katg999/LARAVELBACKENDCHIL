<?php

namespace Tests\Browser;

use App\Models\School;
use App\Models\OneTimeLoginToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SessionAccessTest extends DuskTestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_login_with_one_time_token()
    {
        // Create a test school
        $school = School::factory()->create([
            'email' => 'test@example.com',
            'name' => 'Test School'
        ]);

        // Create a one-time login token
        $token = OneTimeLoginToken::create([
            'token' => 'test-token-123',
            'user_type' => 'school',
            'user_id' => $school->id,
            'email' => 'test@example.com',
            'expires_at' => now()->addHours(1),
            'used' => false
        ]);

        $this->browse(function (Browser $browser) use ($token) {
            $browser->visit('/auth/login/' . $token->token)
                    ->assertPathIs('/school-dashboard')
                    ->assertSee('Test School');
        });
    }

    /** @test */
    public function unauthorized_access_shows_logged_out_page()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/school-dashboard')
                    ->assertSee('You are currently logged out');
        });
    }

    /** @test */
    public function expired_token_shows_error()
    {
        // Create an expired token
        $token = OneTimeLoginToken::create([
            'token' => 'expired-token-123',
            'user_type' => 'school',
            'user_id' => 1,
            'email' => 'test@example.com',
            'expires_at' => now()->subHours(1), // Already expired
            'used' => false
        ]);

        $this->browse(function (Browser $browser) use ($token) {
            $browser->visit('/auth/login/' . $token->token)
                    ->assertSee('This login link has expired');
        });
    }
}