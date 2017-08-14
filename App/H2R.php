<?php


namespace Xandros15\Tumbler;


class H2R extends Tumbler
{
    private const BASE_URI = 'https://hentai2read.com/';
    private const BASE_URI_CDN = 'https://static.hentaicdn.com/hentai';


    /**
     * @param string $ident
     * @param string $directory
     */
    public function download(string $ident, string $directory): void
    {
        $directory = $this->createDirectory($directory);
        $mainPage = $this->fetchHTML(self::BASE_URI . $ident . '/1/');
        foreach ($mainPage->filter('script') as $node) {
            /** @var $node \DOMElement */
            if (strpos($node->textContent, 'var gData') !== false) {
                $script = $this->parseScript($node->textContent);
                foreach ($script->images as $key => $image) {
                    $this->saveMedia(
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
     * @return null|\stdClass
     */
    private function parseScript(string $script):? \stdClass
    {
        $script = str_replace(["\n", 'var gData = ', ' '], '', $script);
        $script = str_replace(['\'', '};',], ['"', '}'], $script);
        $script = trim($script);

        return json_decode($script);
    }
}
