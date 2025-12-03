<?php

// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;
use App\Models\User; // Ensure this is the correct path to your User model

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 🚨 CRITICAL: Explicitly create the test user (customer_id 123) first.
        // We use firstOrCreate to prevent errors if the seeder runs again.
        User::firstOrCreate(['id' => 123], [
            'name' => 'Test Customer',
            'email' => 'test_123@marketplace.com',
            'password' => bcrypt('password'),
            // Add any other required fields your 'users' table has (e.g., email_verified_at)
        ]);
        Client::firstOrCreate(['id' => 123], [
            'name' => 'Test Client',
            'email' => 'test_123@marketplace.com',
            'password' => bcrypt('password'),
            'username' => "samerhassan",
            'phone' => "1234567890",
            'photo' => null,
            'company_name' => null,
            'website' => null,
            'address' => null,
            'city' => null,
            'country' => null,
            // Add any other required fields for the clients table
        ]);

        // 2. Run the marketplace seeders
        $this->call([
            ServiceAppSeeder::class,    // Apps must exist before Bundles/Pivot
            BundlePackageSeeder::class, // Packages must exist before Custom Bundles
            CustomBundleSeeder::class,  // This now runs after user 123, apps, and packages exist
        ]);
    }
}
