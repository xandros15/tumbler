<?php

namespace Xandros15\Tumbler;


use Xandros15\Tumbler\Tumblr\Repository;

final class Tumblr extends Tumbler
{
    private const BASE_URL = 'https://api.tumblr.com/v2/blog/{{blog_name}}.tumblr.com/posts';
    /** @var string */
    private $apiKey;

    /**
     * Tumblr constructor.
     *
     * @param string $apiKey
     */
    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @param string $blogName
     * @param string $directory
     */
    public function download(string $blogName, string $directory): void
    {
        $query = ['api_key' => $this->apiKey, 'type' => 'photo', 'offset' => 0, 'reblog_info' => 'true'];
        $directory = $this->createDirectory($directory);
        $url = $this->getBaseUrl($blogName);
        do {
            $response = $this->fetch($url, ['query' => $query]);
            $repository = new Repository($response);
            foreach ($repository->getPosts() as $post) {
                if ($post->isReblog()) {
                    continue;
                }
                if ($post->hasMedia()) {
                    foreach ($post->getMedia() as $media) {
                        $this->saveImage($media->getRawUri(), $directory . $media->getName());
                    }
                }
            }
            $query['offset'] += 20;
        } while (!$repository->isLast($query['offset']));
    }

    /**
     * @param string $blogName
     *
     * @return string
     */
    private function getBaseUrl(string $blogName): string
    {
        return strtr(self::BASE_URL, ['{{blog_name}}' => $blogName]);
    }
}
