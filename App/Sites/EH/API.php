<?php


namespace Xandros15\Tumbler\Sites\EH;


use Symfony\Component\DomCrawler\Crawler;
use Xandros15\Tumbler\Client;

class API
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $baseUrl
     *
     * @return Gallery
     */
    public function getGallery(string $baseUrl): Gallery
    {
        $main = $this->client->fetchHTML($baseUrl);
        $name = $main->filter('#gn')->text();
        $props = $main->filter('#gdd')->text();
        $imagesCount = preg_replace('/^.+Length:(\d{1,4}).+$/', '${1}', $props);
        $a = $main->filter('#gdt a');
        $next = $a->count() > 0 ? $a->attr('href') : '';

        return new Gallery($name, $imagesCount, $next);
    }

    /**
     * @param string $viewUrl
     *
     * @return Image[]|iterable
     */
    public function getImages(string $viewUrl)
    {
        while ($viewUrl) {
            $page = $this->client->fetchHTML($viewUrl);
            yield $this->parseImage($page);
            $next = $page->filter('#next')->attr('href');
            $viewUrl = $next != $viewUrl ? $next : false;
        }
    }

    /**
     * @param Crawler $page
     *
     * @return Image
     */
    private function parseImage(Crawler $page): Image
    {
        $div = $page->filter('#i2 > .sn + div');
        $a = $page->filter('#i7 a');
        $name = $div->count() ? preg_replace('/(.+)\.\w{2,4}\s::.+::.+/', '${1}', $div->text()) : '';
        $source = $a->count() ? $a->attr('href') : $page->filter('#img')->attr('src');

        return new Image($name, $source);
    }
}
