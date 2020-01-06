<?php


namespace Xandros15\Tumbler\Sites\EH;


use Symfony\Component\DomCrawler\Crawler;

class Image
{
    /** @var string */
    private $source;
    /** @var string */
    private $name;

    public function __construct(Crawler $page)
    {
        $div = $page->filter('#i2 > .sn + div');
        $a = $page->filter('#i7 a');
        $this->name = $div->count() ? preg_replace('/(.+)\.\w{2,4}\s::.+::.+/', '${1}', $div->text()) : '';
        $this->source = $a->count() ? $a->attr('href') : $page->filter('#img')->attr('src');
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
