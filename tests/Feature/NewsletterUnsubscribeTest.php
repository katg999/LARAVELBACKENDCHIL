<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\NewsletterSubscriber;
use PHPUnit\Framework\Attributes\Test;

class NewsletterUnsubscribeTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_unsubscribes_via_post_and_is_case_insensitive()
    {
        $emailOriginal = 'User@Example.com';
        $emailLower = 'user@example.com';

        NewsletterSubscriber::create([
            'email' => $emailOriginal,
            'verification_token' => 'tok',
            'verified_at' => now(),
            'is_active' => true,
        ]);

        $this->postJson('/api/newsletter/unsubscribe', ['email' => $emailLower])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => $emailOriginal,
            'is_active' => false,
        ]);
    }

    #[Test]
    public function it_unsubscribes_via_get_and_renders_html()
    {
        $email = 'get@example.com';

        NewsletterSubscriber::create([
            'email' => $email,
            'verification_token' => 'tok',
            'verified_at' => now(),
            'is_active' => true,
        ]);

        $response = $this->get('/api/newsletter/unsubscribe?email=' . urlencode($email));
        $response->assertStatus(200);
        $response->assertSee("You're unsubscribed", false);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => $email,
            'is_active' => false,
        ]);
    }

    #[Test]
    public function resubscribe_reactivates_if_previously_unsubscribed()
    {
        $email = 'reuser@example.com';

        NewsletterSubscriber::create([
            'email' => $email,
            'verification_token' => null,
            'verified_at' => now(),
            'is_active' => false,
        ]);

        $this->withoutMiddleware()->postJson('/api/newsletter/subscribe', ['email' => $email])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'You have been resubscribed to the newsletter.'
            ]);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => $email,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function resubscribe_sends_new_verification_if_not_verified_yet()
    {
        $email = 'pending@example.com';

        NewsletterSubscriber::create([
            'email' => $email,
            'verification_token' => 'oldtok',
            'verified_at' => null,
            'is_active' => false,
        ]);

        $resp = $this->withoutMiddleware()->postJson('/api/newsletter/subscribe', ['email' => $email]);
        $resp->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // token should be refreshed and still inactive until verified
        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => $email,
            'is_active' => false,
        ]);
    }

    #[Test]
    public function resubscribe_when_already_active_returns_message()
    {
        $email = 'active@example.com';
        NewsletterSubscriber::create([
            'email' => $email,
            'verification_token' => null,
            'verified_at' => now(),
            'is_active' => true,
        ]);

        $this->withoutMiddleware()->postJson('/api/newsletter/subscribe', ['email' => $email])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => "You're already subscribed to the newsletter."
            ]);
    }
}
