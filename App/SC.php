<?php

namespace Xandros15\Tumbler;


use Symfony\Component\DomCrawler\Crawler;

final class SC extends Tumbler
{
    protected const SLEEP = [1000000, 1500000];
    private const START_PAGE = 1;
    private const BASE_URL = 'http://chan.sankakucomplex.com';

    /**
     * SC constructor.
     *
     * @param string $cookie
     */
    public function __construct(string $cookie)
    {
        $this->setHeader('Cookie', $cookie);
    }

    /**
     * @param string $ident
     * @param string $directory
     */
    public function download(string $ident, string $directory)
    {
        $url = self::BASE_URL . '?' . http_build_query(['tags' => $ident, 'page' => self::START_PAGE]);
        $directory = $this->createDirectory($directory);
        $name = '';
        $index = 1;
        while ($url) {
            $page = $this->fetchHTML($url);
            foreach ($this->getImageList($page) as $thumb) {
                $imagePage = $this->getImagePage($thumb);
                $imageUrl = $this->getImageUrl($imagePage);
                $name = $this->getName($imagePage, $name, $index);
                $this->saveImage($imageUrl, $directory . $name);
            }
            $url = $this->getNextPage($page);
        }
    }

    private function getImageList(Crawler $page)
    {
        $thumbs = $page->filter('.content > div > .thumb');
        foreach ($thumbs as $thumb) {
            yield new Crawler($thumb, null, self::BASE_URL);
        }
    }

    private function getImagePage(Crawler $page)
    {
        $link = $page->filter('a')->first()->link();

        return $this->fetchHTML($link->getUri());
    }

    private function getImageUrl(Crawler $page): string
    {
        return $page->filter('a#highres')->first()->link()->getUri();
    }

    private function getName(Crawler $page, string $last, int &$index): string
    {
        $name = strtotime($page->filter('#stats a')->first()->attr('title'));
        if ($last == $name || $last == $name . '_' . $index) {
            $name .= '_' . ++$index;
        } else {
            $index = 1;
        }

        return $name;
    }

    /**
     * @param Crawler $page
     *
     * @return string
     */
    private function getNextPage(Crawler $page)
    {
        $pagination = $page->filter('#paginator .pagination a');
        if ($pagination->count() && $pagination->first()->html() == '&gt;&gt;') {
            return $pagination->first()->link()->getUri();
        }

        return '';
    }
}
