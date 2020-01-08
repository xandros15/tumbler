<?php

namespace Xandros15\Tumbler\Sites;


use Xandros15\Tumbler\Client;
use Xandros15\Tumbler\Filesystem;
use Xandros15\Tumbler\Logger;
use Xandros15\Tumbler\Sites\EH\API;

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
        $api = new API($this->client);
        Logger::info('Fetching gallery...');
        $gallery = $api->getGallery(self::BASE_URL . $gid);
        Logger::info('Total images: ' . $gallery->getImagesCount());
        Logger::info('Fetching images...');
        $firstUrl = $gallery->getFirstViewUrl();
        $images = [];
        $folderName = Filesystem::cleanupName($gallery->getName());
        $directory = Filesystem::createDirectory($directory . '/' . $folderName);
        foreach ($api->getImages($firstUrl) as $i => $image) {
            Logger::info('Image: ' . ($i + 1) . '/' . $gallery->getImagesCount());
            $name = Filesystem::cleanupName($image->getName());
            $images[] = ['url' => $image->getSource(), 'name' => $directory . $name];
        }

        Logger::info('Downloading images...');
        $done = 0;
        $this->client->saveBatchMedia($images, [
            'fulfilled' => function () use ($gallery, &$done) {
                Logger::info('Image ' . ++$done . '/' . $gallery->getImagesCount());
            },
        ]);

        Logger::info('Done \o/.');
    }
}
