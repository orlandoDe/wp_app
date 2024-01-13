<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Common;

use org\wplake\acf_views\Settings;

defined('ABSPATH') || exit;

abstract class Shortcode implements HooksInterface
{
    const NAME = '';

    protected Settings $settings;
    protected CptDataStorage $cptDataStorage;

    public function __construct(Settings $settings, CptDataStorage $cptDataStorage)
    {
        $this->settings = $settings;
        $this->cptDataStorage = $cptDataStorage;
    }

    abstract public function render($attrs): string;

    protected function isShortcodeAvailableForUser(array $userRoles, array $shortcodeArgs): bool
    {
        $userWithRoles = (string)($shortcodeArgs['user-with-roles'] ?? '');
        $userWithRoles = trim($userWithRoles);
        $userWithRoles = $userWithRoles ?
            explode(',', $userWithRoles) :
            [];

        $userWithoutRoles = (string)($shortcodeArgs['user-without-roles'] ?? '');
        $userWithoutRoles = trim($userWithoutRoles);
        $userWithoutRoles = $userWithoutRoles ?
            explode(',', $userWithoutRoles) :
            [];

        if (!$userWithRoles &&
            !$userWithoutRoles) {
            return true;
        }

        $userHasAllowedRoles = !!array_intersect($userWithRoles, $userRoles);
        $userHasDeniedRoles = !!array_intersect($userWithoutRoles, $userRoles);

        if (($userWithRoles && !$userHasAllowedRoles) ||
            ($userWithoutRoles && $userHasDeniedRoles)) {
            return false;
        }

        return true;
    }

    protected function getErrorMarkup(string $shortcode, array $args, string $error): string
    {
        $attrs = [];
        foreach ($args as $name => $value) {
            $attrs[] = sprintf('%s="%s"', $name, $value);
        }
        return sprintf(
            "<p style='color:red;'>%s %s %s</p>",
            __('Shortcode error:', 'acf-views'),
            $error,
            sprintf('(%s %s)', $shortcode, implode(' ', $attrs))
        );
    }

    public function maybeAddQuickLink(string $html, int $postId): string
    {
        $roles = wp_get_current_user()->roles;

        if (!$this->settings->isDevMode() ||
            !in_array('administrator', $roles, true)) {
            return $html;
        }

        $tagName = $this->cptDataStorage->get($postId)->getTagName();
        $regex = sprintf('/<\/%s>[\r\n ]*$/', $tagName);

        preg_match($regex, $html, $matches, PREG_OFFSET_CAPTURE);

        if (1 !== count($matches) ||
            2 !== count($matches[0])) {
            return $html;
        }

        $closingDiv = $matches[0][0];
        $closingDivPosition = (int)$matches[0][1];

        $label = __('Edit', 'acf-views');
        $label .= sprintf(' "%s"', get_the_title($postId));

        $editLink = sprintf(
            '<a href="%s" target="_blank" class="acf-views__quick-link" style="display:block;color:#008BB7;transition: all .3s ease;text-decoration: none;font-size: 12px;white-space: nowrap;opacity:.5;padding:3px 0;" onMouseOver="this.style.opacity=\'1\';this.style.textDecoration=\'underline\'" onMouseOut="this.style.opacity=\'.5\';this.style.textDecoration=\'none\'">%s</a>',
            get_edit_post_link($postId),
            $label
        );

        return substr_replace(
            $html,
            $editLink . $closingDiv,
            $closingDivPosition,
            strlen($closingDiv)
        );
    }

    public function setHooks(bool $isAdmin): void
    {
        add_shortcode(static::NAME, [$this, 'render']);
    }
}