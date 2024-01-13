<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Common;

use org\wplake\acf_views\Groups\MountPointData;

defined('ABSPATH') || exit;

abstract class CptData extends Group
{
    const POST_FIELD_MOUNT_POINTS = 'post_excerpt';
    const POST_FIELD_USED_ITEMS = 'post_content_filtered';
    const FIELD_IS_WITHOUT_WEB_COMPONENT = 'isWithoutWebComponent';


    // fields have 'a-order' is 2 to be after current fields (they have '1' by default)

    /**
     * @a-type tab
     * @label Mount Points
     * @a-order 2
     * @a-pro The field must be not required or have default value!
     */
    public bool $mountPointsTab;
    /**
     * @item \org\wplake\acf_views\Groups\MountPointData
     * @var MountPointData[]
     * @label Mount Points
     * @instructions 'Mount' this View/Card to a location that doesn't support shortcodes. Mounting uses 'the_content' theme hook. <a target="_blank" href="https://docs.acfviews.com/display-content/mount-points-pro">Read more</a>
     * @button_label Add Mount Point
     * @a-no-tab 1
     * @a-order 2
     * @a-pro The field must be not required or have default value!
     */
    public array $mountPoints;
    // just define without any annotations, it'll be overwritten by children
    public bool $isMarkupWithDigitalId;
    public string $customMarkup;
    public string $markup;
    public string $cssCode;
    public string $jsCode;
    public bool $isWithoutWebComponent;


    abstract protected function getUsedItems(): array;

    abstract public function getCssCode(bool $isMinify = true, bool $isPreview = false): string;

    protected function minifyCode(string $code): string
    {
        // remove all CSS comments
        $code = preg_replace('|\/\*(.?)+\*\/|', '', $code);

        // remove unnecessary spaces
        $code = str_replace(["\t", "\n", "\r"], '', $code);

        // replace multiple spaces with one
        $code = preg_replace('|\s+|', ' ', $code);

        $code = str_replace(': ', ':', $code);
        $code = str_replace('; ', ';', $code);

        $code = str_replace(' {', '{', $code);
        $code = str_replace('{ ', '{', $code);

        $code = str_replace(' }', '}', $code);
        $code = str_replace('} ', '}', $code);

        return $code;
    }

    protected function getMinifiedCss(): string
    {
        return $this->minifyCode($this->cssCode);
    }

    protected function getMinifiedJs(): string
    {
        $jsCode = $this->minifyCode($this->jsCode);

        $jsCode = str_replace(' =', '=', $jsCode);
        $jsCode = str_replace('= ', '=', $jsCode);

        $jsCode = str_replace(' ?', '?', $jsCode);
        $jsCode = str_replace('? ', '?', $jsCode);

        return $jsCode;
    }

    public function saveToPostContent(array $postFields = [], bool $isSkipDefaults = false): bool
    {
        $commonMountPoints = [];

        foreach ($this->mountPoints as $mountPoint) {
            // both into one array, as IDs and postTypes are different and can't be mixed up
            $commonMountPoints = array_merge($commonMountPoints, $mountPoint->postTypes);
            $commonMountPoints = array_merge($commonMountPoints, $mountPoint->posts);
        }

        $commonMountPoints = array_values(array_unique($commonMountPoints));

        $postFields = array_merge($postFields, [
            static::POST_FIELD_MOUNT_POINTS => join(',', $commonMountPoints),
            static::POST_FIELD_USED_ITEMS => join(',', $this->getUsedItems()),
        ]);

        // skipDefaults. We won't need to save default values to the DB
        $result = parent::saveToPostContent($postFields, true);

        // we made a direct WP query, which means we need to clean the cache,
        // to make the changes available in the WP cache
        clean_post_cache($this->getSource());

        return $result;
    }

    /**
     * @param bool $isWithoutPrefix Set to true, when need short (abc3 in case of view_abc3)
     * @return string
     */
    public function getUniqueId(bool $isWithoutPrefix = false): string
    {
        $uniqueId = get_post($this->getSource())->post_name ?? '';

        return !$isWithoutPrefix ?
            $uniqueId :
            explode('_', $uniqueId)[1] ?? '';
    }

    public function getMarkupId(): string
    {
        return !$this->isMarkupWithDigitalId ?
            $this->getUniqueId(true) :
            (string)$this->getSource();
    }

    public function getJsCode(bool $isMinified = true): string
    {
        return $isMinified ?
            $this->getMinifiedJs() :
            $this->jsCode;
    }

    public function getTagName(string $prefix = ''): string
    {
        if ($this->isWithoutWebComponent) {
            return 'div';
        }

        $bemName = $this->bemName ?
            str_replace('_', '-', $this->bemName) :
            '';

        // WebComponents require at least one dash in the name
        return ($bemName && false !== strpos($bemName, '-')) ?
            $bemName :
            sprintf('%s-%s', $prefix, $this->getUniqueId(true));
    }


}
