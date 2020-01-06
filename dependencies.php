<?php

use Monolog\{ErrorHandler, Formatter\LineFormatter, Handler\StreamHandler, Logger, Registry};
use Pimple\Container;
use Symfony\Component\Yaml\Yaml;
use Xandros15\Tumbler\Sites\{EH, H2R, HF, Nijie, Pixiv, SC, SiteInterface, Tumblr};

$container = new Container();

$container['logger'] = function (): Monolog\Logger {
    $logger = new Logger('global');
// Line formatter without empty brackets in the end
    $formatter = new LineFormatter(null, null, false, true);
// Debug level handler
    $debugHandler = new StreamHandler('debug.log', Logger::DEBUG);
    $debugHandler->setFormatter($formatter);
// Error level handler
    $errorHandler = new StreamHandler('error.log', Logger::ERROR);
    $errorHandler->setFormatter($formatter);

    $stdoutHandler = new StreamHandler('php://output', Logger::ERROR);
    $stdoutHandler->setFormatter($formatter);

    $logger->pushHandler($errorHandler);
    $logger->pushHandler($debugHandler);
    $logger->pushHandler($stdoutHandler);

    return $logger;
};

$container['logger_info'] = function (): Monolog\Logger {
    $logger = new Logger('info');
// Line formatter without empty brackets in the end
    $formatter = new LineFormatter(null, null, false, true);

    $stdoutHandler = new StreamHandler('php://output', Logger::INFO);
    $stdoutHandler->setFormatter($formatter);

    $logger->pushHandler($stdoutHandler);

    return $logger;
};

$container['config'] = function (): array {
    return Yaml::parseFile(__DIR__ . '/config.yaml');
};

$container['pixiv'] = function (Container $container): SiteInterface {
    return new Pixiv(
        $container['config']['pixiv']['username'],
        $container['config']['pixiv']['password']
    );
};

$container['tumblr'] = function (Container $container): SiteInterface {
    return new Tumblr($container['config']['tumblr']['api_key']);
};

$container['sc'] = function (Container $container): SiteInterface {
    return new SC($container['config']['sc']['cookie']);
};

$container['eh'] = function (Container $container): SiteInterface {
    return new EH($container['config']['eh']['cookie']);
};

$container['hf'] = function (Container $container): SiteInterface {
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

$container['h2r'] = function (): SiteInterface {
    return new H2R();
};

$container['nijie'] = function (Container $container): SiteInterface {
    return new Nijie(
        $container['config']['nijie']['email'],
        $container['config']['nijie']['password']
    );
};

Registry::addLogger($container->offsetGet('logger'));
Registry::addLogger($container->offsetGet('logger_info'));
ErrorHandler::register($container->offsetGet('logger'));

return $container;
