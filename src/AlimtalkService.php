<?php

namespace Sendgo\Php;

/**
 * 카카오 알림톡 전송 서비스.
 *
 * @example
 * $sendgo->alimtalk->send([
 *     'templateCode' => 'ORDER_CONFIRM_001',
 *     'contacts'     => [['contact' => '01012345678', 'var1' => 'ORD-001']],
 * ]);
 */
class AlimtalkService
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $url,
        private readonly string $apiVersion,
        private readonly ?string $kakaoSenderKey,
        private readonly ?string $smsSenderKey,
    ) {}

    /**
     * 알림톡 전송.
     *
     * @param array{
     *   templateCode: string,
     *   contacts: list<array{contact:string, name?:string, var1?:string, ...}>,
     *   scheduleType?: 'DIRECTLY'|'SCHEDULED',
     *   at?: string|null,
     *   replaceSms?: 'Y'|'N',
     *   smsSubject?: string|null,
     *   smsContent?: string|null,
     * } $params
     */
    public function send(array $params): array
    {
        $body = array_merge([
            'at'           => null,
            'scheduleType' => 'DIRECTLY',
            'replaceSms'   => 'N',
            'smsSubject'   => null,
            'smsContent'   => null,
        ], $params, [
            'kakaoSenderKey' => $this->kakaoSenderKey,
            'senderKey'      => $this->smsSenderKey,
        ]);

        return $this->http->post("{$this->url}/api/{$this->apiVersion}/notices/send", $body);
    }
}
