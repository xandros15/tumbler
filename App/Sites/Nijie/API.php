<?php


namespace Xandros15\Tumbler\Sites\Nijie;


use Symfony\Component\DomCrawler\Crawler;
use Xandros15\Tumbler\Client;
use Xandros15\Tumbler\Logger;
use Xandros15\Tumbler\UnauthorizedException;

class API
{
    private const LOGIN_PAGE = 'https://nijie.info/login.php';
    private const ILLUSTRATION_PAGE = 'https://nijie.info/members_illust.php';
    private const AGE_JUMP_PAGE = 'https://nijie.info/age_jump.php?url=';
    private const IMAGE_XPATH = '//div[starts-with(@id,\'diff_\')]//img[starts-with(@src,\'//\') or starts-with(@src,\'http\')]';
    private const VIDEO_XPATH = '//div[starts-with(@id,\'diff_\')]//video[starts-with(@src,\'//\') or starts-with(@src,\'http\')]';
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param int $ident
     *
     * @return string
     */
    public function getIllustratorName(int $ident): string
    {
        $url = self::ILLUSTRATION_PAGE . '?' . http_build_query(['id' => $ident]);

        return trim($this->client->fetchHTML($url)->filter('#pro .name')->text());
    }

    /**
     * @param string $email
     * @param string $password
     *
     * @throws UnauthorizedException
     */
    public function signup(string $email, string $password): void
    {
        //prepare
        $page = $this->client->fetchHTML(self::AGE_JUMP_PAGE);//age_jump
        $form = $page->selectButton('ログイン')->form();
        $form['email'] = $email;
        $form['password'] = $password;
        $responsePage = $this->client->sendForm($form);
        if ($responsePage->filter('#pro')->count() < 1) { //check if is profile div
            throw new UnauthorizedException();
        }
    }

    /**
     * @param Work $work
     *
     * @return array
     */
    public function fetchImagesWork(Work $work): array
    {
        $page = $this->client->fetchHTML($work->getPopupUrl());
        $images = $page->filterXPath(self::IMAGE_XPATH)->each(function (Crawler $img) {
            $src = $img->attr('data-original') ?? $img->attr('src');

            return $src[0] === 'h' ? $src : 'https:' . $src;
        });
        $videos = $page->filterXPath(self::VIDEO_XPATH)->each(function (Crawler $img) {
            $src = $img->attr('data-original') ?? $img->attr('src');

            return $src[0] === 'h' ? $src : 'https:' . $src;
        });

        return array_merge($images, $videos);
    }

    /**
     * @param int $ident
     *
     * @return Work[]
     */
    public function getIllustrations(int $ident): array
    {
        $url = self::ILLUSTRATION_PAGE . '?' . http_build_query(['id' => $ident]);
        $pageNumber = 0;
        $works = [];
        while (1) {
            Logger::info('Page ' . ++$pageNumber);
            $page = $this->client->fetchHTML($url);
            $page->filter('.nijie .mozamoza.ngtag')->each(function (Crawler $post) use (&$works) {
                $id = $post->attr('illust_id');
                $userId = (int) $post->attr('user_id');
                $name = $post->attr('alt');
                $works[] = new Work($id, $userId, $name);
            });

            $next = $page->filter('.page_button a[rel="next"]');
            if (!$next->count()) {
                return $works;
            }
            $url = $next->attr('href');
        }

        return $works;
    }
}
