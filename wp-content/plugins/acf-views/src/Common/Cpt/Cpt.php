<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Common\Cpt;

use org\wplake\acf_views\Cards\Cpt\CardsCpt;
use org\wplake\acf_views\Common\CptDataStorage;
use org\wplake\acf_views\Common\HooksInterface;
use org\wplake\acf_views\Plugin;
use org\wplake\acf_views\Views\Cpt\ViewsCpt;
use WP_Post;
use WP_Query;

defined('ABSPATH') || exit;

abstract class Cpt implements HooksInterface
{
    const NAME = '';

    /**
     * @var CptDataStorage
     */
    protected $cptDataStorage;
    protected SaveActions $saveActions;

    public function __construct(CptDataStorage $cptDataStorage, SaveActions $saveActions)
    {
        $this->cptDataStorage = $cptDataStorage;
        $this->saveActions = $saveActions;
    }

    protected function getActionClone(): string
    {
        return static::NAME . '_clone';
    }

    protected function getActionCloned(): string
    {
        return static::NAME . '_cloned';
    }

    protected function isNecessaryHandle(string $handle): bool
    {
        // acf do not include select2 if it's already included (e.g. by woo, or Avada)
        return in_array($handle, ['select2'], true);
    }

    protected function isNecessaryPluginAsset(string $url, string $handle): bool
    {
        $isPlugin = false !== strpos($url, '/wp-content/plugins/');
        $isAcfViews = false !== strpos($url, '/acf-views/') ||
            false !== strpos($url, '/acf-views-pro/');
        $isAcf = false !== strpos($url, '/advanced-custom-fields/') ||
            false !== strpos($url, '/advanced-custom-fields-pro/');
        /**
         * Gutenberg now is already part of WP, but still available as a plugin.
         * Nowadays, this plugin declares 'beta plugin gives you access to the latest Gutenberg features'.
         * Some users use it (for some weird reason).
         * The plugin overrides the core WP editor, so we must keep assets from this plugin.
         */
        $isGutenberg = false !== strpos($url, '/wp-content/plugins/gutenberg/');

        $necessaryHandles = [
            // admin menu groups plugin
            'amg_admin_menu_style',
        ];
        $isNecessaryHandle = $this->isNecessaryHandle($handle) ||
            in_array($handle, $necessaryHandles, true);

        return !$isPlugin ||
            $isAcfViews ||
            $isAcf ||
            $isGutenberg ||
            $isNecessaryHandle;
    }

    protected function isThemeAsset(string $url): bool
    {
        return false !== strpos($url, '/wp-content/themes/');
    }

    protected function removeUnusedPluginAssets(): void
    {
        $styles = wp_styles()->registered;

        foreach ($styles as $styleHandle => $styleData) {
            // can be false or even NULL
            if (!is_string($styleData->src) ||
                $this->isNecessaryPluginAsset($styleData->src, $styleHandle)) {
                continue;
            }

            wp_deregister_style($styleHandle);
        }

        $scripts = wp_scripts()->registered;

        foreach ($scripts as $scriptHandle => $scriptData) {
            // can be false or even NULL
            if (!is_string($scriptData->src) ||
                $this->isNecessaryPluginAsset($scriptData->src, $scriptHandle)) {
                continue;
            }

            wp_deregister_script($scriptHandle);
        }
    }

    protected function removeThemeAssets(): void
    {
        $styles = wp_styles()->registered;

        foreach ($styles as $styleHandle => $styleData) {
            // can be false or even NULL
            if (!is_string($styleData->src) ||
                !$this->isThemeAsset($styleData->src) ||
                $this->isNecessaryHandle($styleHandle)) {
                continue;
            }

            wp_deregister_style($styleHandle);
        }

        $scripts = wp_scripts()->registered;

        foreach ($scripts as $scriptHandle => $scriptData) {
            // can be false or even NULL
            if (!is_string($scriptData->src) ||
                !$this->isThemeAsset($scriptData->src) ||
                $this->isNecessaryHandle($scriptHandle)) {
                continue;
            }

            wp_deregister_script($scriptHandle);
        }
    }

    protected function removeUnusedWordPressStyles(): void
    {
        $styles = wp_styles()->registered;

        $necessaryStyles = [
            'dashicons',
            'admin-bar',
            'buttons',
            'common',
            'wp-components',
            'forms',
            'wp-reset-editor-styles',
            'wp-block-editor-content',
            'wp-edit-post',
        ];

        foreach ($styles as $styleHandle => $styleData) {
            if (is_bool($styleData->src)) {
                continue;
            }

            $isWPAsset = false !== strpos($styleData->src, '/wp-includes/');

            if (!$isWPAsset ||
                in_array($styleHandle, $necessaryStyles, true)) {
                continue;
            }

            unset($necessaryStyles[$styleHandle]);

            // trick to avoid the style be enqueued if used somewhere as dependency
            wp_deregister_style($styleHandle);
            wp_register_style($styleHandle, false);
        }

        // some necessary styles enqueued as dependencies, as we removed all the extra, we must enqueue them directly
        foreach ($necessaryStyles as $necessaryStyle) {
            wp_enqueue_style($necessaryStyle);
        }
    }

    protected function removeUnusedWordPressScripts(): void
    {
        $scriptsToOverride = [
            //// wp media
            'wp-color-picker',
            'wp-color-picker-alpha',
            'wp-link',
            //// blocks
            'wp-format-library',
            'wp-wordcount',
            'wp-block-directory',
            'wp-server-side-render',
            //// wp general
            'wp-pointer',
            'thickbox',
            'mce-view',
            'quicktags',
            'wp-shortcode',
            'wp-embed',
            'svg-painter',
            //// acf
            'acf-color-picker-alpha',
            'acf-timepicker',
            'acf-blocks',
            'acf-pro-ui-options-page',
        ];

        $scriptsToDeregister = [
            //// wp media
            'media-widgets',
            'media-audio-widget',
            'media-video-widget',
            'media-gallery-widget',
        ];

        // for some plain 'wp_dequeue' cause issues
        // (as they marked as dependencies, and avoid loading of right scripts)
        // so use the trick with deregister and register again

        foreach ($scriptsToOverride as $scriptToOverride) {
            wp_deregister_script($scriptToOverride);
            wp_register_script($scriptToOverride, false);
        }

        // some scripts and plain, and can be deregistered
        // it's even necessary, as they have 'wp_localize_script' that contains 'calls' of the missing scripts

        foreach ($scriptsToDeregister as $scriptToDeregister) {
            wp_deregister_script($scriptToDeregister);
        }
    }

    // for early checks, when get_current_screen isn't available

    protected function isMyRestRequest(): bool
    {
        $requestUrl = $_SERVER['REQUEST_URI'] ?? '';
        return false !== strpos($requestUrl, '/wp-json/') &&
            false !== strpos($requestUrl, '/' . static::NAME . '/');
    }

    protected function isMyEditOrAddPostPage(): bool
    {
        if (!is_admin()) {
            return false;
        }

        // A. if current_screen is already available

        if (function_exists('get_current_screen')) {
            $currentScreen = get_current_screen();

            return $currentScreen->post_type === static::NAME &&
                in_array($currentScreen->base, ['post', 'add',], true);
        }

        // B. manual detection for early calls

        $requestUrl = $_SERVER['REQUEST_URI'] ?? '';
        $postType = sanitize_text_field($_GET['post_type'] ?? '');
        $action = sanitize_text_field($_GET['action'] ?? '');

        $isMyPostPage = false !== strpos($requestUrl, '/post-new.php') &&
            static::NAME === $postType;
        $isEditPage = false !== strpos($requestUrl, '/post.php') &&
            'edit' == $action;
        $isMyEditPage = false;

        if ($isEditPage) {
            $postId = (int)($_GET['post'] ?? 0);
            $isMyEditPage = static::NAME === (string)get_post_type($postId);
        }

        return $isMyEditPage ||
            $isMyPostPage;
    }

    public function insertIntoArrayAfterKey(array $array, string $key, array $newItems): array
    {
        $keys = array_keys($array);
        $index = array_search($key, $keys);

        $pos = false === $index ?
            count($array) :
            $index + 1;

        return array_merge(array_slice($array, 0, $pos), $newItems, array_slice($array, $pos));
    }

    // Gutenberg will try to update the content by the presented value, which is empty, so ignore it
    public function avoidOverridePostContentByGutenberg(array $data): array
    {
        if (!key_exists('post_type', $data) ||
            !in_array($data['post_type'], [ViewsCpt::NAME, CardsCpt::NAME], true)) {
            return $data;
        }

        // avoid any attempts, even not empty (we use direct DB query, so it's safe)
        if (key_exists('post_content', $data)) {
            unset($data['post_content']);
        }

        return $data;
    }

    // add the ACF's class to the body to have a nice look of the list table
    public function maybeAddAcfClassToBody(string $classes): string
    {
        $currentScreen = get_current_screen();

        $isOurPost = in_array($currentScreen->post_type, [ViewsCpt::NAME, CardsCpt::NAME], true);

        if ($isOurPost &&
            'edit' === $currentScreen->base) {
            $classes .= ' acf-internal-post-type';
        }

        return $classes;
    }

    public function addPostNameToSearch(WP_Query $query): void
    {
        $postType = $query->query_vars['post_type'] ?? '';

        if (!is_admin() ||
            !in_array($postType, [ViewsCpt::NAME, CardsCpt::NAME,], true) ||
            !$query->is_main_query() ||
            !$query->is_search()) {
            return;
        }

        $search = $query->query_vars['s'];

        if (13 !== strlen($search) ||
            !preg_match('/^[a-z0-9]+$/', $search)) {
            return;
        }

        $prefix = $postType === ViewsCpt::NAME ?
            'view_' :
            'card_';

        $query->set('s', '');
        $query->set('name', $prefix . $search);
    }

    public function cloneItemAction(): void
    {
        if (!isset($_GET[$this->getActionClone()])) {
            return;
        }

        $postId = (int)$_GET[$this->getActionClone()];
        $post = get_post($postId);

        if (!$post ||
            static::NAME !== $post->post_type) {
            return;
        }

        check_admin_referer('bulk-posts');

        $args = [
            'post_type' => $post->post_type,
            'post_status' => 'draft',
            'post_title' => $post->post_title . ' ' . __('Clone', 'acf-views'),
            'post_author' => $post->post_author,
        ];

        $newPostId = wp_insert_post($args);

        // something went wrong
        if (is_wp_error($newPostId)) {
            return;
        }

        $instanceData = $this->cptDataStorage->get($postId)->getDeepClone();
        $instanceData->setSource($newPostId);

        $prefix = static::NAME === ViewsCpt::NAME ?
            'view_' :
            'card_';
        $this->saveActions->maybeSetUniqueId($instanceData, $prefix);
        // save JSON to the post_content (also will save POST_FIELD_MOUNT_POINTS and others)
        $instanceData->saveToPostContent();

        $targetUrl = get_admin_url(null, '/edit.php?post_type=' . static::NAME);
        $targetUrl .= '&' . $this->getActionCloned() . '=1';

        wp_redirect($targetUrl);
        exit;
    }

    public function showItemClonedMessage(): void
    {
        if (!isset($_GET[$this->getActionCloned()])) {
            return;
        }

        echo '<div class="notice notice-success">' .
            sprintf('<p>%s</p>', __('Item success cloned.', 'acf-views')) .
            '</div>';
    }

    public function getRowActions(array $actions, WP_Post $view): array
    {
        if (static::NAME !== $view->post_type) {
            return $actions;
        }

        $trash = str_replace(
            '>Trash<',
            sprintf('>%s<', __('Delete', 'acf-views')),
            $actions['trash'] ?? ''
        );

        // quick edit
        unset($actions['inline hide-if-no-js']);
        unset($actions['trash']);

        $cloneLink = get_admin_url(null, '/edit.php?post_type=' . static::NAME);
        $cloneLink .= '&' . $this->getActionClone() . '=' . $view->ID . '&_wpnonce=' . wp_create_nonce(
                'bulk-posts'
            );
        $actions['clone'] = sprintf("<a href='%s'>%s</a>", $cloneLink, __('Clone', 'acf-views'));
        $actions['trash'] = $trash;

        return $actions;
    }

    public function printPostTypeDescription($views)
    {
        $screen = get_current_screen();
        $postType = get_post_type_object($screen->post_type);

        if ($postType->description) {
            // don't use esc_html as it contains links
            printf('<p>%s</p>', $postType->description);
        }

        return $views; // return original input unchanged
    }

    /**
     * Otherwise in case editing fields (without saving) and reloading a page,
     * then the fields have these unsaved values, it's wrong and breaks logic (e.g. of group-field selects)
     */
    public function disableAutocompleteForPostEdit(WP_Post $post): void
    {
        if (static::NAME !== $post->post_type) {
            return;
        }

        echo ' autocomplete="off"';
    }

    public function removeUnusedAssetsFromEditScreen(): void
    {
        $currentScreen = get_current_screen();

        if ($currentScreen->post_type !== static::NAME ||
            !in_array($currentScreen->base, ['post', 'add',], true)) {
            return;
        }

        $this->removeUnusedWordPressStyles();
        $this->removeUnusedWordPressScripts();
        $this->removeUnusedPluginAssets();
        $this->removeThemeAssets();
    }

    public function printSurveyLink(string $html): string
    {
        $currentScreen = get_current_screen();

        if ($currentScreen->post_type !== static::NAME) {
            return $html;
        }

        $content = sprintf(
            '%s <a target="_blank" href="%s">%s</a> %s <a target="_blank" href="%s">%s</a>.',
            __('Thank you for creating with', 'acf-views'),
            'https://wordpress.org/',
            __('WordPress', 'acf-views'),
            __('and', 'acf-views'),
            Plugin::BASIC_VERSION_URL,
            __('Advanced Views', 'acf-views')
        );
        $content .= " " . sprintf(
                "<span>%s <a target='_blank' href='%s'>%s</a> %s</span>",
                __('Take', 'acf-views'),
                Plugin::SURVEY_URL,
                __('2 minute survey', 'acf-views'),
                __('to improve the Advanced Views plugin.', 'acf-views')
            );

        return sprintf(
            '<span id="footer-thankyou">%s</span>',
            $content
        );
    }

    public function maybeShowErrorThatGutenbergEditorIsSuppressed(): void
    {
        $currentScreen = get_current_screen();

        if ($currentScreen->post_type !== static::NAME ||
            !in_array($currentScreen->base, ['post', 'add',], true) ||
            $currentScreen->is_block_editor()) {
            return;
        }

        printf(
            '<p style="position: fixed;right: 20px;bottom: 20px;z-index: 9999; color: red;max-width:500px;font-size:13px;">%s</p>',
            __(
                'Advanced Views error: The Gutenberg editor is disabled, indicating a potential compatibility issue. Please <a target="_blank" href="https://wplake.org/acf-views-support/">reach out</a> to our support team for further assistance.',
                'acf-views'
            )
        );
    }

    // Jetpack's markdown module "very polite" and breaks json in our post_content
    public function disableJetpackMarkdownModule(): void
    {
        if (!class_exists('WPCom_Markdown') ||
            // check for future version
            !is_callable(['WPCom_Markdown', 'get_instance'])) {
            return;
        }

        // only for our edit screens
        if (!$this->isMyEditOrAddPostPage() &&
            !$this->isMyRestRequest()) {
            return;
        }

        $markdown = \WPCom_Markdown::get_instance();
        remove_action('init', [$markdown, 'load']);
    }

    public function hideExcerptFromExtendedListView($excerpt, WP_Post $post): string
    {
        if (static::NAME !== $post->post_type) {
            return $excerpt;
        }

        $isEditPage = false !== strpos($_SERVER['REQUEST_URI'], '/edit.php');
        $cpt = (string)($_GET['post_type'] ?? '');

        if (!$isEditPage ||
            static::NAME !== $cpt) {
            return $excerpt;
        }

        return '';
    }

    // https://wordpress.org/plugins/classic-editor/
    // make sure the editor choosing is allowed on our pages (otherwise the second hook won't be called)
    /**
     * @param array|false $settings
     * @return array|false
     */
    public function classicEditorPluginSettingsPatch($settings)
    {
        if (!$this->isMyEditOrAddPostPage()) {
            return $settings;
        }

        return [
            'allow-users' => 'true',
        ];
    }

    // https://wordpress.org/plugins/classic-editor/
    // make sure Gutenberg is always used for our CPT
    public function disableClassicEditorPluginForCpt(array $editors, string $postType): array
    {
        if (static::NAME !== $postType) {
            return $editors;
        }

        return [
            'classic_editor' => false,
            'block_editor' => true,
        ];
    }

    public function forceGutenbergForCptPages(bool $isUseBlockEditor, string $postType): bool
    {
        return static::NAME === $postType ?
            true :
            $isUseBlockEditor;
    }

    public function setHooks(bool $isAdmin): void
    {
        add_filter('wp_insert_post_data', [$this, 'avoidOverridePostContentByGutenberg']);

        if (!$isAdmin) {
            return;
        }

        // Note: do not use ob_start in admin, it causes compatibility issues with some themes (Avada)

        add_action('admin_init', [$this, 'cloneItemAction']);
        add_action('admin_notices', [$this, 'showItemClonedMessage']);

        add_action('post_edit_form_tag', [$this, 'disableAutocompleteForPostEdit']);
        add_action('pre_get_posts', [$this, 'addPostNameToSearch']);
        // print is later than 'admin_enqueue_scripts'
        add_action('admin_print_styles', [$this, 'removeUnusedAssetsFromEditScreen'], 99);
        add_action('admin_footer', [$this, 'maybeShowErrorThatGutenbergEditorIsSuppressed']);
        // priority '9' is earlier than Jetpack's
        add_action('init', [$this, 'disableJetpackMarkdownModule'], 9);

        add_filter('views_edit-' . static::NAME, [$this, 'printPostTypeDescription',]);
        add_filter('post_row_actions', [$this, 'getRowActions',], 10, 2);

        add_filter('admin_body_class', [$this, 'maybeAddAcfClassToBody']);
        add_filter('admin_footer_text', [$this, 'printSurveyLink']);
        add_filter('get_the_excerpt', [$this, 'hideExcerptFromExtendedListView'], 10, 2);

        add_filter('classic_editor_plugin_settings', [$this, 'classicEditorPluginSettingsPatch',]);
        add_filter(
            'classic_editor_enabled_editors_for_post_type',
            [$this, 'disableClassicEditorPluginForCpt',],
            10,
            2
        );

        // very important to avoid Gutenberg to be suppressed on CPT pages by some theme or plugins (Divi theme, etc)
        add_filter('use_block_editor_for_post_type', [$this, 'forceGutenbergForCptPages'], 99999, 2);
    }
}
