<?php

namespace Xandros15\Tumbler\Sites;


use Symfony\Component\DomCrawler\Crawler;
use Xandros15\Tumbler\Client;
use Xandros15\Tumbler\Filesystem;
use Xandros15\Tumbler\Logger;
use Xandros15\Tumbler\Sites\EH\Image;

final class EH implements SiteInterface
{
    private const BASE_URL = 'https://exhentai.org/g/';
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
     * @param string $gid
     * @param string $directory
     */
    public function download(string $gid, string $directory): void
    {
        Logger::info('Fetching gallery...');
        $gallery = $this->client->fetchHTML(self::BASE_URL . $gid);
        Logger::info('Fetching images...');
        $images = $this->getImages($gallery);
        $imagesCount = count($images);
        Logger::info('Total images: ' . $imagesCount);
        Logger::info('Downloading images...');
        $folderName = Filesystem::cleanupName($gallery->filter('#gn')->text());
        $directory = Filesystem::createDirectory($directory . '/' . $folderName);
        foreach ($images as $i => $image) {
            $name = Filesystem::cleanupName($image->getName());
            $this->client->saveMedia($image->getSource(), $directory . $name);
            Logger::info('Image ' . ($i + 1) . '/' . $imagesCount);
        }
        Logger::info('Done \o/.');
    }

    /**
     * @param Crawler $page
     * @param string $currentUrl
     *
     * @return string
     */
    private function getNextPageUrl(Crawler $page, string $currentUrl): string
    {
        $next = $page->filter('#next')->attr('href');

        return $next != $currentUrl ? $next : '';
    }

    /**
     * @param Crawler $gallery
     *
     * @return Image[]
     */
    private function getImages(Crawler $gallery): array
    {
        $images = [];
        $a = $gallery->filter('#gdt a');
        $next = $a->count() > 0 ? $a->attr('href') : '';
        $pageCount = 0;
        while ($next) {
            $page = $this->client->fetchHTML($next);
            $images[] = new Image($page);
            $next = $this->getNextPageUrl($page, $next);
            Logger::info('Page: ' . ++$pageCount);
        }

        return $images;
    }
}
