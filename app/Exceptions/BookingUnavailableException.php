<?php

namespace App\Exceptions;

use Exception;

class BookingUnavailableException extends Exception
{
     public function __construct( string $message = 'Requested rooms are not available' ) {
        parent::__construct($message);
    }
}
