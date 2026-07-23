<?php

namespace Techigh\Sendgo;

/**
 * Sendgo PHP SDK 메인 클라이언트.
 *
 * @example
 * $sendgo = new Sendgo([
 *     'access_key'       => $_ENV['SENDGO_ACCESS_KEY'],
 *     'secret_key'       => $_ENV['SENDGO_SECRET_KEY'],
 *     'kakao_sender_key' => $_ENV['SENDGO_KAKAO_KEY'],
 *     'sms_sender_key'   => $_ENV['SENDGO_SMS_KEY'],
 *     'api_version'      => 'v2',
 * ]);
 *
 * $sendgo->alimtalk->send([
 *     'templateCode' => 'ORDER_CONFIRM_001',
 *     'contacts'     => [['contact' => '01012345678', 'var1' => 'ORD-001']],
 * ]);
 */
class Sendgo
{
    public readonly AlimtalkService  $alimtalk;
    public readonly FriendtalkService $friendtalk;
    public readonly SmsService        $sms;

    /**
     * @param array{
     *   access_key: string,
     *   secret_key: string,
     *   kakao_sender_key?: string|null,
     *   sms_sender_key?: string|null,
     *   api_version?: 'v1'|'v2',
     *   url?: string,
     * } $config
     */
    public function __construct(array $config)
    {
        $url        = $config['url'] ?? 'https://api.sendgo.io';
        $version    = $config['api_version'] ?? 'v1';
        $kakaoKey   = $config['kakao_sender_key'] ?? null;
        $smsKey     = $config['sms_sender_key'] ?? null;

        $tokenManager = new TokenManager($url, $config['access_key'], $config['secret_key'], $version);
        $http         = new HttpClient($tokenManager, $version);

        $this->alimtalk   = new AlimtalkService($http, $url, $version, $kakaoKey, $smsKey);
        $this->friendtalk = new FriendtalkService($http, $url, $version, $kakaoKey, $smsKey);
        $this->sms        = new SmsService($http, $url, $version, $smsKey);
    }
}
