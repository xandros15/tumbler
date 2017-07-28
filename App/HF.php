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
        $page = $this->fetchHTML($url);
        $this->passRestrictionPage($page);
        while ($url) {
            $page = $this->fetchHTML($url);
            foreach ($this->getImageList($page, $url) as $thumb) {
                $imagePage = $this->getImagePage($thumb);
                $this->saveImage($this->getImageSrc($imagePage), $directory . $this->getImageName($imagePage));
            }

            $url = $this->getNextPage($page, $url);
        }
    }

    /**
     * @param Crawler $thumb
     *
     * @return Crawler
     */
    private function getImagePage(Crawler $thumb)
    {
        return $this->fetchHTML($thumb->link()->getUri());
    }

    /**
     * @param Crawler $page
     * @param $currentUri
     *
     * @return \Generator
     */
    private function getImageList(Crawler $page, $currentUri)
    {
        foreach ($page->filter('.thumbLink') as $thumb) {
            yield new Crawler($thumb, $currentUri);
        }
    }

    /**
     * @param Crawler $page
     * @param string $url
     *
     * @return string
     */
    private function getNextPage(Crawler $page, string $url): string
    {
        $link = $page->filter('.yiiPager .next a');

        return $link->count() && ($next = $link->first()->link()->getUri() != $url) ? $next : '';
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
     *
     * @return Crawler
     */
    private function passRestrictionPage(Crawler $page): Crawler
    {
        $link = $page->filter('#frontPage_link');

        return $link->count() ? $this->fetchHTML($link->first()->link()->getUri()) : $page;
    }
}
