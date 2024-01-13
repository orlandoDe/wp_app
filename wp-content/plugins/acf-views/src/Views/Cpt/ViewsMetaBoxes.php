<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views\Cpt;

use org\wplake\acf_views\Cards\Cpt\CardsCpt;
use org\wplake\acf_views\Common\Cpt\MetaBoxes;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Html;
use org\wplake\acf_views\Views\ViewsDataStorage;
use org\wplake\acf_views\Views\ViewShortcode;
use WP_Post;

defined('ABSPATH') || exit;

class ViewsMetaBoxes extends MetaBoxes
{
    public function __construct(Html $html, ViewsDataStorage $viewsDataStorage)
    {
        parent::__construct($html, $viewsDataStorage);
    }

    protected function getCptName(): string
    {
        return ViewsCpt::NAME;
    }

    protected function getJsHover(): string
    {
        return 'onMouseOver="this.style.filter=\'brightness(30%)\'" onMouseOut="this.style.filter=\'brightness(100%)\'"';
    }

    protected function getRelatedViewUniqueIds(ViewData $viewData): array
    {
        $relatedViewIds = [];

        foreach ($viewData->items as $item) {
            if (!$item->field->acfViewId) {
                continue;
            }

            $relatedViewIds[] = $item->field->acfViewId;
        }

        return array_values(array_unique($relatedViewIds));
    }

    public function printRelatedAcfGroupsMetaBox(
        WP_Post $post,
        bool $isIgnorePrint = false,
        bool $isSkipNotFoundMessage = false
    ): string {
        if (!$post->post_content_filtered) {
            $message = __('No assigned ACF Groups.', 'acf-views');

            if (!$isIgnorePrint &&
                !$isSkipNotFoundMessage) {
                echo $message;
            }

            return $message;
        }

        $acfGroupKeys = explode(',', $post->post_content_filtered);
        $links = [];

        foreach ($acfGroupKeys as $acfGroupKey) {
            $acfGroupId = acf_get_field_group($acfGroupKey)['ID'] ?? '';

            if (!$acfGroupId) {
                continue;
            }

            $links[] = sprintf(
                '<a href="%s" target="_blank" style="transition: all .3s ease;" %s>%s</a>',
                get_edit_post_link($acfGroupId),
                $this->getJsHover(),
                get_the_title($acfGroupId)
            );
        }

        $content = implode(', ', $links);

        if (!$isIgnorePrint) {
            echo $content;
        }

        return $content;
    }

    public function printRelatedViewsMetaBox(
        WP_Post $post,
        bool $isIgnorePrint = false,
        bool $isSkipNotFoundMessage = false
    ): string {
        /**
         * @var ViewData $view
         */
        $view = $this->cptDataStorage->get($post->ID);

        $relatedViewUniqueIds = $this->getRelatedViewUniqueIds($view);

        if (!$relatedViewUniqueIds) {
            $message = __('No assigned Views.', 'acf-views');

            if (!$isIgnorePrint &&
                !$isSkipNotFoundMessage) {
                echo $message;
            }

            return $message;
        }

        $links = [];

        foreach ($relatedViewUniqueIds as $relatedViewUniqueId) {
            $relatedViewId = $this->cptDataStorage->getPostIdByUniqueId($relatedViewUniqueId, ViewsCpt::NAME);

            $links[] = sprintf(
                '<a href="%s" target="_blank" style="transition: all .3s ease;" %s>%s</a>',
                get_edit_post_link($relatedViewId),
                $this->getJsHover(),
                get_the_title($relatedViewId)
            );
        }

        $content = implode(', ', $links);

        if (!$isIgnorePrint) {
            echo $content;
        }

        return $content;
    }

    public function getRelatedAcfCardsMetaBox(WP_Post $post, bool $isListLook = false): string
    {
        $content = '';
        $message = __('Not assigned to any Cards.', 'acf-views');
        $message = '<p>' . $message . '</p>';

        if ('publish' !== $post->post_status) {
            if (!$isListLook) {
                $content .= $message;
            }

            return $content;
        }

        global $wpdb;

        $acfViewData = $this->cptDataStorage->get($post->ID);

        $query = $wpdb->prepare(
            "SELECT * from {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'
                      AND FIND_IN_SET(%s,post_content_filtered) > 0",
            CardsCpt::NAME,
            $acfViewData->getUniqueId()
        );
        $relatedCards = $wpdb->get_results($query);

        if (!$relatedCards &&
            !$isListLook) {
            $content .= $message;
        }

        $links = [];

        foreach ($relatedCards as $relatedCard) {
            $links[] = sprintf(
                '<a href="%s" target="_blank" style="transition: all .3s ease;" %s>%s</a>',
                get_edit_post_link($relatedCard),
                $this->getJsHover(),
                get_the_title($relatedCard)
            );
        }

        $content .= $links ?
            implode(', ', $links) :
            '';

        if ($relatedCards ||
            $isListLook) {
            $content .= '<br><br>';
        }


        $label = __('Add new', 'acf-views');
        $style = 'min-height: 0;line-height: 1.2;padding: 3px 7px;font-size:11px;transition:all .3s ease;';
        $content .= sprintf(
            '<a href="%s" target="_blank" class="button" style="%s">%s</a>',
            admin_url('/post-new.php?post_type=acf_cards&_from=' . $post->ID),
            $style,
            $label
        );

        return $content;
    }

    public function addMetaboxes(): void
    {
        add_meta_box(
            'acf-views_shortcode',
            __('Shortcode', 'acf-views'),
            function ($post, $meta) {
                if (!$post ||
                    'publish' !== $post->post_status) {
                    echo __('Your View shortcode is available after publishing.', 'acf-views');

                    return;
                }

                $viewUniqueId = $this->cptDataStorage->get($post->ID)->getUniqueId(true);
                echo $this->html->postboxShortcodes(
                    $viewUniqueId,
                    false,
                    ViewShortcode::NAME,
                    get_the_title($post),
                    false
                );
            },
            [
                $this->getCptName(),
            ],
            'side'
        );

        add_meta_box(
            'acf-views_related_groups',
            __('Assigned Groups', 'acf-views'),
            function (WP_Post $post) {
                $this->printRelatedAcfGroupsMetaBox($post);
            },
            [
                $this->getCptName(),
            ],
            'side'
        );

        add_meta_box(
            'acf-views_related_views',
            __('Assigned Views', 'acf-views'),
            function (WP_Post $post) {
                $this->printRelatedViewsMetaBox($post);
            },
            [
                $this->getCptName(),
            ],
            'side'
        );

        add_meta_box(
            'acf-views_related_cards',
            __('Assigned to Cards', 'acf-views'),
            function (WP_Post $post) {
                echo $this->getRelatedAcfCardsMetaBox($post);
            },
            [
                $this->getCptName(),
            ],
            'side'
        );

        parent::addMetaboxes();
    }

}
