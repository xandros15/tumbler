<?php

namespace Xandros15\Tumbler;


use Symfony\Component\DomCrawler\Crawler;

final class HF extends Tumbler
{
    private const BASE_URL = 'http://www.hentai-foundry.com';
    private const PICTURES_ENDPOINT = '/pictures/user/';

    /**
     * @param string $ident
     * @param string $directory
     */
    public function download(string $ident, string $directory)
    {
        $url = self::BASE_URL . self::PICTURES_ENDPOINT . $ident;
        $directory = $this->createDirectory($directory);
        $page = new Crawler((string) $this->fetch($url)->getBody());
        $this->passRestrictionPage($page);
        while (1) {
            $page = new Crawler((string) $this->fetch($url)->getBody());
            foreach ($this->getImageList($page) as $thumb) {
                $imagePage = $this->getImagePage($thumb);
                $this->saveImage($this->getImageSrc($imagePage), $directory . $this->getImageName($imagePage));
            }

            $next = $this->getNextPage($page);
            if (!$next || $next == $url) {
                break;
            } else {
                $url = $next;
            }
        }
    }

    /**
     * @param Crawler $thumb
     *
     * @return Crawler
     */
    private function getImagePage(Crawler $thumb)
    {
        return new Crawler((string) $this->fetch(self::BASE_URL . $thumb->attr('href'))->getBody());
    }

    /**
     * @param Crawler $page
     *
     * @return \Generator
     */
    private function getImageList(Crawler $page)
    {
        foreach ($page->filter('.thumbLink') as $thumb) {
            yield new Crawler($thumb);
        }
    }

    /**
     * @param Crawler $page
     *
     * @return null|string
     */
    private function getNextPage(Crawler $page):? string
    {
        $next = $page->filter('.yiiPager .next a');

        return $next->count() ? self::BASE_URL . $next->first()->attr('href') : null;
    }

    /**
     * @param Crawler $imagePage
     *
     * @return false|int
     */
    private function getImageName(Crawler $imagePage)
    {
        $time = $imagePage->filter('#yw0 time');

        if ($time->count()) {
            return strtotime($time->first()->attr('datetime'));
        }

        throw new \RuntimeException('Missing date at page');
    }

    /**
     * @param Crawler $image
     *
     * @return string
     */
    private function getImageSrc(Crawler $image)
    {
        /** @var $image Crawler */
        $image = $image->filter('#picBox img');
        if (!$image->count()) {
            return '';
        } else {
            $image = $image->first();
        }

        if ($onclick = $image->attr('onclick')) {
            list(, $src,) = explode('\'', $onclick, 3);
        } else {
            $src = $image->attr('src');
        }

        return strpos($src, '//') === 0 ? 'http://' . ltrim($src, '/') : $src;
    }

    /**
     * @param Crawler $page
     */
    private function passRestrictionPage(Crawler $page)
    {
        $link = $page->filter('#frontPage_link');
        if ($link->count()) {
            $this->fetch(self::BASE_URL . $link->first()->attr('href'));
        }
    }
}
