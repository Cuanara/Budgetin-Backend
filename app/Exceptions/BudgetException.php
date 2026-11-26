<?php

namespace App\Exceptions;

use Exception;

class BudgetException extends Exception
{
    public function __construct(string $message, int $statusCode = 422)
    {
        parent::__construct($message, $statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->getCode();
    }
}
