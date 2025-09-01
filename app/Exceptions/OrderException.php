<?php

namespace App\Exceptions;

use Exception;

class OrderException extends Exception
{
    public function __construct(string $message = "", int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function render()
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error' => 'ORDER_ERROR'
        ], 422);
    }
}
