<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('services.initial_admin.email');
        $password = config('services.initial_admin.password');

        if (blank($email) || blank($password)) {
            $this->command?->warn('ADMIN_EMAIL dan ADMIN_PASSWORD harus diisi. Admin tidak dibuat.');

            return;
        }

        User::query()->updateOrCreate(
            ['email' => strtolower(trim((string) $email))],
            [
                'name' => config('services.initial_admin.name', 'Administrator'),
                'password' => Hash::make((string) $password),
                'role' => 'admin',
            ],
        );

        $this->command?->info('Akun admin berhasil dibuat atau diperbarui.');
    }
}
