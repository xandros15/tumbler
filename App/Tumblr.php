<?php

namespace Xandros15\Tumbler;


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
        while (1) {
            $response = json_decode($this->fetch($url, ['query' => $query])->getBody());
            foreach ($response->response->posts as $post) {
                if ($this->isReblog($post)) {
                    continue;
                }
                if ($this->hasMedia($post)) {
                    $this->downloadMedia($post, $directory);
                }
            }
            $query['offset'] += 20;
            if ($query['offset'] > $response->response->total_posts) {
                //ends
                break;
            }
        }
    }

    /**
     * @param $post
     *
     * @return bool
     */
    private function hasMedia($post): bool
    {
        return $post->photos && count($post->photos) > 0;
    }

    /**
     * @param $post
     * @param string $directory
     */
    private function downloadMedia($post, string $directory): void
    {
        $name = $directory . strtotime($post->date);
        $counter = 0;
        foreach ($post->photos as $photo) {
            $this->saveImage($photo->original_size->url, $name . '_' . ++$counter);
        }
    }

    /**
     * @param $post
     *
     * @return bool
     */
    private function isReblog($post): bool
    {
        return isset($post->reblogged_root_name) || strpos($post->caption, 'blockquote') !== false;
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
