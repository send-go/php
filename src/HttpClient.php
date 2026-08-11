<?php

namespace Sendgo\Php;

use Sendgo\Php\Exception\SendgoException;

class HttpClient
{
    public function __construct(
        private readonly TokenManager $tokenManager,
        private readonly string $apiVersion,
    ) {}

    public function post(string $url, array $body): array
    {
        return $this->request('POST', $url, $body, false);
    }

    /**
     * @param  array<string, mixed>  $query  Appended as a query string.
     */
    public function get(string $url, array $query = []): array
    {
        if ($query !== []) {
            $url .= (str_contains($url, '?') ? '&' : '?').http_build_query($query);
        }

        return $this->request('GET', $url, null, false);
    }

    /**
     * `request()` already drives the method through CURLOPT_CUSTOMREQUEST, so
     * DELETE needs no special handling beyond not sending a body.
     */
    public function delete(string $url): array
    {
        return $this->request('DELETE', $url, null, false);
    }

    /**
     * @param  array<string, mixed>|null  $body  Null for requests without a body.
     */
    private function request(string $method, string $url, ?array $body, bool $isRetry): array
    {
        $token = $this->tokenManager->getToken();
        $bearer = $this->makeBearerAuth($token);

        $headers = ["Authorization: {$bearer}"];
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CUSTOMREQUEST => $method,
        ];

        if ($body !== null) {
            $payload = json_encode($body);
            $options[CURLOPT_POSTFIELDS] = $payload;
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: '.strlen($payload);
        }

        $options[CURLOPT_HTTPHEADER] = $headers;

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);

        $responseBody = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($responseBody, true) ?? [];

        if ($status < 200 || $status >= 300) {
            $errorCode = $data['code'] ?? null;
            $endpoint = basename(parse_url($url, PHP_URL_PATH) ?? $url);

            if (! $isRetry && $this->tokenManager->shouldRefresh($status, $errorCode)) {
                $this->tokenManager->invalidate();

                return $this->request($method, $url, $body, true);
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
