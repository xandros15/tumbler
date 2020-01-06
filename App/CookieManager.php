<?php


namespace Xandros15\Tumbler;


use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Cookie\SetCookie;
use Symfony\Component\Yaml\Yaml;

class CookieManager
{
    const JAR_TMP_FILE = __DIR__ . '/../tmp/cookies.yaml';

    public static function load(): CookieJarInterface
    {
        $data = file_exists(self::JAR_TMP_FILE) ? Yaml::parseFile(self::JAR_TMP_FILE) : [];
        $jar = new CookieJar();

        foreach ($data as $item) {
            $jar->setCookie(new SetCookie($item));
        }

        return $jar;
    }

    public static function save(\Goutte\Client $client)
    {
        $jar = new CookieJar();
        foreach ($client->getCookieJar()->all() as $item) {
            $jar->setCookie(new SetCookie([
                'Domain' => $item->getDomain(),
                'Name' => $item->getName(),
                'Value' => $item->getValue(),
                'Path' => $item->getPath(),
                'Expires' => $item->getExpiresTime(),
                'Secure' => $item->isSecure(),
                'Discard' => true,
                'HttpOnly' => $item->isHttpOnly(),
            ]));
        }
        $dump = Yaml::dump($jar->toArray(), 4, 4);
        file_put_contents(self::JAR_TMP_FILE, $dump);
    }
}
