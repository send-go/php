<?php

namespace Sendgo\Php;

/**
 * 문자(SMS/LMS/MMS) 템플릿 — 자주 쓰는 문구를 저장해 두는 상용구.
 *
 * v2 전용. 카카오 템플릿과 달리 **검수가 없어** 만들면 바로 쓸 수 있고,
 * 기업 계정이 아니어도 된다.
 *
 * @example
 * $sendgo->messageTemplates->create([
 *     'messageTranType'    => 'LMS',
 *     'messageTranSubject' => '주문 안내',
 *     'messageTranMsg'     => '주문이 접수되었습니다.',
 * ]);
 */
class MessageTemplateService
{
    public function __construct(
        private HttpClient $http,
        private string $url,
        private string $apiVersion,
    ) {}

    /**
     * @param array{messageType?: string, search?: string, count?: int} $query
     * @return array<string, mixed>
     */
    public function list(array $query = []): array
    {
        return $this->http->get($this->endpoint(), $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function show(string $templateKey): array
    {
        return $this->http->get($this->endpoint($templateKey));
    }

    /**
     * @param array{
     *   messageTranType: 'SMS'|'LMS'|'MMS',
     *   messageTranMsg: string,
     *   messageTranSubject?: string|null,
     *   isFavorite?: bool,
     * } $params  LMS·MMS 는 messageTranSubject 가 필수다.
     * @return array<string, mixed>
     */
    public function create(array $params): array
    {
        return $this->http->post($this->endpoint(), $params);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function update(string $templateKey, array $params): array
    {
        return $this->http->put($this->endpoint($templateKey), $params);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $templateKey): array
    {
        return $this->http->delete($this->endpoint($templateKey));
    }

    private function endpoint(?string $segment = null): string
    {
        $base = "{$this->url}/api/{$this->apiVersion}/message-templates";

        return $segment === null ? $base : $base.'/'.rawurlencode($segment);
    }
}
