<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views\Cpt;

use org\wplake\acf_views\Common\Cpt\Cpt;
use org\wplake\acf_views\Html;
use org\wplake\acf_views\Views\ViewsDataStorage;
use org\wplake\acf_views\Views\ViewShortcode;
use WP_Query;

defined('ABSPATH') || exit;

class ViewsCpt extends Cpt
{
    const NAME = 'acf_views';
    const COLUMN_DESCRIPTION = self::NAME . '_description';
    const COLUMN_SHORTCODE = self::NAME . '_shortcode';
    const COLUMN_LAST_MODIFIED = self::NAME . '_lastModified';
    const COLUMN_RELATED_GROUPS = self::NAME . '_relatedGroups';
    const COLUMN_RELATED_CARDS = self::NAME . '_relatedCards';

    protected Html $html;
    protected ViewsMetaBoxes $viewsMetaBoxes;
    /**
     * @var ViewsDataStorage
     */
    protected $cptDataStorage;

    public function __construct(
        ViewsDataStorage $viewsDataStorage,
        ViewsSaveActions $saveActions,
        Html $html,
        ViewsMetaBoxes $viewsMetaBoxes
    ) {
        parent::__construct($viewsDataStorage, $saveActions);

        $this->html = $html;
        $this->viewsMetaBoxes = $viewsMetaBoxes;
    }

    public function addCPT(): void
    {
        $labels = [
            'name' => __('Views', 'acf-views'),
            'singular_name' => __('View', 'acf-views'),
            'menu_name' => __('Advanced Views', 'acf-views'),
            'parent_item_colon' => __('Parent View', 'acf-views'),
            'all_ite__(ms' => __('Views', 'acf-views'),
            'view_item' => __('Browse View', 'acf-views'),
            'add_new_item' => __('Add New View', 'acf-views'),
            'add_new' => __('Add New', 'acf-views'),
            'item_updated' => __('View updated.', 'acf-views'),
            'edit_item' => __('Edit View', 'acf-views'),
            'update_item' => __('Update View', 'acf-views'),
            'search_items' => __('Search View', 'acf-views'),
            'not_found' => __('Not Found', 'acf-views'),
            'not_found_in_trash' => __('Not Found In Trash', 'acf-views'),
        ];

        $description = __(
            'Create a View item by selecting post fields, paste the shortcode in place to display field values for a specific post/page/CPT item.',
            'acf-views'
        );

        $args = [
            'label' => __('Views', 'acf-views'),
            'description' => $description,
            'labels' => $labels,
            // shouldn't be presented in the sitemap and other places
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => true,
            'has_archive' => false,
            'show_in_menu' => true,
            'show_in_nav_menus' => false,
            'delete_with_user' => false,
            'exclude_from_search' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'can_export' => false,
            'rewrite' => false,
            'query_var' => false,
            'menu_icon' => 'dashicons-format-gallery',
            'supports' => ['title', 'editor',],
            'show_in_graphql' => false,
            // right under ACF, which has 80
            'menu_position' => 81,
        ];

        register_post_type(self::NAME, $args);
    }

    public function getSortableColumns(array $columns): array
    {
        return array_merge($columns, [
            self::COLUMN_LAST_MODIFIED => self::COLUMN_LAST_MODIFIED,
        ]);
    }

    public function addSortableColumnsToRequest(WP_Query $query): void
    {
        if (!is_admin()) {
            return;
        }

        $orderBy = $query->get('orderby');

        switch ($orderBy) {
            case self::COLUMN_LAST_MODIFIED:
                $query->set('orderby', 'post_modified');
                break;
        }
    }

    public function replacePostUpdatedMessage(array $messages): array
    {
        global $post;

        $restoredMessage = false;
        $scheduledMessage = __('View scheduled for:', 'acf-views');
        $scheduledMessage .= sprintf(
            ' <strong>%1$s</strong>',
            date_i18n('M j, Y @ G:i', strtotime($post->post_date))
        );

        if (isset($_GET['revision'])) {
            $restoredMessage = __('View restored to revision from', 'acf-views');
            $restoredMessage .= ' ' . wp_post_revision_title((int)$_GET['revision'], false);
        }

        $messages[self::NAME] = [
            0 => '', // Unused. Messages start at index 1.
            1 => __('View updated.', 'acf-views'),
            2 => __('Custom field updated.', 'acf-views'),
            3 => __('Custom field deleted.', 'acf-views'),
            4 => __('View updated.', 'acf-views'),
            5 => $restoredMessage,
            6 => __('View published.', 'acf-views'),
            7 => __('View saved.', 'acf-views'),
            8 => __('View submitted.', 'acf-views'),
            9 => $scheduledMessage,
            10 => __('View draft updated.', 'acf-views'),
        ];

        return $messages;
    }

    public function printColumn(string $column, int $postId): void
    {
        switch ($column) {
            case self::COLUMN_DESCRIPTION:
                $view = $this->cptDataStorage->get($postId);

                echo esc_html($view->description);
                break;
            case self::COLUMN_SHORTCODE:
                $viewUniqueId = $this->cptDataStorage->get($postId)->getUniqueId(true);
                echo $this->html->postboxShortcodes(
                    $viewUniqueId,
                    true,
                    ViewShortcode::NAME,
                    get_the_title($postId),
                    false
                );
                break;
            case self::COLUMN_RELATED_GROUPS:
                // without the not found message
                $this->viewsMetaBoxes->printRelatedAcfGroupsMetaBox(get_post($postId), false, true);
                break;
            case self::COLUMN_RELATED_CARDS:
                echo $this->viewsMetaBoxes->getRelatedAcfCardsMetaBox(get_post($postId), true);
                break;
            case self::COLUMN_LAST_MODIFIED:
                echo esc_html(explode(' ', get_post($postId)->post_modified)[0]);
                break;
        }
    }

    public function getTitlePlaceholder(string $title): string
    {
        $screen = get_current_screen()->post_type ?? '';
        if (self::NAME !== $screen) {
            return $title;
        }

        return __('Name your view', 'acf-views');
    }

    public function changeMenuItems(): void
    {
        $url = sprintf('edit.php?post_type=%s', self::NAME);

        global $submenu;

        if (!$submenu[$url]) {
            $submenu[$url] = [];
        }

        foreach ($submenu[$url] as $itemKey => $item) {
            if (3 !== count($item)) {
                continue;
            }

            switch ($item[2]) {
                // remove 'Add new' submenu link
                case 'post-new.php?post_type=acf_views':
                    unset($submenu[$url][$itemKey]);
                    break;
                // rename 'Advanced Views' to 'Views' submenu link
                case 'edit.php?post_type=acf_views':
                    $submenu[$url][$itemKey][0] = __('Views', 'acf-views');
                    break;
            }
        }
    }

    public function getColumns(array $columns): array
    {
        unset($columns['date']);

        return array_merge($columns, [
            self::COLUMN_DESCRIPTION => __('Description', 'acf-views'),
            self::COLUMN_SHORTCODE => __('Shortcode', 'acf-views'),
            self::COLUMN_RELATED_GROUPS => __('Assigned Group', 'acf-views'),
            self::COLUMN_RELATED_CARDS => __('Assigned to Card', 'acf-views'),
            self::COLUMN_LAST_MODIFIED => __('Last modified', 'acf-views'),
        ]);
    }

    public function setHooks(bool $isAdmin): void
    {
        parent::setHooks($isAdmin);

        add_action('init', [$this, 'addCPT']);

        if (!$isAdmin) {
            return;
        }

        add_action('pre_get_posts', [$this, 'addSortableColumnsToRequest',]);
        add_action('manage_' . self::NAME . '_posts_custom_column', [$this, 'printColumn',], 10, 2);
        add_action('admin_menu', [$this, 'changeMenuItems']);

        add_filter('post_updated_messages', [$this, 'replacePostUpdatedMessage']);
        add_filter('manage_' . self::NAME . '_posts_columns', [$this, 'getColumns',]);
        add_filter('enter_title_here', [$this, 'getTitlePlaceholder',]);
        add_filter('manage_edit-' . self::NAME . '_sortable_columns', [$this, 'getSortableColumns',]);
    }
}
