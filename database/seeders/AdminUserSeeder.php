<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (User::where('email', 'admin@college.ru')->exists()) {
            return;
        }

        $admin = User::create([
            'name' => 'Администратор',
            'email' => 'admin@college.ru',
            'password' => Hash::make('Admin@12345'),
            'email_verified_at' => now(),
        ]);

        $superadminRole = DB::table('roles')->where('slug', 'superadmin')->first();
        if ($superadminRole) {
            DB::table('user_roles')->insert([
                'user_id' => $admin->id,
                'role_id' => $superadminRole->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $dispatcher = User::create([
            'name' => 'Диспетчер',
            'email' => 'dispatcher@college.ru',
            'password' => Hash::make('Dispatcher@12345'),
            'email_verified_at' => now(),
        ]);

        $dispatcherRole = DB::table('roles')->where('slug', 'dispatcher')->first();
        if ($dispatcherRole) {
            DB::table('user_roles')->insert([
                'user_id' => $dispatcher->id,
                'role_id' => $dispatcherRole->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
