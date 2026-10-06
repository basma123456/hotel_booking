<?php

use App\Models\Hotel;
use App\Models\Inventory;
use App\Models\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Booking;


uses(RefreshDatabase::class);

function createInventoryForTest(
    RoomType $roomType,
    string $date,
    int $rooms = 10
): Inventory {
    return Inventory::create([
        'room_type_id' => $roomType->id,
        'date' => $date,
        'total_rooms' => $rooms,
        'booked_rooms' => 0,
    ]);
}


test('booking fails when rooms are unavailable', function () {
    $hotel = Hotel::factory()->create();

    $roomType = RoomType::factory()->create([
        'hotel_id' => $hotel->id,
        'total_rooms' => 2,
    ]);

    createInventoryForTest($roomType, '2026-10-10', 2);
    createInventoryForTest($roomType, '2026-10-11', 2);
    createInventoryForTest($roomType, '2026-10-12', 2);

    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');
    $response = $this
        ->withHeader('Idempotency-Key', 'test-unavailable-001')
        ->postJson('/api/v1/bookings', [
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomType->id,
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-13',
            'rooms_count' => 3,
            'customer_name' => 'Basma Gamal',
            'phone' => '01000000000',
            'email' => 'basma@example.com',
        ]);
    //$response->dump();

    $response->assertStatus(409);

    $response->assertJson([
        'message' => 'Not enough rooms available for 2026-10-10.',
    ]);

    $this->assertDatabaseCount('bookings', 0);

    $this->assertDatabaseHas('inventory', [
        'room_type_id' => $roomType->id,
        'date' => '2026-10-10',
        'booked_rooms' => 0,
    ]);
});
test('booking can be created', function () {

    $hotel = Hotel::factory()->create();

    $roomType = RoomType::factory()->create([
        'hotel_id' => $hotel->id,
        'total_rooms' => 10,
    ]);

    createInventoryForTest(
        $roomType,
        '2026-10-10'
    );

    createInventoryForTest(
        $roomType,
        '2026-10-11'
    );

    createInventoryForTest(
        $roomType,
        '2026-10-12'
    );


    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    $response = $this
        ->withHeader(
            'Idempotency-Key',
            'test-booking-001'
        )
        ->postJson('/api/v1/bookings', [

            'hotel_id' => $hotel->id,

            'room_type_id' => $roomType->id,

            'check_in' => '2026-10-10',

            'check_out' => '2026-10-13',

            'rooms_count' => 2,

            'customer_name' => 'Basma Gamal',

            'phone' => '01000000000',

            'email' => 'basma@example.com',
        ]);

    $response->assertStatus(201);

    $response->assertJson([
        'message' => 'Booking created successfully.',
    ]);

    $this->assertDatabaseCount(
        'bookings',
        1
    );

    $this->assertDatabaseHas('bookings', [
        'hotel_id' => $hotel->id,
        'rooms_count' => 2,
        'status' => 'confirmed',
    ]);

    $this->assertDatabaseHas('inventory', [
        'room_type_id' => $roomType->id,
        'date' => '2026-10-10',
        'booked_rooms' => 2,
    ]);

    $this->assertDatabaseHas('inventory', [
        'room_type_id' => $roomType->id,
        'date' => '2026-10-11',
        'booked_rooms' => 2,
    ]);

    $this->assertDatabaseHas('inventory', [
        'room_type_id' => $roomType->id,
        'date' => '2026-10-12',
        'booked_rooms' => 2,
    ]);

});

test('same idempotency key does not create duplicate booking', function () {
    $hotel = Hotel::factory()->create();

    $roomType = RoomType::factory()->create([
        'hotel_id' => $hotel->id,
        'total_rooms' => 10,
    ]);

    createInventoryForTest($roomType, '2026-10-10');
    createInventoryForTest($roomType, '2026-10-11');
    createInventoryForTest($roomType, '2026-10-12');

    $payload = [
        'hotel_id' => $hotel->id,
        'room_type_id' => $roomType->id,
        'check_in' => '2026-10-10',
        'check_out' => '2026-10-13',
        'rooms_count' => 2,
        'customer_name' => 'Basma Gamal',
        'phone' => '01000000000',
        'email' => 'basma@example.com',
    ];


    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    $firstResponse = $this
        ->withHeader('Idempotency-Key', 'same-key-001')
        ->postJson('/api/v1/bookings', $payload);

    $firstResponse->assertStatus(201);

    $firstBookingId = $firstResponse->json('data.id');

    $secondResponse = $this
        ->withHeader('Idempotency-Key', 'same-key-001')
        ->postJson('/api/v1/bookings', $payload);

    $secondResponse->assertStatus(201);

    $secondBookingId = $secondResponse->json('data.id');

    expect($secondBookingId)->toBe($firstBookingId);

    $this->assertDatabaseCount('bookings', 1);

    $this->assertDatabaseHas('inventory', [
        'room_type_id' => $roomType->id,
        'date' => '2026-10-10',
        'booked_rooms' => 2,
    ]);

    $this->assertDatabaseHas('inventory', [
        'room_type_id' => $roomType->id,
        'date' => '2026-10-11',
        'booked_rooms' => 2,
    ]);

    $this->assertDatabaseHas('inventory', [
        'room_type_id' => $roomType->id,
        'date' => '2026-10-12',
        'booked_rooms' => 2,
    ]);
});


test('different idempotency keys create different bookings', function () {
    $hotel = Hotel::factory()->create();

    $roomType = RoomType::factory()->create([
        'hotel_id' => $hotel->id,
        'total_rooms' => 10,
    ]);

    createInventoryForTest($roomType, '2026-10-10');
    createInventoryForTest($roomType, '2026-10-11');
    createInventoryForTest($roomType, '2026-10-12');

    $payload = [
        'hotel_id' => $hotel->id,
        'room_type_id' => $roomType->id,
        'check_in' => '2026-10-10',
        'check_out' => '2026-10-13',
        'rooms_count' => 2,
        'customer_name' => 'Basma Gamal',
        'phone' => '01000000000',
        'email' => 'basma@example.com',
    ];

    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    $firstResponse = $this
        ->withHeader('Idempotency-Key', 'different-key-001')
        ->postJson('/api/v1/bookings', $payload);

    $firstResponse->assertStatus(201);

    $secondResponse = $this
        ->withHeader('Idempotency-Key', 'different-key-002')
        ->postJson('/api/v1/bookings', $payload);

    $secondResponse->assertStatus(201);

    $firstBookingId = $firstResponse->json('data.id');
    $secondBookingId = $secondResponse->json('data.id');

    expect($secondBookingId)->not->toBe($firstBookingId);

    $this->assertDatabaseCount('bookings', 2);

    $this->assertDatabaseHas('inventory', [
        'room_type_id' => $roomType->id,
        'date' => '2026-10-10',
        'booked_rooms' => 4,
    ]);

    $this->assertDatabaseHas('inventory', [
        'room_type_id' => $roomType->id,
        'date' => '2026-10-11',
        'booked_rooms' => 4,
    ]);

    $this->assertDatabaseHas('inventory', [
        'room_type_id' => $roomType->id,
        'date' => '2026-10-12',
        'booked_rooms' => 4,
    ]);
});


test('booking cancellation restores inventory', function () {
    $hotel = Hotel::factory()->create();

    $roomType = RoomType::factory()->create([
        'hotel_id' => $hotel->id,
        'total_rooms' => 10,
    ]);

    createInventoryForTest($roomType, '2026-10-10');
    createInventoryForTest($roomType, '2026-10-11');
    createInventoryForTest($roomType, '2026-10-12');


    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    $response = $this
        ->withHeader('Idempotency-Key', 'cancel-test-001')
        ->postJson('/api/v1/bookings', [
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomType->id,
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-13',
            'rooms_count' => 2,
            'customer_name' => 'Basma Gamal',
            'phone' => '01000000000',
            'email' => 'basma@example.com',
        ]);

    $response->assertStatus(201);

    $bookingId = $response->json('data.id');

    // Verify rooms were booked
    $this->assertDatabaseHas('inventory', [
        'room_type_id' => $roomType->id,
        'date' => '2026-10-10',
        'booked_rooms' => 2,
    ]);

    // Cancel booking
    $cancelResponse = $this
        ->postJson("/api/v1/bookings/{$bookingId}/cancel");

    $cancelResponse->assertStatus(200);

    $cancelResponse->assertJson([
        'message' => 'Booking cancelled successfully.',
        'data' => [
            'id' => $bookingId,
            'status' => 'cancelled',
        ],
    ]);

    // Verify rooms were restored
    $this->assertDatabaseHas('inventory', [
        'room_type_id' => $roomType->id,
        'date' => '2026-10-10',
        'booked_rooms' => 0,
    ]);

    $this->assertDatabaseHas('inventory', [
        'room_type_id' => $roomType->id,
        'date' => '2026-10-11',
        'booked_rooms' => 0,
    ]);

    $this->assertDatabaseHas('inventory', [
        'room_type_id' => $roomType->id,
        'date' => '2026-10-12',
        'booked_rooms' => 0,
    ]);

    // Verify booking status
    $this->assertDatabaseHas('bookings', [
        'id' => $bookingId,
        'status' => 'cancelled',
    ]);
});



test('repeated cancellation does not restore inventory twice', function () {
    $hotel = Hotel::factory()->create();

    $roomType = RoomType::factory()->create([
        'hotel_id' => $hotel->id,
        'total_rooms' => 10,
    ]);

    createInventoryForTest($roomType, '2026-10-10');
    createInventoryForTest($roomType, '2026-10-11');
    createInventoryForTest($roomType, '2026-10-12');


    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    // Create booking
    $response = $this
        ->withHeader('Idempotency-Key', 'cancel-twice-001')
        ->postJson('/api/v1/bookings', [
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomType->id,
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-13',
            'rooms_count' => 2,
            'customer_name' => 'Basma Gamal',
            'phone' => '01000000000',
            'email' => 'basma@example.com',
            'user_id' => $user->id,
        ]);

    $response->assertStatus(201);

    $bookingId = $response->json('data.id');

    // First cancellation
    $firstCancelResponse = $this
        ->postJson("/api/v1/bookings/{$bookingId}/cancel");

    $firstCancelResponse->assertStatus(200);

    // Verify inventory restored once
    $this->assertDatabaseHas('inventory', [
        'room_type_id' => $roomType->id,
        'date' => '2026-10-10',
        'booked_rooms' => 0,
    ]);

    // Second cancellation
    $secondCancelResponse = $this
        ->postJson("/api/v1/bookings/{$bookingId}/cancel");

    $secondCancelResponse->assertStatus(200);

    // Inventory must still be 0
    $this->assertDatabaseHas('inventory', [
        'room_type_id' => $roomType->id,
        'date' => '2026-10-10',
        'booked_rooms' => 0,
    ]);

    $this->assertDatabaseHas('inventory', [
        'room_type_id' => $roomType->id,
        'date' => '2026-10-11',
        'booked_rooms' => 0,
    ]);

    $this->assertDatabaseHas('inventory', [
        'room_type_id' => $roomType->id,
        'date' => '2026-10-12',
        'booked_rooms' => 0,
    ]);

    // Booking must remain cancelled
    $this->assertDatabaseHas('bookings', [
        'id' => $bookingId,
        'status' => 'cancelled',
    ]);
});

test('each booking gets a unique booking number', function () {
    $hotel = Hotel::factory()->create();

    $roomType = RoomType::factory()->create([
        'hotel_id' => $hotel->id,
        'total_rooms' => 10,
    ]);

    createInventoryForTest($roomType, '2026-10-10');
    createInventoryForTest($roomType, '2026-10-11');
    createInventoryForTest($roomType, '2026-10-12');


    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    $response1 = $this
        ->withHeader('Idempotency-Key', 'unique-booking-001')
        ->postJson('/api/v1/bookings', [
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomType->id,
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-13',
            'rooms_count' => 2,
            'customer_name' => 'Customer One',
        ]);

    $response1->assertStatus(201);

    $response2 = $this
        ->withHeader('Idempotency-Key', 'unique-booking-002')
        ->postJson('/api/v1/bookings', [
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomType->id,
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-13',
            'rooms_count' => 2,
            'customer_name' => 'Customer Two',
        ]);

    $response2->assertStatus(201);

    $bookingNumber1 = $response1->json('data.booking_number');
    $bookingNumber2 = $response2->json('data.booking_number');

    expect($bookingNumber1)
        ->not->toBe($bookingNumber2);

    expect($bookingNumber1)
        ->toMatch('/^AFP-\d{8}-[A-Z0-9]{10}$/');

    expect($bookingNumber2)
        ->toMatch('/^AFP-\d{8}-[A-Z0-9]{10}$/');

    $this->assertDatabaseCount('bookings', 2);
});

test('booking validation rejects invalid data', function () {

    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    $response = $this
        ->withHeader('Idempotency-Key', 'validation-test-001')
        ->postJson('/api/v1/bookings', [
            'hotel_id' => 999999,
            'room_type_id' => 999999,
            'check_in' => '2026-10-15',
            'check_out' => '2026-10-10',
            'rooms_count' => 0,
            'customer_name' => '',
            'email' => 'invalid-email',
        ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'hotel_id',
        'room_type_id',
        'check_out',
        'rooms_count',
        'customer_name',
        'email',
    ]);

    $this->assertDatabaseCount('bookings', 0);
});


// test('booking requires idempotency key', function () {
//     $response = $this->postJson('/api/v1/bookings', [
//         'hotel_id' => 1,
//         'room_type_id' => 1,
//         'check_in' => '2026-10-10',
//         'check_out' => '2026-10-13',
//         'rooms_count' => 1,
//         'customer_name' => 'Test Customer',
//     ]);

//     $response->assertStatus(400);

//     $response->assertJson([
//         'message' => ' Idempotency key header is required.',
//     ]);

//     $this->assertDatabaseCount('bookings', 0);
// });
test('booking requires idempotency key', function () {

    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    $response = $this->postJson('/api/v1/bookings', [
        'hotel_id' => 1,
        'room_type_id' => 1,
        'check_in' => '2026-10-10',
        'check_out' => '2026-10-13',
        'rooms_count' => 1,
        'customer_name' => 'Test Customer',
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'idempotency_key',
    ]);

    $this->assertDatabaseCount('bookings', 0);
});

it('cannot cancel another user booking', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $hotel = Hotel::factory()->create();

    $roomType = RoomType::factory()->create([
        'hotel_id' => $hotel->id,
        'total_rooms' => 10,
    ]);

    foreach ([
        '2026-10-10',
        '2026-10-11',
        '2026-10-12',
    ] as $date) {
        Inventory::factory()->create([
            'room_type_id' => $roomType->id,
            'date' => $date,
            'total_rooms' => 10,
            'booked_rooms' => 2,
        ]);
    }

    $booking  = Booking::create([
    'booking_number' => 'AFP-TEST-UNAUTHORIZED-001',
    'idempotency_key' => 'unauthorized-test-key',
    'user_id' => $owner->id,
    'hotel_id' => $hotel->id,
    'customer_name' => 'Owner User',
    'phone' => '01000000000',
    'email' => 'owner@example.com',
    'check_in' => '2026-10-10',
    'check_out' => '2026-10-13',
    'rooms_count' => 2,
    'status' => 'confirmed',
]);

    foreach ([
        '2026-10-10',
        '2026-10-11',
        '2026-10-12',
    ] as $date) {
        $booking->items()->create([
            'room_type_id' => $roomType->id,
            'date' => $date,
            'rooms' => 2,
        ]);
    }

    $this->actingAs($otherUser, 'sanctum');

    $response = $this->postJson(
        "/api/v1/bookings/{$booking->id}/cancel"
    );

    $response->assertStatus(404);

    $this->assertDatabaseHas('bookings', [
        'id' => $booking->id,
        'user_id' => $owner->id,
        'status' => 'confirmed',
    ]);

    $this->assertDatabaseHas('inventory', [
        'room_type_id' => $roomType->id,
        'date' => '2026-10-10',
        'booked_rooms' => 2,
    ]);
});


it('rate limits booking requests', function () {
    $user = User::factory()->create();

    $hotel = Hotel::factory()->create();

    $roomType = RoomType::factory()->create([
        'hotel_id' => $hotel->id,
        'total_rooms' => 100,
    ]);

    foreach ([
        '2026-10-10',
        '2026-10-11',
        '2026-10-12',
    ] as $date) {
        Inventory::factory()->create([
            'room_type_id' => $roomType->id,
            'date' => $date,
            'total_rooms' => 100,
            'booked_rooms' => 0,
        ]);
    }

    $this->actingAs($user, 'sanctum');

    $responses = [];

    for ($i = 1; $i <= 11; $i++) {
        $responses[] = $this->postJson('/api/v1/bookings', [
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomType->id,
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-13',
            'rooms_count' => 1,
            'customer_name' => 'Rate Limit Test',
            'phone' => '01000000000',
            'email' => 'rate-limit@example.com',
        ], [
            'Idempotency-Key' => 'rate-limit-test-' . $i,
        ]);
    }

    $responses[9]->assertStatus(201);
    $responses[10]->assertStatus(429);
});