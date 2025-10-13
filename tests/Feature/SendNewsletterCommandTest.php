<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use App\Mail\NewsletterMail;

class SendNewsletterCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_send_newsletter_to_active_subscribers()
    {
        // Fake the mail system
        Mail::fake();

        // Create test subscribers
        $activeSubscriber = NewsletterSubscriber::create([
            'email' => 'active@example.com',
            'verification_token' => null,
            'verified_at' => now(),
            'is_active' => true,
        ]);

        $inactiveSubscriber = NewsletterSubscriber::create([
            'email' => 'inactive@example.com',
            'verification_token' => null,
            'verified_at' => now(),
            'is_active' => false,
        ]);

        $unverifiedSubscriber = NewsletterSubscriber::create([
            'email' => 'unverified@example.com',
            'verification_token' => 'token123',
            'verified_at' => null,
            'is_active' => true,
        ]);

        // Run the command
        $this->artisan('newsletter:send', [
            '--subject' => 'Test Newsletter',
            '--content' => '<p>Test content</p>',
        ])
        ->expectsConfirmation('Are you sure you want to send this newsletter to 1 subscribers?', 'yes')
        ->expectsOutput('Found 1 active subscribers.')
        ->expectsOutput('Subject: Test Newsletter')
        ->expectsOutput('Sending newsletter...')
        ->expectsOutput('Newsletter sending completed!')
        ->expectsOutput('Sent: 1')
        ->expectsOutput('Failed: 0')
        ->assertExitCode(0);

        // Assert that the mail was queued
        Mail::assertQueued(NewsletterMail::class, 1);
    }

    /** @test */
    public function it_can_perform_dry_run()
    {
        // Create test subscriber
        NewsletterSubscriber::create([
            'email' => 'test@example.com',
            'verification_token' => null,
            'verified_at' => now(),
            'is_active' => true,
        ]);

        // Run the command in dry run mode
        $this->artisan('newsletter:send', [
            '--subject' => 'Test Newsletter',
            '--content' => '<p>Test content</p>',
            '--dry-run' => true,
        ])
        ->expectsOutput('Found 1 active subscribers.')
        ->expectsOutput('Subject: Test Newsletter')
        ->expectsOutput('DRY RUN MODE - No emails will be sent')
        ->assertExitCode(0);
    }

    /** @test */
    public function it_requires_subject_and_content()
    {
        $this->artisan('newsletter:send')
            ->expectsQuestion('Enter newsletter subject', 'Test Subject')
            ->expectsQuestion('Enter newsletter content (HTML allowed)', '<p>Test content</p>')
            ->expectsOutput('No active subscribers found.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_read_content_from_file()
    {
        // Fake the mail system
        Mail::fake();

        // Create a temporary file with content
        $content = '<h1>Test Newsletter</h1><p>This is test content from file.</p>';
        $filePath = tempnam(sys_get_temp_dir(), 'newsletter');
        file_put_contents($filePath, $content);

        // Create test subscriber
        NewsletterSubscriber::create([
            'email' => 'test@example.com',
            'verification_token' => null,
            'verified_at' => now(),
            'is_active' => true,
        ]);

        // Run the command with file option
        $this->artisan('newsletter:send', [
            '--subject' => 'File Newsletter',
            '--file' => $filePath,
        ])
        ->expectsConfirmation('Are you sure you want to send this newsletter to 1 subscribers?', 'yes')
        ->expectsOutput('Found 1 active subscribers.')
        ->expectsOutput('Subject: File Newsletter')
        ->expectsOutput('Newsletter sending completed!')
        ->expectsOutput('Sent: 1')
        ->expectsOutput('Failed: 0')
        ->assertExitCode(0);

        // Assert that the mail was queued with file content
        Mail::assertQueued(NewsletterMail::class, 1);

        // Clean up
        unlink($filePath);
    }

    /** @test */
    public function it_handles_file_not_found()
    {
        $this->artisan('newsletter:send', [
            '--subject' => 'Test',
            '--file' => '/nonexistent/file.html',
        ])
        ->expectsOutput('File not found: /nonexistent/file.html')
        ->assertExitCode(1);
    }

    /** @test */
    public function it_handles_no_active_subscribers()
    {
        $this->artisan('newsletter:send', [
            '--subject' => 'Test Newsletter',
            '--content' => '<p>Test</p>',
        ])
        ->expectsOutput('No active subscribers found.')
        ->assertExitCode(0);
    }
}
