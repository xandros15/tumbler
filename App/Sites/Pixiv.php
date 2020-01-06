<?php

namespace Xandros15\Tumbler\Sites;


use Exception;
use Symfony\Component\Yaml\Yaml;
use Xandros15\Tumbler\Client;
use Xandros15\Tumbler\Filesystem;
use Xandros15\Tumbler\Logger;
use Xandros15\Tumbler\Sites\Pixiv\PixivClient;
use Xandros15\Tumbler\Sites\Pixiv\Work;

final class Pixiv implements SiteInterface
{
    private const START_PAGE = 1;
    private const HEADERS = [
        'Referer' => 'http://www.pixiv.net/',
    ];
    /** @var PixivClient */
    private $api;
    /** @var string */
    private $username;
    /** @var string */
    private $password;
    /** @var Client */
    private $client;

    /**
     * Pixiv constructor.
     *
     * @param string $username
     * @param string $password
     *
     * @throws Exception
     */
    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
        $this->client = new Client();
        $this->api = new PixivClient($this->client);
    }

    /**
     * @param string $user_id
     * @param string $directory
     *
     * @throws Exception
     */
    public function download(string $user_id, string $directory): void
    {
        $directory = Filesystem::createDirectory($directory);
        $page = self::START_PAGE;
        Logger::info('Login...');
        $this->api->loginByCredentials($this->username, $this->password);
        Logger::info('Fetching works...');
        $works = [];
        $worksCount = 0;
        $imagesCount = 0;
        while (1) {
            $response = $this->api->works($user_id, $page);
            Logger::info("Page: {$page}");

            foreach ($response['response'] as $work) {
                $work = new Work($work);
                $works[] = $work;
                $worksCount++;
                $imagesCount += $work->getPageCount();
            }
            if ($response['pagination']['pages'] < ++$page) {
                break;
            }
        }
        Logger::info('Total works: ' . $worksCount);
        Logger::info('Total images: ' . $imagesCount);
        Logger::info('Saving images...');
        foreach ($works as $i => $work) {
            Logger::info('Work ' . ($i + 1) . '/' . $worksCount);
            $this->saveWorkImages($directory, $work);
        }
        Logger::info('Making info file...');
        $this->saveInfoFile($directory, $works);
        Logger::info('Done \o/.');
    }

    /**
     * @param string $directory
     * @param Work $work
     */
    private function saveWorkImages(string $directory, Work $work): void
    {
        $images = $work->getImages();
        foreach ($images as $i => $image) {
            $name = $work->getId() . '_p' . $i;
            $name = Filesystem::cleanupName($name);
            $this->client->saveMedia($image, $directory . $name, ['headers' => self::HEADERS]);
            Logger::info('Image ' . ($i + 1) . '/' . $work->getPageCount());
        }
    }

    /**
     * @param string $directory
     * @param Work[] $works
     */
    private function saveInfoFile(string $directory, array $works)
    {
        $data = [];
        foreach ($works as $work) {
            $data[$work->getId()] = $work->getName();
        }
        $dump = Yaml::dump($data, 4, 4);
        file_put_contents($directory . 'info-' . date('Ymd') . '.yaml', $dump);
    }
}
