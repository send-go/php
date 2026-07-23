# techigh/sendgo

> **Sendgo** PHP SDK — 카카오 알림톡/친구톡, SMS/LMS/MMS
> Laravel 및 순수 PHP 8.2+ 프로젝트에서 사용 가능합니다.

[![Packagist](https://img.shields.io/packagist/v/techigh/sendgo)](https://packagist.org/packages/techigh/sendgo)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue)](https://php.net)

---

## 빠른 시작 (3단계)

### 1단계 — 설치

```bash
composer require techigh/sendgo
```

### 2단계 — 설정

```php
// 순수 PHP
$sendgo = new \Techigh\Sendgo\Sendgo([
    'access_key'       => $_ENV['SENDGO_ACCESS_KEY'],
    'secret_key'       => $_ENV['SENDGO_SECRET_KEY'],
    'kakao_sender_key' => $_ENV['SENDGO_KAKAO_SENDER_KEY'],
    'sms_sender_key'   => $_ENV['SENDGO_SMS_SENDER_KEY'],
    'api_version'      => 'v2',
]);
```

**Laravel `.env`:**
```env
SENDGO_ACCESS_KEY=your_access_key
SENDGO_SECRET_KEY=your_secret_key
SENDGO_KAKAO_SENDER_KEY=your_kakao_key
SENDGO_SENDER_KEY=your_sms_key
SENDGO_API_VERSION=v2
```

### 3단계 — 알림톡 전송

```php
$sendgo->alimtalk->send([
    'templateCode' => 'ORDER_CONFIRM_001',
    'contacts'     => [
        ['contact' => '01012345678', 'name' => '홍길동', 'var1' => 'ORD-001'],
    ],
]);
```

---

## 기능별 사용법

### 알림톡

```php
// 다건 발송
$sendgo->alimtalk->send([
    'templateCode' => 'ORDER_CONFIRM_001',
    'contacts'     => [
        ['contact' => '01011111111', 'var1' => 'ORD-001'],
        ['contact' => '01022222222', 'var1' => 'ORD-002'],
    ],
]);

// SMS 대체 발송
$sendgo->alimtalk->send([
    'templateCode' => 'DELIVERY_001',
    'replaceSms'   => 'Y',
    'smsSubject'   => '[배송 안내]',
    'smsContent'   => '상품이 출고되었습니다.',
    'contacts'     => [['contact' => '01012345678', 'var1' => 'ORD-001']],
]);

// 예약 발송
$sendgo->alimtalk->send([
    'templateCode' => 'PROMO_001',
    'scheduleType' => 'SCHEDULED',
    'at'           => '2026-04-01 09:00:00',
    'contacts'     => [['contact' => '01012345678']],
]);
```

### SMS / LMS / MMS

```php
// SMS
$sendgo->sms->sendSms([
    'content'  => '인증번호: 123456',
    'contacts' => [['contact' => '01012345678']],
]);

// LMS
$sendgo->sms->sendLms([
    'subject'  => '[공지사항]',
    'content'  => '서비스 점검이 예정되어 있습니다...',
    'contacts' => [['contact' => '01012345678']],
]);
```

### 친구톡

```php
$sendgo->friendtalk->send([
    'content'  => '이번 주 특가 이벤트를 확인하세요!',
    'contacts' => [['contact' => '01012345678']],
]);
```

---

## Laravel 통합

`config/sendgo.php`를 퍼블리시하고 `Sendgo` 파사드를 사용하세요:

```bash
php artisan vendor:publish --tag=sendgo-config
```

```php
use Techigh\Sendgo\Sendgo;

app(Sendgo::class)->alimtalk->send([...]);
```

---

## 예외 처리

```php
use Techigh\Sendgo\Exception\SendgoException;

try {
    $sendgo->alimtalk->send([...]);
} catch (SendgoException $e) {
    logger()->error('알림톡 실패', [
        'status'     => $e->getStatusCode(),
        'error_code' => $e->getErrorCode(),
        'endpoint'   => $e->getEndpoint(),
    ]);
}
```

---

## 라이선스

MIT License © [Sendgo](https://sendgo.io)
