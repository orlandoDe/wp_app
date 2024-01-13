<?php

declare(strict_types=1);

namespace org\wplake\acf_views;

use org\wplake\acf_views\Common\HooksInterface;
use org\wplake\acf_views\Dashboard\Dashboard;
use org\wplake\acf_views\Views\Cpt\ViewsCpt;

defined('ABSPATH') || exit;

class Plugin implements HooksInterface
{
    const DOCS_URL = 'https://docs.acfviews.com/getting-started/acf-views-for-wordpress';
    const PRO_VERSION_URL = 'https://wplake.org/acf-views-pro/';
    const PRO_PRICING_URL = 'https://wplake.org/acf-views-pro/#pricing';
    const BASIC_VERSION_URL = 'https://wplake.org/acf-views/';
    const ACF_INSTALL_URL = 'plugin-install.php?s=deliciousbrains&tab=search&type=author';
    const SURVEY_URL = 'https://forms.gle/Wjb16B4mzgLEQvru6';
    const CONFLICTS_URL = 'https://docs.acfviews.com/troubleshooting/compatibility#conflicts';

    protected string $slug = 'acf-views/acf-views.php';
    protected string $shortSlug = 'acf-views';
    protected string $version = '2.4.7';
    protected bool $isProVersion = false;
    protected bool $isSwitchingVersions;

    protected Options $options;
    protected Settings $settings;

    public function __construct(Options $options, Settings $settings)
    {
        $this->options = $options;
        $this->settings = $settings;
        $this->isSwitchingVersions = false;
    }

    // static, as called also in AcfGroup
    public static function isAcfProPluginAvailable(): bool
    {
        return class_exists('acf_pro');
    }

    public static function getThemeTextDomain(): string
    {
        return (string)wp_get_theme()->get('TextDomain');
    }

    public static function getLabelTranslation(string $label, string $textDomain = ''): string
    {
        $textDomain = $textDomain ?: self::getThemeTextDomain();

        // escape quotes to keep compatibility with the generated translation file
        // (quotes there escaped to prevent breaking the PHP string)
        $label = str_replace("'", "&#039;", $label);
        $label = str_replace('"', "&quot;", $label);

        $translation = __($label, $textDomain);

        $translation = str_replace("&#039;", "'", $translation);
        $translation = str_replace("&quot;", '"', $translation);

        return $translation;
    }

    protected function amendProFieldLabelAndInstruction(array $field): array
    {
        $isProField = key_exists('a-pro', $field) &&
            $this->isProFieldLocked();
        $isAcfProField = !$this->isAcfPluginAvailable(true) &&
            key_exists('a-acf-pro', $field);

        if (!$isProField &&
            !$isAcfProField) {
            return $field;
        }

        $type = $field['type'] ?? '';
        $field['label'] = $field['label'] ?? '';
        $field['instructions'] = $field['instructions'] ?? '';

        if ($isProField) {
            $field['label'] = $field['label'] . ' (Pro)';
            if ('tab' !== $type) {
                $label = !$this->isProVersion() ?
                    __('Upgrade to Pro', 'acf-views') :
                    __('Activate your license', 'acf-views');
                $link = !$this->isProVersion() ?
                    Plugin::PRO_VERSION_URL :
                    $this->getAdminUrl(Dashboard::PAGE_PRO);
                $field['instructions'] = sprintf(
                    '<a href="%s" target="_blank">%s</a> %s %s',
                    $link,
                    $label,
                    __('to unlock.', 'acf-views'),
                    "<br>" . $field['instructions']
                );
            }
        }

        if ($isAcfProField) {
            $field['instructions'] = sprintf(
                '(<a href="%s" target="_blank">%s</a> %s) %s',
                'https://www.advancedcustomfields.com/pro/',
                __('ACF Pro', 'acf-views'),
                __('version is required for this feature', 'acf-views'),
                $field['instructions']
            );
        }

        return $field;
    }

    protected function addDeprecatedFieldClass(array $field): array
    {
        if (!key_exists('a-deprecated', $field)) {
            return $field;
        }

        if (!key_exists('wrapper', $field)) {
            $field['wrapper'] = [];
        }

        if (!key_exists('class', $field['wrapper'])) {
            $field['wrapper']['class'] = '';
        }

        $field['wrapper']['class'] .= ' acf-field--deprecated';

        return $field;
    }

    public function isProFieldLocked(): bool
    {
        return !$this->isProVersion() ||
            !$this->settings->isActiveLicense();
    }

    public function getName(): string
    {
        return __('Advanced Views Lite', 'acf-views');
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getShortSlug(): string
    {
        return $this->shortSlug;
    }

    public function getVersion(): string
    {
        // return strval(time());

        return $this->version;
    }

    public function isProVersion(): bool
    {
        return $this->isProVersion;
    }

    public function getAssetsUrl(string $file): string
    {
        return plugin_dir_url(__FILE__) . 'Assets/' . $file;
    }

    public function getAcfProAssetsUrl(string $file): string
    {
        return plugin_dir_url(__FILE__) . 'AcfPro/assets/' . $file;
    }

    public function isAcfPluginAvailable(bool $isProOnly = false): bool
    {
        // don't use 'is_plugin_active()' as the function available lately
        return static::isAcfProPluginAvailable() ||
            (!$isProOnly && class_exists('ACF'));
    }

    public function showWarningAboutInactiveAcfPlugin(): void
    {
        if ($this->isAcfPluginAvailable()) {
            return;
        }

        $acfPluginInstallLink = get_admin_url(null, static::ACF_INSTALL_URL);
        $acfFree = 'https://wordpress.org/plugins/advanced-custom-fields/';
        $acfPro = 'https://www.advancedcustomfields.com/pro/';

        echo sprintf(
            '<div class="notice notice-error">' .
            '<p>%s <a target="_blank" href="%s">%s</a> (<a target="_blank" href="%s">%s</a> %s <a target="_blank" href="%s">%s</a>) %s</p>' .
            '</div>',
            __('"Advanced Views" requires', 'acf-views'),
            $acfPluginInstallLink,
            __('Advanced Custom Fields', 'acf-views'),
            $acfFree,
            __('free', 'acf-views'),
            __('or', 'acf-views'),
            $acfPro,
            __('pro', 'acf-views'),
            __('to be installed and activated.', 'acf-views'),
        );
    }

    public function showWarningAboutOpcacheIssue(): void
    {
        if (!function_exists('ini_get') ||
            '0' !== ini_get('opcache.save_comments')) {
            return;
        }

        $readMoreLink = sprintf(
            '<a target="_blank" href="%s">%s</a>',
            self::CONFLICTS_URL,
            __('Read more', 'acf-views')
        );
        printf(
            '<div class="notice notice-error"><p>%s 
<br>%s %s
</p></div>',
            __(
                'Compatibility issue detected! "Advanced Views" plugin requires "PHPDoc" comments in code.',
                'acf-views'
            ),
            __(
                'Please change the "opcache.save_comments" option in your php.ini file to the default value of "1" on your hosting.',
                'acf-views'
            ),
            $readMoreLink
        );
    }

    public function isCPTScreen(string $cptName, array $targetBase = ['post', 'add',]): bool
    {
        $currentScreen = get_current_screen();

        $isTargetPost = in_array($currentScreen->id, [$cptName,], true) ||
            in_array($currentScreen->post_type, [$cptName], true);

        // base = edit (list management), post (editing), add (adding)
        return $isTargetPost &&
            in_array($currentScreen->base, $targetBase, true);
    }

    public function deactivateOtherInstances(string $activatedPlugin): void
    {
        if (!in_array($activatedPlugin, ['acf-views/acf-views.php', 'acf-views-pro/acf-views-pro.php'], true)) {
            return;
        }

        $pluginToDeactivate = 'acf-views/acf-views.php';
        $deactivatedNoticeId = 1;

        // If we just activated the free version, deactivate the pro version.
        if ($activatedPlugin === $pluginToDeactivate) {
            $pluginToDeactivate = 'acf-views-pro/acf-views-pro.php';
            $deactivatedNoticeId = 2;
        }

        if (is_multisite() &&
            is_network_admin()) {
            $activePlugins = (array)get_site_option('active_sitewide_plugins', []);
            $activePlugins = array_keys($activePlugins);
        } else {
            $activePlugins = (array)get_option('active_plugins', []);
        }

        foreach ($activePlugins as $pluginBasename) {
            if ($pluginToDeactivate !== $pluginBasename) {
                continue;
            }

            $this->options->setTransient(
                Options::TRANSIENT_DEACTIVATED_OTHER_INSTANCES,
                $deactivatedNoticeId,
                1 * HOUR_IN_SECONDS
            );
            // flag that allows to detect this switching. E.g. Twig won't remove the templates dir
            $this->isSwitchingVersions = true;

            deactivate_plugins($pluginBasename);

            return;
        }
    }

    // notice when either Basic or Pro was automatically deactivated
    public function showPluginDeactivatedNotice(): void
    {
        $deactivatedNoticeId = (int)$this->options->getTransient(Options::TRANSIENT_DEACTIVATED_OTHER_INSTANCES);

        // not set = false = 0
        if (!in_array($deactivatedNoticeId, [1, 2,], true)) {
            return;
        }

        $message = sprintf(
            '%s "%s".',
            __(
                "'Advanced Views Lite' and 'Advanced Views Pro' should not be active at the same time. We've automatically deactivated",
                'acf-views'
            ),
            1 === $deactivatedNoticeId ?
                __('Advanced Views Lite', 'acf-views') :
                __('Advanced Views Pro', 'acf-views')
        );

        $this->options->deleteTransient(Options::TRANSIENT_DEACTIVATED_OTHER_INSTANCES);

        echo sprintf(
            '<div class="notice notice-warning">' .
            '<p>%s</p>' .
            '</div>',
            $message
        );
    }

    public function amendFieldSettings(array $field): array
    {
        $field = $this->amendProFieldLabelAndInstruction($field);
        $field = $this->addDeprecatedFieldClass($field);

        return $field;
    }

    public function addClassToAdminProFieldClasses(array $wrapper, array $field): array
    {
        $isProField = key_exists('a-pro', $field) &&
            $this->isProFieldLocked();
        $isAcfProField = !$this->isAcfPluginAvailable(true) &&
            key_exists('a-acf-pro', $field);

        if (!$isProField &&
            !$isAcfProField) {
            return $wrapper;
        }

        if (!key_exists('class', $wrapper)) {
            $wrapper['class'] = '';
        }

        $wrapper['class'] .= ' acf-views-pro';

        return $wrapper;
    }

    public function getAdminUrl(
        string $page = '',
        string $cptName = ViewsCpt::NAME,
        string $base = 'edit.php'
    ): string {
        $pageArg = $page ?
            '&page=' . $page :
            '';

        // don't use just '/wp-admin/x' as some websites can have custom admin url, like 'wp.org/wordpress/wp-admin'
        $pageUrl = get_admin_url(null, $base . '?post_type=');

        return $pageUrl . $cptName . $pageArg;
    }

    public function isSwitchingVersions(): bool
    {
        return $this->isSwitchingVersions;
    }

    // for some reason, ACF ajax form validation doesn't work on the wordpress.com hosting. So need to use a special approach
    public function isWordpressComHosting(): bool
    {
        return defined('WPCOMSH_VERSION') ||
            defined('WPCOM_CORE_ATOMIC_PLUGINS');
    }

    public function setHooks(bool $isAdmin): void
    {
        if (!$isAdmin) {
            return;
        }

        add_action('admin_notices', [$this, 'showWarningAboutInactiveAcfPlugin']);
        add_action('admin_notices', [$this, 'showWarningAboutOpcacheIssue']);
        add_action('activated_plugin', [$this, 'deactivateOtherInstances']);
        add_action('pre_current_active_plugins', [$this, 'showPluginDeactivatedNotice']);

        add_filter('acf/prepare_field', [$this, 'amendFieldSettings']);
        add_filter('acf/field_wrapper_attributes', [$this, 'addClassToAdminProFieldClasses'], 10, 2);
    }
}
