<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\NewsletterSubscriber;
use App\Mail\NewsletterMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendNewsletter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'newsletter:send
                            {--subject= : The newsletter subject}
                            {--content= : The newsletter content (HTML allowed)}
                            {--file= : Path to file containing newsletter content}
                            {--dry-run : Show what would be sent without actually sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send newsletter to all active subscribers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $subject = $this->option('subject');
        $content = $this->option('content');
        $file = $this->option('file');
        $dryRun = $this->option('dry-run');

        // Get subject
        if (!$subject) {
            $subject = $this->ask('Enter newsletter subject');
        }

        // Get content
        if (!$content && !$file) {
            $content = $this->ask('Enter newsletter content (HTML allowed)');
        }

        if ($file) {
            if (!file_exists($file)) {
                $this->error("File not found: {$file}");
                return 1;
            }
            $content = file_get_contents($file);
        }

        if (empty($content)) {
            $this->error('Newsletter content cannot be empty');
            return 1;
        }

        // Get active subscribers
        $subscribers = NewsletterSubscriber::active()->verified()->get();

        if ($subscribers->isEmpty()) {
            $this->info('No active subscribers found.');
            return 0;
        }

        $this->info("Found {$subscribers->count()} active subscribers.");
        $this->info("Subject: {$subject}");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No emails will be sent');
            $this->table(
                ['Email', 'Subscribed At'],
                $subscribers->map(function ($subscriber) {
                    return [
                        $subscriber->email,
                        $subscriber->created_at->format('Y-m-d H:i:s')
                    ];
                })->toArray()
            );
            return 0;
        }

        if (!$this->confirm('Are you sure you want to send this newsletter to ' . $subscribers->count() . ' subscribers?')) {
            $this->info('Newsletter sending cancelled.');
            return 0;
        }

        // Send newsletter
        $this->info('Sending newsletter...');
        $bar = $this->output->createProgressBar($subscribers->count());
        $bar->start();

        $sent = 0;
        $failed = 0;

        foreach ($subscribers as $subscriber) {
            try {
                Mail::to($subscriber->email)->send(new NewsletterMail($subject, $content, $subscriber->email));
                $sent++;
                Log::info("Newsletter sent to: {$subscriber->email}");
            } catch (\Exception $e) {
                $failed++;
                Log::error("Failed to send newsletter to {$subscriber->email}: {$e->getMessage()}");
                $this->error("Failed to send to {$subscriber->email}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Newsletter sending completed!");
        $this->info("Sent: {$sent}");
        $this->info("Failed: {$failed}");

        if ($failed > 0) {
            $this->warn("{$failed} emails failed to send. Check logs for details.");
        }

        return 0;
    }
}
