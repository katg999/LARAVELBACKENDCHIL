<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use App\Mail\AdminInviteMail;
use App\Models\AdminInvite;
use App\User;

class SendAdminInvite extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:invite {email?} {--force : Force resend even if an active invite exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send an admin registration invite to the specified email address';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') ?? $this->ask('Enter the admin email address');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address provided.');
            return 1;
        }

        // Check if an admin with this email already exists
        $existingAdmin = User::where('email', $email)->where('is_admin', true)->first();
        if ($existingAdmin) {
            $this->error('An admin account with this email address already exists. Cannot send invitation.');
            return 1;
        }

        // Check if an invite already exists for this email
        $existingInvite = AdminInvite::where('email', $email)->where('used', false)->first();
        if ($existingInvite && !$existingInvite->isExpired() && !$this->option('force')) {
            $this->error('An active invite already exists for this email address. Use --force to resend.');
            return 1;
        }

        // If there's an existing invite, update it; otherwise create a new one
        if ($existingInvite) {
            $invite = $existingInvite;
            $invite->update([
                'token' => AdminInvite::generateToken(),
                'expires_at' => now()->addHours(24),
            ]);
        } else {
            $invite = AdminInvite::create([
                'email' => $email,
                'token' => AdminInvite::generateToken(),
                'expires_at' => now()->addHours(24),
            ]);
        }

        // Generate the invite URL
        $inviteUrl = route('admin.register', ['token' => $invite->token]);

        // Send the email
        Mail::to($email)->send(new AdminInviteMail($inviteUrl));

        $this->info("Admin registration invite sent to {$email}. The link will expire in 24 hours.");
        return 0;
    }
}
