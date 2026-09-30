<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdminCommand extends Command
{
    protected $signature = 'admin:create {username} {name} {email}';

    protected $description = 'Create or promote an administrator account';

    public function handle(): int
    {
        $username = (string) $this->argument('username');
        $name = (string) $this->argument('name');
        $email = (string) $this->argument('email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Provide a valid email address.');

            return self::FAILURE;
        }

        $password = $this->secret('Admin password');

        if (! is_string($password) || strlen($password) < 8) {
            $this->error('The password must contain at least 8 characters.');

            return self::FAILURE;
        }

        $user = User::firstOrNew(['email' => $email]);
        $user->name = $name;
        $user->username = $username;
        $user->password = $password;
        $user->role = 'admin';
        $user->save();

        $this->info("Administrator account ready for {$email}.");

        return self::SUCCESS;
    }
}
