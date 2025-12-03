<?php

// database/seeders/CustomBundleSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CustomBundle;
use Carbon\Carbon;

class CustomBundleSeeder extends Seeder
{
    public function run(): void
    {
        // Define the applications for the test bundle (IDs 10, 11, 15, 21, 25, 27)
        $applicationIds = [10, 11, 15, 21, 25, 27];

        $customBundle = CustomBundle::firstOrCreate(
            ['id' => 501], // Use the ID from your contract example
            [
                'customer_id' => 123, // MUST exist in your users/customers table
                'bundle_package_id' => 2, // Professional Bundle
                'total_price_amount' => 50000, // 500 EGP
                'total_price_currency' => 'EGP',
                'status' => 'active',
                'purchased_at' => Carbon::parse('2025-11-26 00:00:00'),
                'expires_at' => Carbon::now()->addDays(2)->addHours(13), // Set a future date for modification testing
            ]
        );

        // Sync the applications to the pivot table
        $customBundle->applications()->sync($applicationIds);
    }
}
