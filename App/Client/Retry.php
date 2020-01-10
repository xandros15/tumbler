<?php


namespace Xandros15\Tumbler\Client;


use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Xandros15\Tumbler\Logger;

class Retry
{
    const MAX_RETRIES = 5;

    /**
     * @param $retries
     * @param Request $request
     * @param Response|null $response
     * @param RequestException|null $exception
     *
     * @return bool
     */
    public function __invoke($retries, Request $request, Response $response = null, RequestException $exception = null)
    {
        if ($exception instanceof ConnectException || ($response && $response->getStatusCode() >= 500)) {
            Logger::info("Connection failed.");
            if ($retries < self::MAX_RETRIES) {
                Logger::info("Retry " . $retries . '/' . self::MAX_RETRIES);

                return false;
            }
            Logger::info("Extended max retries.");
        }

        return false;
    }
}
