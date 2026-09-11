<?php

namespace Sendgo\Php;

/**
 * 이벤트 웹훅 구독 — 등록·심사 결과를 밀어 받는다.
 *
 * v2 전용. 심사는 비동기라 폴링 말고는 방법이 없었다. 구독해 두면 상태가
 * 바뀔 때마다 도착한다.
 *
 * @example
 * $created = $sendgo->webhook->subscribe('https://reseller.example.com/hooks/sendgo');
 *
 * // 시크릿은 이 응답에서 한 번만 나온다. 즉시 저장한다.
 * $secret = $created['data']['secret'] ?? null;
 */
class WebhookService
{
    /** 발신번호 심사 상태가 바뀜. */
    public const EVENT_SENDER_STATUS = 'sender.status_changed';

    /** 알림톡 검수 상태가 바뀜. */
    public const EVENT_NOTICE_TEMPLATE_INSPECTION = 'notice_template.inspection_status_changed';

    /** 카카오 채널의 차단·휴면·프로필 상태가 바뀜. */
    public const EVENT_KAKAO_SENDER_STATUS = 'kakao_sender.status_changed';

    /** 브랜드메시지 M/N 신청 상태가 바뀜. */
    public const EVENT_BRAND_MESSAGE_TARGETING = 'kakao_sender.brand_message_status_changed';

    public function __construct(
        private HttpClient $http,
        private string $url,
        private string $apiVersion,
    ) {}

    /**
     * 현재 구독 설정. 마지막 전송 결과(`lastStatus`)도 함께 온다 —
     * 내 엔드포인트가 실제로 받고 있는지 확인할 수 있어야 한다.
     *
     * @return array<string, mixed>
     */
    public function show(): array
    {
        return $this->http->get($this->endpoint());
    }

    /**
     * 구독 생성·수정.
     *
     * `$secret` 을 생략하면 서버가 만들어 **이 응답에서 한 번만** 돌려준다.
     * 이미 시크릿이 있는 상태에서 생략하면 기존 값을 유지한다 — URL 만 바꾸는
     * 호출이 서명 키를 날리지 않는다.
     *
     * `$events` 가 null 이면 전체 구독이다. 이벤트가 늘어도 설정을 고칠 필요가 없다.
     *
     * @param  list<string>|null  $events
     * @return array<string, mixed>
     */
    public function subscribe(string $url, ?string $secret = null, ?array $events = null, bool $enabled = true): array
    {
        $body = ['url' => $url, 'enabled' => $enabled];

        if ($secret !== null) {
            $body['secret'] = $secret;
        }
        if ($events !== null) {
            $body['events'] = $events;
        }

        return $this->http->put($this->endpoint(), $body);
    }

    /**
     * 테스트 이벤트 발송. 구독 목록과 무관하게 도착하므로 배선 확인에 쓴다.
     *
     * @return array<string, mixed>
     */
    public function test(): array
    {
        return $this->http->post($this->endpoint('test'), []);
    }

    /**
     * 구독 해지.
     *
     * @return array<string, mixed>
     */
    public function unsubscribe(): array
    {
        return $this->http->delete($this->endpoint());
    }

    /**
     * 수신한 웹훅의 서명을 검증한다.
     *
     * **`$rawBody` 는 받은 바이트 그대로**여야 한다. 파싱한 뒤 다시 인코딩한
     * 값으로 계산하면 키 순서나 이스케이프 차이로 검증이 깨진다.
     *
     * @param  string  $rawBody    요청 본문 원본 (`file_get_contents('php://input')`)
     * @param  string  $signature  `X-Sendgo-Signature` 헤더 값
     */
    public static function verifySignature(string $rawBody, string $signature, string $secret): bool
    {
        return hash_equals(hash_hmac('sha256', $rawBody, $secret), $signature);
    }

    private function endpoint(?string $segment = null): string
    {
        $base = "{$this->url}/api/{$this->apiVersion}/webhook";

        return $segment === null ? $base : $base.'/'.$segment;
    }
}
