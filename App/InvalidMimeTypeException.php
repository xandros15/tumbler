<?php


namespace Xandros15\Tumbler;


use InvalidArgumentException;

class InvalidMimeTypeException extends InvalidArgumentException
{

    /**
     * InvalidMimeTypeException constructor.
     *
     * @param string $contentType
     */
    public function __construct(string $contentType)
    {
        parent::__construct("Unexpected mimetype: {$contentType}.");
    }
}
