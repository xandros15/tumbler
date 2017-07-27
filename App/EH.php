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
     * @param string $firstImageUrl
     * @param string $directory
     */
    function download(string $firstImageUrl, string $directory)
    {
        $pageUrl = $firstImageUrl;
        $directory = $this->createDirectory($directory);
        do {
            $request = $this->fetch($pageUrl);
            $page = new Crawler((string) $request->getBody());
            $url = $this->getImageUrl($page);
            $name = $this->getName($page);
            $this->saveImage($url, $directory . $name);
            $pageUrl = $this->getNextPage($page, $pageUrl);
        } while ($pageUrl);
    }

    /**
     * @param Crawler $page
     *
     * @return null|string
     */
    private function getImageUrl(Crawler $page):? string
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
    private function getNextPage(Crawler $page, string $currentUrl):? string
    {
        $next = $page->filter('#next')->attr('href');

        return $next != $currentUrl ? $next : '';
    }
}
