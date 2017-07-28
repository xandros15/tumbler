<?php

namespace Xandros15\Tumbler\Tumblr;


use GuzzleHttp\Psr7\Uri;

final class Media
{
    /** @var string */
    private $name;
    /** @var string */
    private $uri;

    /**
     * Media constructor.
     *
     * @param string $name
     * @param string $uri
     */
    public function __construct(string $name, string $uri)
    {
        $this->name = $name;
        $this->uri = $uri;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getRawUri(): string
    {
        return $this->uri;
    }

    /**
     * @return Uri
     */
    public function getUri(): Uri
    {
        return new Uri($this->uri);
    }

}