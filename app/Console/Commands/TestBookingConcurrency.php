<?php

namespace App\Console\Commands;

use App\Models\Hotel;
use App\Models\Inventory;
use App\Models\RoomType;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

 class TestBookingConcurrency extends Command
{
     protected $signature = 'booking:test-concurrency';

    protected $description = 'Test concurrent booking requests and prevent overbooking';

    public function handle(): int
    {
        $hotel = Hotel::create([
            'name' => 'Concurrency Test Hotel',
        ]);

        $roomType = RoomType::create([
            'hotel_id' => $hotel->id,
            'name' => 'Standard Room',
            'total_rooms' => 2,
        ]);

        foreach ([
            '2026-10-10',
            '2026-10-11',
            '2026-10-12',
        ] as $date) {
            Inventory::create([
                'room_type_id' => $roomType->id,
                'date' => $date,
                'total_rooms' => 2,
                'booked_rooms' => 0,
            ]);
        }

        $this->info('Test data created.');
        $this->info("Hotel ID: {$hotel->id}");
        $this->info("Room Type ID: {$roomType->id}");

        return self::SUCCESS;
    }
    
}
