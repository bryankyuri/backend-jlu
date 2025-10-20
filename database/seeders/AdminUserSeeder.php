<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user if it doesn't exist
        $adminEmail = 'admin@parallelstudio.asia';
        
        $existingUser = User::where('email', $adminEmail)->first();
        
        if (!$existingUser) {
            User::create([
                'name' => 'Admin',
                'email' => $adminEmail,
                'password' => Hash::make('Valkimer417'), // Change this password after first login
                'email_verified_at' => now(),
            ]);
            
            $this->command->info('Admin user created successfully!');
            $this->command->info('Email: ' . $adminEmail);
            $this->command->info('Password: Valkimer417');
            $this->command->warn('Please change the password after first login!');
        } else {
            $this->command->info('Admin user already exists.');
        }
    }
}
