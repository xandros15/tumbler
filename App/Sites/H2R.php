<?php


namespace Xandros15\Tumbler\Sites;


use DOMElement;
use stdClass;
use Xandros15\Tumbler\Client;
use Xandros15\Tumbler\Filesystem;
use Xandros15\Tumbler\Logger;

final class H2R implements SiteInterface
{
    private const BASE_URI = 'https://hentai2read.com/';
    private const BASE_URI_CDN = 'https://static.hentaicdn.com/hentai';
    /** @var Client */
    private $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * @param string $ident
     * @param string $directory
     */
    public function download(string $ident, string $directory): void
    {
        $directory = Filesystem::createDirectory($directory);
        $mainPage = $this->client->fetchHTML(self::BASE_URI . $ident . '/1/');
        foreach ($mainPage->filter('script') as $node) {
            /** @var $node DOMElement */
            if (strpos($node->textContent, 'var gData') !== false) {
                $script = $this->parseScript($node->textContent);
                $total = count($script->images);
                foreach ($script->images as $key => $image) {
                    Logger::info('Image ' . ($key + 1) . '/' . $total);
                    $this->client->saveMedia(
                        self::BASE_URI_CDN . $image,
                        sprintf("%s/%03d", $directory, $key)
                    );
                }
                break;
            }
        }
    }

    /**
     * @param string $script
     *
     * @return null|stdClass
     */
    private function parseScript(string $script): ?stdClass
    {
        $script = str_replace(["\n", 'var gData = ', ' '], '', $script);
        $script = str_replace(['\'', '};',], ['"', '}'], $script);
        $script = trim($script);

        return json_decode($script);
    }
}
