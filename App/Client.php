<?php


namespace Xandros15\Tumbler;


use Exception;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

class Client
{
    private const DEFAULT_SLEEP = [500000, 800000];
    private const DEFAULT_CLIENT_OPTIONS = [
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

    /** @var array */
    private $headers = [];
    /** @var \Goutte\Client */
    private $client;

    public function __construct()
    {
        $goutte = new \Goutte\Client();
        $goutte->setClient(new \GuzzleHttp\Client(static::DEFAULT_CLIENT_OPTIONS));
        $this->client = $goutte;
    }

    /**
     * @param Form $form
     *
     * @return Crawler
     */
    public function sendForm(Form $form): Crawler
    {
        return $this->client->submit($form);
    }

    /**
     * @param string $uri
     * @param array $options
     *
     * @return Crawler
     */
    public function fetchHTML(string $uri, array $options = []): Crawler
    {
        $options['headers'] = $this->prepareHeaders($options['headers'] ?? []);
        $options['sleep'] = $options['sleep'] ?? self::DEFAULT_SLEEP;
        foreach ($options['headers'] as $name => $value) {
            $this->client->setHeader($name, $value);
        }
        $this->sleep($options['sleep']);
        Logger::debug('Connect: ' . $uri, $options);

        return $this->client->request('get', $uri, $options['query'] ?? []);
    }

    /**
     * @param string $uri
     * @param array $options
     *
     * @return ResponseInterface
     */
    public function fetch(string $uri, array $options = []): ResponseInterface
    {
        $options['sleep'] = $options['sleep'] ?? self::DEFAULT_SLEEP;
        $options['headers'] = $this->prepareHeaders($options['headers'] ?? []);
        $this->sleep($options['sleep']);
        Logger::debug('Connect: ' . $uri, $options);

        return $this->client->getClient()->request($options['method'] ?? 'get', $uri, $options);
    }

    /**
     * @param string $url
     * @param string $name
     * @param array $options
     */
    public function saveMedia(string $url, string $name, array $options = []): void
    {
        $image = $this->fetch($url, $options);
        $contentType = $image->getHeaderLine('content-type');
        $filename = $name . $this->resolveExtension($contentType);
        if (!file_exists($filename) || !empty($options['override'])) {
            file_put_contents($filename, (string) $image->getBody());
        }
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function setHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }

    /**
     * @param array $times
     */
    private function sleep(array $times): void
    {
        try {
            usleep(random_int(min($times), max($times)));
        } catch (Exception $exception) {
            usleep(max($times));
        }
    }

    /**
     * @param string $contentType
     *
     * @return string
     */
    private function resolveExtension(string $contentType): string
    {
        $list = [
            'video/mp4' => '.mp4',
            'image/jpeg' => '.jpg',
            'image/png' => '.png',
            'image/gif' => '.gif',
            'image/bmp' => '.bmp',
            'video/webm' => '.webm',
            'video/ogg' => '.ogg',
            'application/octet-stream' => '.jpg',//ugh hf
        ];

        if (isset($list[$contentType])) {
            return $list[$contentType];
        }

        throw new InvalidMimeTypeException($contentType);
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
