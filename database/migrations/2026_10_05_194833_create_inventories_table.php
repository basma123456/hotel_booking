<?php
/*
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up(): void
    {
            Schema::create('inventory', function (Blueprint $table) {
                $table->id();

                $table->foreignId('room_type_id')->constrained('room_types')->cascadeOnDelete();

                $table->date('date');

                $table->unsignedInteger('total_rooms');

                $table->unsignedInteger('booked_rooms')->default(0);

                $table->timestamps();

                $table->unique([   'room_type_id',  'date'   ]);

                $table->index([   'room_type_id',  'date'   ]);
            });    
        
        }
     public function down(): void
    {
        Schema::dropIfExists('inventory');

    }
};
*/


 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table) {
                $table->id();
                $table->foreignId('room_type_id')->constrained('room_types')->cascadeOnDelete();
                $table->date('date');
                $table->unsignedInteger('total_rooms');
                $table->unsignedInteger('booked_rooms')->default(0);
                $table->timestamps();
                $table->unique(['room_type_id', 'date']);
        });
    }

    public function down(): void
    {
            Schema::dropIfExists('inventory');
    }
};