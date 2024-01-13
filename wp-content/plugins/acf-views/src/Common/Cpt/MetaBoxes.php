<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Common\Cpt;

use org\wplake\acf_views\Common\CptData;
use org\wplake\acf_views\Common\CptDataStorage;
use org\wplake\acf_views\Common\HooksInterface;
use org\wplake\acf_views\Html;

defined('ABSPATH') || exit;

abstract class MetaBoxes implements HooksInterface
{
    protected Html $html;
    protected CptDataStorage $cptDataStorage;

    public function __construct(Html $html, CptDataStorage $cptDataStorage)
    {
        $this->html = $html;
        $this->cptDataStorage = $cptDataStorage;
    }

    abstract protected function getCptName(): string;

    public function addMetaboxes(): void
    {
        add_meta_box(
            'acf-views_review',
            __('Rate & Review', 'acf-views'),
            function ($post, $meta) {
                echo $this->html->postboxReview();
            },
            [
                $this->getCptName(),
            ],
            'side'
        );

        add_meta_box(
            'acf-views_support',
            __('Having issues?', 'acf-views'),
            function ($post, $meta) {
                echo $this->html->postboxSupport();
            },
            [
                $this->getCptName(),
            ],
            'side'
        );
        // $this->addProBannerMetabox();
    }

    public function printMountPoints(CptData $acfCptData): void
    {
        $postTypes = [];
        $posts = [];

        foreach ($acfCptData->mountPoints as $mountPoint) {
            $postTypes = array_merge($postTypes, $mountPoint->postTypes);
            $posts = array_merge($posts, $mountPoint->posts);
        }

        $postTypes = array_unique($postTypes);
        $posts = array_unique($posts);

        foreach ($posts as $index => $post) {
            $postInfo = sprintf(
                '<a target="_blank" href="%s">%s</a>',
                get_the_permalink($post),
                get_the_title($post)
            );

            $posts[$index] = $postInfo;
        }

        if ($postTypes) {
            echo __('Post Types:', 'acf-views') . ' ' . join(', ', $postTypes);
        }

        if ($posts) {
            if ($postTypes) {
                echo '<br>';
            }

            echo __('Pages:', 'acf-views') . ' ' . join(', ', $posts);
        }
    }

    public function setHooks(bool $isAdmin): void
    {
        if (!$isAdmin) {
            return;
        }

        add_action('add_meta_boxes', [$this, 'addMetaboxes']);
    }
}