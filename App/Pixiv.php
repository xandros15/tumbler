<?php

namespace Xandros15\Tumbler;


use Monolog\Registry;

final class Pixiv extends Tumbler
{
    protected const DEFAULT_HEADERS = parent::DEFAULT_HEADERS + [
        'Referer' => 'http://www.pixiv.net/',
    ];
    private const AUTH_ERROR = 'The access token provided is invalid.';
    private const START_PAGE = 1;
    private $api;
    private $refreshToken;
    private $username;
    private $password;

    public function __construct(string $username, string $password)
    {
        $this->api = new \PixivAPI();
        $this->username = $username;
        $this->password = $password;
        parent::__construct();
    }

    /**
     * @param string $user_id
     * @param string $directory
     */
    public function download(string $user_id, string $directory)
    {
        $directory = $this->createDirectory($directory);
        $page = self::START_PAGE;
        while (true) {
            Registry::getInstance('global')->info("Getting page {$page} from user {$user_id}");
            $works = $this->api->users_works($user_id, $page);
            if ($works['status'] !== 'success') {
                if (self::AUTH_ERROR == $works['errors']['system']['message']) {
                    $this->auth();
                    continue;
                } else {
                    throw new \RuntimeException('Api have an error: ' . $works['errors']['system']['message'], 403);
                }
            }

            foreach ($works['response'] as $work) {
                $name = strtotime($work['created_time']);
                if ($work['page_count'] > 1) {
                    for ($imagePage = 0; $imagePage < $work['page_count']; $imagePage++) {
                        $url = $this->changeImagePage($work['image_urls']['large'], $imagePage);
                        $this->saveImage($url, $directory . $name . '_' . ($imagePage + 1));
                    }
                } else {
                    $this->saveImage($work['image_urls']['large'], $directory . $name);
                }
            }
            if ($works['pagination']['pages'] < ++$page) {
                //ends
                break;
            }
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
        $page = '_p' . $page . '.';

        return preg_replace('/_p\d{1,2}./', $page, $url);
    }

    /**
     * @throws \RuntimeException
     */
    private function auth()
    {
        if ($this->refreshToken) {
            $this->api->login(null, null, $this->refreshToken);
            Registry::getInstance('global')->info('Login by refresh token');
        } elseif ($this->username && $this->password) {
            Registry::getInstance('global')->info('Login by password');
            $this->api->login($this->username, $this->password);
            $this->refreshToken = $this->api->getRefreshToken();
        } else {
            throw new \RuntimeException('Missing authorization');
        }
    }
}
