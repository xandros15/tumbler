<?php

namespace Xandros15\Tumbler\Sites;


use Xandros15\Tumbler\Client;
use Xandros15\Tumbler\Filesystem;
use Xandros15\Tumbler\Sites\Tumblr\Post;
use Xandros15\Tumbler\Sites\Tumblr\Repository;

final class Tumblr implements SiteInterface
{
    private const BASE_URL = 'https://api.tumblr.com/v2/blog/{{blog_name}}.tumblr.com/posts';
    /** @var string */
    private $apiKey;
    /** @var Client */
    private $client;

    /**
     * Tumblr constructor.
     *
     * @param string $apiKey
     */
    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->client = new Client();
    }

    /**
     * @param string $blogName
     * @param string $directory
     */
    public function download(string $blogName, string $directory): void
    {
        $directory = Filesystem::createDirectory($directory);
        $uri = $this->getBaseUri($blogName);
        $this->downloadPhoto($uri, $directory);
        $this->downloadVideo($uri, $directory);
    }

    /**
     * @param string $uri
     * @param string $directory
     */
    private function downloadPhoto(string $uri, string $directory)
    {
        $query = ['api_key' => $this->apiKey, 'type' => Post::PHOTO, 'offset' => 0, 'reblog_info' => 'true'];
        do {
            $response = $this->client->fetch($uri, ['query' => $query]);
            $repository = new Repository($response);
            foreach ($repository->getPosts() as $post) {
                if ($post->isReblog()) {
                    continue;
                }
                if ($post->hasMedia()) {
                    foreach ($post->getMedia() as $media) {
                        $this->client->saveMedia($media->getRawUri(), $directory . $media->getName());
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
            $response = $this->client->fetch($uri, ['query' => $query]);
            $repository = new Repository($response);
            foreach ($repository->getPosts() as $post) {
                if ($post->isReblog()) {
                    continue;
                }
                if ($post->hasMedia()) {
                    foreach ($post->getMedia() as $media) {
                        $this->client->saveMedia($media->getRawUri(), $directory . $media->getName());
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
