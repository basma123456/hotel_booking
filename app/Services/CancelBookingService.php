<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;

class CancelBookingService
{
 public function cancel(int $bookingId ,  int $userId): Booking
    {
        return DB::transaction(   function () use ($bookingId, $userId) {

            $booking  = Booking::query()->where(['id'=> $bookingId , 'user_id' => $userId])->lockForUpdate()->firstOrFail();
// in case of already cancelled booking before 
            if ($booking->status  === 'cancelled') {
                 return  $booking->load('items');
            }

            $items =   $booking->items()->orderBy('date')->get();

            $inventories = [];
            foreach ($items as $item) {
// also lock the inventory through the process to avoid concurrency problems
                $inventory = Inventory::query()
                            ->where('room_type_id', $item->room_type_id)
                            ->whereDate('date', $item->date)
                            ->lockForUpdate()
                            ->firstOrFail();

                $inventories[$item->id] = $inventory;
            }


            foreach($items as $item){
// deacrese the booked rooms in inventory for each items
                $inventory = $inventories[$item->id];
                $inventory->decrement(
                    'booked_rooms',
                    $item->rooms
                );
            }

            $booking->update([  'status' => 'cancelled' ]);
            return $booking->load('items');


        }, attempts: 5); //retry for five tries after failure
    }
    
    }
