<?php

use Monolog\{ErrorHandler, Formatter\LineFormatter, Handler\StreamHandler, Logger, Registry};
use Pimple\Container;
use Symfony\Component\Yaml\Yaml;
use Xandros15\Tumbler\{EH, H2R, HF, Pixiv, SC, Tumblr};

$container = new Container();

$container['logger'] = function (): Monolog\Logger {
    $logger = new Logger('global' . microtime());
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
    return Yaml::parseFile(__DIR__ . '/config.yaml');
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

$container['sc'] = function (Container $container): SC {
    return new SC($container['config']['sc']['cookie']);
};

$container['eh'] = function (Container $container): EH {
    return new EH($container['config']['eh']['cookie']);
};

$container['hf'] = function (Container $container): HF {
    return new HF(
        $container['config']['hf']['username'],
        $container['config']['hf']['password']
    );
};

$container['mail'] = function (Container $container) {
    $config = $container['config']['mailer'];
    $smtp = new Swift_SmtpTransport($config['host'], $config['port'], $config['security']);
    $smtp->setPassword($config['password']);
    $smtp->setUsername($config['login']);

    return new Swift_Mailer($smtp);
};

$container['h2r'] = function (): H2R {
    return new H2R();
};

Registry::addLogger($container->offsetGet('logger'));
ErrorHandler::register($container->offsetGet('logger'));

return $container;