<?php

namespace Xandros15\Tumbler;


use Goutte\Client;
use GuzzleHttp\RequestOptions;
use Monolog\Registry;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

abstract class Tumbler
{
    protected const SLEEP = [500000, 800000];
    private const DEFAULT_OPTIONS = [
        self::OVERRIDE => false,
    ];
    protected const DEFAULT_CLIENT_OPTIONS = [
        RequestOptions::TIMEOUT => 0,
        RequestOptions::ALLOW_REDIRECTS => true,
        RequestOptions::COOKIES => true,
    ];

    private const DEFAULT_HEADERS = [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:57.0) Gecko/20100101 Firefox/57.0',
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language' => 'en-US,en;q=0.5',
        'Pragma' => 'no-cache',
        'Connection' => 'keep-alive',
        'Cache-Control' => 'no-cache',
        'Accept-Encoding' => 'gzip, deflate',
        'Upgrade-Insecure-Requests' => 1,
        'DNT' => 1,
    ];
    const OVERRIDE = 'override';

    /** @var array */
    protected $headers = [];

    /** @var Client */
    private $client;
    /** @var array */
    private $options = self::DEFAULT_OPTIONS;

    /**
     * @param string $ident
     * @param string $directory
     */
    abstract public function download(string $ident, string $directory): void;

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge(static::DEFAULT_OPTIONS, $options);
    }

    /**
     * @param string $uri
     * @param array $options
     *
     * @return Crawler
     */
    protected function fetchHTML(string $uri, array $options = []): Crawler
    {
        $options['headers'] = $this->prepareHeaders($options['headers'] ?? []);
        $client = $this->getClient();
        foreach ($options['headers'] as $name => $value) {
            $client->setHeader($name, $value);
        }
        usleep(random_int(min(static::SLEEP), max(static::SLEEP)));
        $this->getLogger()->info("Connect: {$uri} | " . json_encode($options));

        return $client->request('get', $uri, $options['query'] ?? []);
    }

    /**
     * @param string $uri
     * @param array $options
     *
     * @return ResponseInterface
     */
    protected function fetch(string $uri, array $options = []): ResponseInterface
    {
        $options['headers'] = $this->prepareHeaders($options['headers'] ?? []);
        usleep(random_int(min(static::SLEEP), max(static::SLEEP)));
        $this->getLogger()->info("Connect: {$uri} | " . json_encode($options));

        return $this->getClient()->getClient()->request('get', $uri, $options);
    }

    /**
     * @param string $url
     * @param string $name
     * @param array $options
     */
    protected function saveMedia(string $url, string $name, array $options = []): void
    {
        $image = $this->fetch($url, $options);
        $contentType = $image->getHeaderLine('content-type');
        $filename = $name . $this->getExtension($contentType);
        if (!file_exists($filename) || $this->options[self::OVERRIDE]) {
            file_put_contents($filename, (string) $image->getBody());
        }
    }

    /**
     * @return Client
     */
    protected function getClient(): Client
    {
        if (!$this->client instanceof Client) {
            $this->client = new Client();
            $this->client->setClient(new \GuzzleHttp\Client(static::DEFAULT_CLIENT_OPTIONS));
        }

        return $this->client;
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger(): LoggerInterface
    {
        return Registry::getInstance('global');
    }

    /**
     * @param string $directory
     *
     * @return string
     * @throws RuntimeException
     */
    protected function createDirectory(string $directory): string
    {
        if ($realPath = realpath($directory)) {
            return $realPath . DIRECTORY_SEPARATOR;
        }

        if (!mkdir($directory, 0744, true)) {
            throw new RuntimeException('Can\'t create new directory: ' . $directory);
        }

        return realpath($directory) . DIRECTORY_SEPARATOR;
    }

    /**
     * @param string $name
     * @param string $value
     */
    protected function setHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }

    /**
     * @param string $contentType
     *
     * @return string
     */
    private function getExtension(string $contentType): string
    {
        $list = [
            'video/mp4' => '.mp4',
            'image/jpeg' => '.jpg',
            'image/png' => '.png',
            'image/gif' => '.gif',
            'image/bmp' => '.bmp',
            'video/webm' => '.webm',
            'video/ogg' => '.ogg',
        ];

        return $list[$contentType] ?? '';
    }

    /**
     * @param array $headers
     *
     * @return array
     */
    private function prepareHeaders(array $headers): array
    {
        return array_merge(self::DEFAULT_HEADERS, $this->headers, $headers);
    }
}
