<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Common;

use org\wplake\acf_views\Views\Cpt\ViewsCpt;
use WP_Query;

defined('ABSPATH') || exit;

/**
 * Avoid querying and parsing View/Card's fields multiple times
 * (e.g. one Card can call View's shortcode 10 times, it's better to save than create objects every time
 * (parsing json + objects (its fields) creation))
 *
 * There are more internal cache in the plugin:
 * 1. FieldsMeta (AcfViewData class; avoid calling 'get_field_object()' for every field multiple times)
 * 2. ViewMarkup (ViewMarkup class; save time for processing)
 */
abstract class CptDataStorage
{
    protected CptData $cptData;
    /**
     * @var CptData[]
     */
    protected array $items;

    public function __construct(CptData $cptData)
    {
        $this->cptData = $cptData->getDeepClone();
        $this->items = [];
    }

    public function get(int $postId)
    {
        if (key_exists($postId, $this->items)) {
            return $this->items[$postId];
        }

        $cptData = $this->cptData->getDeepClone();
        $cptData->loadFromPostContent($postId);

        $this->items[$postId] = $cptData;

        return $cptData;
    }

    public function replace(int $postId, CptData $cptData): void
    {
        $this->items[$postId] = $cptData;
    }

    public function getPostIdByUniqueId(string $uniqueId, string $postType): ?int
    {
        // do not use 'is_numeric', as unique id may consist from numbers only
        $isUniqueId = 13 === strlen($uniqueId) ||
            false !== strpos($uniqueId, '_');

        if ($isUniqueId) {
            if (false === strpos($uniqueId, '_')) {
                $prefix = ViewsCpt::NAME === $postType ?
                    'view_' :
                    'card_';
                $uniqueId = $prefix . $uniqueId;
            }

            $query = new WP_Query([
                'post_type' => $postType,
                'post_name__in' => [$uniqueId],
                'posts_per_page' => 1,
            ]);
            $post = $query->get_posts()[0] ?? null;
        } // keep back compatibility for direct postIds (for shortcodes that were already pasted)
        else {
            $post = get_post($uniqueId);
        }

        if ($post &&
            in_array($post->post_type, [$postType,], true) &&
            'publish' === $post->post_status
        ) {
            return $post->ID;
        }

        return null;
    }
}
