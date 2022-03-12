<?php

use Monolog\{ErrorHandler, Formatter\LineFormatter, Handler\StreamHandler, Logger, Registry};
use Pimple\{Container, Psr11\Container as Psr11Container};
use Symfony\Component\Yaml\Yaml;
use Xandros15\Tumbler\Logger as StaticLogger;
use Xandros15\Tumbler\Sites\{EH, H2R, HF, Nijie, SC, SiteInterface, Tumblr};

$container = new Container();

$container['logger'] = function (): Monolog\Logger {
    $logger = new Logger(StaticLogger::INSTANCE_NAME);
// Line formatter without empty brackets in the end
    $formatter = new LineFormatter(null, null, false, true);
// Debug level handler
    $debugHandler = new StreamHandler(__DIR__ . '/tmp/debug.log', Logger::DEBUG);
    $debugHandler->setFormatter($formatter);
// Error level handler
    $errorHandler = new StreamHandler(__DIR__ . '/tmp/error.log', Logger::ERROR);
    $errorHandler->setFormatter($formatter);

    $formatter = new LineFormatter("[%datetime%] %message% %context% %extra%\n", 'H:i:s', true, true);
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
ErrorHandler::register($container->offsetGet('logger'));

return new Psr11Container($container);
