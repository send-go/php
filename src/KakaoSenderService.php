<?php

namespace Sendgo\Php;

/**
 * 카카오 발신프로필(채널) 관리 — 등록 · 동기화 · 브랜드메시지 타겟팅 신청.
 *
 * v2 전용이며 **기업(Team) 소유 애플리케이션**만 사용할 수 있다.
 *
 * 채널 등록은 두 단계다. 카카오가 인증번호를 채널 관리자 **휴대폰으로 SMS
 * 발송**하므로, 완전 무인 자동화는 불가능하다 — 사람이 문자를 받아
 * `create()` 에 넣어야 한다.
 *
 * @example
 * // 1단계 — 관리자 휴대폰으로 인증번호 발송
 * $sendgo->kakaoSenders->requestToken('@my-channel', '01012345678');
 *
 * // 2단계 — 문자로 받은 인증번호로 발신프로필 생성
 * $created = $sendgo->kakaoSenders->create([
 *     'token'        => '123456',
 *     'yellowId'     => '@my-channel',
 *     'phoneNumber'  => '01012345678',
 *     'categoryCode' => '001001',
 * ]);
 *
 * $kakaoSenderKey = $created['data']['sender']['kakaoSenderKey'];
 */
class KakaoSenderService
{
    public function __construct(
        private HttpClient $http,
        private string $url,
        private string $apiVersion,
    ) {}

    /**
     * 1단계 — 채널 인증번호 발송.
     *
     * 응답에 인증번호는 들어있지 않다. 카카오가 `$phoneNumber` 로 SMS 를 보낸다.
     *
     * @return array<string, mixed>
     */
    public function requestToken(string $yellowId, string $phoneNumber): array
    {
        return $this->http->post($this->endpoint('token'), [
            'yellowId' => $yellowId,
            'phoneNumber' => $phoneNumber,
        ]);
    }

    /**
     * 2단계 — 발신프로필 등록.
     *
     * 이미 등록된 채널을 다시 등록해도 오류가 아니다. 카카오가 같은 senderKey 를
     * 돌려주고 서버가 기존 행을 갱신한다.
     *
     * @param array{
     *   token: string,
     *   yellowId: string,
     *   phoneNumber: string,
     *   categoryCode: string,
     * } $params
     * @return array<string, mixed>
     */
    public function create(array $params): array
    {
        return $this->http->post($this->endpoint(), $params);
    }

    /**
     * 목록 조회.
     *
     * @return array<string, mixed>
     */
    public function list(): array
    {
        return $this->http->get($this->endpoint());
    }

    /**
     * 상세 조회.
     *
     * @return array<string, mixed>
     */
    public function show(string $kakaoSenderKey): array
    {
        return $this->http->get($this->endpoint($kakaoSenderKey));
    }

    /**
     * 카테고리 조회. 등록 시 `categoryCode` 로 넣을 값이다.
     *
     * @return array<string, mixed>
     */
    public function categories(?string $categoryCode = null): array
    {
        return $this->http->get(
            $this->endpoint('categories'),
            $categoryCode === null ? [] : ['categoryCode' => $categoryCode],
        );
    }

    /**
     * 상태 동기화. 키를 주면 단건, 없으면 팀 전체.
     *
     * 채널이 카카오 쪽에서 차단·휴면되면 발송이 조용히 실패하기 시작한다.
     * 그 사실을 먼저 알 방법은 이 호출뿐이므로 하루 한 번 정도 돌리는 게 좋다.
     *
     * @return array<string, mixed>
     */
    public function sync(?string $kakaoSenderKey = null): array
    {
        $endpoint = $kakaoSenderKey === null
            ? $this->endpoint('sync')
            : $this->endpoint($kakaoSenderKey).'/sync';

        return $this->http->post($endpoint, []);
    }

    /**
     * 브랜드메시지 M 신청에 필요한 광고성 정보 수신동의 증적자료 업로드.
     *
     * jpg/png, 5MB 이하.
     *
     * @param  string|\CURLFile  $evidence  파일 경로 또는 CURLFile
     * @return array<string, mixed>
     */
    public function uploadBrandMessageEvidence(string $kakaoSenderKey, string|\CURLFile $evidence): array
    {
        return $this->http->postMultipart(
            $this->endpoint($kakaoSenderKey).'/brand-message/evidence',
            [],
            ['evidence' => $evidence],
        );
    }

    /**
     * 브랜드메시지 M(마케팅) / N(정보성) 사용 신청.
     *
     * 결과는 즉시 확정되지 않는다. 발신프로필의 `brandMessageStatus` 로 확인한다.
     *
     * @param  'M'|'N'  $targetType
     * @return array<string, mixed>
     */
    public function applyBrandMessageTargeting(string $kakaoSenderKey, string $targetType): array
    {
        return $this->http->post(
            $this->endpoint($kakaoSenderKey).'/brand-message/apply',
            ['targetType' => $targetType],
        );
    }

    private function endpoint(?string $segment = null): string
    {
        $base = "{$this->url}/api/{$this->apiVersion}/kakao-senders";

        return $segment === null ? $base : $base.'/'.rawurlencode($segment);
    }
}
