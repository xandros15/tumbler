<?php

namespace Xandros15\Tumbler\Sites;


use InvalidArgumentException;
use Symfony\Component\DomCrawler\Crawler;
use Xandros15\Tumbler\Client;
use Xandros15\Tumbler\Filesystem;

final class EH implements SiteInterface
{
    /** @var Client */
    private $client;

    /**
     * EH constructor.
     *
     * @param string $cookie
     */
    public function __construct(string $cookie)
    {
        $this->client = new Client();
        $this->client->setHeader('Cookie', $cookie);
    }

    /**
     * @param string $galleryUrl
     * @param string $directory
     */
    function download(string $galleryUrl, string $directory): void
    {
        $directory = Filesystem::createDirectory($directory);

        $gallery = $this->client->fetchHTML($galleryUrl);
        $url = $this->getGalleryFirstPage($gallery) ?: $galleryUrl;
        while ($url) {
            $page = $this->client->fetchHTML($url);
            $this->client->saveMedia($this->getImageUrl($page), $directory . $this->getName($page));
            $url = $this->getNextPage($page, $url);
        }
    }

    /**
     * @param Crawler $gallery
     *
     * @return string
     */
    private function getGalleryFirstPage(Crawler $gallery): string
    {
        try {
            return $gallery->filter('#gdt a')->first()->link()->getUri();
        } catch (InvalidArgumentException $e) {
            return '';
        }
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
