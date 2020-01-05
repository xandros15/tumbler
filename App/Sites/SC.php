<?php

namespace Xandros15\Tumbler\Sites;


use Symfony\Component\DomCrawler\Crawler;
use Traversable;
use Xandros15\Tumbler\Client;
use Xandros15\Tumbler\Filesystem;

final class SC implements SiteInterface
{
    private const SLEEP = [1000000, 1500000];
    private const START_PAGE = 1;
    private const BASE_URL = 'http://chan.sankakucomplex.com';
    /** @var Client */
    private $client;

    /**
     * SC constructor.
     *
     * @param string $cookie
     */
    public function __construct(string $cookie)
    {
        $this->client = new Client();
        $this->client->setHeader('Cookie', $cookie);
    }

    /**
     * @param string $ident
     * @param string $directory
     */
    public function download(string $ident, string $directory): void
    {
        $url = self::BASE_URL . '?' . http_build_query(['tags' => $ident, 'page' => self::START_PAGE]);
        $directory = Filesystem::createDirectory($directory);
        $name = '';
        $index = 1;
        while ($url) {
            $page = $this->client->fetchHTML($url, ['sleep' => self::SLEEP]);
            foreach ($this->getImageList($page) as $thumb) {
                $imagePage = $this->getImagePage($thumb);
                $imageUrl = $this->getImageUrl($imagePage);
                $name = $this->getName($imagePage, $name, $index);
                $this->client->saveMedia($imageUrl, $directory . $name, ['sleep' => self::SLEEP]);
            }
            $url = $this->getNextPage($page);
        }
    }

    /**
     * @param Crawler $page
     *
     * @return Traversable
     */
    private function getImageList(Crawler $page): Traversable
    {
        $thumbs = $page->filter('.content > div > .thumb');
        foreach ($thumbs as $thumb) {
            yield new Crawler($thumb, null, self::BASE_URL);
        }
    }

    /**
     * @param Crawler $page
     *
     * @return Crawler
     */
    private function getImagePage(Crawler $page): Crawler
    {
        $link = $page->filter('a')->first()->link();

        return $this->client->fetchHTML($link->getUri(), ['sleep' => self::SLEEP]);
    }

    /**
     * @param Crawler $page
     *
     * @return string
     */
    private function getImageUrl(Crawler $page): string
    {
        return $page->filter('a#highres')->first()->link()->getUri();
    }

    /**
     * @param Crawler $page
     * @param string $last
     * @param int $index
     *
     * @return string
     */
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
    private function getNextPage(Crawler $page): string
    {
        $pagination = $page->filter('#paginator .pagination a');
        if ($pagination->count() && $pagination->first()->html() == '&gt;&gt;') {
            return $pagination->first()->link()->getUri();
        }

        return '';
    }
}
