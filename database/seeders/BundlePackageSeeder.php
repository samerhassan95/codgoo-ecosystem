<?php

// database/seeders/BundlePackageSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BundlePackage;

class BundlePackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            // Starter Bundle (ID 1)
            [
                'id' => 1,
                'name' => 'Starter Bundle',
                'tagline' => 'Perfect for getting started',
                'price_amount' => 30000, // 300 EGP
                'features' => json_encode(["Choose 3 Applications", "Lifetime Updates", "Basic Support", "Single Developer License"]),
                'savings_percentage' => 29,
                'savings_text' => 'Save up to 120 EGP',
                'badges' => json_encode([]),
            ],
            // Professional Bundle (ID 2)
            [
                'id' => 2,
                'name' => 'Professional Bundle',
                'tagline' => 'Optimized for growth',
                'price_amount' => 50000, // 500 EGP
                'features' => json_encode(["Choose 6 Applications", "Lifetime Updates", "Priority Support", "Team License (5 developers)", "Free Future Updates"]),
                'savings_percentage' => 40,
                'savings_text' => 'Save up to 340 EGP',
                'badges' => json_encode(["Most Popular"]),
            ],
            // Enterprise Bundle (ID 3)
            [
                'id' => 3,
                'name' => 'Enterprise Bundle',
                'tagline' => 'Built for large-scale operations',
                'price_amount' => 100000, // 1000 EGP
                'features' => json_encode(["Choose 10 Applications", "Lifetime Updates", "Premium Support", "Unlimited Team License", "Free Future Updates", "Custom Integrations", "Dedicated Account Manager"]),
                'savings_percentage' => 29,
                'savings_text' => 'Save up to 120 EGP',
                'badges' => json_encode(["Best Value"]),
            ],
        ];

        foreach ($packages as $package) {
            BundlePackage::firstOrCreate(['id' => $package['id']], $package);
        }
    }
}
