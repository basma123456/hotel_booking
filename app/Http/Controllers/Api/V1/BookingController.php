<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\BookingUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Services\CancelBookingService;
use App\Services\CreateBookingService;
use Illuminate\Http\JsonResponse;
use Laravel\Mcp\Request;

class BookingController extends Controller
{
  public function store(  StoreBookingRequest $request ,  CreateBookingService $service ): JsonResponse 
  {
        // $idempotencyKey   =   $request->header('Idempotency-Key');
            // if (!$idempotencyKey) {
            //         return response()->json([
            //                     'message' => ' Idempotency key header is required.',
            //                     ] , 400);
            // }

        $data = $request->validated();  
        $userId = $request->user()->id;
        $idempotencyKey = $data['idempotency_key'];
        unset($data['idempotency_key']);

        try {

            $booking = $service->create(
                        $data,
                        $idempotencyKey,
                        $userId
                    );

            return response()->json(['message'  =>   'Booking created successfully.' ,
                'data' =>  [
                    'id' =>  $booking->id,
                    'booking_number' => $booking->booking_number,
                    'hotel_id' =>  $booking->hotel_id,
                    'customer_name' =>  $booking->customer_name ,
                    'check_in' => $booking->check_in->format('Y-m-d'),
                    'check_out' =>  $booking->check_out->format('Y-m-d'),
                    'rooms_count' => $booking->rooms_count,
                    'status' =>  $booking->status ,
                ],
            ], 201);

        } catch (BookingUnavailableException $e) {
            return  response()->json( [  'message' =>  $e->getMessage() ,  ] , 409);
        }
    }
    
    




    public function cancel( int $id , CancelBookingService $service , Request $request ): JsonResponse {

        $booking = $service->cancel( $id, $request->user()->id);

        return response()->json(['message' => 'Booking cancelled successfully.',  'data' => [ 'id' =>  $booking->id , 'booking_number' => $booking->booking_number , 'status' => $booking->status   ], ]);
    }
}