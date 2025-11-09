<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Str;

class MakeAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:admin {identifier : user id or email} {--name= : name when creating new user} {--password= : password when creating new user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Promote an existing user to admin or create a new admin by email';

    public function handle()
    {
        $idOrEmail = $this->argument('identifier');

        $user = null;

        if (is_numeric($idOrEmail)) {
            $user = User::find($idOrEmail);
        } else {
            $user = User::where('email', $idOrEmail)->first();
        }

        if (!$user) {
            $name = $this->option('name') ?? 'Admin User';
            $password = $this->option('password') ?? Str::random(12);

            $this->info("No user found. Creating new user with email {$idOrEmail}");

            $user = User::create([
                'name' => $name,
                'email' => $idOrEmail,
                'password' => bcrypt($password),
                'is_admin' => true,
            ]);

            $this->info("Created user {$user->id} with password: {$password}");
            return 0;
        }

        $user->is_admin = true;
        $user->save();

        $this->info("User {$user->id} ({$user->email}) promoted to admin.");
        return 0;
    }
}
