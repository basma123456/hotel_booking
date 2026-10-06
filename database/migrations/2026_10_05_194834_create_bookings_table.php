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
        Schema::create('bookings', function (Blueprint $table) {
                $table->id();
                $table->string('booking_number') ->unique();
                $table->string('idempotency_key')->unique();
                $table->foreignId('hotel_id')->constrained('hotels')->restrictOnDelete();
                $table->string('customer_name');
                $table->string('phone', 30)->nullable();
                $table->string('email')->nullable();
                $table->date('check_in');
                $table->date('check_out');
                $table->unsignedInteger('rooms_count');
                $table->enum('status' , [  'confirmed' , 'cancelled' ])->default('confirmed');
                $table->timestamps();
                $table->index([  'hotel_id' ,  'check_in' ,  'check_out'  ]);
                $table->index('status');
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
