<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
protected $fillable = [
        'booking_number',
        'idempotency_key',
        'hotel_id',
        'customer_name',
        'phone',
        'email',
        'check_in',
        'check_out',
        'rooms_count',
        'status',
        'user_id',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
    ];

        public function hotel(): BelongsTo
        {
            return $this->belongsTo(Hotel::class);
        }


        public function items(): HasMany
        {
            return $this->hasMany(BookingItem::class);
        }

    
        public function user(): BelongsTo
        {
            return $this->belongsTo(User::class);
        }
    }
