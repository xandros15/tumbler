<?php

namespace Xandros15\Tumbler;


use Abraham\TwitterOAuth\TwitterOAuth;

final class Twitter extends Tumbler
{
    protected const SLEEP = [1200000, 1500000];
    /** @var TwitterOAuth */
    private $api;

    /**
     * Twitter constructor.
     *
     * @param array $consumer
     * @param array $token
     */
    public function __construct(array $consumer, array $token)
    {
        $this->api = new TwitterOAuth($consumer['key'], $consumer['secret'], $token['key'], $token['secret']);
    }

    /**
     * @param string $ident
     * @param string $directory
     */
    public function download(string $ident, string $directory): void
    {
        $directory = $this->createDirectory($directory);
        $query = [
            'count' => 200,
            'screen_name' => $ident,
            'exclude_replies' => 1,
            'include_rts' => 1,
        ];
        $i = 5;
        do {
            $response = $this->api->get('statuses/user_timeline', $query);

            foreach ($response as $post) {
                if ($this->hasImages($post)) {
                    $this->downloadImages($post, $directory);
                }
            }
            $query['since_id'] = end($response)->id - 200;
        } while (--$i);
    }

    private function hasImages($post): bool
    {
        return isset($post->extended_entities->media);
    }

    private function downloadImages($post, $directory): void
    {
        $name = strtotime($post->created_at);
        $suffix = 0;
        foreach ($post->extended_entities->media as $media) {
            $this->saveMedia($media->media_url, $directory . $name . '_' . $suffix++);
        }
    }
}
