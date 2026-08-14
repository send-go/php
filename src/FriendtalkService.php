<?php

namespace Sendgo\Php;

/**
 * 카카오 친구톡 전송 서비스.
 *
 * @deprecated 친구톡은 카카오 정책에 따라 2025-12-31 로 종료되었습니다.
 *             2026-01-01 부터 친구톡 발송 요청은 카카오 측에서 브랜드메시지(자유형)로
 *             자동 대체 발송되므로, 이 서비스는 호출해도 실제로는 브랜드메시지가 나갑니다.
 *             신규 연동은 {@see BrandMessageService} 를 사용하세요. 다만 자유 본문
 *             타입(FT/FI/FW)을 개별 수신자에게 보내는 경로는 아직 이 서비스뿐입니다 —
 *             브랜드메시지 API 는 그 조합에 NOT_A_BRAND_MESSAGE 를 반환합니다.
 *             메시지 타입은 1:1 대응됩니다 — FT→BT, FI→BI, FW→BW, FL→BL, FC→BC, FM→BM, FP→BP, FA→BA.
 *
 * @example
 * $sendgo->friendtalk->send([
 *     'content'  => '안녕하세요! 이번 주 특가 이벤트입니다.',
 *     'contacts' => [['contact' => '01012345678']],
 * ]);
 */
class FriendtalkService
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $url,
        private readonly string $apiVersion,
        private readonly ?string $kakaoSenderKey,
        private readonly ?string $smsSenderKey,
    ) {}

    /**
     * 친구톡 전송.
     *
     * @deprecated 2025-12-31 종료. {@see BrandMessageService::send()} 를 사용하세요.
     *
     * @param array{
     *   content: string,
     *   contacts: list<array{contact:string, name?:string}>,
     *   messageType?: 'FT'|'FI'|'FW'|'FL'|'FM'|'FC'|'FA'|'FP',
     *   scheduleType?: 'DIRECTLY'|'SCHEDULED',
     *   at?: string|null,
     *   buttons?: list<array>,
     *   imageUrl?: string|null,
     *   imageLink?: string|null,
     *   adFlag?: 'Y'|'N',
     *   wide?: 'Y'|'N',
     *   adult?: 'Y'|'N',
     *   replaceSms?: 'Y'|'N',
     *   smsSubject?: string|null,
     *   smsContent?: string|null,
     * } $params
     */
    public function send(array $params): array
    {
        $body = array_merge([
            'at'          => null,
            'scheduleType'=> 'DIRECTLY',
            'messageType' => 'FT',
            'buttons'     => [],
            'image'       => null,
            'imageUrl'    => null,
            'imageLink'   => null,
            'adFlag'      => 'Y',
            'wide'        => 'N',
            'adult'       => 'N',
            'header'      => null,
            'replaceSms'  => 'N',
            'smsSubject'  => null,
            'smsContent'  => null,
        ], $params, [
            'kakaoSenderKey' => $this->kakaoSenderKey,
            'senderKey'      => $this->smsSenderKey,
        ]);

        return $this->http->post("{$this->url}/api/{$this->apiVersion}/friends/send", $body);
    }
}
