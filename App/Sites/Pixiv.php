<?php

namespace Xandros15\Tumbler\Sites;


use Exception;
use Monolog\Registry;
use RuntimeException;
use Xandros15\Tumbler\Client;
use Xandros15\Tumbler\Filesystem;
use Xandros15\Tumbler\Sites\Pixiv\PixivClient;

final class Pixiv implements SiteInterface
{
    private const AUTH_ERROR = 'The access token provided is invalid.';
    private const START_PAGE = 1;
    private const HEADERS = [
        'Referer' => 'http://www.pixiv.net/',
    ];
    /** @var PixivClient */
    private $api;
    /** @var  string */
    private $refreshToken;
    /** @var string */
    private $username;
    /** @var string */
    private $password;
    /** @var Client */
    private $client;

    /**
     * Pixiv constructor.
     *
     * @param string $username
     * @param string $password
     *
     * @throws Exception
     */
    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
        $this->client = new Client();
        $this->api = new PixivClient($this->client);
    }

    /**
     * @param string $user_id
     * @param string $directory
     *
     * @throws Exception
     */
    public function download(string $user_id, string $directory): void
    {
        $directory = Filesystem::createDirectory($directory);
        $page = self::START_PAGE;
        $this->authorization();
        while (1) {
            $response = $this->api->works($user_id, $page);
            if ($this->isRequireAuthorization($response)) {
                $this->authorization();
                continue;
            }
            $this->log("Page {$page}.");

            foreach ($response['response'] as $work) {
                $this->log("Work {$work['id']}.");
                $this->saveWorkImages($directory, $work);
            }
            if ($response['pagination']['pages'] < ++$page) {
                //ends
                break;
            }
        }
    }

    /**
     * @param array $response
     *
     * @return bool
     */
    private function isRequireAuthorization(array $response): bool
    {
        return $response['status'] !== 'success' && self::AUTH_ERROR == $response['errors']['system']['message'];
    }

    /**
     * @throws RuntimeException|Exception
     */
    private function authorization(): void
    {
        if ($this->refreshToken) {
            $this->api->loginByRefreshToken($this->refreshToken);
            $this->log('Login by refresh token.');
        } elseif ($this->username && $this->password) {
            $this->log('Login by password.');
            $this->api->loginByCredentials($this->username, $this->password);
            $this->refreshToken = $this->api->getRefreshToken();
        } else {
            throw new RuntimeException('Missing authorization.');
        }
    }

    /**
     * @param string $message
     */
    private function log(string $message): void
    {
        Registry::getInstance('info')->info($message);
    }

    /**
     * @param string $directory
     * @param array $work
     */
    private function saveWorkImages(string $directory, array $work): void
    {
        if ($work['page_count'] > 1) {
            for ($index = 0; $index < $work['page_count']; $index++) {
                $name = $work['id'] . '_' . ($index + 1) . '_' . $work['title'];
                $name = Filesystem::cleanupName($name);
                $url = $this->changeImagePage($work['image_urls']['large'], $index);
                $this->client->saveMedia($url, $directory . $name, ['headers' => self::HEADERS]);
                $this->log('Image ' . ($index + 1) . '/' . $work['page_count']);
            }
        } else {
            $name = $work['id'] . '_' . $work['title'];
            $name = Filesystem::cleanupName($name);
            $this->client->saveMedia($work['image_urls']['large'], $directory . $name, ['headers' => self::HEADERS]);
            $this->log('Image 1/1');
        }
    }

    /**
     * @param string $url
     * @param string $page
     *
     * @return string
     */
    private function changeImagePage(string $url, string $page): string
    {
        return preg_replace('/_p\d{1,2}./', '_p' . $page . '.', $url);
    }
}
