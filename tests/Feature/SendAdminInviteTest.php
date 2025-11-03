<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
use App\Mail\AdminInviteMail;
use App\Models\AdminInvite;
use Tests\TestCase;

class SendAdminInviteTest extends TestCase
{
    use RefreshDatabase;
    public function setUp(): void
    {
        parent::setUp();

        // Seed any persistent test data here if needed
        // Example: $this->seed(SomeTestSeeder::class);
    }

    /**
     * Test sending admin invite with email argument.
     */
    public function test_send_admin_invite_with_email_argument(): void
    {
        Mail::fake();

        $email = 'admin@example.com';

        $this->artisan('admin:invite', ['email' => $email])
             ->expectsOutput("Admin registration invite sent to {$email}. The link will expire in 24 hours.")
             ->assertExitCode(0);

        Mail::assertSent(AdminInviteMail::class, function ($mail) use ($email) {
            return $mail->hasTo($email);
        });

        // Check that an invite record was created
        $this->assertDatabaseHas('admin_invites', [
            'email' => $email,
            'used' => false,
        ]);

        // Check that the mail contains a token-based URL
        Mail::assertSent(AdminInviteMail::class, function ($mail) use ($email) {
            $inviteUrl = $mail->inviteUrl;
            $invite = AdminInvite::where('email', $email)->first();
            return strpos($inviteUrl, "/admin/register/{$invite->token}") !== false;
        });
    }

    /**
     * Test sending admin invite with prompted email.
     */
    public function test_send_admin_invite_with_prompted_email(): void
    {
        Mail::fake();

        $email = 'admin2@example.com';

        $this->artisan('admin:invite')
             ->expectsQuestion('Enter the admin email address', $email)
             ->expectsOutput("Admin registration invite sent to {$email}. The link will expire in 24 hours.")
             ->assertExitCode(0);

        Mail::assertSent(AdminInviteMail::class, function ($mail) use ($email) {
            return $mail->hasTo($email);
        });

        // Check that an invite record was created
        $this->assertDatabaseHas('admin_invites', [
            'email' => $email,
            'used' => false,
        ]);
    }

    /**
     * Test invalid email address.
     */
    public function test_send_admin_invite_invalid_email(): void
    {
        $this->artisan('admin:invite', ['email' => 'invalid-email'])
             ->expectsOutput('Invalid email address provided.')
             ->assertExitCode(1);
    }

    /**
     * Test the admin register route with valid token.
     */
    public function test_admin_register_route_with_valid_token(): void
    {
        $invite = AdminInvite::create([
            'email' => 'test@example.com',
            'token' => 'valid-token-123',
            'expires_at' => now()->addHours(24),
            'used' => false,
        ]);

        $response = $this->get(route('admin.register', ['token' => $invite->token]));

        $response->assertStatus(200);
        $response->assertViewIs('auth.register');
        $response->assertViewHas('email', $invite->email);
        $response->assertViewHas('invite', $invite);
    }

    /**
     * Test admin register route with invalid token.
     */
    public function test_admin_register_route_with_invalid_token(): void
    {
        $response = $this->get(route('admin.register', ['token' => 'invalid-token']));

        $response->assertStatus(404);
    }

    /**
     * Test admin register route with expired token.
     */
    public function test_admin_register_route_with_expired_token(): void
    {
        $invite = AdminInvite::create([
            'email' => 'test@example.com',
            'token' => 'expired-token-123',
            'expires_at' => now()->subHours(1), // Expired
            'used' => false,
        ]);

        $response = $this->get(route('admin.register', ['token' => $invite->token]));

        $response->assertStatus(403);
    }

    /**
     * Test admin register route with used token.
     */
    public function test_admin_register_route_with_used_token(): void
    {
        $invite = AdminInvite::create([
            'email' => 'test@example.com',
            'token' => 'used-token-123',
            'expires_at' => now()->addHours(24),
            'used' => true, // Already used
        ]);

        $response = $this->get(route('admin.register', ['token' => $invite->token]));

        $response->assertStatus(403);
    }

    /**
     * Test duplicate invite prevention.
     */
    public function test_duplicate_invite_prevention(): void
    {
        Mail::fake();

        $email = 'admin@example.com';

        // Create an admin user with this email
        \App\User::create([
            'name' => 'Test Admin',
            'email' => $email,
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);

        $this->artisan('admin:invite', ['email' => $email])
             ->expectsOutput('An admin account with this email address already exists. Cannot send invitation.')
             ->assertExitCode(1);

        // Should not have sent another email
        Mail::assertNotSent(AdminInviteMail::class);
    }
}
