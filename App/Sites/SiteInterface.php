<?php

namespace Xandros15\Tumbler\Sites;


interface SiteInterface
{
    /**
     * @param string $ident
     * @param string $directory
     */
    public function download(string $ident, string $directory): void;
}

