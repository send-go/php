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

    // ---------------------------------------------------------- 관리 API (v2)
    // 콘솔에서만 되던 등록·심사를 코드로 옮긴 것들. 발송과 달리 대부분
    // 즉시 완료되지 않는다 — 등록 성공은 "접수됨"이지 "사용 가능"이 아니다.

    /** 카카오 발신프로필(채널) 등록·동기화. v2 전용, 기업 계정 전용. */
    public readonly KakaoSenderService  $kakaoSenders;
    /** 템플릿 공용 폴더. v2 전용, 기업 계정 전용. */
    public readonly TemplateFolderService $templateFolders;
    /** 알림톡 템플릿 등록·수정·검수 요청. v2 전용, 기업 계정 전용. */
    public readonly NoticeTemplateService $noticeTemplates;
    /** 브랜드메시지(구 친구톡) 템플릿 관리. v2 전용, 기업 계정 전용. */
    public readonly BrandTemplateService $brandTemplates;
    /** 발신번호 등록·심사 접수. v2 전용. */
    public readonly SenderRegistrationService $senderRegistration;
    /** 문자 상용구 템플릿. v2 전용. */
    public readonly MessageTemplateService $messageTemplates;
    /** 카카오 이미지 업로드 — 브랜드메시지 템플릿용 URL 발급. v2 전용, 기업 계정 전용. */
    public readonly KakaoImageService $kakaoImages;
    /** 수신거부(080) 번호 조회. v2 전용. */
    public readonly RejectedNumberService $rejectedNumbers;
    /** 이벤트 웹훅 구독 — 등록·심사 결과를 밀어 받는다. v2 전용. */
    public readonly WebhookService $webhook;

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

        $this->kakaoSenders       = new KakaoSenderService($http, $url, $version);
        $this->templateFolders    = new TemplateFolderService($http, $url, $version);
        $this->noticeTemplates    = new NoticeTemplateService($http, $url, $version);
        $this->brandTemplates     = new BrandTemplateService($http, $url, $version);
        $this->senderRegistration = new SenderRegistrationService($http, $url, $version);
        $this->messageTemplates   = new MessageTemplateService($http, $url, $version);
        $this->kakaoImages        = new KakaoImageService($http, $url, $version);
        $this->rejectedNumbers    = new RejectedNumberService($http, $url, $version);
        $this->webhook            = new WebhookService($http, $url, $version);
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
    public function __call(string $name, array $arguments): TemplateFolderService|AlimtalkService|FriendtalkService|BrandMessageService|ShortUrlService|SmsService|KakaoSenderService|NoticeTemplateService|BrandTemplateService|SenderRegistrationService|MessageTemplateService|KakaoImageService|RejectedNumberService|WebhookService
    {
        return match ($name) {
            'alimtalk' => $this->alimtalk,
            'friendtalk' => $this->friendtalk,
            'brandMessage', 'brand_message' => $this->brandMessage,
            'shortUrl', 'short_url' => $this->shortUrl,
            'sms' => $this->sms,
            'kakaoSenders', 'kakao_senders' => $this->kakaoSenders,
            'templateFolders', 'template_folders' => $this->templateFolders,
            'noticeTemplates', 'notice_templates' => $this->noticeTemplates,
            'brandTemplates', 'brand_templates' => $this->brandTemplates,
            'senderRegistration', 'sender_registration' => $this->senderRegistration,
            'messageTemplates', 'message_templates' => $this->messageTemplates,
            'kakaoImages', 'kakao_images' => $this->kakaoImages,
            'rejectedNumbers', 'rejected_numbers' => $this->rejectedNumbers,
            'webhook' => $this->webhook,
            default => throw new \BadMethodCallException(
                sprintf('Call to undefined method %s::%s()', static::class, $name)
            ),
        };
    }
}
