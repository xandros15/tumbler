<?php

namespace Xandros15\Tumbler;


final class Pixiv extends Tumbler
{
    private const AUTH_ERROR = 'The access token provided is invalid.';
    private const START_PAGE = 1;
    protected $headers = [
        'Referer' => 'http://www.pixiv.net/',
    ];
    /** @var \PixivAPI */
    private $api;
    /** @var  string */
    private $refreshToken;
    /** @var string */
    private $username;
    /** @var string */
    private $password;

    /**
     * Pixiv constructor.
     *
     * @param string $username
     * @param string $password
     */
    public function __construct(string $username, string $password)
    {
        $this->api = new \PixivAPI();
        $this->username = $username;
        $this->password = $password;
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
            $response = $this->api->users_works($user_id, $page);
            $this->getLogger()->info("Connect: id {$user_id} page {$page}");
            if ($this->isRequireAuthorization($response)) {
                $this->authorization();
                continue;
            }

            foreach ($response['response'] as $work) {
                $name = $directory . strtotime($work['created_time']);
                if ($work['page_count'] > 1) {
                    $this->createGallery($name, $work['image_urls']['large'], $work['page_count']);
                } else {
                    $this->saveImage($work['image_urls']['large'], $name);
                }
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
    private function isRequireAuthorization(array $response)
    {
        return $response['status'] !== 'success' && self::AUTH_ERROR == $response['errors']['system']['message'];
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

    /**
     * @throws \RuntimeException
     */
    private function authorization()
    {
        if ($this->refreshToken) {
            $this->api->login(null, null, $this->refreshToken);
            $this->getLogger()->info('Login by refresh token');
        } elseif ($this->username && $this->password) {
            $this->getLogger()->info('Login by password');
            $this->api->login($this->username, $this->password);
            $this->refreshToken = $this->api->getRefreshToken();
        } else {
            throw new \RuntimeException('Missing authorization');
        }
    }

    /**
     * @param string $name
     * @param string $url
     * @param int $count
     */
    private function createGallery(string $name, string $url, int $count)
    {
        for ($index = 0; $index < $count; $index++) {
            $url = $this->changeImagePage($url, $index);
            $this->saveImage($url, $name . '_' . ($index + 1));
        }
    }
}
