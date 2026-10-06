<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Hotel extends Model
{
        use HasFactory;

    protected $fillable = [
        'name' 
    ];

    public function roomTypes(): HasMany
    {
        return  $this->hasMany(RoomType::class);
    }

    public function bookings(): HasMany
    {
        return  $this->hasMany(Booking::class);
    }
    
    }
