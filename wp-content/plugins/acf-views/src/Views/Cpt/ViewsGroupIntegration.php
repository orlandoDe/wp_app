<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views\Cpt;

use org\wplake\acf_views\Common\HooksInterface;
use org\wplake\acf_views\Groups\Integration\FieldDataIntegration;
use org\wplake\acf_views\Groups\ItemData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Views\Post;
use org\wplake\acf_views\Views\ViewFactory;
use org\wplake\acf_views\Views\ViewsDataStorage;
use WP_Post;

defined('ABSPATH') || exit;

class ViewsGroupIntegration implements HooksInterface
{
    protected ItemData $item;
    protected ViewsDataStorage $viewsDataStorage;
    protected FieldDataIntegration $fieldIntegration;
    protected ViewsSaveActions $viewsSaveActions;
    protected ViewFactory $viewFactory;

    public function __construct(
        ItemData $item,
        ViewsDataStorage $viewsDataStorage,
        FieldDataIntegration $fieldIntegration,
        ViewsSaveActions $acfViewsSaveActions,
        ViewFactory $viewFactory
    ) {
        $this->item = $item;
        $this->viewsDataStorage = $viewsDataStorage;
        $this->fieldIntegration = $fieldIntegration;
        $this->viewsSaveActions = $acfViewsSaveActions;
        $this->viewFactory = $viewFactory;
    }

    protected function getJsHover(): string
    {
        return 'onMouseOver="this.style.filter=\'brightness(30%)\'" onMouseOut="this.style.filter=\'brightness(100%)\'"';
    }

    protected function addItemToView(
        string $groupKey,
        array $field,
        ViewData $acfViewData,
        array $supportedFieldTypes
    ): ?ItemData {
        $fieldType = $field['type'];

        if (!in_array($fieldType, $supportedFieldTypes, true)) {
            return null;
        }

        $item = $this->item->getDeepClone();
        $item->group = $groupKey;
        $item->field->key = $item->field->createKey($groupKey, $field['key']);

        $acfViewData->items[] = $item;

        return $item;
    }

    protected function printRelatedAcfViews(WP_Post $group, bool $isListLook = false): void
    {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT * from {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'
                      AND FIND_IN_SET(%s,post_content_filtered) > 0",
            ViewsCpt::NAME,
            $group->post_name
        );
        $relatedViews = $wpdb->get_results($query);

        $label = $relatedViews ?
            __('Assigned to Views:', 'acf-views') . ' ' :
            __('Not assigned to any Views.', 'acf-views');

        if (!$isListLook) {
            echo $label;
        }

        $links = [];

        foreach ($relatedViews as $relatedView) {
            $links[] = sprintf(
                '<a href="%s" target="_blank" style="transition:all .3s ease;" %s>%s</a>',
                get_edit_post_link($relatedView),
                $this->getJsHover(),
                get_the_title($relatedView)
            );
        }

        echo implode(', ', $links);

        // ignore on the creation page
        if ('publish' !== $group->post_status) {
            return;
        }

        if (!$relatedViews &&
            $isListLook) {
            echo '';
        }

        echo '<br><br>';


        $label = __('Add new', 'acf-views');

        $style = 'min-height: 0;line-height: 1.2;padding: 3px 7px;font-size:11px;height:auto;transition:all .3s ease;';
        printf(
            '<a href="%s" target="_blank" class="button" style="%s" onmouseover="this.style.color=\'#044767\'" onmouseout="this.style.color=\'#0783BE\'">%s</a>',
            admin_url('/post-new.php?post_type=acf_views&_from=' . $group->ID),
            $style,
            $label
        );
    }

    protected function updateMarkupPreview(array $viewPosts): void
    {
        foreach ($viewPosts as $viewPost) {
            $viewId = (int)$viewPost->ID;

            $relatedViewData = $this->viewsDataStorage->get($viewId);

            // update the markup preview in all the cases (even if View has custom, Preview must be fresh for copy/paste)
            $this->viewsSaveActions->updateMarkup($relatedViewData);
            $relatedViewData->saveToPostContent();
        }
    }

    protected function getRelatedViewLinksWithInvalidCustomMarkup(array $relatedViewPosts): array
    {
        $viewsWithInvalidCustomMarkup = [];

        foreach ($relatedViewPosts as $relatedViewPost) {
            $viewId = (int)$relatedViewPost->ID;

            $relatedViewData = $this->viewsDataStorage->get($viewId);

            // update the markup preview in all the cases (even if View has custom, Preview must be fresh for copy/paste)
            // also, it's necessary to update the markupPreview before the validation
            // as the validation uses the markupPreview as 'canonical' for the 'array' type validation
            $this->viewsSaveActions->updateMarkup($relatedViewData);
            $relatedViewData->saveToPostContent();

            $customMarkup = trim($relatedViewData->customMarkup);

            if (!$customMarkup) {
                continue;
            }

            $view = $this->viewFactory->make(new Post(0), $viewId, 0);
            $isWithError = !!$view->getMarkupValidationError();

            if (!$isWithError) {
                continue;
            }

            $viewsWithInvalidCustomMarkup[] = sprintf(
                "<a target='_blank' href='%s'>%s</a>",
                get_edit_post_link($relatedViewPost),
                get_the_title($relatedViewPost)
            );
        }

        return $viewsWithInvalidCustomMarkup;
    }

    public function addRelatedViewsToAcfGroupsList(array $columns): array
    {
        return array_merge($columns, [
            'relatedAcfViews' => __('Assigned to View', 'acf-views'),
        ]);
    }

    public function addAcfViewsTabToAcfGroup(array $tabs): array
    {
        return array_merge($tabs, [
            'acf_views' => __('Advanced Views', 'acf-views'),
        ]);
    }

    public function printAcfViewsTabOnAcfGroup(array $fieldGroup): void
    {
        $this->printRelatedAcfViews(get_post($fieldGroup['ID']));
    }

    public function maybeCreateViewForGroup(): void
    {
        $screen = get_current_screen();
        $from = (int)($_GET['_from'] ?? 0);
        $fromPost = $from ?
            get_post($from) :
            null;

        $isAddScreen = 'post' === $screen->base &&
            'add' === $screen->action;

        if (ViewsCpt::NAME !== $screen->post_type ||
            !$isAddScreen ||
            !$fromPost ||
            'acf-field-group' !== $fromPost->post_type ||
            'publish' !== $fromPost->post_status) {
            return;
        }
        $viewId = wp_insert_post([
            'post_type' => ViewsCpt::NAME,
            'post_status' => 'publish',
            'post_title' => $fromPost->post_title,
        ]);

        if (is_wp_error($viewId)) {
            return;
        }

        $acfViewData = $this->viewsDataStorage->get($viewId);

        $fields = acf_get_fields($fromPost->ID);
        $supportedFieldTypes = $this->fieldIntegration->getFieldTypes();

        foreach ($fields as $field) {
            $this->addItemToView($fromPost->post_name, $field, $acfViewData, $supportedFieldTypes);
        }

        $this->viewsSaveActions->performSaveActions($viewId);

        wp_redirect(get_edit_post_link($viewId, 'redirect'));
        exit;
    }

    public function printRelatedViewsColumnOnAcfGroupList(string $column, int $postId): void
    {
        if ('relatedAcfViews' !== $column) {
            return;
        }

        $this->printRelatedAcfViews(get_post($postId), true);
    }

    public function validateRelatedViewsOnGroupChange(array $messages): array
    {
        if (!key_exists('acf-field-group', $messages)) {
            return $messages;
        }

        global $post;

        if (!$post ||
            'acf-field-group' !== $post->post_type ||
            'publish' !== $post->post_status) {
            return $messages;
        }

        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT * from {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'
                      AND FIND_IN_SET(%s,post_content_filtered) > 0",
            ViewsCpt::NAME,
            $post->post_name
        );
        $relatedViews = $wpdb->get_results($query);

        $this->updateMarkupPreview($relatedViews);

        $relatedViewLinksWithInvalidCustomMarkup = $this->getRelatedViewLinksWithInvalidCustomMarkup($relatedViews);

        if (!$relatedViewLinksWithInvalidCustomMarkup) {
            return $messages;
        }

        $extra = sprintf(
            "<br><br><span style='color:#dc3232;'>%s %s:</span><br><br>",
            count($relatedViewLinksWithInvalidCustomMarkup),
            __('Views associated with this group contain invalid Custom Markup', 'acf-views')
        );
        $extra .= implode('<br>', $relatedViewLinksWithInvalidCustomMarkup);

        $messages['acf-field-group'][1] .= $extra;

        return $messages;
    }

    public function setHooks(bool $isAdmin): void
    {
        if (!$isAdmin) {
            return;
        }

        add_filter('acf/field_group/additional_group_settings_tabs', [$this, 'addAcfViewsTabToAcfGroup']);
        // higher priority, to run after ACF's listener (they don't use 'merge')
        add_filter('manage_acf-field-group_posts_columns', [$this, 'addRelatedViewsToAcfGroupsList',], 20);
        add_action(
            'acf/field_group/render_group_settings_tab/acf_views',
            [$this, 'printAcfViewsTabOnAcfGroup',],
            10,
            2
        );
        add_action('current_screen', [$this, 'maybeCreateViewForGroup']);
        add_action(
            'manage_acf-field-group_posts_custom_column',
            [$this, 'printRelatedViewsColumnOnAcfGroupList',],
            10,
            2
        );
        // with '20', to make sure it's after ACF
        add_action('post_updated_messages', [$this, 'validateRelatedViewsOnGroupChange'], 20);
    }
}
