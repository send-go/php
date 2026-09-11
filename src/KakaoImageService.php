<?php

namespace Sendgo\Php;

/**
 * 카카오 이미지 업로드 — 브랜드메시지 템플릿에 넣을 URL 발급.
 *
 * v2 전용, 기업 계정 전용.
 *
 * 브랜드메시지 템플릿의 `imageUrl` 은 아무 URL 이나 되는 게 아니라 **카카오가
 * 호스팅하는 URL** 이어야 한다. 그 URL 을 얻는 방법이 이 업로드뿐이다.
 *
 * @example
 * $uploaded = $sendgo->kakaoImages->upload('default', '/path/to/banner.jpg');
 *
 * $sendgo->brandTemplates->create([
 *     'kakaoSenderKey' => $kakaoSenderKey,
 *     'templateName'   => '여름 세일 안내',
 *     'templateType'   => 'FI',
 *     'imageUrl'       => $uploaded['data']['imageUrl'],
 * ]);
 */
class KakaoImageService
{
    /** 파일 하나를 올리고 URL 하나를 받는 유형. */
    public const SINGLE_TYPES = [
        'alimtalk',
        'alimtalk_highlight',
        'default',
        'wide',
        'wide_item_list_first',
    ];

    /** 파일 여러 개를 올리고 URL 목록을 받는 유형 => 최대 개수. */
    public const MULTI_TYPES = [
        'wide_item_list' => 4,
        'carousel_feed' => 10,
        'carousel_commerce' => 11,
    ];

    public function __construct(
        private HttpClient $http,
        private string $url,
        private string $apiVersion,
    ) {}

    /**
     * 업로드 가능한 유형과 제약 조회.
     *
     * @return array<string, mixed>
     */
    public function types(): array
    {
        return $this->http->get($this->endpoint('types'));
    }

    /**
     * 단일 이미지 업로드. jpg/png, 2MB 이하.
     *
     * @param  string  $type   {@see SINGLE_TYPES}
     * @param  string|\CURLFile  $image  파일 경로 또는 CURLFile
     * @return array<string, mixed>  `data.imageUrl` 에 카카오 호스팅 URL
     */
    public function upload(string $type, string|\CURLFile $image): array
    {
        return $this->http->postMultipart($this->endpoint($type), [], ['image' => $image]);
    }

    /**
     * 다중 이미지 업로드.
     *
     * @param  string  $type  {@see MULTI_TYPES}
     * @param  array<int, string|\CURLFile>  $images
     * @return array<string, mixed>
     */
    public function uploadMany(string $type, array $images): array
    {
        $files = [];
        foreach (array_values($images) as $index => $image) {
            $files["images[{$index}]"] = $image;
        }

        return $this->http->postMultipart($this->endpoint($type), [], $files);
    }

    private function endpoint(string $segment): string
    {
        return "{$this->url}/api/{$this->apiVersion}/kakao-images/".rawurlencode($segment);
    }
}
