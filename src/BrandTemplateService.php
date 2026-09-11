<?php

namespace Sendgo\Php;

/**
 * 브랜드메시지(구 친구톡) 템플릿 관리.
 *
 * v2 전용이며 **기업(Team) 소유 애플리케이션**만 사용할 수 있다.
 *
 * 알림톡 템플릿과 달리 **검수 요청 단계가 없다.** 등록하면 카카오가 바로 상태를
 * 돌려주고 그 값이 `status` 로 나온다.
 *
 * `templateType` 은 친구톡 표기(FT/FI/FW/FL/FC/FM/FP/FA)를 그대로 쓴다.
 * 서버가 chatBubbleType(TEXT/IMAGE/WIDE/...) 으로 변환한다.
 *
 * @example
 * $created = $sendgo->brandTemplates->create([
 *     'kakaoSenderKey'  => $kakaoSenderKey,
 *     'templateName'    => '여름 세일 안내',
 *     'templateType'    => 'FI',
 *     'templateContent' => '여름 세일이 시작되었습니다.',
 *     'imageUrl'        => 'https://mud-kage.kakao.com/....jpg',
 * ]);
 *
 * // 동보 발송(targeting=F)에는 변수가 없는 템플릿만 쓸 수 있다.
 * if ($created['data']['template']['containsVariables']) {
 *     // 개별 발송(M/N/I)으로만 사용
 * }
 */
class BrandTemplateService
{
    public function __construct(
        private HttpClient $http,
        private string $url,
        private string $apiVersion,
    ) {}

    /**
     * 목록 조회.
     *
     * @param array{kakaoSenderKey?: string, search?: string, count?: int} $query
     * @return array<string, mixed>
     */
    public function list(array $query = []): array
    {
        return $this->http->get($this->endpoint(), $query);
    }

    /**
     * 상세 조회. sendgo 코드(KFT-...)와 카카오 브랜드 템플릿 코드 둘 다 받는다.
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
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function create(array $params): array
    {
        return $this->http->post($this->endpoint(), $params);
    }

    /**
     * 템플릿 수정. 발신프로필은 바꿀 수 없다.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function update(string $templateCode, array $params): array
    {
        return $this->http->put($this->endpoint($templateCode), $params);
    }

    /**
     * 템플릿 삭제. 알림톡과 달리 카카오 쪽에서도 실제로 삭제된다.
     *
     * @return array<string, mixed>
     */
    public function delete(string $templateCode): array
    {
        return $this->http->delete($this->endpoint($templateCode));
    }

    /**
     * 동기화. 카카오 쪽에서 이미 삭제됐으면 로컬에서도 제거하고
     * `data.deleted: true` 를 반환한다.
     *
     * @return array<string, mixed>
     */
    public function sync(string $templateCode): array
    {
        return $this->http->post($this->endpoint($templateCode).'/sync', []);
    }

    /**
     * 발신프로필 단위 가져오기 — 카카오 쪽에 이미 있는 템플릿을 들여온다.
     *
     * @return array<string, mixed>
     */
    public function import(string $kakaoSenderKey): array
    {
        return $this->http->post($this->endpoint('import'), ['kakaoSenderKey' => $kakaoSenderKey]);
    }

    private function endpoint(?string $segment = null): string
    {
        $base = "{$this->url}/api/{$this->apiVersion}/brand-templates";

        return $segment === null ? $base : $base.'/'.rawurlencode($segment);
    }
}
