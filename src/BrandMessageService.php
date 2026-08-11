<?php

namespace Sendgo\Php;

/**
 * 카카오 브랜드메시지 전송 서비스.
 *
 * 브랜드메시지는 친구톡의 후속 채널로, 메시지 타입이 친구톡과 1:1 대응됩니다
 * (FT→BT, FI→BI, FW→BW, FL→BL, FC→BC, FM→BM, FP→BP, FA→BA).
 * 요청에는 친구톡 코드를 그대로 넘기고, 변환은 서버가 처리합니다.
 *
 * 친구톡과 달리 채널 친구가 아닌 수신자에게도 보낼 수 있고(targeting=N),
 * 수신 동의한 전체 채널 친구에게 동보 발송할 수 있습니다(targeting=F).
 *
 * @example
 * // 단건 발송 — 채널 친구 대상
 * $sendgo->brandMessage->send([
 *     'targeting'          => 'M',
 *     'messageType'        => 'FL',
 *     'friendTemplateUuid' => '9cd5460b-6458-4edc-9b11-c26d3013c340',
 *     'contacts'           => [['contact' => '01012345678', 'var1' => '29,000원']],
 * ]);
 *
 * // 동보 발송 — 수신 동의한 전체 채널 친구 (contacts 불필요)
 * $sendgo->brandMessage->broadcast([
 *     'messageType'        => 'FW',
 *     'friendTemplateUuid' => '9cd5460b-6458-4edc-9b11-c26d3013c340',
 * ]);
 */
class BrandMessageService
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $url,
        private readonly string $apiVersion,
        private readonly ?string $kakaoSenderKey,
        private readonly ?string $smsSenderKey,
    ) {}

    /**
     * 브랜드메시지 전송.
     *
     * `targeting` 이 `M`/`N`/`I` 이면 `contacts` 가 필요하고 응답에 발송 건수가 담깁니다.
     * `F` 이면 동보 발송이라 `contacts` 없이 접수 여부만 반환됩니다 — 이 경우
     * broadcast() 를 쓰는 편이 의도가 분명합니다.
     *
     * @param array{
     *   targeting?: 'M'|'N'|'I'|'F',
     *   messageType: 'FT'|'FI'|'FW'|'FL'|'FM'|'FC'|'FA'|'FP',
     *   friendTemplateUuid: string,
     *   contacts?: list<array{contact:string, name?:string}>,
     *   content?: string|null,
     *   scheduleType?: 'DIRECTLY'|'SCHEDULED',
     *   at?: string|null,
     *   buttons?: list<array>,
     *   imageUrl?: string|null,
     *   imageLink?: string|null,
     *   adFlag?: 'Y'|'N',
     *   adult?: 'Y'|'N',
     *   pushAlarm?: 'Y'|'N',
     *   header?: string|null,
     *   coupon?: array|null,
     *   item?: array|null,
     *   commerce?: array|null,
     *   list?: list<array>|null,
     *   head?: array|null,
     *   tail?: array|null,
     *   video?: array|null,
     *   additionalContent?: string|null,
     *   friendGroupKey?: string|null,
     *   replaceSms?: 'Y'|'N',
     *   smsSubject?: string|null,
     *   smsContent?: string|null,
     *   rejectServiceId?: string|null,
     *   webhooks?: list<string>,
     * } $params
     */
    public function send(array $params): array
    {
        $body = array_merge([
            'at'           => null,
            'scheduleType' => 'DIRECTLY',
            'targeting'    => 'M',
            'buttons'      => [],
            'imageUrl'     => null,
            'imageLink'    => null,
            'adFlag'       => 'Y',
            'adult'        => 'N',
            'pushAlarm'    => 'Y',
            'header'       => null,
            'replaceSms'   => 'N',
            'smsSubject'   => null,
            'smsContent'   => null,
        ], $params, [
            'kakaoSenderKey' => $this->kakaoSenderKey,
            'senderKey'      => $this->smsSenderKey,
        ]);

        return $this->http->post("{$this->url}/api/{$this->apiVersion}/brand-messages/send", $body);
    }

    /**
     * 동보 발송 — 수신 동의한 전체 채널 친구 (targeting=F).
     *
     * 수신자 목록은 카카오 측에서 확장하므로 `contacts` 를 넘기지 않습니다.
     * 응답에는 발송 건수 대신 접수 여부(`accepted`)가 담기며, 결과는
     * campaigns()/campaign() 으로 확인합니다.
     *
     * @param  array<string, mixed>  $params
     */
    public function broadcast(array $params): array
    {
        unset($params['contacts']);

        return $this->send(array_merge($params, ['targeting' => 'F']));
    }

    /**
     * 브랜드메시지 캠페인 목록 조회.
     *
     * @param array{from?: string, to?: string, count?: int} $query
     */
    public function campaigns(array $query = []): array
    {
        return $this->http->get("{$this->url}/api/{$this->apiVersion}/brand-messages", $query);
    }

    /**
     * 브랜드메시지 캠페인 상세 조회.
     *
     * @param  string  $campaignId  발송 응답의 campaignId (UUID)
     */
    public function campaign(string $campaignId): array
    {
        return $this->http->get("{$this->url}/api/{$this->apiVersion}/brand-messages/{$campaignId}");
    }
}
