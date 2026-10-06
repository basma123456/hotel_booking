<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\Inventory;
use App\Models\RoomType;
use Illuminate\Database\Seeder;

class HotelBookingSeeder extends Seeder
{
    public function run(): void
    {
        $hotel = Hotel::create([
            'name' => 'Afaq Plus Hotel',
        ]);

        $roomType = RoomType::create([
            'hotel_id' => $hotel->id,
            'name' => 'Standard Room',
            'total_rooms' => 10,
        ]);

        foreach ([
            '2026-10-10',
            '2026-10-11',
            '2026-10-12',
            '2026-10-13',
            '2026-10-14',
        ] as $date) {
            Inventory::create([
                'room_type_id' => $roomType->id,
                'date' => $date,
                'total_rooms' => 10,
                'booked_rooms' => 0,
            ]);
        }
    }
}