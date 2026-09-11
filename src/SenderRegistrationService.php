<?php

namespace Sendgo\Php;

/**
 * 발신번호(문자) 등록 · 심사 접수.
 *
 * v2 전용. 카카오와 달리 **개인 계정 애플리케이션도** 쓸 수 있다.
 *
 * 등록하면 곧바로 쓸 수 있는 게 아니라 `PENDING` 으로 **접수**되고, 운영자
 * 승인 후 `SUCCESS` 가 된다.
 *
 * ## 본인확인을 어떻게 대신하나
 *
 * 콘솔은 휴대폰 계열(`personal_mobile`, `team_representative_mobile`,
 * `team_emp_mobile`)에 PASS 본인인증을 요구한다. API 에는 그 화면이 없으므로
 * **신분증 사본(`identityDocument`)을 받아 sendgo 운영자가 직접 확인**한다.
 * 모든 유형을 API 로 접수할 수 있다.
 *
 * 이 경로로 접수된 건은 응답의 `identityVerificationMethod` 가 `document` 이고
 * **자동 승인되지 않는다** — 운영자 확인 전까지 `PENDING` 이다.
 *
 * @example
 * $check = $sendgo->senderRegistration->validate('02-1234-5678', 'team_main');
 *
 * // 유선번호 — 신분증 불필요
 * $sendgo->senderRegistration->create(
 *     [
 *         'senderAlias'      => '고객센터 대표번호',
 *         'senderNumberType' => 'team_main',
 *         'phoneE164'        => '02-1234-5678',
 *     ],
 *     ['csuCertificate' => '/path/to/csu.pdf'],
 * );
 *
 * // 대표자 휴대폰 — PASS 대신 신분증 사본
 * $sendgo->senderRegistration->create(
 *     [
 *         'senderAlias'      => '대표자 휴대폰',
 *         'senderNumberType' => 'team_representative_mobile',
 *         'phoneE164'        => '01012345678',
 *     ],
 *     [
 *         'csuCertificate'   => '/path/to/csu.pdf',
 *         'identityDocument' => '/path/to/id-card.jpg',
 *     ],
 * );
 */
class SenderRegistrationService
{
    /** API 로 접수할 수 있는 발신번호 유형 — 전부다. */
    public const REGISTRABLE_TYPES = [
        'personal_mobile',
        'personal_other',
        'team_main',
        'team_representative_mobile',
        'team_emp_mobile',
        'team_other_company',
    ];

    /** `identityDocument`(신분증 사본)가 필요한 유형. */
    public const IDENTITY_DOCUMENT_TYPES = [
        'personal_mobile',
        'team_representative_mobile',
        'team_emp_mobile',
    ];

    public function __construct(
        private HttpClient $http,
        private string $url,
        private string $apiVersion,
    ) {}

    /**
     * 목록 조회. 심사 상태(`status`)를 여기서 확인한다.
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
    public function show(string $senderKey): array
    {
        return $this->http->get($this->endpoint($senderKey));
    }

    /**
     * 계정 종류에 맞는 발신번호 유형과 유형별 필수 서류.
     *
     * 유형별 `identityVerification`(`none`/`document`)과 필수 서류 목록을 준다.
     *
     * @return array<string, mixed>
     */
    public function numberTypes(): array
    {
        return $this->http->get($this->endpoint('number-types'));
    }

    /**
     * 등록 전 형식·중복 확인.
     *
     * 응답의 `duplicationReasonRequired` 가 true 면 `create()` 에
     * `duplicationReason` 을 함께 넣어야 한다.
     *
     * @return array<string, mixed>
     */
    public function validate(string $phoneE164, string $senderNumberType): array
    {
        return $this->http->post($this->endpoint('validate'), [
            'phoneE164' => $phoneE164,
            'senderNumberType' => $senderNumberType,
        ]);
    }

    /**
     * 등록 신청. 서류가 붙으므로 multipart 로 나간다.
     *
     * @param array{
     *   senderAlias: string,
     *   senderNumberType: string,
     *   phoneE164: string,
     *   duplicationReason?: string|null,
     *   acceptanceName?: string|null,
     *   delegationName?: string|null,
     *   delegationReason?: string|null,
     * } $params
     * @param  array<string, string|\CURLFile>  $files  `csuCertificate` 는 항상 필수.
     *                                                  휴대폰 계열은 `identityDocument` 가 더 필요하고,
     *                                                  `team_other_company` 는 수임/위임 서류가 더 필요하다.
     *                                                  유형별 목록은 {@see numberTypes()} 로 확인한다.
     * @return array<string, mixed>
     */
    public function create(array $params, array $files): array
    {
        return $this->http->postMultipart($this->endpoint(), $params, $files);
    }

    /**
     * 별칭 변경 / 기본 발신 지정. 번호와 심사 상태는 바꿀 수 없다.
     *
     * @param array{senderAlias: string, primaryType?: 'PRIMARY'|'SECONDARY'} $params
     * @return array<string, mixed>
     */
    public function update(string $senderKey, array $params): array
    {
        return $this->http->patch($this->endpoint($senderKey), $params);
    }

    /**
     * 삭제. 기본 발신번호를 지우면 남은 번호 중 하나가 기본으로 승계된다.
     *
     * @return array<string, mixed>
     */
    public function delete(string $senderKey): array
    {
        return $this->http->delete($this->endpoint($senderKey));
    }

    private function endpoint(?string $segment = null): string
    {
        $base = "{$this->url}/api/{$this->apiVersion}/senders";

        return $segment === null ? $base : $base.'/'.rawurlencode($segment);
    }
}
