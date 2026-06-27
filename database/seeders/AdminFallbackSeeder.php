<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminFallbackSeeder extends Seeder
{
    public function run(): void
    {
        if (User::query()->where('role', 'admin')->exists()) {
            return;
        }

        $admin = User::query()->firstOrNew(['username' => 'webbox']);

        $admin->forceFill([
            'first_name' => $admin->first_name ?: 'Admin',
            'last_name' => $admin->last_name ?: 'User',
            'email' => $admin->email ?: 'webbox@charley.local',
            'password' => Hash::make('charleyplatform'),
            'role' => 'admin',
            'is_verified' => true,
            'verified_at' => $admin->verified_at ?: now(),
            'status' => 'active',
            'login_attempts' => 0,
            'mfa_enabled' => false,
        ])->save();
    }
}
