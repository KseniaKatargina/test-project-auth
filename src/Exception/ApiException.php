<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiException extends HttpException
{
    public function __construct(string $message = "Ошибка", int $statusCode = 400)
    {
        parent::__construct($statusCode, $message);
    }
}
