<?php

namespace Xandros15\Tumbler;


use GuzzleHttp\{
    Client, Exception\ServerException, RequestOptions
};
use Monolog\Registry;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

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
    protected const DEFAULT_HEADERS = [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:51.0) Gecko/20100101 Firefox/51.0',
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language' => 'pl,en-US;q=0.7,en;q=0.3',
        'Connection' => 'keep-alive',
//            'Accept-Encoding' => 'gzip, deflate, br',
        'Upgrade-Insecure-Requests' => 1,
        'DNT' => 1,
    ];
    /** @const string */
    const OVERRIDE = 'override';

    /** @var Client */
    private $client;
    /** @var array */
    private $options = self::DEFAULT_OPTIONS;

    /**
     * Tumbler constructor.
     */
    public function __construct()
    {
        $this->client = new Client(static::DEFAULT_CLIENT_OPTIONS);
    }

    /**
     * @param string $ident
     * @param string $directory
     */
    abstract function download(string $ident, string $directory);

    /**
     * @param string $method
     * @param string $uri
     * @param array $options
     *
     * @return ResponseInterface
     */
    protected function fetch(string $method, string $uri, array $options = []): ResponseInterface
    {
        usleep($sleep = $lagSleep = random_int(min(static::SLEEP), max(static::SLEEP)));
        $options['headers'] = $this->prepareHeaders($options);
        $tries = 5;
        while (true) {
            try {
                $this->getLogger()->info("Trying connect ({$tries}): {$uri} | " . json_encode($options));
                $response = $this->client->request($method, $uri, $options);
                break;
            } catch (ServerException $exception) {
                if (!--$tries) {
                    throw $exception;
                } else {
                    $this->getLogger()->error($exception->getMessage());
                    usleep($lagSleep += $sleep);
                }
            }
        }

        if (!isset($response)) {
            throw new \RuntimeException('Missing response');
        }

        return $response;
    }

    /**
     * @param string $url
     * @param string $name
     * @param array $options
     */
    protected function saveImage(string $url, string $name, array $options = [])
    {
        $image = $this->fetch('get', $url, $options);
        $contentType = $image->getHeaderLine('content-type');
        $filename = $name . $this->getExtension($contentType);
        if (!file_exists($filename) || $this->options[self::OVERRIDE]) {
            file_put_contents($filename, (string) $image->getBody());
        }
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger(): LoggerInterface
    {
        return Registry::getInstance('global');
    }

    /**
     * @param string $contentType
     *
     * @return string
     */
    protected function getExtension(string $contentType): string
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
     * @param string $directory
     *
     *
     * @throws \RuntimeException
     * @return string
     */
    protected function createDirectory(string $directory): string
    {
        if ($realPath = realpath($directory)) {
            return $realPath . DIRECTORY_SEPARATOR;
        } else {
            if (!mkdir($directory, 744)) {
                throw new \RuntimeException('Can\'t create new directory: ' . $directory);
            }

            return realpath($directory) . DIRECTORY_SEPARATOR;
        }
    }

    /**
     * @param array $options
     *
     * @return array
     */
    private function prepareHeaders(array $options)
    {
        return array_merge(static::DEFAULT_HEADERS, $options['headers'] ?? []);
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge(static::DEFAULT_OPTIONS, $options);
    }
}
