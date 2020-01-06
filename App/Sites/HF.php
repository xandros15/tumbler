<?php

namespace Xandros15\Tumbler\Sites;


use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;
use Traversable;
use Xandros15\Tumbler\Client;
use Xandros15\Tumbler\Filesystem;
use Xandros15\Tumbler\Logger;

final class HF implements SiteInterface
{
    private const BASE_URL = 'http://www.hentai-foundry.com';
    private const PICTURES_ENDPOINT = '/pictures/user/';

    /** @var string */
    private $username;
    /** @var string */
    private $password;
    /** @var Client */
    private $client;

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

        $this->client = new Client();
    }

    /**
     * @param string $ident
     * @param string $directory
     */
    public function download(string $ident, string $directory): void
    {
        $url = self::BASE_URL . self::PICTURES_ENDPOINT . $ident;
        $directory = Filesystem::createDirectory($directory);
        $page = $this->client->fetchHTML($url);
        $page = $this->passRestrictionPage($page);
        if ($this->canSignup()) {
            $this->signup($page);
        }
        //fetch pages
        Logger::info('Fetching pages...');
        $pageCount = 0;
        $imagesCount = 0;
        $imagePages = [];
        while ($url) {
            $pageCount++;
            $page = $this->client->fetchHTML($url);
            Logger::info("Page: $pageCount.");
            foreach ($this->getImageList($page, $url) as $thumb) {
                $imagesCount++;
                $imagePages[] = $thumb;
            }

            $url = $this->getNextPage($page);
        }

        Logger::info('Download Images...');
        $downloaded = 0;
        foreach ($imagePages as $page) {
            $downloaded++;
            Logger::info("Image {$downloaded}/{$imagesCount}");
            $imagePage = $this->getImagePage($page);
            $this->client->saveMedia($this->getImageSrc($imagePage), $directory . $this->getImageName($imagePage));
        }
    }

    /**
     * @param Crawler $thumb
     *
     * @return Crawler
     */
    private function getImagePage(Crawler $thumb): Crawler
    {
        return $this->client->fetchHTML($thumb->link()->getUri());
    }

    /**
     * @param Crawler $page
     * @param string $currentUri
     *
     * @return Traversable
     */
    private function getImageList(Crawler $page, string $currentUri): Traversable
    {
        foreach ($page->filter('.thumbLink') as $thumb) {
            yield new Crawler($thumb, $currentUri);
        }
    }

    /**
     * @param Crawler $page
     *
     * @return string
     */
    private function getNextPage(Crawler $page): string
    {
        $link = $page->filter('.yiiPager .next:not(.hidden) a');

        return $link->count() ? $link->first()->link()->getUri() : '';
    }

    /**
     * @param Crawler $imagePage
     *
     * @return string
     */
    private function getImageName(Crawler $imagePage): string
    {
        $time = $imagePage->filter('#yw0 time');
        $title = $imagePage->filter('#picBox .titleSemantic');

        if ($time->count() && $title->count()) {
            $timestamp = strtotime($time->first()->attr('datetime'));
            $name = Filesystem::cleanupName($title->first()->text());

            return $timestamp . '_' . $name;
        }

        throw new RuntimeException('Missing date at page');
    }

    /**
     * @param Crawler $image
     *
     * @return string
     */
    private function getImageSrc(Crawler $image): string
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

        return $link->count() ? $this->client->fetchHTML($link->first()->link()->getUri()) : $page;
    }

    /**
     * @return bool
     */
    private function canSignup(): bool
    {
        return $this->password && $this->username;
    }

    /**
     * @param Crawler $page
     */
    private function signup(Crawler $page): void
    {
        $form = $page->selectButton('Login')->form();
        $form['LoginForm[username]'] = $this->username;
        $form['LoginForm[password]'] = $this->password;
        $this->client->sendForm($form);
        Logger::info('Authorize by form.');
    }
}
