<?php

namespace Xandros15\Tumbler\Sites;


use Exception;
use Symfony\Component\DomCrawler\Crawler;
use Xandros15\Tumbler\Client;
use Xandros15\Tumbler\Filesystem;
use Xandros15\Tumbler\Logger;
use Xandros15\Tumbler\Sites\Nijie\Post;
use Xandros15\Tumbler\UnauthorizedException;

final class Nijie implements SiteInterface
{
    private const LOGIN_PAGE = 'https://nijie.info/login.php';
    private const ILLUSTRATION_PAGE = 'https://nijie.info/members_illust.php';


    /** @var string */
    private $email;
    /** @var string */
    private $password;
    /** @var Client */
    private $client;

    /**
     * HF constructor.
     *
     * @param string $email
     * @param string $password
     */
    public function __construct(string $email, string $password)
    {
        $this->email = $email;
        $this->password = $password;

        $this->client = new Client();
    }

    /**
     * @param string $ident
     * @param string $directory
     *
     * @throws Exception
     */
    public function download(string $ident, string $directory): void
    {
        $directory = Filesystem::createDirectory($directory);
        $this->signup();
        Logger::info('Fetching pages...');
        $posts = $this->fetchIllustrationPages($ident);
        //post has many images
        Logger::info('Fetching posts...');
        $posts = $this->fetchImages($posts);
        Logger::info('Download images...');
        $this->downloadImages($posts, $directory);
        Logger::info('Done \o/.');
    }

    /**
     * @param Post[] $posts
     * @param string $directory
     */
    private function downloadImages(array $posts, string $directory): void
    {
        $postDownloaded = 0;
        $postCount = count($posts);
        foreach ($posts as $post) {
            $postDownloaded++;
            Logger::info("Post {$postDownloaded}/{$postCount}");
            $images = $post->getImages();
            $imagesCount = count($images);
            if ($imagesCount > 1) {
                foreach ($images as $k => $image) {
                    $name = $post->getId() . '_' . $k . '_' . $post->getName();
                    $name = Filesystem::cleanupName($name);

                    Logger::info('Image ' . (1 + $k) . '/' . $imagesCount);
                    $this->client->saveMedia($image, $directory . '/' . $name);
                }
            } else {
                $name = $post->getId() . '_' . $post->getName();
                $name = Filesystem::cleanupName($name);
                Logger::info('Image 1/' . $imagesCount);
                $this->client->saveMedia($images[0], $directory . '/' . $name);
            }
        }
    }

    /**
     * @param Post[] $posts
     *
     * @return array
     */
    private function fetchImages(array $posts): array
    {
        $postCount = 0;
        foreach ($posts as $post) {
            Logger::info('Post ' . ++$postCount);
            $page = $this->client->fetchHTML($post->getPopupUrl());
            $images = $page->filterXPath('//div[starts-with(@id,\'diff_\')]//img')->each(function (Crawler $img) {
                return 'https:' . ($img->attr('data-original') ?? $img->attr('src'));
            });
            $videos = $page->filterXPath('//div[starts-with(@id,\'diff_\')]//video')->each(function (Crawler $img) {
                return 'https:' . ($img->attr('data-original') ?? $img->attr('src'));
            });

            $post->attachImages(array_merge($images, $videos));
        }

        return $posts;
    }

    /**
     * @param int $ident
     *
     * @return array
     */
    private function fetchIllustrationPages(int $ident): array
    {
        $url = self::ILLUSTRATION_PAGE . '?' . http_build_query(['id' => $ident]);
        $pageNumber = 0;
        $posts = [];
        while (1) {
            Logger::info('Page ' . ++$pageNumber);
            $page = $this->client->fetchHTML($url);
            $page->filter('.nijie .mozamoza.ngtag')->each(function (Crawler $post) use (&$posts) {
                $posts[] = new Post($post);
            });

            $next = $page->filter('.page_button a[rel="next"]');
            if (!$next->count()) {
                return $posts;
            }
            $url = $next->attr('href');
        }

        return $posts;
    }

    /**
     * @throws UnauthorizedException
     */
    private function signup(): void
    {
        //prepare
        $page = $this->client->fetchHTML(self::LOGIN_PAGE);
        $form = $page->selectButton('ログイン')->form();
        $form['email'] = $this->email;
        $form['password'] = $this->password;
        $responsePage = $this->client->sendForm($form);
        if ($responsePage->filter('#pro')->count() > 0) { //check if is profile div
            Logger::info('Authorize by form.');
        } else {
            throw new UnauthorizedException();
        }
    }
}
