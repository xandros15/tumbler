<?php


namespace Xandros15\Tumbler\Sites\Nijie;


use Symfony\Component\DomCrawler\Crawler;

class Post
{
    const POPUP_BASE_URL = 'https://nijie.info/view_popup.php';
    const VIEW_BASE_URL = 'https://nijie.info/view.php';
    private $id;
    private $userId;
    private $name;
    private $popupUrl;
    private $viewUrl;
    private $images = [];

    /**
     * Post constructor.
     *
     * @param Crawler $crawler
     */
    public function __construct(Crawler $crawler)
    {
        $this->id = (int) $crawler->attr('illust_id');
        $this->userId = (int) $crawler->attr('user_id');
        $this->name = $crawler->attr('alt');
        $this->popupUrl = self::POPUP_BASE_URL . '?' . http_build_query(['id' => $this->id]);
        $this->viewUrl = self::VIEW_BASE_URL . '?' . http_build_query(['id' => $this->id]);
    }

    /**
     * @param array $images
     */
    public function attachImages(array $images): void
    {
        $this->images = $images;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getPopupUrl(): string
    {
        return $this->popupUrl;
    }

    /**
     * @return string
     */
    public function getViewUrl(): string
    {
        return $this->viewUrl;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getImages(): array
    {
        return $this->images;
    }
}
