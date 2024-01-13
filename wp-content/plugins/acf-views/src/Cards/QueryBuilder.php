<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Cards;

use org\wplake\acf_views\Groups\CardData;
use org\wplake\acf_views\Views\FieldMeta;
use WP_Query;

defined('ABSPATH') || exit;

class QueryBuilder
{
    protected function filterPostsData(
        int $pagesAmount,
        array $postIds,
        string $shortUniqueCardId,
        int $pageNumber,
        WP_Query $query,
        array $queryArgs
    ): array {
        return [
            'pagesAmount' => $pagesAmount,
            'postIds' => $postIds,
        ];
    }

    public function getQueryArgs(CardData $cardData, int $pageNumber): array
    {
        $args = [
            'fields' => 'ids',
            'post_type' => $cardData->postTypes,
            'post_status' => $cardData->postStatuses,
            'posts_per_page' => $cardData->limit,
            'order' => $cardData->order,
            'orderby' => $cardData->orderBy,
            'ignore_sticky_posts' => $cardData->isIgnoreStickyPosts,
        ];

        if ($cardData->postIn) {
            $args['post__in'] = $cardData->postIn;
        }

        if ($cardData->postNotIn) {
            $args['post__not_in'] = $cardData->postNotIn;
        }

        if (in_array($cardData->orderBy, ['meta_value', 'meta_value_num',], true)) {
            $fieldMeta = new FieldMeta($cardData->getOrderByMetaAcfFieldId());

            if ($fieldMeta->isFieldExist()) {
                $args['meta_key'] = $fieldMeta->getName();
            }
        }

        return $args;
    }

    public function getPostsData(CardData $acfCardData, int $pageNumber = 1): array
    {
        // stub for tests
        if (!class_exists('WP_Query')) {
            return [
                'pagesAmount' => 0,
                'postIds' => [],
            ];
        }

        $queryArgs = $this->getQueryArgs($acfCardData, $pageNumber);
        $query = new WP_Query($queryArgs);

        $foundPosts = (-1 !== $acfCardData->limit &&
            $query->found_posts > $acfCardData->limit) ?
            $acfCardData->limit :
            $query->found_posts;

        $postsPerPage = $queryArgs['posts_per_page'] ?? 0;

        // otherwise, can be DivisionByZero error
        $pagesAmount = $postsPerPage ?
            (int)ceil($foundPosts / $postsPerPage) :
            0;

        // only ids, as the 'fields' argument is set
        $postIds = $query->get_posts();

        return $this->filterPostsData(
            $pagesAmount,
            $postIds,
            $acfCardData->getUniqueId(true),
            $pageNumber,
            $query,
            $queryArgs
        );
    }
}
