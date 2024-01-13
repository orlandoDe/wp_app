<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Dashboard;

use org\wplake\acf_views\Cards\Cpt\CardsCpt;
use org\wplake\acf_views\Common\HooksInterface;
use org\wplake\acf_views\Groups\Integration\AcfIntegration;
use org\wplake\acf_views\Html;
use org\wplake\acf_views\Plugin;
use org\wplake\acf_views\Views\Cpt\ViewsCpt;
use WP_Screen;

defined('ABSPATH') || exit;

class Dashboard implements HooksInterface
{
    const PAGE_DEMO_IMPORT = 'demo-import';
    const PAGE_DOCS = 'docs';
    const PAGE_SURVEY = 'survey';
    // constant is in use in Lite too, so should be here, not in Pro
    const PAGE_PRO = 'pro';
    const URL_SUPPORT = 'https://wordpress.org/support/plugin/acf-views/';

    protected Plugin $plugin;
    /**
     * @var Html
     */
    protected $html;
    protected DemoImport $demoImport;
    protected AcfIntegration $acfIntegration;

    public function __construct(Plugin $plugin, Html $html, DemoImport $demoImport, AcfIntegration $acfIntegration)
    {
        $this->plugin = $plugin;
        $this->html = $html;
        $this->demoImport = $demoImport;
        $this->acfIntegration = $acfIntegration;
    }

    protected function getProBanner(): array
    {
        return $this->html->getProBanner(Plugin::PRO_VERSION_URL, $this->plugin->getAssetsUrl('pro.png'));
    }

    protected function getVideoReview(): string
    {
        return 'https://www.youtube.com/embed/0Vv23bmYzzo';
    }

    protected function getPages(): array
    {
        return [
            [
                'isLeftBlock' => true,
                'url' => $this->plugin->getAdminUrl(),
                'label' => __('Views', 'acf-views'),
                'isActive' => false,
                'isSecondary' => false,
            ],
            [
                'isLeftBlock' => true,
                'url' => $this->plugin->getAdminUrl('', CardsCpt::NAME),
                'label' => __('Cards', 'acf-views'),
                'isActive' => false,
                'isSecondary' => false,
            ],
            [
                'isLeftBlock' => true,
                'url' => $this->plugin->getAdminUrl(SettingsPage::SLUG),
                'label' => __('Settings', 'acf-views'),
                'isActive' => false,
                'isSecondary' => false,
            ],
            [
                'isLeftBlock' => true,
                'url' => $this->plugin->getAdminUrl(Tools::SLUG),
                'label' => __('Tools', 'acf-views'),
                'isActive' => false,
                'isSecondary' => false,
            ],
            [
                'isLeftBlock' => true,
                'url' => Plugin::PRO_VERSION_URL,
                'isBlank' => true,
                'label' => __('Get PRO', 'acf-views'),
                'isActive' => false,
                'icon' => '<i class="av-toolbar__external-icon dashicons dashicons-star-filled"></i>',
                'isSecondary' => false,
            ],
            [
                'isRightBlock' => true,
                'url' => $this->plugin->getAdminUrl(self::PAGE_DEMO_IMPORT),
                'label' => __('Demo Import', 'acf-views'),
                'isActive' => false,
                'isSecondary' => true,
            ],
            [
                'isRightBlock' => true,
                'url' => $this->plugin->getAdminUrl(self::PAGE_DOCS),
                'label' => __('Docs', 'acf-views'),
                'isActive' => false,
                'isSecondary' => false,
            ],
            [
                'isRightBlock' => true,
                // static to be overridden in child
                'url' => static::URL_SUPPORT,
                'label' => __('Support', 'acf-views'),
                'isActive' => false,
                'isSecondary' => false,
                'icon' => '<i class="av-toolbar__license-icon dashicons dashicons-external"></i>',
                'isBlank' => true,
            ],
        ];
    }

    protected function getCurrentAdminUrl(): string
    {
        $uri = isset($_SERVER['REQUEST_URI']) ?
            esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) :
            '';
        $uri = preg_replace('|^.*/wp-admin/|i', '', $uri);

        if (!$uri) {
            return '';
        }

        return admin_url($uri);
    }

    protected function getPlugin(): Plugin
    {
        return $this->plugin;
    }

    public function addPages(): void
    {
        add_submenu_page(
            sprintf('edit.php?post_type=%s', ViewsCpt::NAME),
            __('Demo import', 'acf-views'),
            __('Demo import', 'acf-views'),
            'edit_posts',
            self::PAGE_DEMO_IMPORT,
            [$this, 'getImportPage']
        );
        add_submenu_page(
            sprintf('edit.php?post_type=%s', ViewsCpt::NAME),
            __('Docs', 'acf-views'),
            __('Docs', 'acf-views'),
            'edit_posts',
            self::PAGE_DOCS,
            function () {
                printf(
                    '<iframe src="%s" style="border: 0;width: calc(100%% + 20px);height: calc(100vh - 32px - 65px);margin-left: -20px;"></iframe>',
                    Plugin::DOCS_URL
                );
            }
        );
        add_submenu_page(
            sprintf('edit.php?post_type=%s', ViewsCpt::NAME),
            __('Survey', 'acf-views'),
            __('Survey', 'acf-views'),
            'edit_posts',
            self::PAGE_SURVEY,
            function () {
                printf(
                    '<iframe src="%s" style="border: 0;width: calc(100%% + 20px);height: calc(100vh - 32px - 65px);margin-left: -20px;"></iframe>',
                    Plugin::SURVEY_URL
                );
            }
        );
    }

    public function getHeader(): void
    {
        $tabs = $this->getPages();

        $currentUrl = $this->getCurrentAdminUrl();
        $acfViewsListUrl = $this->plugin->getAdminUrl();
        $acfCardsListUrl = $this->plugin->getAdminUrl('', CardsCpt::NAME);

        $currentScreen = get_current_screen();
        $isEditScreen = $currentScreen && 'post' === $currentScreen->base && !$currentScreen->action;
        $isAddScreen = $currentScreen && 'post' === $currentScreen->base && 'add' === $currentScreen->action;
        $isActiveChild = ($isEditScreen || $isAddScreen);
        $isActiveAcfViewsChild = $isActiveChild && $currentScreen && $currentScreen->post_type === ViewsCpt::NAME;
        $isActiveAcfCardsChild = $isActiveChild && $currentScreen && $currentScreen->post_type === CardsCpt::NAME;

        foreach ($tabs as &$tab) {
            $isAcfViewsListPage = $tab['url'] === $acfViewsListUrl;
            $isAcfCardsListPage = $tab['url'] === $acfCardsListUrl;

            $isActiveChild = $isAcfViewsListPage && $isActiveAcfViewsChild;
            $isActiveChild = $isActiveChild || ($isAcfCardsListPage && $isActiveAcfCardsChild);

            if ($currentUrl !== $tab['url'] &&
                !$isActiveChild) {
                continue;
            }

            $tab['isActive'] = true;
            break;
        }

        echo $this->html->dashboardHeader($this->plugin->getName(), $this->plugin->getVersion(), $tabs);
    }

    public function getImportPage(): void
    {
        $isWithDeleteButton = false;

        $formMessage = '';

        if ($this->demoImport->isProcessed()) {
            if (!$this->demoImport->isHasError()) {
                $message = $this->demoImport->isImportRequest() ?
                    __("Import was successful. You’re all set!", 'acf-views') :
                    __('All demo objects have been deleted.', 'acf-views');
                $formMessage .= sprintf('<p class="av-introduction__title">%s</p>', $message);
            } else {
                $message = __('Request is failed.', 'acf-views');
                $formMessage .= sprintf(
                    '<p class="av-introduction__title">%s</p><br><br>%s',
                    $message,
                    $this->demoImport->getError()
                );
            }
        } else {
            $this->demoImport->readIDs();
        }

        if ($this->demoImport->isHasData() &&
            !$this->demoImport->isHasError()) {
            $isWithDeleteButton = true;
            $formMessage .= sprintf(
                '<p class="av-introduction__title">%s</p>',
                __('Imported items', 'acf-views')
            );

            $formMessage .= sprintf(
                '<p><b>%s</b></p>',
                __("Display page's ACF fields on the same page", 'acf-views')
            );
            $formMessage .= sprintf(
                '<a target="_blank" href="%s">%s</a><br><br>',
                $this->demoImport->getSamsungLink(),
                __('"Samsung Galaxy A53" Page', 'acf-views')
            );
            $formMessage .= sprintf(
                '<a target="_blank" href="%s">%s</a><br><br>',
                $this->demoImport->getNokiaLink(),
                __('"Nokia X20" Page', 'acf-views')
            );
            $formMessage .= sprintf(
                '<a target="_blank" href="%s">%s</a><br><br>',
                $this->demoImport->getXiaomiLink(),
                __('"Xiaomi 12T" Page', 'acf-views')
            );
            $formMessage .= sprintf(
                '<a target="_blank" href="%s">%s</a><br><br>',
                $this->demoImport->getAcfGroupLink(),
                __('"Phone" Field Group', 'acf-views')
            );
            $formMessage .= sprintf(
                '<a target="_blank" href="%s">%s</a><br><br>',
                $this->demoImport->getPhoneAcfViewLink(),
                __('"Phone" View', 'acf-views')
            );

            $formMessage .= sprintf(
                '<p><b>%s</b></p>',
                __('Display a specific post, page or CPT item with its fields', 'acf-views')
            );
            $formMessage .= sprintf(
                '<a target="_blank" href="%s">%s</a><br><br>',
                $this->demoImport->getSamsungArticleLink(),
                __('"Article about Samsung" page', 'acf-views')
            );

            $formMessage .= sprintf(
                '<p><b>%s<br>%s</b></p>',
                __('Display specific posts, pages or CPT items and their fields by using filters', 'acf-views'),
                __('or by manually assigning items', 'acf-views')
            );
            $formMessage .= sprintf(
                '<a target="_blank" href="%s">%s</a><br><br>',
                $this->demoImport->getPhonesAcfCardLink(),
                __('"Phones" Card', 'acf-views')
            );
            $formMessage .= sprintf(
                '<a target="_blank" href="%s">%s</a><br><br>',
                $this->demoImport->getPhonesArticleLink(),
                __('"Most popular phones in 2022" page', 'acf-views')
            );
        }

        $formNonce = wp_create_nonce('_av-demo-import');
        echo $this->html->dashboardImport($isWithDeleteButton, $formNonce, $formMessage);
    }

    public function addUpgradeToProLink(array $links): array
    {
        $settings_link = sprintf(
            '<a href="%s" target="_blank">%s</a>',
            Plugin::PRO_VERSION_URL,
            __('Get Pro', 'acf-views')
        );

        array_unshift($links, $settings_link);

        return $links;
    }

    public function removeSubmenuLinks(): void
    {
        $url = sprintf('edit.php?post_type=%s', ViewsCpt::NAME);

        global $submenu;

        if (!$submenu[$url]) {
            $submenu[$url] = [];
        }

        foreach ($submenu[$url] as $itemKey => $item) {
            if (4 !== count($item) ||
                !in_array($item[2], [
                    self::PAGE_DEMO_IMPORT,
                    self::PAGE_DOCS,
                    self::PAGE_SURVEY
                ], true)) {
                continue;
            }

            unset($submenu[$url][$itemKey]);
        }
    }

    public function setHooks(bool $isAdmin): void
    {
        if (!$isAdmin) {
            return;
        }

        $pluginSlug = $this->plugin->getSlug();

        add_action('admin_menu', [$this, 'addPages']);

        add_action('current_screen', function (WP_Screen $screen) {
            if (!isset($screen->post_type) ||
                !in_array($screen->post_type, [ViewsCpt::NAME, CardsCpt::NAME,])) {
                return;
            }
            add_action('in_admin_header', [$this, 'getHeader']);
        });

        add_filter("plugin_action_links_{$pluginSlug}", [$this, 'addUpgradeToProLink']);

        add_action('admin_menu', [$this, 'removeSubmenuLinks']);
    }
}