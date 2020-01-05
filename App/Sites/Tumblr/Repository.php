<?php

namespace Xandros15\Tumbler\Sites\Tumblr;


use Psr\Http\Message\ResponseInterface;
use Traversable;

final class Repository
{

    /** @var []Post */
    private $posts;
    /** @var  int */
    private $max;

    /**
     * Repository constructor.
     *
     * @param ResponseInterface $response
     */
    public function __construct(ResponseInterface $response)
    {
        $response = json_decode($response->getBody(), true)['response'];
        $this->max = $response['total_posts'] ?? 0;
        $this->posts = $this->setupPosts($response['posts'] ?? []);
    }

    /**
     * @param int $offset
     *
     * @return bool
     */
    public function isLast(int $offset): bool
    {
        return $this->max <= $offset;
    }

    /**
     * @return Traversable|Post[]
     */
    public function getPosts(): Traversable
    {
        return $this->posts;
    }

    /**
     * @param array $posts
     *
     * @return Traversable|Post[]
     */
    private function setupPosts(array $posts): Traversable
    {
        foreach ($posts as $post) {
            yield new Post($post);
        }
    }
}
