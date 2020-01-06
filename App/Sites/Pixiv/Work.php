<?php


namespace Xandros15\Tumbler\Sites\Pixiv;


final class Work
{
    /** @var int */
    private $id;
    /** @var string */
    private $name;
    /** @var int */
    private $pageCount;
    /** @var array */
    private $images;

    public function __construct(array $work)
    {
        $this->id = (int) $work['id'];
        $this->name = (string) $work['title'];
        $this->pageCount = (int) $work['page_count'];
        $this->images = $this->prepareImages($work['image_urls']['large'], $work['page_count']);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
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
    public function getPageCount(): int
    {
        return $this->pageCount;
    }

    /**
     * @return array
     */
    public function getImages(): array
    {
        return $this->images;
    }

    /**
     * @param string $url
     * @param int $pageCount
     *
     * @return array
     */
    private function prepareImages(string $url, int $pageCount): array
    {
        $images = [];
        if ($pageCount === 1) {
            $images[] = $url;
        } else {
            for ($index = 0; $index < $pageCount; $index++) {
                $images[] = preg_replace('/_p\d{1,2}./', '_p' . $index . '.', $url);
            }
        }

        return $images;
    }
}
