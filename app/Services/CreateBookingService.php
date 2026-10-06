<?php

namespace App\Services;

use App\Exceptions\BookingUnavailableException;
use App\Models\Booking;
use App\Models\Inventory;
use App\Models\RoomType;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;



class CreateBookingService
{
  public function create(  array $data, string $idempotencyKey ,  int $userId ): Booking {


        $existingBooking  =  Booking::query()->where('idempotency_key', $idempotencyKey)->first();

        if ($existingBooking) {
            return $existingBooking->load('items');
        }

        try {

            return DB::transaction(function () use (   $data,  $idempotencyKey , $userId ) {

                $roomType = RoomType::query()
                    ->where('id', $data['room_type_id'])
                    ->where('hotel_id', $data['hotel_id'])
                    ->first();

                if (!$roomType) { 
                    throw  new BookingUnavailableException( 'The selected room type does not belong to this hotel' );
                }


                $dates   =  $this->getBookingDates(  $data['check_in'] ,  $data['check_out'] );

                sort($dates); // to make the dates in a certain  order to avoid dead lock

                $inventories = [];

                foreach ($dates as $date){
                  // lock in time of updating to avoid concurrency
                    $inventory = Inventory::query()->where('room_type_id' ,  $roomType->id)->whereDate('date' ,   $date)->lockForUpdate()->first();

                    if (!$inventory){
                        throw  new BookingUnavailableException(  "No inventory exists for {$date}." );
                    }

                    $availableRooms   =  $inventory->total_rooms - $inventory->booked_rooms;

                    if ($availableRooms < $data['rooms_count']){ 
                        
                        throw new BookingUnavailableException(
                            "Not enough rooms available for {$date}."
                        );
                    }

                    $inventories[$date] = $inventory;
                }

                
                // Booking::create([
                //     'booking_number' =>  $this->generateBookingNumber() ,
                //     'idempotency_key' =>   $idempotencyKey,
                //     'hotel_id' =>  $data['hotel_id'],
                //     'customer_name' =>  $data['customer_name'] ,
                //     'phone' =>  $data['phone']??null ,
                //     'email' =>  $data['email']??null ,
                //     'check_in' =>  $data['check_in'],
                //     'check_out' =>   $data['check_out'],
                //     'rooms_count' =>  $data['rooms_count'] ,
                //     'status' =>   'confirmed',
                //     'user_id' => $userId,

                // ]);

                $booking = $this->createBookingWithUniqueNumber([
                    'idempotency_key' => $idempotencyKey,
                    'hotel_id' => $data['hotel_id'],
                    'customer_name' => $data['customer_name'],
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'] ?? null,
                    'check_in' => $data['check_in'],
                    'check_out' => $data['check_out'],
                    'rooms_count' => $data['rooms_count'],
                    'status' => 'confirmed',
                    'user_id' => $userId,
                ]);
                
                foreach ($inventories as $date => $inventory){

                    $inventory->increment( 'booked_rooms',     $data[ 'rooms_count']  );

                    $booking->items()->create(['room_type_id'  => $roomType->id,  'date' =>$date,  'rooms' => $data['rooms_count']]);
                }

                return $booking->load('items');



            }, attempts: 5);  // to retry 5 tries after failure

        } catch (QueryException $e) {


        //in case of idempotency key is found before
                if (   $e->getCode() === '23000'   && str_contains(strtolower($e->getMessage()), 'idempotency_key')) {
                    $existingBooking = Booking::query()
                        ->where('idempotency_key', $idempotencyKey)
                        ->first();

                    if ($existingBooking) {
                        return $existingBooking->load('items');
                    }
                }

                 throw $e;
        }
    }




    private  function   getBookingDates(  string  $checkIn,  string  $checkOut ): array {

        $start =   Carbon::parse($checkIn);
        $end =   Carbon::parse($checkOut);
        $dates = [];

        for (  $date = $start->copy();      $date->lt($end);     $date->addDay() ){ //here
                $dates[]  = $date->format('Y-m-d');
        }

         return $dates;
    }



    private function createBookingWithUniqueNumber(
        array $bookingData,
        int $maxAttempts = 5
    ): Booking {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {

            try {
                return Booking::create([
                    ...$bookingData,
                    'booking_number' => $this->generateBookingNumber(),
                ]);

            } catch (QueryException $e) {

                // Duplicate booking number: generate another one.
                if (
                    $e->getCode() === '23000'
                    && str_contains(strtolower($e->getMessage()), 'booking_number')
                ) {
                    if ($attempt === $maxAttempts) {
                        throw $e;
                    }

                    continue;
                }

                throw $e;
            }
        }

        throw new \RuntimeException('Unable to generate a unique booking number.');
    }



    private function generateBookingNumber(): string
    {
            return 'AFP-' .  now()->format('Ymd') . '-' . strtoupper(Str::random(10));
    }
    
    
    }
