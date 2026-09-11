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
     * @param  array<string, mixed>  $body
     */
    public function put(string $url, array $body): array
    {
        return $this->request('PUT', $url, $body, false);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public function patch(string $url, array $body): array
    {
        return $this->request('PATCH', $url, $body, false);
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
     * multipart/form-data POST — 서류·이미지 첨부가 있는 관리 API 전용.
     *
     * 발신번호 등록과 템플릿 이미지 업로드는 JSON 으로 보낼 수 없다.
     * 파일 값은 경로 문자열이나 `CURLFile` 둘 다 받는다. 배열 값은 JSON 으로
     * 직렬화해 보낸다 — multipart 에는 배열 타입이 없고, 서버가 JSON 문자열을
     * 풀어서 읽는다.
     *
     * @param  array<string, mixed>  $fields  스칼라 값과 배열
     * @param  array<string, string|\CURLFile>  $files  필드명 => 파일 경로 또는 CURLFile
     * @return array<string, mixed>
     */
    public function postMultipart(string $url, array $fields, array $files = []): array
    {
        return $this->multipartRequest($url, $fields, $files, false);
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

    /**
     * @param  array<string, mixed>  $fields
     * @param  array<string, string|\CURLFile>  $files
     * @return array<string, mixed>
     */
    private function multipartRequest(string $url, array $fields, array $files, bool $isRetry): array
    {
        $token = $this->tokenManager->getToken();

        $payload = [];

        foreach ($fields as $key => $value) {
            if ($value === null) {
                continue;
            }

            // multipart 에는 배열도 bool 도 없다. 서버가 읽는 형태로 눌러 둔다.
            if (is_array($value)) {
                $payload[$key] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } elseif (is_bool($value)) {
                $payload[$key] = $value ? '1' : '0';
            } else {
                $payload[$key] = (string) $value;
            }
        }

        foreach ($files as $key => $file) {
            if ($file === null) {
                continue;
            }

            $payload[$key] = $file instanceof \CURLFile ? $file : new \CURLFile($file);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            // 파일 업로드는 JSON 요청보다 오래 걸린다.
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Authorization: '.$this->makeBearerAuth($token),
                'Accept: application/json',
            ],
        ]);

        $responseBody = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode((string) $responseBody, true) ?? [];

        if ($status < 200 || $status >= 300) {
            $errorCode = $data['code'] ?? null;
            $endpoint = basename(parse_url($url, PHP_URL_PATH) ?? $url);

            if (! $isRetry && $this->tokenManager->shouldRefresh($status, $errorCode)) {
                $this->tokenManager->invalidate();

                return $this->multipartRequest($url, $fields, $files, true);
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
