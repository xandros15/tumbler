<?php

namespace Xandros15\Tumbler\Sites;


use Symfony\Component\Yaml\Yaml;
use Xandros15\Tumbler\Client;
use Xandros15\Tumbler\Filesystem;
use Xandros15\Tumbler\Logger;
use Xandros15\Tumbler\Sites\Nijie\API;
use Xandros15\Tumbler\Sites\Nijie\Work;
use Xandros15\Tumbler\UnauthorizedException;

final class Nijie implements SiteInterface
{
    /** @var string */
    private $email;
    /** @var string */
    private $password;
    /** @var API */
    private $api;
    /** @var Client */
    private $client;

    /**
     * HF constructor.
     *
     * @param string $email
     * @param string $password
     */
    public function __construct(string $email, string $password)
    {
        $this->email = $email;
        $this->password = $password;
        $this->client = new Client();
        $this->api = new API($this->client);
    }

    /**
     * @param string $ident
     * @param string $directory
     *
     * @throws UnauthorizedException
     */
    public function download(string $ident, string $directory): void
    {
        $this->api->signup($this->email, $this->password);
        Logger::info('Authorize by form.');
        $name = $this->api->getIllustratorName($ident);
        $name = Filesystem::cleanupName($name);
        $directory = Filesystem::createDirectory($directory . '/' . $name);
        $worksCount = 0;
        $imagesCount = 0;
        Logger::info('Fetching works...');
        $works = $this->api->getIllustrations($ident);
        Logger::info('Fetching images...');
        $images = [];
        foreach ($works as $work) {
            Logger::info('Work id: ' . $work->getId());
            foreach ($this->api->fetchImagesWork($work) as $i => $url) {
                $name = $work->getId() . '_p' . $i;
                $name = Filesystem::cleanupName($name);
                $images[] = ['url' => $url, 'name' => $directory . '/' . $name];
                $imagesCount++;
            }
            $worksCount++;
        }
        Logger::info('Total works: ' . $worksCount);
        Logger::info('Total images: ' . $imagesCount);
        Logger::info('Download images...');
        $done = 0;
        $this->client->saveBatchMedia($images, [
            'fulfilled' => function () use ($imagesCount, &$done) {
                Logger::info('Image ' . ++$done . '/' . $imagesCount);
            },
        ]);
        $this->saveInfoFile($works, $directory);
        Logger::info('Done \o/.');
    }

    /**
     * @param Work[] $works
     * @param string $directory
     */
    private function saveInfoFile(array $works, string $directory)
    {
        Logger::info('Making info file...');
        $data = [];
        foreach ($works as $work) {
            $data[$work->getId()] = $work->getName();
        }
        $dump = Yaml::dump($data, 4, 4);
        file_put_contents($directory . 'info-' . date('Ymd') . '.yaml', $dump);
    }
}
