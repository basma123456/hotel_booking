<?php

$url = 'http://127.0.0.1:8000/api/v1/bookings';

$hotelId = (int) ($argv[1] ?? 0);
$roomTypeId = (int) ($argv[2] ?? 0);

if (!$hotelId || !$roomTypeId) {
    echo "Usage:\n";
    echo "php tests/Concurrency/idempotency_concurrency.php HOTEL_ID ROOM_TYPE_ID\n";
    exit(1);
}

/*
|--------------------------------------------------------------------------
| Create a Sanctum token
|--------------------------------------------------------------------------
*/

// $user = \App\Models\User::create([
//     'name' => 'Concurrency Test User',
//     'email' => 'concurrency-' . uniqid() . '@example.com',
//     'password' => bcrypt('password'),
// ]);

// $token = $user->createToken('concurrency-test')->plainTextToken;
$token ="1|MWvmoKhFAmKAHWzgqtkFvYnQAi5KSArUBseLIbSTd93a6aa0";
/*
|--------------------------------------------------------------------------
| Booking payload
|--------------------------------------------------------------------------
*/

$payload = json_encode([
    'hotel_id' => $hotelId,
    'room_type_id' => $roomTypeId,
    'check_in' => '2026-10-10',
    'check_out' => '2026-10-13',
    'rooms_count' => 1,
    'customer_name' => 'Idempotency Test',
    'phone' => '01000000000',
    'email' => 'idempotency@example.com',
]);

/*
|--------------------------------------------------------------------------
| Create HTTP request
|--------------------------------------------------------------------------
*/

function createHandle(
    string $url,
    string $payload,
    string $idempotencyKey,
    string $token
) {
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
            'Idempotency-Key: ' . $idempotencyKey,
        ],
    ]);

    return $ch;
}

/*
|--------------------------------------------------------------------------
| Same Idempotency-Key
|--------------------------------------------------------------------------
*/

$idempotencyKey = 'concurrent-same-key-' . uniqid();

$multiHandle = curl_multi_init();

$handle1 = createHandle(
    $url,
    $payload,
    $idempotencyKey,
    $token
);

$handle2 = createHandle(
    $url,
    $payload,
    $idempotencyKey,
    $token
);

curl_multi_add_handle($multiHandle, $handle1);
curl_multi_add_handle($multiHandle, $handle2);

$running = null;

do {
    curl_multi_exec($multiHandle, $running);

    if ($running) {
        curl_multi_select($multiHandle);
    }
} while ($running);

$response1 = curl_multi_getcontent($handle1);
$response2 = curl_multi_getcontent($handle2);

$status1 = curl_getinfo($handle1, CURLINFO_HTTP_CODE);
$status2 = curl_getinfo($handle2, CURLINFO_HTTP_CODE);

curl_multi_remove_handle($multiHandle, $handle1);
curl_multi_remove_handle($multiHandle, $handle2);

curl_close($handle1);
curl_close($handle2);

curl_multi_close($multiHandle);

echo "\n";
echo "====================================\n";
echo "Idempotency Concurrency Test Result\n";
echo "====================================\n";

echo "\nIdempotency-Key:\n";
echo $idempotencyKey . "\n";

echo "\nRequest 1:\n";
echo "HTTP Status: {$status1}\n";
echo $response1 . "\n";

echo "\nRequest 2:\n";
echo "HTTP Status: {$status2}\n";
echo $response2 . "\n";