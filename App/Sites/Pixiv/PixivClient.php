<?php


namespace Xandros15\Tumbler\Sites\Pixiv;


use GuzzleHttp\RequestOptions;
use Symfony\Component\Yaml\Yaml;
use Xandros15\Tumbler\Client;
use Xandros15\Tumbler\UnauthorizedException;

class PixivClient
{
    const LAST_OATH_RESPONSE_FILE = __DIR__ . '/../../../tmp/pixiv.yaml';
    private const PIXIV_HASH = '28c1fdd170a5204386cb1313c7077b34f83e4aaf4aa829ce78c231e05b0bae2c';
    private const CLIENT_ID = 'MOBrBDS8blbauoSck0ZfDbtuzpyT';
    private const CLIENT_SECRET = 'lsACyCD94FhDUtGTXi3QzcFE2uU1hqtDaKeqrdwj';
    private const OAUTH_URL = 'https://oauth.secure.pixiv.net/auth/token';
    private const BASE_URL = 'https://public-api.secure.pixiv.net';

    /** @var array */
    protected $headers = [];

    protected $accessToken;
    protected $refreshToken;
    private $accessTokenExpiresAt;

    /** @var Client */
    private $client;


    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->loadLastOath();
    }

    /**
     * @param string $user
     * @param string $password
     *
     * @throws UnauthorizedException
     */
    public function loginByCredentials(string $user, string $password): void
    {
        if (time() < $this->accessTokenExpiresAt) {
            return;
        }

        if ($this->refreshToken) {
            try {
                $this->loginByRefreshToken($this->refreshToken);

                return;
            } catch (UnauthorizedException $exception) {
            }
        }

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
        $refreshToken = $refreshToken ?: $this->refreshToken;
        if ($refreshToken === '') {
            throw new UnauthorizedException('Missing refresh token.');
        }
        $this->login([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
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
        $headers = array_merge([
            'Referer' => 'https://spapi.pixiv.net/',
            'User-Agent' => 'PixivIOSApp/5.8.7',
            'content-type' => 'application/x-www-form-urlencoded',
            'x-client-time' => $date,
            'x-client-hash' => md5($date . self::PIXIV_HASH),
        ]);

        $response = $this->client->fetch(self::OAUTH_URL, [
            RequestOptions::HEADERS => array_merge($this->headers, $headers),
            RequestOptions::FORM_PARAMS => $params,
            RequestOptions::HTTP_ERRORS => false,
            'method' => 'post',
        ]);
        $result = json_decode((string) $response->getBody(), true);

        if (isset($result['has_error'])) {
            throw new UnauthorizedException('Login error: ' . $result['errors']['system']['message']);
        }
        $result['response']['expires_at'] = time() + $result['response']['expires_in'];
        $this->setAccessToken($result['response']['access_token'], $result['response']['expires_at']);
        $this->setRefreshToken($result['response']['refresh_token']);
        $this->saveLastOath($result);
    }

    private function loadLastOath(): void
    {
        if (file_exists(self::LAST_OATH_RESPONSE_FILE)) {
            $result = Yaml::parseFile(self::LAST_OATH_RESPONSE_FILE);
            if (time() < $result['response']['expires_at']) {
                $this->setAccessToken($result['response']['access_token'], $result['response']['expires_at']);
            }
            $this->setRefreshToken($result['response']['refresh_token']);
        }
    }

    /**
     * @param $result
     */
    private function saveLastOath(array $result): void
    {
        $dump = Yaml::dump($result, 4, 4);
        file_put_contents(self::LAST_OATH_RESPONSE_FILE, $dump);
    }

    /**
     * @param string $accessToken
     * @param string $accessTokenExpiriesAt
     */
    public function setAccessToken(string $accessToken, string $accessTokenExpiriesAt)
    {
        $this->accessTokenExpiresAt = $accessTokenExpiriesAt;
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

    /**
     * @param int $id
     * @param int $page
     *
     * @return array
     * @throws UnauthorizedException
     */
    public function works(int $id, int $page): array
    {
        if (time() > $this->accessTokenExpiresAt) {
            $this->loginByRefreshToken();
        }

        $params = [
            'page' => $page,
            'per_page' => 30,
            'include_stats' => false,
            'include_sanity_level' => false,
            'image_sizes' => 'large',
        ];
        $url = self::BASE_URL . '/v1/users/' . $id . '/works.json?' . http_build_query($params);
        $response = $this->client->fetch($url, [
            RequestOptions::HEADERS => array_merge($this->headers, [
                'User-Agent' => 'PixivIOSApp/5.8.3',
            ]),
            RequestOptions::HTTP_ERRORS => false,
        ]);

        return json_decode($response->getBody(), true);
    }

}
