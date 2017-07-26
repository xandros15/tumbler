<?php

namespace Xandros15\Tumbler;


use Symfony\Component\DomCrawler\Crawler;

final class SC extends Tumbler
{
    protected const SLEEP = [1000000, 1500000];
    private const BASE_URL = 'https://chan.sankakucomplex.com';
    /** @var string */
    private $cookie;

    /**
     * SC constructor.
     *
     * @param string $cookie
     */
    public function __construct(string $cookie)
    {
        $this->cookie = $cookie;
    }

    /**
     * @param string $ident
     * @param string $directory
     */
    function download(string $ident, string $directory)
    {
        $url = self::BASE_URL . '?' . http_build_query(['tags' => $ident, 'page' => 10]);
        $directory = $this->createDirectory($directory);
        $lastId = 0;
        while (1) {
            $page = $this->fetch($url, ['headers' => ['Cookie' => $this->cookie]]);
            $list = new Crawler((string) $page->getBody());
            $thumbs = $list->filter('.content > div > .thumb');
            if ($thumbs->count() == 0) {
                break;
            }
            $galleryItem = 0;
            foreach ($thumbs as $thumb) {
                $thumb = new Crawler($thumb);
                $imagePageUrl = self::BASE_URL . $thumb->filter('a')->first()->attr('href');
                $imagePageRequest = $this->fetch($imagePageUrl, ['headers' => ['Cookie' => $this->cookie]]);
                $imagePage = new Crawler((string) $imagePageRequest->getBody());
                $imageUrl = $imagePage->filter('a#highres')->first()->attr('href');
                $name = strtotime($imagePage->filter('#stats a')->first()->attr('title'));
                if ($name == $lastId) {
                    $suffix = '_' . ++$galleryItem;
                } else {
                    $suffix = '';
                    $galleryItem = 0;
                }
                $lastId = $name;
                if (strpos($imageUrl, 'http') !== 0) {
                    $imageUrl = 'https://' . ltrim($imageUrl, '/');
                }
                $this->saveImage($imageUrl, $directory . $name . $suffix);
            }
            $nextPage = $list->filter('#paginator .pagination a')->last();
            if ($nextPage->html() != '&gt;&gt;') {
                //end
                break;
            }
            $url = self::BASE_URL . $nextPage->attr('href');
        }
    }
}
