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
            [
                'id' => 1,
                'name' => 'Beepost App',
                'slug' => 'beepost-app',
                'type' => 'General',
                'category' => 'Logistics',
                'description' => 'The dedicated logistics and posting application.',
                'price_amount' => 11000,
                'rating_average' => 4.9,
                'reviews_count' => 1500,
                'icon_url' => 'https://cdn.example.com/icons/review.svg',
                'app_url' => 'http://127.0.0.1:8000/',
                'sso_entrypoint' => '/sso/auth',
            ],

            [
                'id' => 2,
                'name' => 'worksuite',
                'slug' => 'worksuite',
                'type' => 'General',
                'category' => 'Finance',
                'description' => 'A fast and lightweight POS system for small businesses.',
                'price_amount' => 9000,
                'rating_average' => 4.8,
                'reviews_count' => 980,
                'icon_url' => 'https://cdn.example.com/icons/pos.svg',
                'app_url' => 'http://127.0.0.1:8004',
                'sso_entrypoint' => '/auth/sso',
            ],

            [
                'id' => 3,
                'name' => 'SmartInventory',
                'slug' => 'smartinventory',
                'type' => 'Master',
                'category' => 'Inventory',
                'description' => 'Advanced inventory management with analytics and warehouse tools.',
                'price_amount' => 15000,
                'rating_average' => 4.7,
                'reviews_count' => 1220,
                'icon_url' => 'https://cdn.example.com/icons/inventory.svg',
                'app_url' => 'https://smartinventory.io/api',
                'sso_entrypoint' => '/sso/login',
            ],

            [
                'id' => 4,
                'name' => 'FoodDash Delivery',
                'slug' => 'fooddash-delivery',
                'type' => 'General',
                'category' => 'Food Delivery',
                'description' => 'Delivery management system for restaurants and couriers.',
                'price_amount' => 10500,
                'rating_average' => 4.6,
                'reviews_count' => 860,
                'icon_url' => 'https://cdn.example.com/icons/delivery.svg',
                'app_url' => 'https://fooddash.app/api',
                'sso_entrypoint' => '/v1/sso',
            ],

            [
                'id' => 5,
                'name' => 'EduMaster LMS',
                'slug' => 'edumaster-lms',
                'type' => 'Master',
                'category' => 'Education',
                'description' => 'A modern LMS platform for academies and online tutors.',
                'price_amount' => 18000,
                'rating_average' => 4.9,
                'reviews_count' => 2100,
                'icon_url' => 'https://cdn.example.com/icons/education.svg',
                'app_url' => 'https://edumaster.io/api',
                'sso_entrypoint' => '/sso/access',
            ],

            [
                'id' => 6,
                'name' => 'ClinicOne EMR',
                'slug' => 'clinicone-emr',
                'type' => 'General',
                'category' => 'Healthcare',
                'description' => 'Electronic medical records & patient management system.',
                'price_amount' => 25000,
                'rating_average' => 4.5,
                'reviews_count' => 740,
                'icon_url' => 'https://cdn.example.com/icons/health.svg',
                'app_url' => 'https://clinicone.health/api',
                'sso_entrypoint' => '/auth/sso',
            ],

            [
                'id' => 7,
                'name' => 'FleetTrack Pro',
                'slug' => 'fleettrack-pro',
                'type' => 'General',
                'category' => 'Transport',
                'description' => 'Fleet and vehicle tracking system with GPS integration.',
                'price_amount' => 20000,
                'rating_average' => 4.7,
                'reviews_count' => 1350,
                'icon_url' => 'https://cdn.example.com/icons/gps.svg',
                'app_url' => 'https://fleettrack.pro/api',
                'sso_entrypoint' => '/sso/login',
            ],

            [
                'id' => 8,
                'name' => 'HotelEase PMS',
                'slug' => 'hoteleaze-pms',
                'type' => 'Master',
                'category' => 'Hospitality',
                'description' => 'A complete hotel management and reservation system.',
                'price_amount' => 22000,
                'rating_average' => 4.8,
                'reviews_count' => 1620,
                'icon_url' => 'https://cdn.example.com/icons/hotel.svg',
                'app_url' => 'https://hoteleaze.app/api',
                'sso_entrypoint' => '/connect/sso',
            ],

            [
                'id' => 9,
                'name' => 'BusinessSuite CRM',
                'slug' => 'businesssuite-crm',
                'type' => 'General',
                'category' => 'CRM',
                'description' => 'Powerful CRM for sales, leads, and customer engagement.',
                'price_amount' => 13000,
                'rating_average' => 4.8,
                'reviews_count' => 1900,
                'icon_url' => 'https://cdn.example.com/icons/crm.svg',
                'app_url' => 'https://businesssuite.io/api',
                'sso_entrypoint' => '/oauth/sso',
            ],

            [
                'id' => 10,
                'name' => 'ShopStack Ecommerce',
                'slug' => 'shopstack-ecommerce',
                'type' => 'Master',
                'category' => 'E‑commerce',
                'description' => 'A complete e‑commerce backend with catalog, orders, and analytics.',
                'price_amount' => 16000,
                'rating_average' => 4.9,
                'reviews_count' => 2300,
                'icon_url' => 'https://cdn.example.com/icons/shop.svg',
                'app_url' => 'https://shopstack.app/api',
                'sso_entrypoint' => '/sso/auth',
            ],

        ];

        foreach ($apps as $app) {
            // Using firstOrCreate ensures we don't duplicate data if the seeder is run multiple times
            ServiceApp::firstOrCreate(['id' => $app['id']], $app);
        }
    }
}
