<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views;

use org\wplake\acf_views\Common\Shortcode;
use org\wplake\acf_views\Settings;
use org\wplake\acf_views\Views\Cpt\ViewsCpt;
use WP_Comment;
use WP_Term;

defined('ABSPATH') || exit;

class ViewShortcode extends Shortcode
{
    const NAME = ViewsCpt::NAME;

    protected ViewFactory $viewFactory;

    // used to avoid recursion with post_object/relationship fields
    protected array $displayingView;
    protected int $queryLoopPostId;

    public function __construct(Settings $settings, ViewsDataStorage $viewsDataStorage, ViewFactory $viewFactory)
    {
        parent::__construct($settings, $viewsDataStorage);

        $this->viewFactory = $viewFactory;

        $this->displayingView = [];
        // don't use '0' as the default, because it can be 0 in the 'render_callback' hook
        $this->queryLoopPostId = -1;
    }

    /**
     * block theme: skip execution for the Gutenberg common call, as query-loop may be used, and the post id won't be available yet
     * Exceptions:
     * 1. if the object-id is set, e.g. as part of the Card shortcode
     * 2. If mount-point is set, e.g. as part of the MountPoint functionality
     */
    protected function maybeSkipShortcode(string $objectId, array $attrs): string
    {
        $isMountPoint = isset($attrs['mount-point']);

        // we shouldn't skip shortcode if it's inner (View's shortcode in another View), otherwise it won't be rendered
        $isInnerShortcode = count($this->displayingView) > 0;

        if (!wp_is_block_theme() ||
            -1 !== $this->queryLoopPostId ||
            $isInnerShortcode ||
            $objectId ||
            $isMountPoint) {
            return '';
        }

        $stringAttrs = array_map(
            function ($key, $value) {
                return sprintf('%s="%s"', $key, $value);
            },
            array_keys($attrs),
            array_values($attrs)
        );

        return sprintf('[%s %s]', self::NAME, implode(' ', $stringAttrs));
    }

    protected function getDataPostId(
        string $objectId,
        int $currentPageId,
        int $userId,
        int $termId,
        int $commentId
    ): string {
        switch ($objectId) {
            case 'options':
                return 'options';
            case '$user$':
            case 'user':
                return 'user_' . $userId;
            case '$term$':
            case 'term':
                return 'term_' . $termId;
            case 'comment':
                return 'comment_' . $commentId;
        }

        global $post;

        // a. dataPostId from the shortcode argument

        $dataPostId = (int)$objectId;

        // b. from the Gutenberg query loop

        if (!in_array($this->queryLoopPostId, [-1, 0,], true)) {
            $dataPostId = $dataPostId ?: $this->queryLoopPostId;
        }

        // c. dataPostId from the current loop (WordPress posts, WooCommerce products...)

        $dataPostId = $dataPostId ?: ($post->ID ?? 0);

        // d. dataPostId from the current page

        $dataPostId = $dataPostId ?: $currentPageId;

        // validate the ID

        return (string)(get_post($dataPostId) ?
            $dataPostId :
            0);
    }

    public function render($attrs): string
    {
        $attrs = $attrs ?
            (array)$attrs :
            [];

        $viewId = (string)($attrs['view-id'] ?? 0);
        $objectId = (string)($attrs['object-id'] ?? 0);

        $viewId = $this->cptDataStorage->getPostIdByUniqueId($viewId, ViewsCpt::NAME);

        if (!$viewId) {
            return $this->getErrorMarkup(
                self::NAME,
                $attrs,
                __('view-id attribute is missing or wrong', 'acf-views')
            );
        }

        $skippedShortcode = $this->maybeSkipShortcode($objectId, $attrs);

        if ($skippedShortcode) {
            return $skippedShortcode;
        }

        if (!$this->isShortcodeAvailableForUser(wp_get_current_user()->roles, $attrs)) {
            return '';
        }

        // equals to 0 on WooCommerce Shop Page, but in this case pageID can't be gotten with built-in WP functions
        // also works in the taxonomy case
        $currentPageId = get_queried_object_id();

        $userId = (string)($attrs['user-id'] ?? get_current_user_id());
        // validate
        $userId = get_user_by('id', $userId)->ID ?? 0;

        // do not use 'get_queried_object_id()' as default value, because PostID can meet some TermId
        $termId = (int)($attrs['term-id'] ?? 0);
        // validate
        $termId = ($termId && get_term($termId) instanceof WP_Term) ?
            $termId :
            0;

        if (!$termId) {
            $menuSlug = (string)($attrs['menu-slug'] ?? '');
            $menuTerm = $menuSlug ?
                get_term_by('slug', $menuSlug, 'nav_menu') :
                null;
            $termId = $menuTerm->term_id ?? 0;
        }

        // load the default value, only if the 'menu-slug' is missing and current page is a taxonomy page
        $termId = !$termId && get_queried_object() instanceof WP_Term ?
            get_queried_object()->term_id :
            $termId;

        $commentId = (int)($attrs['comment-id'] ?? 0);
        // validate
        $commentId = ($commentId && get_comment($commentId) instanceof WP_Comment) ?
            $commentId :
            0;

        // enable the $term$ mode by default, if we're on a taxonomy page (and nothing was set)
        $objectId = is_tax() && !$objectId ?
            'term' :
            $objectId;

        $dataPostId = $this->getDataPostId(
            $objectId,
            $currentPageId,
            $userId,
            $termId,
            $commentId
        );

        if (!$dataPostId) {
            return $this->getErrorMarkup(
                self::NAME,
                $attrs,
                __('object-id argument contains the wrong value', 'acf-views')
            );
        }

        // recursionKey must consist from both. It's allowed to use the same View for a post_object field, but with another id
        $recursionKey = $viewId . '-' . $dataPostId;

        /*
         * In case with post_object and relationship fields can be a recursion
         * e.g. There is a post_object field. PostA contains link to PostB. PostB contains link to postA. View displays PostA...
         * In this case just return empty string, without any error message (so user can display PostB in PostA without issues)
         */
        if (isset($this->displayingView[$recursionKey])) {
            return '';
        }

        $classes = (string)($attrs['class'] ?? '');

        $this->displayingView[$recursionKey] = true;

        $post = new Post($dataPostId, [], false, $userId, $termId, $commentId);
        $html = $this->viewFactory->makeAndGetHtml($post, $viewId, $currentPageId, true, $classes);

        unset($this->displayingView[$recursionKey]);

        return $this->maybeAddQuickLink($html, $viewId);
    }

    /**
     * The issue that for now (6.3), Gutenberg shortcode element doesn't support context.
     * So if you place shortcode in the Query Loop template, it's impossible to get the post ID.
     * Furthermore, it seems Gutenberg renders all the shortcodes at once, before blocks parsing.
     * Which means even hooking into 'register_block_type_args' won't work by default, because in the 'render_callback'
     * it'll receive already rendered shortcode's content. So having the postId is too late here.
     *
     * https://github.com/WordPress/gutenberg/issues/43053
     * https://support.advancedcustomfields.com/forums/topic/add-custom-field-to-query-loop/
     * https://wptavern.com/wordpress-6-2-2-restores-shortcode-support-in-block-templates-fixes-security-issue
     */
    public function extendGutenbergShortcode(array $args, string $name): array
    {
        if (!wp_is_block_theme() ||
            'core/shortcode' !== $name) {
            return $args;
        }

        $args['usesContext'] = $args['usesContext'] ?? [];
        $args['usesContext'][] = 'postId';
        $args['render_callback'] = function ($attributes, $content, $block) {
            // can be 0, if the shortcode is outside of the query loop
            $postId = (int)($block->context['postId'] ?? 0);

            if (false === strpos($content, '[' . self::NAME)) {
                return $content;
            }

            $this->queryLoopPostId = $postId;

            $content = do_shortcode($content);

            // don't use '0' as the default, because it can be 0 in the 'render_callback' hook
            $this->queryLoopPostId = -1;

            return $content;
        };

        return $args;
    }

    public function setHooks(bool $isAdmin): void
    {
        parent::setHooks($isAdmin);

        add_filter('register_block_type_args', [$this, 'extendGutenbergShortcode'], 10, 2);
    }
}