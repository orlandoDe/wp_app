<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Groups\Integration;

use org\wplake\acf_views\Common\Group;
use org\wplake\acf_views\Common\HooksInterface;
use org\wplake\acf_views\Views\Cpt\ViewsCpt;
use org\wplake\acf_views\Views\Fields\Comment\CommentFields;
use org\wplake\acf_views\Views\Fields\CommentItems\CommentItemFields;
use org\wplake\acf_views\Views\Fields\Menu\MenuFields;
use org\wplake\acf_views\Views\Fields\MenuItem\MenuItemFields;
use org\wplake\acf_views\Views\Fields\Post\PostFields;
use org\wplake\acf_views\Views\Fields\TaxonomyTerms\TaxonomyTermFields;
use org\wplake\acf_views\Views\Fields\Term\TermFields;
use org\wplake\acf_views\Views\Fields\User\UserFields;
use org\wplake\acf_views\Views\Fields\Woo\WooFields;
use WP_Query;

defined('ABSPATH') || exit;

class AcfIntegration implements HooksInterface
{
    // Important! Use this wrapper to avoid recursion
    protected function getGroups(): array
    {
        if (!function_exists('acf_get_field_groups')) {
            return [];
        }

        $acfGroups = acf_get_field_groups();

        // Important! To avoid recursion, otherwise within 'getChoices()' will be available the same group as the current
        // and this class will call 'acf_get_fields()' that will call 'getChoices()'
        $acfGroups = array_filter($acfGroups, function ($acfGroup) {
            $isPrivate = (bool)($acfGroup['private'] ?? false);
            $isOwn = 0 === strpos($acfGroup['key'], Group::GROUP_NAME_PREFIX);
            // don't check at all, as 'local' not presented only when json is disabled.
            // in other cases contains 'php' or 'json'
            // $isLocal = (bool)($acfGroup['local'] ?? false);

            return (!$isPrivate &&
                !$isOwn);
        });


        return array_values($acfGroups);
    }

    protected function getGroupChoices(bool $isWithExtra = true, array $onlySpecificExtra = []): array
    {
        $groupChoices = [
            '' => __('Select', 'acf-views'),
        ];

        if ($isWithExtra) {
            $extra = [];
            $extra[PostFields::GROUP_NAME] = __('Post (WordPress)', 'acf-views');
            $extra[TaxonomyTermFields::GROUP_NAME] = __('Taxonomy terms (WordPress)', 'acf-views');
            $extra[TermFields::GROUP_NAME] = __('Term (WordPress)', 'acf-views');
            $extra[UserFields::GROUP_NAME] = __('User (WordPress)', 'acf-views');
            $extra[CommentItemFields::GROUP_NAME] = __('Comment items (WordPress)', 'acf-views');
            $extra[CommentFields::GROUP_NAME] = __('Comment (WordPress)', 'acf-views');
            $extra[MenuFields::GROUP_NAME] = __('Menu (WordPress)', 'acf-views');
            $extra[MenuItemFields::GROUP_NAME] = __('Menu item (WordPress)', 'acf-views');
            $extra[WooFields::GROUP_NAME] = __('Product (WooCommerce)', 'acf-views');

            $extra = $onlySpecificExtra ?
                array_intersect_key($extra, array_flip($onlySpecificExtra)) :
                $extra;

            $groupChoices = array_merge($groupChoices, $extra);
        }

        $groups = $this->getGroups();

        $acfSourceLabel = __('(ACF)', 'acf-views');

        foreach ($groups as $group) {
            $groupId = $group['key'];
            $groupChoices[$groupId] = $group['title'] . ' ' . $acfSourceLabel;
        }

        return $groupChoices;
    }

    protected function getPostTypeChoices(): array
    {
        return get_post_types();
    }

    protected function getAcfViewChoices(): array
    {
        $acfViews = new WP_Query([
            'post_type' => ViewsCpt::NAME,
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ]);
        $acfViews = $acfViews->get_posts();

        $acfViewChoices = [];

        foreach ($acfViews as $acfView) {
            $acfViewChoices[$acfView->post_name] = $acfView->post_title;
        }

        return $acfViewChoices;
    }

    ////

    protected function setFieldChoices(): void
    {
    }

    protected function setConditionalFieldRules(): void
    {
    }

    ////

    public function getGroupedFieldTypes(): array
    {
        return [
            'basic' => [
                'text',
                'textarea',
                'number',
                'range',
                'email',
                'url',
                'password',
            ],
            'content' => [
                'image',
                'file',
                'wysiwyg',
                'oembed',
                'gallery',
            ],
            'choice' => [
                'select',
                'checkbox',
                'radio',
                'button_group',
                'true_false',
            ],
            'relational' => [
                'link',
                'post_object',
                'page_link',
                'relationship',
                'taxonomy',
                'user',
            ],
            'jquery' => [
                'google_map',
                // https://wordpress.org/plugins/acf-google-map-field-multiple-markers/
                'google_map_multi',
                // https://wordpress.org/plugins/acf-openstreetmap-field/
                'open_street_map',
                'date_picker',
                'date_time_picker',
                'time_picker',
                'color_picker',
            ],
            'layout' => [
                'repeater',
                'group',
            ],
        ];
    }

    public function setHooks(bool $isAdmin): void
    {
        if (!$isAdmin) {
            return;
        }

        $this->setFieldChoices();
        $this->setConditionalFieldRules();
    }
}
