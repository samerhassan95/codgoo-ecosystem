<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\CustomBundle;
use Carbon\Carbon;

class CustomBundleSeeder extends Seeder
{
    public function run(): void
    {
        // Define the applications for the test bundle (IDs 1–6 exist in ServiceAppSeeder)
        $applicationIds = [1, 2, 3, 4, 5, 6];

        // Ensure the client exists (replace with a real client ID from your clients table)
        $clientId = 123;
        $clientExists = DB::table('clients')->where('id', $clientId)->exists();

        if (!$clientExists) {
            DB::table('clients')->insert([
                'id' => $clientId,
                'name' => 'Test Client',
                'email' => 'testclient@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create or get the custom bundle
        $customBundle = CustomBundle::firstOrCreate(
            ['id' => 501], // Fixed ID
            [
                'customer_id' => $clientId, // references clients.id
                'bundle_package_id' => 2,   // Example: Professional Bundle
                'total_price_amount' => 50000, // Total amount in EGP
                'total_price_currency' => 'EGP',
                'status' => 'active',
                'purchased_at' => Carbon::parse('2025-11-26 00:00:00'),
                'expires_at' => Carbon::now()->addDays(30), // Set a future expiration
            ]
        );

        // Only sync applications that exist to avoid FK issues
        $existingAppIds = DB::table('service_apps')
            ->whereIn('id', $applicationIds)
            ->pluck('id')
            ->toArray();

        $customBundle->applications()->sync($existingAppIds);

        $this->command->info('CustomBundleSeeder completed successfully!');
    }
}
