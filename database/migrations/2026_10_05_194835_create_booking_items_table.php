<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('booking_items', function (Blueprint $table) {
  $table->id();

            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->cascadeOnDelete();

            $table->foreignId('room_type_id')
                ->constrained('room_types')
                ->restrictOnDelete();

            $table->date('date');

            $table->unsignedInteger('rooms');

            $table->timestamps();

            $table->unique([
                'booking_id',
                'room_type_id',
                'date'
            ]);

            $table->index([
                'room_type_id',
                'date'
            ]);
                    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_items');
    }
};
