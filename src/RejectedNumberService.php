<?php

namespace Sendgo\Php;

/**
 * 수신거부(080) 번호 조회. v2 전용.
 *
 * 발송 API 가 알아서 제외하지만, **자기 DB 의 수신 상태도 맞춰야** 한다 —
 * 그러지 않으면 매번 보내고 매번 걸러지는 것을 반복하고, 자기 화면에서는
 * 여전히 "수신 동의" 로 보인다.
 *
 * 조회 전용이다. 수신거부는 수신자가 ARS·웹으로 직접 하는 것이고 발송자가
 * 넣거나 뺄 수 있는 값이 아니다.
 *
 * @example
 * // 증분만 가져간다. 전체를 매번 받으면 번호가 쌓일수록 무거워진다.
 * $rejected = $sendgo->rejectedNumbers->list(['since' => '2026-09-01', 'count' => 500]);
 */
class RejectedNumberService
{
    public function __construct(
        private HttpClient $http,
        private string $url,
        private string $apiVersion,
    ) {}

    /**
     * @param array{since?: string, search?: string, count?: int} $query
     * @return array<string, mixed>
     */
    public function list(array $query = []): array
    {
        return $this->http->get("{$this->url}/api/{$this->apiVersion}/rejected-numbers", $query);
    }
}
