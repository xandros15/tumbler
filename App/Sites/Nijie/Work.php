<?php


namespace Xandros15\Tumbler\Sites\Nijie;


class Work
{
    const POPUP_BASE_URL = 'https://nijie.info/view_popup.php';
    const VIEW_BASE_URL = 'https://nijie.info/view.php';
    private $id;
    private $userId;
    private $name;
    private $popupUrl;
    private $viewUrl;

    /**
     * Post constructor.
     *
     * @param int $id
     * @param int $userId
     * @param string $name
     */
    public function __construct(int $id, int $userId, string $name)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->name = $name;
        $this->popupUrl = self::POPUP_BASE_URL . '?' . http_build_query(['id' => $this->id]);
        $this->viewUrl = self::VIEW_BASE_URL . '?' . http_build_query(['id' => $this->id]);
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
}
