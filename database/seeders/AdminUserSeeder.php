<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use InvalidArgumentException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $name = config('admin.seed.name');
        $username = config('admin.seed.username');
        $email = config('admin.seed.email');
        $password = config('admin.seed.password');

        if (! $name || ! $username || ! $email || ! $password) {
            $this->command?->info('Admin seeding skipped. Configure ADMIN_NAME, ADMIN_USERNAME, ADMIN_EMAIL, and ADMIN_PASSWORD or use php artisan admin:create.');

            return;
        }

        $localDefaultCredentials = app()->environment(['local', 'testing'])
            && $username === 'admin'
            && $email === 'admin@example.com'
            && $password === 'admin';

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || (strlen($password) < 8 && ! $localDefaultCredentials)) {
            throw new InvalidArgumentException('Admin seed credentials must include a valid email and a password of at least 8 characters, except for the local default account.');
        }

        $user = User::firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => $name,
            'username' => $username,
            'password' => $password,
            'role' => 'admin',
        ]);
        $user->save();
    }
}
