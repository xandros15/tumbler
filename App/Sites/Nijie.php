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
    /** @var $works */
    private $works = [];

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
        $this->loadInfoFile($directory);
        $worksCount = 0;
        $imagesCount = 0;
        Logger::info('Fetching works...');
        $works = $this->api->getIllustrations($ident);
        Logger::info('Fetching images...');
        $images = [];
        foreach ($works as $work) {
            if (isset($this->works[$work->getId()])) {
                Logger::info('Skipped work id: ' . $work->getId());
                continue;
            }
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
        $this->saveInfoFile($ident, $works, $directory);
        Logger::info('Done \o/.');
    }

    /**
     * @param string $directory
     */
    private function loadInfoFile(string $directory)
    {
        $files = glob($directory . 'info-*.yaml');
        $last = '';
        foreach ($files as $file) {
            $last = $file;
        }
        if ($last) {
            $yaml = Yaml::parseFile($last);
            $this->works = $yaml['works'] ?? [];
        }
    }

    /**
     * @param string $ident
     * @param Work[] $works
     * @param string $directory
     */
    private function saveInfoFile(string $ident, array $works, string $directory)
    {
        Logger::info('Making info file...');
        $data = [];
        foreach ($works as $work) {
            $data[$work->getId()] = $work->getName();
        }
        $dump = Yaml::dump([
            'ident' => $ident,
            'works' => $data,
        ], 4, 4);
        file_put_contents($directory . 'info-' . date('Ymd') . '.yaml', $dump);
    }
}
