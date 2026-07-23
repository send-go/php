<?php

namespace Sendgo\Php\Exception;

use RuntimeException;

class SendgoException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 0,
        private readonly ?string $errorCode = null,
        private readonly string $endpoint = '',
        private readonly string $apiVersion = '',
    ) {
        parent::__construct($message);
    }

    public static function fromResponse(int $status, array $body, string $endpoint, string $apiVersion): static
    {
        $code = $body['code'] ?? null;
        $msg  = $body['message'] ?? 'Unknown error';
        $message = "HTTP {$status}" . ($code ? " [{$code}]" : '') . " {$msg}";
        return new static($message, $status, $code, $endpoint, $apiVersion);
    }

    public function getStatusCode(): int    { return $this->statusCode; }
    public function getErrorCode(): ?string { return $this->errorCode; }
    public function getEndpoint(): string   { return $this->endpoint; }
    public function getApiVersion(): string { return $this->apiVersion; }
}
