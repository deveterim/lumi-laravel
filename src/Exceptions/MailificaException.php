<?php

declare(strict_types=1);

namespace Mailifica\Laravel\Exceptions;

use Exception;

class MailificaException extends Exception
{
    protected ?int $statusCode;
    protected ?array $responseBody;

    public function __construct(string $message, int $statusCode = 0, ?array $responseBody = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): ?array
    {
        return $this->responseBody;
    }
}
