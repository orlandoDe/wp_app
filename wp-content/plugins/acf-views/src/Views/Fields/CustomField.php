<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views\Fields;

use WC_Product;
use WP_Comment;
use WP_Post;
use WP_Term;
use WP_User;

defined('ABSPATH') || exit;

trait CustomField
{
    protected function getPost($notFormattedValue): ?WP_Post
    {
        $notFormattedValue = $notFormattedValue ?
            (array)$notFormattedValue :
            [];

        $postId = (int)($notFormattedValue[0] ?? 0);

        return $postId ?
            // returns null if post doesn't exist
            get_post($postId) :
            null;
    }

    protected function getTerm($notFormattedValue, string $taxonomy = ''): ?WP_Term
    {
        $notFormattedValue = $notFormattedValue ?
            (array)$notFormattedValue :
            [];

        $termId = (int)($notFormattedValue[0] ?? 0);

        $term = $termId ?
            get_term($termId, $taxonomy) :
            null;

        // can be null or WP_Error
        return $term instanceof WP_Term ?
            $term :
            null;
    }

    protected function getUser($notFormattedValue): ?WP_User
    {
        $notFormattedValue = $notFormattedValue ?
            (array)$notFormattedValue :
            [];

        $userId = (int)($notFormattedValue[0] ?? 0);

        $user = $userId ?
            // returns false if user doesn't exist
            get_user_by('id', $userId) :
            false;

        return $user ?: null;
    }

    protected function getComment($notFormattedValue): ?WP_Comment
    {
        $notFormattedValue = $notFormattedValue ?
            (array)$notFormattedValue :
            [];

        $commentId = (int)($notFormattedValue[0] ?? 0);

        return get_comment($commentId);
    }

    protected function getProduct($notFormattedValue): ?WC_Product
    {
        $notFormattedValue = $notFormattedValue ?
            (array)$notFormattedValue :
            [];

        $postId = (int)($notFormattedValue[0] ?? 0);

        $product = ($postId &&
            function_exists('wc_get_product')) ?
            wc_get_product($postId) :
            null;

        // extra check, as can be false (we need null)
        return $product ?: null;
    }

    protected function getMenuItemInfo(WP_Post $menuItem): array
    {
        $targetPage = (int)get_post_meta($menuItem->ID, '_menu_item_object_id', true);

        // if equal, it means that the menu item is a custom link
        $targetPage = ($targetPage && $targetPage !== $menuItem->ID) ?
            get_post($targetPage) :
            null;
        $targetPageTitle = $targetPage->post_title ?? '';
        $targetPageLink = $targetPage ?
            (string)get_the_permalink($targetPage) :
            '';

        $title = $menuItem->post_title ?: $targetPageTitle;
        $url = $targetPageLink ?: (string)get_post_meta($menuItem->ID, '_menu_item_url', true);
        $target = (bool)get_post_meta($menuItem->ID, '_menu_item_target', true);

        return [
            // avoid double encoding in Twig
            'title' => html_entity_decode($title, ENT_QUOTES),
            'url' => $url,
            'target' => $target,
        ];
    }
}
