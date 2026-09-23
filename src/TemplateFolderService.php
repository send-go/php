<?php

namespace Sendgo\Php;

/** 템플릿 공용 폴더. v2 전용, 기업 계정 전용. */
class TemplateFolderService
{
    public function __construct(private HttpClient $http, private string $url, private string $apiVersion) {}

    /** @param array{templateType?: 'notice'|'brand', kakaoSenderKey?: string} $query */
    public function list(array $query = []): array
    {
        return $this->http->get($this->endpoint(), $query);
    }

    /** @param array{name: string, parentUuid?: string|null} $params */
    public function create(array $params): array
    {
        return $this->http->post($this->endpoint(), $params);
    }

    /** 1~100개 템플릿 이동. folderUuid는 필수이며 null이면 미분류로 이동합니다. */
    public function assign(array $params): array
    {
        return $this->http->patch($this->endpoint().'/templates', $params);
    }

    private function endpoint(): string
    {
        return "{$this->url}/api/{$this->apiVersion}/template-folders";
    }
}
