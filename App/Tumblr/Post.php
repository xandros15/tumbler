<?php

namespace Xandros15\Tumbler\Tumblr;


final class Post
{
    const VIDEO = 'video';
    const PHOTO = 'photo';
    const VIDEO_TYPE = 'tumblr';

    /** @var array */
    private $params;


    /**
     * Post constructor.
     *
     * @param array $params
     */
    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * @return bool
     */
    public function hasMedia(): bool
    {
        return
            ($this->isPhoto() && $this->params['photos'] && count($this->params['photos']) > 0) ||
            ($this->isVideo() && isset($this->params['video_url']) && $this->params['video_type'] == self::VIDEO_TYPE);
    }

    /**
     * @return Media[]|\Traversable
     */
    public function getMedia(): \Traversable
    {
        $name = strtotime($this->params['date']);
        $counter = 0;
        if ($this->isPhoto()) {
            foreach ($this->params['photos'] as $photo) {
                yield new Media($name . '_' . ++$counter, $photo['original_size']['url']);
            }
        } elseif ($this->isVideo()) {
            yield new Media($name, $this->params['video_url']);
        } else {
            return [];
        }
    }

    /**
     * @return bool
     */
    public function isReblog(): bool
    {
        return isset($this->params['reblogged_root_name']) || strpos($this->params['caption'], 'blockquote') !== false;
    }

    /**
     * @return bool
     */
    private function isVideo(): bool
    {
        return $this->params['type'] == self::VIDEO;
    }

    /**
     * @return bool
     */
    private function isPhoto(): bool
    {
        return $this->params['type'] == self::PHOTO;
    }

}
