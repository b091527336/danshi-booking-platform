<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('DBP_ADMIN_EMAIL');
        $password = env('DBP_ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->command?->warn('未建立管理者：請先設定 DBP_ADMIN_EMAIL 與 DBP_ADMIN_PASSWORD。');

            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => env('DBP_ADMIN_NAME', 'DBP 管理者'),
                'password' => Hash::make($password),
            ],
        );
    }
}
