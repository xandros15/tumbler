<?php

namespace Xandros15\Tumbler;


use Xandros15\Tumbler\Tumblr\Post;
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
        $directory = $this->createDirectory($directory);
        $uri = $this->getBaseUri($blogName);
        $this->downloadPhoto($directory, $uri);
        $this->downloadVideo($directory, $uri);
    }

    /**
     * @param string $uri
     * @param string $directory
     */
    private function downloadPhoto(string $uri, string $directory)
    {
        $query = ['api_key' => $this->apiKey, 'type' => Post::PHOTO, 'offset' => 0, 'reblog_info' => 'true'];
        do {
            $response = $this->fetch($uri, ['query' => $query]);
            $repository = new Repository($response);
            foreach ($repository->getPosts() as $post) {
                if ($post->isReblog()) {
                    continue;
                }
                if ($post->hasMedia()) {
                    foreach ($post->getMedia() as $media) {
                        $this->saveMedia($media->getRawUri(), $directory . $media->getName());
                    }
                }
            }
            $query['offset'] += 20;
        } while (!$repository->isLast($query['offset']));
    }

    /**
     * @param string $uri
     * @param string $directory
     */
    private function downloadVideo(string $uri, string $directory)
    {
        $query = ['api_key' => $this->apiKey, 'type' => Post::VIDEO, 'offset' => 0, 'reblog_info' => 'true'];
        do {
            $response = $this->fetch($uri, ['query' => $query]);
            $repository = new Repository($response);
            foreach ($repository->getPosts() as $post) {
                if ($post->isReblog()) {
                    continue;
                }
                if ($post->hasMedia()) {
                    foreach ($post->getMedia() as $media) {
                        $this->saveMedia($media->getRawUri(), $directory . $media->getName());
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
    private function getBaseUri(string $blogName): string
    {
        return strtr(self::BASE_URL, ['{{blog_name}}' => $blogName]);
    }
}
