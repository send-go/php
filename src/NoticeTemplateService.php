<?php

namespace Sendgo\Php;

/**
 * 알림톡 템플릿 관리 — 등록 · 수정 · 검수 요청.
 *
 * v2 전용이며 **기업(Team) 소유 애플리케이션**만 사용할 수 있다.
 *
 * 템플릿은 만든 즉시 쓸 수 없다. 카카오 검수를 통과해야 한다.
 *
 * ```
 * 등록      inspectionStatus=REG   ← 발송 불가
 * 검수 요청  inspectionStatus=REQ   ← 카카오 심사 중
 * 승인      inspectionStatus=APR   ← 여기부터 발송 가능
 * 반려      inspectionStatus=REJ   ← comments 에 사유
 * ```
 *
 * 검수 결과는 비동기다. 웹훅이 없으므로 `sync()` 로 폴링한다.
 *
 * @example
 * $created = $sendgo->noticeTemplates->create([
 *     'kakaoSenderKey'        => $kakaoSenderKey,
 *     'templateName'          => '주문 접수 안내',
 *     'templateContent'       => '#{name}님, 주문 #{orderNo}이 접수되었습니다.',
 *     'templateMessageType'   => 'BA',
 *     'templateEmphasizeType' => 'NONE',
 *     'categoryCode'          => '001001',
 *     'messagePurpose'        => 'order_delivery',
 *     'legalBasis'            => 'transaction',
 *     'benefitOrigin'         => 'none',
 *     'expiryType'            => 'none',
 *     'optInReviewConfirmed'  => true,
 *     'ctaClearConfirmed'     => true,
 *     'policyConfirmed'       => true,
 * ]);
 *
 * $code = $created['data']['template']['templateCode'];
 * $sendgo->noticeTemplates->requestInspection($code);
 */
class NoticeTemplateService
{
    public function __construct(
        private HttpClient $http,
        private string $url,
        private string $apiVersion,
    ) {}

    /**
     * 목록 조회.
     *
     * @param array{
     *   kakaoSenderKey?: string,
     *   inspectionStatus?: string,
     *   search?: string,
     *   count?: int,
     * } $query
     * @return array<string, mixed>
     */
    public function list(array $query = []): array
    {
        return $this->http->get($this->endpoint(), $query);
    }

    /**
     * 상세 조회. 응답의 `data.template.policy` 에 정책 검토 상태가 들어 있다.
     *
     * @return array<string, mixed>
     */
    public function show(string $templateCode): array
    {
        return $this->http->get($this->endpoint($templateCode));
    }

    /**
     * 템플릿 등록.
     *
     * `messagePurpose`·`legalBasis`·`benefitOrigin`·`expiryType` 과 3종 확인
     * 필드는 sendgo 자체 정책 게이트다. 카카오 심사와 별개이며 빠뜨리면
     * `POLICY_VALIDATION_FAILED` 로 거절된다.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function create(array $params): array
    {
        return $this->http->post($this->endpoint(), $params);
    }

    /**
     * 이미지 템플릿 등록 (`templateEmphasizeType => 'IMAGE'`).
     *
     * multipart 로 나가므로 `buttons` 같은 배열 필드는 HttpClient 가 JSON 문자열로
     * 직렬화해 보낸다 — 호출부는 그냥 배열로 넘기면 된다.
     *
     * @param  array<string, mixed>  $params
     * @param  string|\CURLFile  $image  jpg/png, 500KB 이하
     * @return array<string, mixed>
     */
    public function createWithImage(array $params, string|\CURLFile $image): array
    {
        $params['templateEmphasizeType'] ??= 'IMAGE';

        return $this->http->postMultipart($this->endpoint(), $params, ['image' => $image]);
    }

    /**
     * 템플릿 수정.
     *
     * 발신프로필과 템플릿 코드는 바꿀 수 없다. 본문·버튼처럼 카카오에 등록된
     * 내용이 바뀌면 검수 상태가 되돌아가므로 재검수를 요청해야 한다.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function update(string $templateCode, array $params): array
    {
        return $this->http->put($this->endpoint($templateCode), $params);
    }

    /**
     * 템플릿 삭제.
     *
     * **카카오는 템플릿 삭제 API 를 제공하지 않는다.** sendgo 목록에서만 지워지고
     * 비즈니스 채널 쪽 템플릿은 남는다. 동기화하면 다시 나타난다.
     *
     * @return array<string, mixed>
     */
    public function delete(string $templateCode): array
    {
        return $this->http->delete($this->endpoint($templateCode));
    }

    /**
     * 카카오에서 검수 상태와 반려 사유를 다시 읽어 온다.
     *
     * @return array<string, mixed>
     */
    public function sync(string $templateCode): array
    {
        return $this->http->post($this->endpoint($templateCode).'/sync', []);
    }

    /**
     * 검수 요청.
     *
     * sendgo 정책 검토를 통과하지 못한 템플릿은 `POLICY_REVIEW_REQUIRED` 로
     * 거절된다. 응답 `errors.reasons` 에 미충족 항목이 담긴다.
     *
     * @param  array<int, string|\CURLFile>  $attachments  jpg/png/pdf, 각 5MB 이하, 최대 5개.
     *                                                     첨부가 있으면 $comment 는 필수.
     * @return array<string, mixed>
     */
    public function requestInspection(string $templateCode, ?string $comment = null, array $attachments = []): array
    {
        $endpoint = $this->endpoint($templateCode).'/inspection';

        if ($attachments === []) {
            return $this->http->post($endpoint, $comment === null ? [] : ['comment' => $comment]);
        }

        $files = [];
        foreach (array_values($attachments) as $index => $attachment) {
            $files["attachments[{$index}]"] = $attachment;
        }

        return $this->http->postMultipart($endpoint, ['comment' => $comment], $files);
    }

    /**
     * 검수 요청 취소. 아직 심사 중(REQ)일 때만 통한다.
     *
     * @return array<string, mixed>
     */
    public function cancelInspection(string $templateCode): array
    {
        return $this->http->delete($this->endpoint($templateCode).'/inspection');
    }

    /**
     * 승인 취소. 승인(APR)된 템플릿을 되돌린다. 이후에는 발송할 수 없다.
     *
     * @return array<string, mixed>
     */
    public function cancelApproval(string $templateCode): array
    {
        return $this->http->delete($this->endpoint($templateCode).'/approval');
    }

    /**
     * 휴면 해제. 오래 안 쓴 템플릿이 dormant 로 잠기면 이걸로 깨운다.
     *
     * @return array<string, mixed>
     */
    public function release(string $templateCode): array
    {
        return $this->http->post($this->endpoint($templateCode).'/release', []);
    }

    /**
     * 템플릿 카테고리 코드 조회.
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

    private function endpoint(?string $segment = null): string
    {
        $base = "{$this->url}/api/{$this->apiVersion}/notice-templates";

        return $segment === null ? $base : $base.'/'.rawurlencode($segment);
    }
}
