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
        $userName = '';
        while (1) {
            $response = $this->api->works($user_id, $page);
            Logger::info("Page: {$page}");

            foreach ($response['response'] as $work) {
                $userName = $work['user']['name'];
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
        Logger::info('Download images...');
        $name = Filesystem::cleanupName($userName);
        $directory = Filesystem::createDirectory($directory . $name);
        foreach ($works as $i => $work) {
            Logger::info('Work ' . ($i + 1) . '/' . $worksCount);
            $this->downloadImages($work, $directory);
        }
        Logger::info('Making info file...');
        $this->saveInfoFile($works, $directory);
        Logger::info('Done \o/.');
    }

    /**
     * @param Work $work
     * @param string $directory
     */
    private function downloadImages(Work $work, string $directory): void
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
     * @param Work[] $works
     * @param string $directory
     */
    private function saveInfoFile(array $works, string $directory)
    {
        $data = [];
        foreach ($works as $work) {
            $data[$work->getId()] = $work->getName();
        }
        $dump = Yaml::dump($data, 4, 4);
        file_put_contents($directory . 'info-' . date('Ymd') . '.yaml', $dump);
    }
}
