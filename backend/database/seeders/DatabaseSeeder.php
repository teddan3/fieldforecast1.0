<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate([
            'email' => env('CMS_ADMIN_EMAIL', 'admin@fieldforecast.local'),
        ], [
            'name' => env('CMS_ADMIN_NAME', 'Field Forecast Admin'),
            'password' => env('CMS_ADMIN_PASSWORD', 'ChangeMeNow123!'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}
