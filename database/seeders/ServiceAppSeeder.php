<?php

// database/seeders/ServiceAppSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceApp;

class ServiceAppSeeder extends Seeder
{
    public function run(): void
    {
        $apps = [
            // Example apps from the contract
            [
                'id' => 1,
                'name' => 'Snapchat Ads',
                'slug' => 'snapchat-ads',
                'type' => 'General',
                'category' => 'Marketing',
                'description' => 'Connect your store with Snapchat advertising platform for better reach.',
                'price_amount' => 13500, // 135 EGP
                'rating_average' => 4.9,
                'reviews_count' => 1234,
                'icon_url' => 'https://cdn.example.com/icons/snapchat-ads.svg',
            ],
            [
                'id' => 21,
                'name' => 'Store Master Suite',
                'slug' => 'store-master-suite',
                'type' => 'Master',
                'category' => 'E-commerce',
                'description' => 'All-in-one suite to manage your online store and marketing.',
                'price_amount' => 49900, // 499 EGP
                'rating_average' => 4.8,
                'reviews_count' => 987,
                'icon_url' => 'https://cdn.example.com/icons/store-master.svg',
            ],

            // Additional data for testing
            [
                'id' => 10,
                'name' => 'Facebook Pixel Connector',
                'slug' => 'fb-pixel',
                'type' => 'General',
                'category' => 'Analytics',
                'description' => 'Track user events for better retargeting.',
                'price_amount' => 9900,
                'rating_average' => 4.5,
                'reviews_count' => 500,
                'icon_url' => 'https://cdn.example.com/icons/facebook.svg',
            ],
            [
                'id' => 11,
                'name' => 'Klaviyo Email Marketing',
                'slug' => 'klaviyo-email',
                'type' => 'General',
                'category' => 'Marketing',
                'description' => 'Automated email flows and segmentation.',
                'price_amount' => 19900,
                'rating_average' => 4.7,
                'reviews_count' => 750,
                'icon_url' => 'https://cdn.example.com/icons/klaviyo.svg',
            ],
            [
                'id' => 15,
                'name' => 'Fraud Protection Module',
                'slug' => 'fraud-protect',
                'type' => 'General',
                'category' => 'Security',
                'description' => 'Advanced algorithm to detect fraudulent orders.',
                'price_amount' => 25000,
                'rating_average' => 4.2,
                'reviews_count' => 300,
                'icon_url' => 'https://cdn.example.com/icons/security.svg',
            ],
            [
                'id' => 25,
                'name' => 'SEO Optimization Suite',
                'slug' => 'seo-suite',
                'type' => 'General',
                'category' => 'Optimization',
                'description' => 'Improve search engine rankings and traffic.',
                'price_amount' => 7500,
                'rating_average' => 4.6,
                'reviews_count' => 600,
                'icon_url' => 'https://cdn.example.com/icons/seo.svg',
            ],
            [
                'id' => 27,
                'name' => 'Product Review Manager',
                'slug' => 'review-manager',
                'type' => 'General',
                'category' => 'E-commerce',
                'description' => 'Collect and display verified customer reviews.',
                'price_amount' => 11000,
                'rating_average' => 4.9,
                'reviews_count' => 1500,
                'icon_url' => 'https://cdn.example.com/icons/review.svg',
            ],
            [
                'id' => 30,
                'name' => 'Loyalty & Rewards Pro',
                'slug' => 'loyalty-pro',
                'type' => 'Master',
                'category' => 'Retention',
                'description' => 'Build customer loyalty with tiered rewards programs.',
                'price_amount' => 65000,
                'rating_average' => 4.7,
                'reviews_count' => 400,
                'icon_url' => 'https://cdn.example.com/icons/loyalty.svg',
            ],
        ];

        foreach ($apps as $app) {
            // Using firstOrCreate ensures we don't duplicate data if the seeder is run multiple times
            ServiceApp::firstOrCreate(['id' => $app['id']], $app);
        }
    }
}
