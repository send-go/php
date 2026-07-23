<?php

namespace Techigh\Sendgo;

/**
 * SMS / LMS / MMS 전송 서비스.
 *
 * @example
 * $sendgo->sms->sendSms(['content' => '인증번호: 123456', 'contacts' => [['contact' => '01012345678']]]);
 * $sendgo->sms->sendLms(['subject' => '[공지]', 'content' => '...', 'contacts' => [...]]);
 */
class SmsService
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $url,
        private readonly string $apiVersion,
        private readonly ?string $smsSenderKey,
    ) {}

    /** SMS 전송 (90자 이하) */
    public function sendSms(array $params): array { return $this->send($params + ['messageType' => 'SMS']); }

    /** LMS 전송 (장문) */
    public function sendLms(array $params): array { return $this->send($params + ['messageType' => 'LMS']); }

    /** MMS 전송 (멀티미디어) */
    public function sendMms(array $params): array { return $this->send($params + ['messageType' => 'MMS']); }

    /**
     * @param array{
     *   content: string,
     *   contacts: list<array{contact:string, name?:string}>,
     *   messageType?: 'SMS'|'LMS'|'MMS',
     *   campaignType?: 'MESSAGE'|'ADVERTISE'|'ELECTION',
     *   scheduleType?: 'DIRECTLY'|'SCHEDULED',
     *   at?: string|null,
     *   subject?: string|null,
     *   files?: list<array>,
     * } $params
     */
    public function send(array $params): array
    {
        $body = array_merge([
            'campaignType' => 'MESSAGE',
            'messageType'  => 'SMS',
            'scheduleType' => 'DIRECTLY',
            'at'           => null,
            'subject'      => null,
            'files'        => [],
        ], $params, [
            'senderKey' => $this->smsSenderKey,
        ]);

        return $this->http->post("{$this->url}/api/{$this->apiVersion}/messages/send", $body);
    }
}
