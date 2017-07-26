<?php

use Monolog\{
    ErrorHandler, Formatter\LineFormatter, Handler\StreamHandler, Logger, Registry
};
use Pimple\Container;
use Xandros15\Tumbler\{
    EH, Pixiv, Tumblr
};

require_once 'vendor/autoload.php';

$container = new Container();

$container['logger'] = function (): Monolog\Logger {
    $logger = new Logger('global');
// Line formatter without empty brackets in the end
    $formatter = new LineFormatter(null, null, false, true);
// Debug level handler
    $debugHandler = new StreamHandler('debug.log', Logger::INFO);
    $debugHandler->setFormatter($formatter);
// Error level handler
    $errorHandler = new StreamHandler('error.log', Logger::ERROR);
    $errorHandler->setFormatter($formatter);
    // STDOUT
    $stdoutHandler = new StreamHandler('php://output', Logger::INFO);
    $stdoutHandler->setFormatter($formatter);

    $logger->pushHandler($errorHandler);
    $logger->pushHandler($debugHandler);
    $logger->pushHandler($stdoutHandler);

    return $logger;
};

$container['config'] = function (): array {
    return require_once __DIR__ . '/config.php';
};

$container['pixiv'] = function (Container $container): Pixiv {
    return new Pixiv(
        $container['config']['pixiv']['username'],
        $container['config']['pixiv']['password']
    );
};

$container['tumblr'] = function (Container $container): Tumblr {
    return new Tumblr($container['config']['tumblr']['api_key']);
};

$container['eh'] = function (Container $container): EH {
    return new EH($container['config']['eh']['cookie']);
};

Registry::addLogger($container->offsetGet('logger'));
ErrorHandler::register($container->offsetGet('logger'));

$name = '';

//$container->offsetGet('pixiv')->download($name, __DIR__ . '/tmp/' . $name);
//$container->offsetGet('tumblr')->download($name, __DIR__ . '/tmp/' . $name);
//$container->offsetGet('eh')->download($name, __DIR__ . '/tmp/' . $name);