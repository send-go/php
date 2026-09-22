<?php

namespace Sendgo\Php;

use Sendgo\Php\Exception\SendgoException;

/** 서버 전용 계정 API. 에이전트 토큰은 자동 갱신하지 않는다. */
class AccountClient
{
    private readonly string $baseUrl;

    public function __construct(private readonly string $agentToken, string $baseUrl = 'https://sendgo.io')
    {
        if (trim($agentToken) === '') {
            throw new \InvalidArgumentException('Sendgo: agentToken은 필수입니다.');
        }
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /** 계정 상태와 다음 단계 조회. */
    public function me(): array
    {
        return $this->request('GET', '');
    }

    /** 조직 목록 조회. */
    public function organizations(): array
    {
        return $this->request('GET', 'organizations');
    }

    /** 조직 선택. null은 개인 계정. */
    public function selectOrganization(?string $organizationId): array
    {
        return $this->request('POST', 'organizations/select', ['organizationId' => $organizationId]);
    }

    /** 현재 조직의 API 키 목록. */
    public function apiKeys(): array
    {
        return $this->request('GET', 'api-keys');
    }

    /** API 키 발급. secretKey는 이 응답에서만 반환. */
    public function createApiKey(array $params): array
    {
        return $this->request('POST', 'api-keys', $params);
    }

    /** API 키 상세 조회. */
    public function apiKey(string $apiKeyId): array
    {
        return $this->request('GET', 'api-keys/'.rawurlencode($apiKeyId).'');
    }

    /** API 키 이름 변경. */
    public function updateApiKey(string $apiKeyId, string $name): array
    {
        return $this->request('PATCH', 'api-keys/'.rawurlencode($apiKeyId).'', ['name' => $name]);
    }

    /** API 키 폐기. */
    public function deleteApiKey(string $apiKeyId): array
    {
        return $this->request('DELETE', 'api-keys/'.rawurlencode($apiKeyId).'');
    }

    /** 승인된 API 키의 발송용 토큰 발급. */
    public function issueToken(string $apiKeyId): array
    {
        return $this->request('POST', 'api-keys/'.rawurlencode($apiKeyId).'/token', (object) []);
    }

    /** 허용 IP 목록과 호출자 IP 조회. */
    public function allowedIps(string $apiKeyId): array
    {
        return $this->request('GET', 'api-keys/'.rawurlencode($apiKeyId).'/allowed-ips');
    }

    /** 허용 IP 추가. ip와 선택적 description 사용. */
    public function addAllowedIp(string $apiKeyId, array $params): array
    {
        return $this->request('POST', 'api-keys/'.rawurlencode($apiKeyId).'/allowed-ips', $params);
    }

    /** 허용 IP 삭제. */
    public function deleteAllowedIp(string $apiKeyId, string $ipId): array
    {
        return $this->request('DELETE', 'api-keys/'.rawurlencode($apiKeyId).'/allowed-ips/'.rawurlencode($ipId).'');
    }

    private function request(string $method, string $path, array|object|null $body = null): array
    {
        $headers = ['Authorization: Bearer '.$this->agentToken, 'Accept: application/json'];
        $options = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_CUSTOMREQUEST => $method];
        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode($body, JSON_THROW_ON_ERROR);
            $headers[] = 'Content-Type: application/json';
        }
        $options[CURLOPT_HTTPHEADER] = $headers;
        $ch = curl_init($this->baseUrl.'/api/v2/account'.($path === '' ? '' : '/'.$path));
        curl_setopt_array($ch, $options);
        $raw = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($raw === false) {
            throw new SendgoException('Sendgo 요청 실패: '.$error);
        }
        $data = json_decode($raw, true) ?? [];
        if ($status < 200 || $status >= 300) {
            throw SendgoException::fromResponse($status, $data, $path ?: 'account', 'v2');
        }
        return $data;
    }
}
