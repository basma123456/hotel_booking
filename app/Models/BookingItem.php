<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingItem extends Model
{
        use HasFactory;

        protected $fillable = [
            'booking_id',
            'room_type_id',
            'date',
            'rooms',
        ];

        
        protected $casts = [
            'date' =>  'date',
        ];



        public function booking():  BelongsTo
        {

            return $this->belongsTo(Booking::class);
        }

        public function roomType(): BelongsTo
        {
            return $this->belongsTo(RoomType::class);
        }
            
    
    }
