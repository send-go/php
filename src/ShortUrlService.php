<?php

namespace Sendgo\Php;

/**
 * 짧은 URL — 메시지에 넣는 링크를 줄이고 클릭 반응을 집계한다.
 *
 * v2 전용이다.
 *
 * @example
 * $short = $sendgo->shortUrl->create([
 *     'targetUrl' => 'https://example.com/promotions/summer-sale',
 *     'title'     => '여름 세일 랜딩',
 * ]);
 *
 * // $short['data']['shortUrl'] 를 문자/알림톡 본문에 넣는다.
 * $stats = $sendgo->shortUrl->stats($short['data']['code']);
 */
class ShortUrlService
{
    public function __construct(
        private HttpClient $http,
        private string $url,
        private string $apiVersion,
    ) {}

    /**
     * 짧은 URL 을 만든다.
     *
     * 같은 원본 URL 을 다시 줄이면 기존 링크가 그대로 반환된다.
     * 캠페인별로 반응을 분리해 집계하려면 `forceNew => true` 를 쓴다.
     *
     * @param array{
     *   targetUrl: string,
     *   title?: string|null,
     *   expiresAt?: string|null,
     *   forceNew?: bool,
     * } $params
     * @return array<string, mixed>
     */
    public function create(array $params): array
    {
        return $this->http->post($this->endpoint(), $params);
    }

    /**
     * 목록 조회.
     *
     * @param array{from?: string, to?: string, count?: int} $query
     * @return array<string, mixed>
     */
    public function list(array $query = []): array
    {
        return $this->http->get($this->endpoint(), $query);
    }

    /**
     * 상세 조회.
     *
     * @return array<string, mixed>
     */
    public function show(string $code): array
    {
        return $this->http->get($this->endpoint($code));
    }

    /**
     * 반응 통계. 일별 추이와 디바이스/유입경로/국가별 분해를 반환한다.
     *
     * @param array{from?: string, to?: string} $query
     * @return array<string, mixed>
     */
    public function stats(string $code, array $query = []): array
    {
        return $this->http->get($this->endpoint($code).'/stats', $query);
    }

    /**
     * 리다이렉트를 중지한다. 링크는 삭제되지 않고, 누적 통계도 남는다.
     * 이후 그 링크로 들어오면 410 Gone 이 반환된다.
     *
     * @return array<string, mixed>
     */
    public function deactivate(string $code): array
    {
        return $this->http->delete($this->endpoint($code));
    }

    private function endpoint(?string $code = null): string
    {
        $base = "{$this->url}/api/{$this->apiVersion}/short-urls";

        return $code === null ? $base : $base.'/'.rawurlencode($code);
    }
}
