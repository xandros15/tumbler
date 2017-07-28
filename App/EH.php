<?php

namespace Xandros15\Tumbler;


use Symfony\Component\DomCrawler\Crawler;

final class EH extends Tumbler
{
    /**
     * EH constructor.
     *
     * @param string $cookie
     */
    public function __construct(string $cookie)
    {
        $this->setHeader('Cookie', $cookie);
    }

    /**
     * @param string $galleryUrl
     * @param string $directory
     */
    function download(string $galleryUrl, string $directory)
    {
        $directory = $this->createDirectory($directory);

        $gallery = $this->fetchHTML($galleryUrl);
        $url = $this->getGalleryFirstPage($gallery);
        while ($url) {
            $page = $this->fetchHTML($url);
            $this->saveImage($this->getImageUrl($page), $directory . $this->getName($page));
            $url = $this->getNextPage($page, $url);
        }
    }

    /**
     * @param Crawler $gallery
     *
     * @return string
     */
    private function getGalleryFirstPage(Crawler $gallery)
    {
        return $gallery->filter('#gdt a')->first()->link()->getUri();
    }

    /**
     * @param Crawler $page
     *
     * @return string
     */
    private function getImageUrl(Crawler $page): string
    {
        if ($page->filter('#i7 a')->count()) {
            return $page->filter('#i7 a')->attr('href');//(0)->getAttribute('href');
        }

        return $page->filter('#img')->attr('src');
    }

    /**
     * @param Crawler $page
     *
     * @return string
     */
    private function getName(Crawler $page): string
    {
        return pathinfo(explode(' ', $page->filter('#i2 > div')->getNode(1)->nodeValue, 2)[0], PATHINFO_FILENAME);
    }

    /**
     * @param Crawler $page
     * @param string $currentUrl
     *
     * @return string
     */
    private function getNextPage(Crawler $page, string $currentUrl): string
    {
        $next = $page->filter('#next')->attr('href');

        return $next != $currentUrl ? $next : '';
    }
}
