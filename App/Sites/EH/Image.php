<?php


namespace Xandros15\Tumbler\Sites\EH;


class Image
{
    /** @var string */
    private $source;
    /** @var string */
    private $name;

    public function __construct(string $name, string $source)
    {
        $this->name = $name;
        $this->source = $source;
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
