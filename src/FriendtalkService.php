<?php

namespace Techigh\Sendgo;

/**
 * 카카오 친구톡 전송 서비스.
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
