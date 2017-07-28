<?php

namespace Xandros15\Tumbler;


use Symfony\Component\DomCrawler\Crawler;

final class HF extends Tumbler
{
    private const BASE_URL = 'http://www.hentai-foundry.com';
    private const PICTURES_ENDPOINT = '/pictures/user/';

    /** @var string */
    private $username;
    /** @var string */
    private $password;

    /**
     * HF constructor.
     *
     * @param string $username
     * @param string $password
     */
    public function __construct(string $username = '', string $password = '')
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @param string $ident
     * @param string $directory
     */
    public function download(string $ident, string $directory)
    {
        $url = self::BASE_URL . self::PICTURES_ENDPOINT . $ident;
        $directory = $this->createDirectory($directory);
        $page = $this->fetchHTML($url);
        $page = $this->passRestrictionPage($page);
        if ($this->canSignup()) {
            $this->signup($page);
        }
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

    /**
     * @return bool
     */
    private function canSignup()
    {
        return $this->password && $this->username;
    }

    private function signup(Crawler $page)
    {
        $form = $page->selectButton('Login')->form();
        $form['LoginForm[username]'] = $this->username;
        $form['LoginForm[password]'] = $this->password;
        $this->getClient()->submit($form);
        $this->getLogger()->info('Authorize by form');
    }
}
