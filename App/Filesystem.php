<?php


namespace Xandros15\Tumbler;


use RuntimeException;

class Filesystem
{
    /**
     * @param string $directory
     *
     * @return string
     * @throws RuntimeException
     */
    public static function createDirectory(string $directory): string
    {
        if ($realPath = realpath($directory)) {
            return $realPath . DIRECTORY_SEPARATOR;
        }

        if (!mkdir($directory, 0744, true)) {
            throw new RuntimeException('Can\'t create new directory: ' . $directory);
        }

        return realpath($directory) . DIRECTORY_SEPARATOR;
    }
}
