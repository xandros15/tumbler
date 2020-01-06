<?php

namespace Xandros15\Tumbler\Sites;


use Exception;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Yaml\Yaml;
use Xandros15\Tumbler\Client;
use Xandros15\Tumbler\Filesystem;
use Xandros15\Tumbler\Logger;
use Xandros15\Tumbler\Sites\Nijie\Work;
use Xandros15\Tumbler\UnauthorizedException;

final class Nijie implements SiteInterface
{
    private const LOGIN_PAGE = 'https://nijie.info/login.php';
    private const ILLUSTRATION_PAGE = 'https://nijie.info/members_illust.php';
    private const IMAGE_XPATH = '//div[starts-with(@id,\'diff_\')]//img[starts-with(@src,\'//\') or starts-with(@src,\'http\')]';
    private const VIDEO_XPATH = '//div[starts-with(@id,\'diff_\')]//video[starts-with(@src,\'//\') or starts-with(@src,\'http\')]';


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

        $worksCount = 0;
        $imagesCount = 0;
        Logger::info('Fetching works...');
        $works = $this->getIllustrations($ident);
        Logger::info('Fetching images...');
        foreach ($works as $work) {
            Logger::info('Work id: ' . $work->getId());
            $this->fetchImagesWork($work);
            $worksCount++;
            $imagesCount += $work->getPageCount();
        }
        Logger::info('Total works: ' . $worksCount);
        Logger::info('Total images: ' . $imagesCount);
        Logger::info('Download images...');
        foreach ($works as $i => $work) {
            Logger::info('Work ' . ($i + 1) . '/' . $worksCount);
            $this->downloadImages($work, $directory);
        }
        $this->saveInfoFile($works, $directory);
        Logger::info('Done \o/.');
    }

    /**
     * @param Work $work
     * @param string $directory
     */
    private function downloadImages(Work $work, string $directory): void
    {
        foreach ($work->getImages() as $i => $image) {
            $name = $work->getId() . '_p' . $i;
            $name = Filesystem::cleanupName($name);
            $this->client->saveMedia($image, $directory . '/' . $name);
            Logger::info('Image ' . (1 + $i) . '/' . $work->getPageCount());
        }
    }

    /**
     * @param Work $work
     */
    private function fetchImagesWork(Work $work): void
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

        $work->attachImages(array_merge($images, $videos));
    }

    /**
     * @param int $ident
     *
     * @return Work[]
     */
    private function getIllustrations(int $ident): array
    {
        $url = self::ILLUSTRATION_PAGE . '?' . http_build_query(['id' => $ident]);
        $pageNumber = 0;
        $works = [];
        while (1) {
            Logger::info('Page ' . ++$pageNumber);
            $page = $this->client->fetchHTML($url);
            $page->filter('.nijie .mozamoza.ngtag')->each(function (Crawler $post) use (&$works) {
                $works[] = new Work($post);
            });

            $next = $page->filter('.page_button a[rel="next"]');
            if (!$next->count()) {
                return $works;
            }
            $url = $next->attr('href');
        }

        return $works;
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

    /**
     * @param Work[] $works
     * @param string $directory
     */
    private function saveInfoFile(array $works, string $directory)
    {
        Logger::info('Making info file...');
        $data = [];
        foreach ($works as $work) {
            $data[$work->getId()] = $work->getName();
        }
        $dump = Yaml::dump($data, 4, 4);
        file_put_contents($directory . 'info-' . date('Ymd') . '.yaml', $dump);
    }
}
