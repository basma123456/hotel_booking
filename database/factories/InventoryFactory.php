<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inventory>
 */
class InventoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

 protected $model = Inventory::class;

    public function definition(): array
    {
        return [
            'room_type_id' => RoomType::factory(),
            'date' => fake()->date(),
            'total_rooms' => 10,
            'booked_rooms' => 0,
        ];
    }
    
}
