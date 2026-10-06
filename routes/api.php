<?php

use App\Http\Controllers\Api\V1\BookingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::prefix('v1')
->middleware(['auth:sanctum' ,  'throttle:bookings'])
->group(function () {
    Route::post( '/bookings' , [BookingController::class , 'store']);
    Route::post( '/bookings/{id}/cancel' , [BookingController::class , 'cancel' ] );
});