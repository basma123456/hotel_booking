# Afaq Plus Hotel Booking System

Laravel API for managing hotel room inventory and bookings.

## Requirements

* PHP 8.3+
* Laravel 12
* MySQL
* Composer

## Installation

Clone the repository and install the dependencies:

```bash
git clone https://github.com/basma123456/hotel_booking.git
cd hotes_booking
composer install
```

Create the environment file:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Configure the database in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=afaq_booking
DB_USERNAME=root
DB_PASSWORD=
```

Run the migrations and seed the database:

```bash
php artisan migrate --seed
```

Start the application:

```bash
php artisan serve
```

The API will be available at:

```text
http://127.0.0.1:8000
```

## Authentication

The booking endpoints use Laravel Sanctum.

Send the token with each request:

```http
Authorization: Bearer YOUR_TOKEN
```

## API Endpoints

### Create Booking

```http
POST /api/v1/bookings
```

Headers:

```http
Authorization: Bearer YOUR_TOKEN
Idempotency-Key: unique-request-key
Content-Type: application/json
```

Request example:

```json
{
    "hotel_id": 1,
    "room_type_id": 1,
    "check_in": "2026-10-10",
    "check_out": "2026-10-13",
    "rooms_count": 2,
    "customer_name": "Test User",
    "phone": "01000000000",
    "email": "test@example.com"
}
```

A successful request returns `201 Created`.

### Cancel Booking

```http
POST /api/v1/bookings/{id}/cancel
```

Authentication is required.

A booking can only be cancelled by its owner.

## Booking Dates

The check-out date is not included in the booking.

For example:

```text
check_in  = 2026-10-10
check_out = 2026-10-13
```

The inventory dates used are:

```text
2026-10-10
2026-10-11
2026-10-12
```

## Inventory and Overbooking

Inventory is stored per room type and date.

When a booking is created, the required inventory rows are locked using database row-level locking inside a transaction.

Available rooms are calculated as:

```text
available = total_rooms - booked_rooms
```

If there are not enough rooms for any requested date, the booking is rejected.

The inventory rows are locked before they are updated. This prevents concurrent requests from booking the same available rooms.

The dates are processed in a consistent order to reduce the possibility of deadlocks when multiple bookings are created at the same time.

## Idempotency

The booking endpoint requires an `Idempotency-Key` header.

The key is stored with the booking and has a unique database constraint.

If the same request is sent again with the same key, the existing booking is returned instead of creating another booking.

The unique database constraint also protects against duplicate bookings when identical requests arrive concurrently.

Example:

```http
Idempotency-Key: booking-request-123
```

Sending the request multiple times with the same key returns the same booking.

## Cancellation

Cancellation is handled inside a database transaction.

When a confirmed booking is cancelled:

1. The booking row is locked.
2. Its booking items are loaded.
3. The related inventory rows are locked.
4. The booked room count is decreased.
5. The booking status is changed to `cancelled`.

If the booking is already cancelled, the operation does not change the inventory again.

This prevents inventory from being restored more than once.

## Database Structure

Main tables:

```text
hotels
room_types
inventory
bookings
booking_items
```

Relationships:

```text
Hotel
 └── Room Types
       └── Inventory

Hotel
 └── Bookings
       └── Booking Items
             └── Room Type
```

Important database constraints include:

* Foreign keys between related tables
* Unique booking numbers
* Unique idempotency keys
* Unique inventory per room type and date
* Unique booking item per booking, room type and date
* Indexes for frequently queried columns

## Validation

The API validates:

* Hotel existence
* Room type existence
* Room type belonging to the selected hotel
* Valid check-in and check-out dates
* Check-out after check-in
* Positive room count
* Customer name
* Valid email when provided
* Required `Idempotency-Key`

Invalid requests return validation errors with HTTP `422 Unprocessable Entity`.

When inventory is not available, the API returns `409 Conflict`.

## Authorization

Booking operations require authentication through Laravel Sanctum.

A user can only cancel their own booking. Attempting to cancel another user's booking is rejected.

## Rate Limiting

The booking API uses Laravel's rate limiter.

The current limit is:

```text
10 requests per minute
```

The limit is applied per authenticated user.

Requests exceeding the limit return:

```text
429 Too Many Requests
```

## Testing

The tests use a separate MySQL database to avoid affecting the development database.

### 1. Create the testing database

Create a MySQL database named:

```text
afaq_booking_testing
```

For example:

```sql
CREATE DATABASE afaq_booking_testing;
```

### 2. Configure `.env.testing`

Create a `.env.testing` file in the project root:

```env
APP_ENV=testing
APP_KEY=

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=afaq_booking_testing
DB_USERNAME=root
DB_PASSWORD=
```

Update the database username and password if needed.

### 3. Generate the testing application key

Run:

```bash
php artisan key:generate --env=testing
```

### 4. Run the tests

Run:

```bash
php artisan test
```

The tests use the testing database and recreate the required database structure as part of the test setup.

The test suite covers:

* Successful booking
* Validation
* Unavailable rooms
* Booking date handling
* Concurrent booking requests
* Overbooking prevention
* Idempotency
* Concurrent idempotency requests
* Booking cancellation
* Inventory restoration
* Repeated cancellation
* Unauthorized cancellation
* Rate limiting

## Concurrency Tests

The project also contains scripts for testing real concurrent HTTP requests.

Start the Laravel server:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Then run the booking concurrency test:

```bash
php tests/Concurrency/booking_concurrency.php
```

The idempotency concurrency test can be run with:

```bash
php tests/Concurrency/idempotency_concurrency.php
```

These tests send multiple requests at the same time and verify that database locking and idempotency prevent duplicate or overbooked reservations.

## Seed Data

The project includes a hotel booking seeder.

Run:

```bash
php artisan db:seed
```

The seeder creates:

* Afaq Plus Hotel
* Standard Room
* Inventory for several dates

## Project Structure

Relevant files:

```text
app/
├── Exceptions/
│   └── BookingUnavailableException.php
├── Http/
│   ├── Controllers/Api/V1/
│   │   └── BookingController.php
│   └── Requests/
│       └── StoreBookingRequest.php
├── Models/
│   ├── Booking.php
│   ├── BookingItem.php
│   ├── Hotel.php
│   ├── Inventory.php
│   └── RoomType.php
└── Services/
    ├── CreateBookingService.php
    └── CancelBookingService.php

database/
├── factories/
├── migrations/
└── seeders/

tests/
├── Feature/
│   └── BookingTest.php
└── Concurrency/
    ├── booking_concurrency.php
    └── idempotency_concurrency.php
```

## Notes

The booking and inventory operations are handled using database transactions and row-level locking to keep inventory consistent when requests are processed concurrently.

The implementation focuses on the required booking flow, inventory consistency, idempotency, cancellation, authorization, rate limiting, and automated testing.
