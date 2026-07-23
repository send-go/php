<?php

namespace Sendgo\Php;

use Sendgo\Php\Exception\SendgoException;

class TokenManager
{
    private const NO_REFRESH_CODES = [
        'INVALID_AUTH_HEADER', 'INVALID_BASIC_AUTH', 'INVALID_BASIC_AUTH_PAYLOAD',
        'INVALID_ACCESS_KEY', 'INVALID_SECRET_KEY', 'ACCESS_KEY_NOT_APPROVED',
        'TEAM_REQUIRED_FOR_KAKAO', 'IP_NOT_ALLOWED', 'INVALID_SENDER_KEY', 'INVALID_KAKAO_SENDER_KEY',
    ];

    private ?string $token = null;
    private int $expiresAt = 0;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $accessKey,
        private readonly string $secretKey,
        private readonly string $apiVersion,
    ) {}

    public function getToken(): string
    {
        if ($this->token !== null && time() < $this->expiresAt) {
            return $this->token;
        }
        return $this->fetchToken();
    }

    public function invalidate(): void
    {
        $this->token = null;
        $this->expiresAt = 0;
    }

    public function shouldRefresh(int $status, ?string $errorCode): bool
    {
        if ($status !== 401 && $status !== 403) return false;
        if ($this->apiVersion === 'v2' && $errorCode !== null && in_array($errorCode, self::NO_REFRESH_CODES)) {
            return false;
        }
        return true;
    }

    private function fetchToken(): string
    {
        $url = "{$this->baseUrl}/api/{$this->apiVersion}/token";
        $credentials = base64_encode("{$this->accessKey}:{$this->secretKey}");

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                "Authorization: Basic {$credentials}",
            ],
        ]);

        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($body, true) ?? [];

        if ($status < 200 || $status >= 300 || empty($data['data']['token'])) {
            throw SendgoException::fromResponse($status, $data, 'token', $this->apiVersion);
        }

        $this->token = $data['data']['token'];
        $this->expiresAt = time() + (50 * 60);
        return $this->token;
    }
}
