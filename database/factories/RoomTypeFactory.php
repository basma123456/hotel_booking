<?php

namespace Database\Factories;

use App\Models\Hotel;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomType>
 */
class RoomTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */


    protected $model = RoomType::class;

    public function definition(): array
    {
        return [
            'hotel_id' => Hotel::factory(),

            'name' => fake()->randomElement([
                'Standard Room',
                'Deluxe Room',
                'Suite',
            ]),

            'total_rooms' => 10,
        ];
    }

}
