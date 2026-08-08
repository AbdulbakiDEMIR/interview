<?php

namespace App\Exceptions;

class TurkpinApiException extends \Exception
{
    private string $errorCode;
    private $rawResponse;

    public function __construct(string $message, string $errorCode = 'UNKNOWN', $rawResponse = null, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->rawResponse = $rawResponse;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getRawResponse()
    {
        return $this->rawResponse;
    }
}
