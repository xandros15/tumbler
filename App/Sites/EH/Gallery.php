<?php


namespace Xandros15\Tumbler\Sites\EH;


class Gallery
{
    /** @var string */
    private $name;
    /** @var int */
    private $imagesCount;
    /** @var string */
    private $firstViewUrl;

    /**
     * Gallery constructor.
     *
     * @param string $name
     * @param int $imagesCount
     * @param string $firstViewUrl
     */
    public function __construct(string $name, int $imagesCount, string $firstViewUrl)
    {
        $this->name = $name;
        $this->imagesCount = $imagesCount;
        $this->firstViewUrl = $firstViewUrl;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getImagesCount(): int
    {
        return $this->imagesCount;
    }

    /**
     * @return string
     */
    public function getFirstViewUrl(): string
    {
        return $this->firstViewUrl;
    }
}
