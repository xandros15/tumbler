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
    public function download(string $blogName, string $directory)
    {
        $query = ['api_key' => $this->apiKey, 'type' => 'photo', 'offset' => 0, 'reblog_info' => 'true'];
        $directory = $this->createDirectory($directory);
        $url = $this->getBaseUrl($blogName);
        while (true) {
            $response = json_decode($this->fetch('get', $url, ['query' => $query])->getBody());
            if ($response->meta->status != 200) {
                throw new \RuntimeException(
                    'Got http error: ' .
                    $response->meta->msg . ' url: ' . $url,
                    $response->meta->status
                );
            }
            foreach ($response->response->posts as $post) {
                if ($this->isReblog($post)) {
                    continue;
                }
                $name = $directory . strtotime($post->date);
                if (count($post->photos) > 1) {
                    //gallery
                    $counter = 0;
                    foreach ($post->photos as $photo) {
                        $this->saveImage($photo->original_size->url, $name . '_' . ++$counter);
                    }
                } else {
                    $photo = reset($post->photos);
                    $this->saveImage($photo->original_size->url, $name);
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
    private function isReblog($post)
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
