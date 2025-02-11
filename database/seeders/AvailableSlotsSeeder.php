<?php

namespace Database\Seeders;

use App\Models\AvailableSlot;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class AvailableSlotsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $startDate = Carbon::now();
        $endDate = $startDate->copy()->addMonths(3);

        while ($startDate->lte($endDate)) {
            if ($startDate->isFriday()) {
                $startDate->addDay();
                continue;
            }

            AvailableSlot::create([
                'date' => $startDate->toDateString(),
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
            ]);

            $startDate->addDay();
        }
    }
}
