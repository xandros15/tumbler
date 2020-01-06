<?php


namespace Xandros15\Tumbler\Sites\Pixiv;


use GuzzleHttp\RequestOptions;
use Xandros15\Tumbler\Client;
use Xandros15\Tumbler\UnauthorizedException;

class PixivClient
{
    private const OAUTH_URL = 'https://oauth.secure.pixiv.net/auth/token';
    private const PIXIV_HASH = '28c1fdd170a5204386cb1313c7077b34f83e4aaf4aa829ce78c231e05b0bae2c';
    private const CLIENT_ID = 'bYGKuGVw91e0NMfPGp44euvGt59s';
    private const CLIENT_SECRET = 'HP3RmkgAmEGro0gn1x9ioawQE8WMfvLXDz3ZqxpK';//huh
    private const BASE_URL = 'https://public-api.secure.pixiv.net';
    private const DEFAULT_HEADERS = [
        'Host' => 'oauth.secure.pixiv.net',
        'User-Agent' => 'PixivAndroidApp/5.0.156 (Android 9; ONEPLUS A6013)',
    ];

    /** @var array */
    protected $headers = [
        'Authorization' => 'Bearer WHDWCGnwWA2C8PRfQSdXJxjXp0G6ULRaRkkd6t5B6h8',
    ];

    protected $accessToken;
    protected $refreshToken;

    /** @var Client */
    private $client;


    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $user
     * @param string $password
     *
     * @throws UnauthorizedException
     */
    public function loginByCredentials(string $user, string $password): void
    {
        $this->login([
            'username' => $user,
            'password' => $password,
            'grant_type' => 'password',
        ]);
    }

    /**
     * @param string $refreshToken
     *
     * @throws UnauthorizedException
     */
    public function loginByRefreshToken(string $refreshToken = ''): void
    {
        $this->login([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken ?: $this->refreshToken,
        ]);
    }

    /**
     * @param array $params
     *
     * @throws UnauthorizedException
     */
    public function login(array $params): void
    {
        $params = array_merge([
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'device_token' => 'pixiv',
            'get_secure_url' => 'true',
            'include_policy' => 'true',
        ], $params);

        $date = date(DATE_RFC3339);
        $headers = array_merge(self::DEFAULT_HEADERS, [
            'x-client-time' => $date,
            'x-client-hash' => md5($date . self::PIXIV_HASH),
            'content-type' => 'application/x-www-form-urlencoded',
        ]);

        $response = $this->client->fetch(self::OAUTH_URL, [
            RequestOptions::HEADERS => array_merge($this->headers, $headers),
            RequestOptions::FORM_PARAMS => $params,
            RequestOptions::HTTP_ERRORS => false,
            'method' => 'post',
        ]);
        $result = json_decode((string) $response->getBody());

        if (isset($result->has_error)) {
            throw new UnauthorizedException('Login error: ' . $result->errors->system->message);
        }
        $this->setAccessToken($result->response->access_token);
        $this->setRefreshToken($result->response->refresh_token);
    }

    /**
     * @param string $accessToken
     */
    public function setAccessToken(string $accessToken)
    {
        $this->accessToken = $accessToken;
        $this->headers['Authorization'] = 'Bearer ' . $accessToken;
    }

    /**
     * @return string
     */
    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    /**
     * @param string $refreshToken
     */
    public function setRefreshToken(string $refreshToken): void
    {
        $this->refreshToken = $refreshToken;
    }

    public function works(int $id, int $page): array
    {
        $params = [
            'page' => $page,
            'per_page' => 30,
            'include_stats' => false,
            'include_sanity_level' => false,
            'image_sizes' => 'large',
        ];
        $url = self::BASE_URL . '/v1/users/' . $id . '/works.json?' . http_build_query($params);
        $response = $this->client->fetch($url, [
            RequestOptions::HEADERS => array_merge(self::DEFAULT_HEADERS, $this->headers, [
                'Host' => 'public-api.secure.pixiv.net',
                'User-Agent' => 'PixivIOSApp/5.8.3',
            ]),
            RequestOptions::HTTP_ERRORS => false,
        ]);

        return json_decode($response->getBody(), true);
    }

}
