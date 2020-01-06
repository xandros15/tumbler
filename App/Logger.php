<?php


namespace Xandros15\Tumbler;


use Monolog\Registry;

/**
 * @method static void emergency (string $message, array $context = [])
 * @method static void alert (string $message, array $context = [])
 * @method static void critical (string $message, array $context = [])
 * @method static void error (string $message, array $context = [])
 * @method static void warning (string $message, array $context = [])
 * @method static void notice (string $message, array $context = [])
 * @method static void info (string $message, array $context = [])
 * @method static void debug (string $message, array $context = [])
 * @method static void log (mixed $level, string $message, array $context = [])
 */
class Logger
{
    const INSTANCE_NAME = 'global';

    public static function __callStatic($name, $arguments)
    {
        Registry::getInstance(self::INSTANCE_NAME)->{$name}(...$arguments);
    }
}
