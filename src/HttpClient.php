<?php

namespace Techigh\Sendgo;

use Techigh\Sendgo\Exception\SendgoException;

class HttpClient
{
    public function __construct(
        private readonly TokenManager $tokenManager,
        private readonly string $apiVersion,
    ) {}

    public function post(string $url, array $body): array
    {
        return $this->doPost($url, $body, false);
    }

    private function doPost(string $url, array $body, bool $isRetry): array
    {
        $token = $this->tokenManager->getToken();
        $bearer = $this->makeBearerAuth($token);
        $payload = json_encode($body);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload),
                "Authorization: {$bearer}",
            ],
        ]);

        $responseBody = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($responseBody, true) ?? [];

        if ($status < 200 || $status >= 300) {
            $errorCode = $data['code'] ?? null;
            $endpoint = basename(parse_url($url, PHP_URL_PATH) ?? $url);

            if (!$isRetry && $this->tokenManager->shouldRefresh($status, $errorCode)) {
                $this->tokenManager->invalidate();
                return $this->doPost($url, $body, true);
            }

            throw SendgoException::fromResponse($status, $data, $endpoint, $this->apiVersion);
        }

        return $data;
    }

    private function makeBearerAuth(string $token): string
    {
        return $this->apiVersion === 'v2'
            ? "Bearer {$token}"
            : 'Bearer ' . base64_encode($token);
    }
}
