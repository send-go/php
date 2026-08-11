# sendgo/php

> **PHP에서 카카오 알림톡, 친구톡, SMS를 가장 쉽게 발송하는 순수 PHP SDK**

[![Packagist](https://img.shields.io/packagist/v/sendgo/php)](https://packagist.org/packages/sendgo/php)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-blue)](LICENSE)

`sendgo/php`는 [Sendgo](https://sendgo.io) 알림 API를 위한 **순수 PHP SDK**입니다.
Laravel 등 특정 프레임워크에 의존하지 않으며, `ext-curl`과 `ext-json`만으로 동작합니다.

---

## 설치

```bash
composer require sendgo/php
```

---

## 빠른 시작

```php
<?php

use Sendgo\Php\Sendgo;

$sendgo = new Sendgo([
    'access_key'       => $_ENV['SENDGO_ACCESS_KEY'],
    'secret_key'       => $_ENV['SENDGO_SECRET_KEY'],
    'kakao_sender_key' => $_ENV['SENDGO_KAKAO_SENDER_KEY'],
    'sms_sender_key'   => $_ENV['SENDGO_SMS_SENDER_KEY'],
    'api_version'      => 'v2',
]);

// 알림톡 발송
$sendgo->alimtalk->send([
    'templateCode' => 'ORDER_CONFIRM_001',
    'contacts'     => [
        ['contact' => '01012345678', 'name' => '홍길동', 'var1' => 'ORD-001', 'var2' => '29,000원'],
    ],
]);

// SMS 발송
$sendgo->sms->sendSms([
    'content'  => '[Sendgo] 인증번호: 123456 (5분 이내 입력)',
    'contacts' => [['contact' => '01012345678']],
]);
```

---

## 알림톡 상세 사용법

```php
<?php

// 다건 발송
$sendgo->alimtalk->send([
    'templateCode' => 'ORDER_CONFIRM_001',
    'contacts'     => [
        ['contact' => '01011111111', 'name' => '홍길동', 'var1' => 'ORD-001', 'var2' => '29,000원'],
        ['contact' => '01022222222', 'name' => '김철수', 'var1' => 'ORD-002', 'var2' => '15,000원'],
        ['contact' => '01033333333', 'name' => '이영희', 'var1' => 'ORD-003', 'var2' => '52,000원'],
    ],
]);

// 예약 발송
$sendgo->alimtalk->send([
    'templateCode' => 'PROMO_SUMMER_2026',
    'scheduleType' => 'SCHEDULED',
    'at'           => '2026-07-28 09:00:00',
    'contacts'     => [['contact' => '01012345678', 'var1' => '여름 한정 50% 할인']],
]);

// 알림톡 실패 시 SMS 자동 대체 발송
$sendgo->alimtalk->send([
    'templateCode' => 'DELIVERY_START_001',
    'replaceSms'   => 'Y',
    'smsSubject'   => '[배송 시작 안내]',
    'smsContent'   => "주문하신 상품이 출고되었습니다.\n송장번호: #{var2}",
    'contacts'     => [['contact' => '01012345678', 'var1' => 'ORD-001', 'var2' => '1234567890']],
]);
```

---

## 친구톡 사용법

```php
<?php

// 텍스트형
$sendgo->friendtalk->send([
    'content'  => '안녕하세요! 7월 한정 특가 이벤트를 확인해보세요.',
    'contacts' => [['contact' => '01012345678']],
]);

// 이미지형
$sendgo->friendtalk->send([
    'messageType' => 'FI',
    'content'     => '이번 주 특가 상품을 확인하세요!',
    'imageUrl'    => 'https://cdn.example.com/banner.jpg',
    'imageLink'   => 'https://example.com/event',
    'contacts'    => [['contact' => '01012345678']],
]);

// 버튼 포함
$sendgo->friendtalk->send([
    'content'  => '7월 쿠폰이 도착했습니다! 지금 바로 사용하세요.',
    'buttons'  => [
        ['name' => '쿠폰 받기', 'type' => 'WL', 'linkMo' => 'https://example.com/coupon'],
    ],
    'contacts' => [['contact' => '01012345678']],
]);
```

---

## 브랜드메시지 사용법

브랜드메시지는 친구톡의 후속 채널입니다. 메시지 타입이 친구톡과 1:1 대응되며
(`FT`→`BT`, `FI`→`BI`, `FW`→`BW`, `FL`→`BL`, `FC`→`BC`, `FM`→`BM`, `FP`→`BP`, `FA`→`BA`),
요청에는 **친구톡 코드를 그대로** 넘기고 변환은 서버가 처리합니다.

친구톡과 달리 다음이 가능합니다.

- 채널 친구가 **아닌** 수신자에게 발송 (`targeting: N`)
- 수신 동의한 **전체 채널 친구 동보** 발송 (`targeting: F`, 수신자 목록 불필요)
- 리스트·캐러셀·커머스·동영상 등 **템플릿 기반 리치 메시지**

> v2 전용입니다. `FT`/`FI`/`FW`를 채널 친구에게만 보낼 때는 친구톡 API가 더 간단합니다.

```php
<?php

// 단건 발송 — 채널 친구 대상
$sendgo->brandMessage->send([
    'targeting'          => 'M',
    'messageType'        => 'FL',
    'friendTemplateUuid' => '9cd5460b-6458-4edc-9b11-c26d3013c340',
    'contacts'           => [['contact' => '01012345678', 'var1' => '29,000원']],
]);

// 동보 발송 — 수신 동의한 전체 채널 친구 (contacts 불필요)
$sendgo->brandMessage->broadcast([
    'messageType'        => 'FW',
    'friendTemplateUuid' => '9cd5460b-6458-4edc-9b11-c26d3013c340',
]);

// 캠페인 조회
$list = $sendgo->brandMessage->campaigns(['count' => 10]);
$one  = $sendgo->brandMessage->campaign('1f0a6d0e-6b3b-4f0f-9b2f-2f6f6a1b7c11');
```

---

## SMS / LMS / MMS 사용법

```php
<?php

// SMS (90자 이하)
$sendgo->sms->sendSms([
    'content'  => '[Sendgo] 인증번호: 123456 (5분 이내 입력)',
    'contacts' => [['contact' => '01012345678']],
]);

// LMS (장문, 2,000자 이하)
$sendgo->sms->sendLms([
    'subject'  => '[중요] 서비스 점검 안내',
    'content'  => "안녕하세요. 서비스 점검이 예정되어 있습니다.\n\n■ 일시: 2026-07-25 02:00 ~ 06:00\n■ 영향: 전체 서비스",
    'contacts' => [['contact' => '01012345678']],
]);

// MMS (이미지 포함)
$sendgo->sms->sendMms([
    'subject'  => '[이벤트] 7월 특가',
    'content'  => '이번 달 특가 상품을 확인하세요!',
    'contacts' => [['contact' => '01012345678']],
]);

// 예약 문자
$sendgo->sms->sendSms([
    'content'      => '[알림] 예약 미팅을 확인해주세요.',
    'scheduleType' => 'SCHEDULED',
    'at'           => '2026-07-23 08:00:00',
    'contacts'     => [['contact' => '01012345678']],
]);
```

---

## 프레임워크 통합

### Symfony

```php
<?php
// src/Service/NotificationService.php

namespace App\Service;

use Sendgo\Php\Sendgo;
use Sendgo\Php\Exception\SendgoException;

class NotificationService
{
    public function __construct(private Sendgo $sendgo) {}

    public function sendOrderConfirm(string $phone, string $orderNo): void
    {
        $this->sendgo->alimtalk->send([
            'templateCode' => 'ORDER_CONFIRM_001',
            'contacts'     => [['contact' => $phone, 'var1' => $orderNo]],
        ]);
    }

    public function sendShippingAlert(string $phone, string $trackingNo): void
    {
        $this->sendgo->alimtalk->send([
            'templateCode' => 'SHIPPING_001',
            'replaceSms'   => 'Y',
            'smsContent'   => "배송이 시작되었습니다.\n송장번호: {$trackingNo}",
            'contacts'     => [['contact' => $phone, 'var1' => $trackingNo]],
        ]);
    }
}
```

### Slim Framework

```php
<?php
// bootstrap/app.php

use DI\Container;
use Sendgo\Php\Sendgo;

$container = new Container();
$container->set(Sendgo::class, fn() => new Sendgo([
    'access_key'       => $_ENV['SENDGO_ACCESS_KEY'],
    'secret_key'       => $_ENV['SENDGO_SECRET_KEY'],
    'kakao_sender_key' => $_ENV['SENDGO_KAKAO_KEY'],
    'api_version'      => 'v2',
]));
```

### WordPress / WooCommerce

```php
<?php

use Sendgo\Php\Sendgo;
use Sendgo\Php\Exception\SendgoException;

function get_sendgo(): Sendgo {
    static $instance = null;
    if ($instance === null) {
        $instance = new Sendgo([
            'access_key'       => get_option('sendgo_access_key'),
            'secret_key'       => get_option('sendgo_secret_key'),
            'kakao_sender_key' => get_option('sendgo_kakao_key'),
            'api_version'      => 'v2',
        ]);
    }
    return $instance;
}

// WooCommerce 주문 완료 시 알림톡 발송
add_action('woocommerce_order_status_completed', function (int $orderId) {
    $order = wc_get_order($orderId);
    try {
        get_sendgo()->alimtalk->send([
            'templateCode' => 'ORDER_CONFIRM_001',
            'contacts'     => [
                ['contact' => $order->get_billing_phone(), 'var1' => $order->get_order_number()],
            ],
        ]);
    } catch (SendgoException $e) {
        error_log("Sendgo 알림 실패: {$e->getMessage()}");
    }
});
```

---

## 예외 처리

```php
<?php

use Sendgo\Php\Exception\SendgoException;

try {
    $sendgo->alimtalk->send([
        'templateCode' => 'ORDER_CONFIRM_001',
        'contacts'     => [['contact' => '01012345678']],
    ]);
} catch (SendgoException $e) {
    echo "발송 실패: HTTP {$e->getStatusCode()} [{$e->getErrorCode()}]" . PHP_EOL;

    match ($e->getErrorCode()) {
        'INVALID_ACCESS_KEY',
        'INVALID_SECRET_KEY'    => alertOps('Sendgo 인증키를 확인하세요.'),
        'INVALID_TEMPLATE_CODE' => logger('존재하지 않는 템플릿'),
        'PAYMENT_REQUIRED'      => alertOps('Sendgo 크레딧이 부족합니다.'),
        'IP_NOT_ALLOWED'        => alertOps('허용되지 않은 IP'),
        default                 => logger('알 수 없는 오류: ' . $e->getMessage()),
    };
}
```

---

## 설정 옵션

| 파라미터 | 타입 | 필수 | 기본값 | 설명 |
|---------|------|------|--------|------|
| `access_key` | `string` | **필수** | — | Sendgo 액세스 키 |
| `secret_key` | `string` | **필수** | — | Sendgo 시크릿 키 |
| `kakao_sender_key` | `string\|null` | 선택 | `null` | 카카오 발신프로필 키 |
| `sms_sender_key` | `string\|null` | 선택 | `null` | SMS 발신자 키 |
| `api_version` | `string` | 선택 | `'v1'` | API 버전 (`v1` \| `v2`) |
| `url` | `string` | 선택 | `'https://sendgo.io'` | API 기본 URL |

---

## 관련 패키지

| 언어/프레임워크 | 패키지 | GitHub |
|----------------|--------|--------|
| Laravel | `sendgo/laravel` | [laravel](https://github.com/send-go/laravel) |
| Spring Boot | `io.sendgo:sendgo-spring` | [spring](https://github.com/send-go/spring) |
| Node.js | `@sendgo/node` | [node](https://github.com/send-go/node) |
| Python | `sendgo-python` | [python](https://github.com/send-go/python) |
| Go | `github.com/send-go/go` | [go](https://github.com/send-go/go) |
| 전체 목록 | — | [send-go GitHub 조직](https://github.com/send-go) |

---

## 짧은 URL

짧은 URL 은 메시지 본문의 링크를 줄이고, 그 링크가 실제로 눌렸는지 집계합니다.
문자는 바이트 수가 요금과 직결되므로 링크를 줄이면 그만큼 본문을 더 쓸 수 있습니다.

같은 원본 URL 을 다시 줄이면 **기존 링크가 그대로 반환**됩니다. 캠페인별로 반응을
따로 집계하려면 `forceNew` 로 새 코드를 만드세요.

`deactivate` 는 링크를 삭제하지 않고 리다이렉트만 중지합니다. 이미 발송한 메시지의
링크를 무효화할 때 쓰며, 누적 통계는 남고 이후 접속은 `410 Gone` 이 됩니다.

```php
// 짧은 URL 생성 (v2 전용)
$short = $sendgo->shortUrl->create([
    'targetUrl' => 'https://example.com/promotions/summer-sale',
    'title'     => '여름 세일 랜딩',
]);

$link = $short['data']['shortUrl'];   // 문자/알림톡 본문에 넣을 짧은 링크
$code = $short['data']['code'];

// 반응 통계 — 일별 추이 + 디바이스/유입경로/국가별 분해
$stats = $sendgo->shortUrl->stats($code, ['from' => '2026-08-01']);

$sendgo->shortUrl->list(['count' => 10]);
$sendgo->shortUrl->show($code);
$sendgo->shortUrl->deactivate($code);   // 리다이렉트만 중지, 통계는 남는다
```

`stats` 는 일별 추이(`daily`)와 디바이스(`byDevice`)·유입경로(`byReferer`)·국가(`byCountry`)별
분해를 반환합니다. 일별 추이는 사전 집계 표에서 읽으므로 클릭이 많아도 응답 시간이 일정합니다.

## 변경 사항

### 1.1.0 (2026-08-11)

- 짧은 URL 추가 — `$sendgo->shortUrl` (생성/목록/상세/반응통계/중지)
- `HttpClient::delete()` 추가
- `Sendgo::__call` 에 `shortUrl`/`short_url` 포워딩 추가 (Laravel 파사드 대응)

## 라이선스

MIT License © 2026 [Sendgo](https://sendgo.io)

---

*키워드: 카카오 알림톡 PHP, 카카오 친구톡 PHP, SMS 발송 PHP, 알림톡 SDK Composer, PHP 카카오 API 연동, Sendgo PHP SDK, Packagist 알림 발송*
