<?php

namespace Sendgo\Php;

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
    public readonly AlimtalkService     $alimtalk;
    /** @deprecated 친구톡은 2025-12-31 종료. $brandMessage 를 사용하세요. */
    public readonly FriendtalkService   $friendtalk;
    /** 카카오 브랜드메시지 — 친구톡의 후속 채널. v2 전용. */
    public readonly BrandMessageService $brandMessage;
    /** 짧은 URL — 링크 단축과 클릭 반응 분석. v2 전용. */
    public readonly ShortUrlService     $shortUrl;
    public readonly SmsService          $sms;

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
        $url        = $config['url'] ?? 'https://sendgo.io';
        $version    = $config['api_version'] ?? 'v1';
        $kakaoKey   = $config['kakao_sender_key'] ?? null;
        $smsKey     = $config['sms_sender_key'] ?? null;

        $tokenManager = new TokenManager($url, $config['access_key'], $config['secret_key'], $version);
        $http         = new HttpClient($tokenManager, $version);

        $this->alimtalk     = new AlimtalkService($http, $url, $version, $kakaoKey, $smsKey);
        $this->friendtalk   = new FriendtalkService($http, $url, $version, $kakaoKey, $smsKey);
        $this->brandMessage = new BrandMessageService($http, $url, $version, $kakaoKey, $smsKey);
        $this->shortUrl     = new ShortUrlService($http, $url, $version);
        $this->sms          = new SmsService($http, $url, $version, $smsKey);
    }

    /**
     * Allow the services to be reached as methods as well as properties.
     *
     * The Laravel facade documents `Sendgo::alimtalk()->send(...)`, and
     * Illuminate's Facade::__callStatic forwards that as `$instance->alimtalk()`.
     * Because the services are readonly *properties*, that call died with
     * "Call to undefined method" — the package's own primary example did not
     * work. Resolving a method call to the matching property fixes the facade
     * without changing the property API anyone already depends on.
     *
     * @param  array<int, mixed>  $arguments
     *
     * @throws \BadMethodCallException
     */
    public function __call(string $name, array $arguments): AlimtalkService|FriendtalkService|BrandMessageService|ShortUrlService|SmsService
    {
        return match ($name) {
            'alimtalk' => $this->alimtalk,
            'friendtalk' => $this->friendtalk,
            'brandMessage', 'brand_message' => $this->brandMessage,
            'shortUrl', 'short_url' => $this->shortUrl,
            'sms' => $this->sms,
            default => throw new \BadMethodCallException(
                sprintf('Call to undefined method %s::%s()', static::class, $name)
            ),
        };
    }
}
