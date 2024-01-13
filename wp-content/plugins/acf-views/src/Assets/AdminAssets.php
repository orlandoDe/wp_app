<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Assets;

use org\wplake\acf_views\Cards\CardFactory;
use org\wplake\acf_views\Cards\CardsDataStorage;
use org\wplake\acf_views\Cards\Cpt\CardsCpt;
use org\wplake\acf_views\Common\HooksInterface;
use org\wplake\acf_views\Groups\CardData;
use org\wplake\acf_views\Groups\FieldData;
use org\wplake\acf_views\Groups\ItemData;
use org\wplake\acf_views\Groups\MetaFieldData;
use org\wplake\acf_views\Groups\RepeaterFieldData;
use org\wplake\acf_views\Groups\TaxFieldData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Plugin;
use org\wplake\acf_views\Settings;
use org\wplake\acf_views\Views\Cpt\ViewsCpt;
use org\wplake\acf_views\Views\Post;
use org\wplake\acf_views\Views\ViewFactory;
use org\wplake\acf_views\Views\ViewsDataStorage;

defined('ABSPATH') || exit;

class AdminAssets implements HooksInterface
{
    /**
     * @var Plugin
     */
    protected $plugin;
    protected CardsDataStorage $cardsDataStorage;
    protected ViewsDataStorage $viewsDataStorage;
    protected ViewFactory $viewFactory;
    protected CardFactory $cardFactory;
    protected Settings $settings;

    public function __construct(
        Plugin $plugin,
        CardsDataStorage $cardsDataStorage,
        ViewsDataStorage $viewsDataStorage,
        ViewFactory $acfViewFactory,
        CardFactory $acfCardFactory,
        Settings $settings
    ) {
        $this->plugin = $plugin;
        $this->cardsDataStorage = $cardsDataStorage;
        $this->viewsDataStorage = $viewsDataStorage;
        $this->viewFactory = $acfViewFactory;
        $this->cardFactory = $acfCardFactory;
        $this->settings = $settings;
    }

    protected function getViewPreviewJsData(): array
    {
        $jsData = [
            'HTML' => '',
            'CSS' => '',
        ];

        global $post;

        if (!$this->plugin->isCPTScreen(ViewsCpt::NAME) ||
            'publish' !== $post->post_status) {
            return $jsData;
        }

        $acfViewData = $this->viewsDataStorage->get($post->ID);
        $previewPostId = $acfViewData->previewPost ?: 0;

        if ($previewPostId) {
            $postData = new Post($previewPostId, [], false, get_current_user_id());
            // without minify, it's a preview
            $viewHTML = $this->viewFactory->makeAndGetHtml(
                $postData,
                $post->ID,
                0,
                false,
            );
        } else {
            // $this->viewMarkup->getMarkup give TWIG, there is no sense to show it
            // so the HTML is empty until the preview Post ID is selected
            $viewHTML = '';
        }

        // amend to allow work the '#view' alias
        $viewHTML = str_replace('class="acf-view ', 'id="view" class="acf-view ', $viewHTML);
        $jsData['HTML'] = htmlentities($viewHTML, ENT_QUOTES);

        $jsData['CSS'] = htmlentities($acfViewData->getCssCode(false, true), ENT_QUOTES);
        $jsData['HOME'] = get_site_url();

        return $jsData;
    }

    protected function getCardPreviewJsData(): array
    {
        $jsData = [
            'HTML' => '',
            'CSS' => '',
        ];

        global $post;

        if (!$this->plugin->isCPTScreen(CardsCpt::NAME) ||
            'publish' !== $post->post_status) {
            return $jsData;
        }

        $acfCardData = $this->cardsDataStorage->get($post->ID);
        $acfCardHtml = $this->cardFactory->makeAndGetHtml($acfCardData, 1, false);
        $viewId = $this->viewsDataStorage->getPostIdByUniqueId($acfCardData->acfViewId, ViewsCpt::NAME);

        if (!$viewId) {
            return $jsData;
        }

        $acfViewData = $this->viewsDataStorage->get($viewId);

        // amend to allow work the '#card' alias
        $viewHTML = str_replace(
            'class="acf-card ',
            'id="card" class="acf-card ',
            $acfCardHtml
        );
        $jsData['HTML'] = htmlentities($viewHTML, ENT_QUOTES);
        // Card CSS without minification as it's for views' purposes
        $jsData['CSS'] = htmlentities($acfCardData->getCssCode(false, true), ENT_QUOTES);
        $jsData['VIEW_CSS'] = htmlentities($acfViewData->getCssCode(), ENT_QUOTES);
        $jsData['HOME'] = get_site_url();

        return $jsData;
    }

    protected function enqueueCodeEditor(): void
    {
        wp_enqueue_script(
            ViewsCpt::NAME . '_ace',
            $this->plugin->getAssetsUrl('admin/code-editor/ace.js'),
            [],
            $this->plugin->getVersion()
        );

        $extensions = ['ext-beautify', 'ext-language_tools', 'ext-linking',];

        foreach ($extensions as $extension) {
            wp_enqueue_script(
                ViewsCpt::NAME . '_ace-' . $extension,
                $this->plugin->getAssetsUrl('admin/code-editor/' . $extension . '.js'),
                [
                    ViewsCpt::NAME . '_ace',
                ],
                $this->plugin->getVersion()
            );
        }
    }

    protected function getAutocompleteFunctions(): array
    {
        return [
            'date' => '(format[,timezone]):string',
        ];
    }

    protected function getAutocompleteFilters(): array
    {
        return [
            'abs' => ':number',
            'capitalize' => ':string',
            'raw' => ':string',
            'upper' => ':string',
            'lower' => ':string',
            'round' => '([precision, method]):int',
            'range' => '(low,high[,step]):array',
            'date' => '(format):string',
            'date_modify' => '(modify):Date',
            'default' => '(default):string',
            'replace' => '({"search":"replace"}):string',
            'random' => '(from[,max]):mixed',
        ];
    }

    protected function getJsDataForCptItemPage(): array
    {
        global $post;

        $isView = ViewsCpt::NAME === $post->post_type;
        $isPublished = 'publish' === $post->post_status;

        if ($isView) {
            $autocompleteVariables = $isPublished ?
                $this->viewFactory->getAutocompleteVariables($post->ID) :
                [];
            $textareaItemsToRefresh = [
                'acf-local_acf_views_view__markup',
                'acf-local_acf_views_view__css-code',
                'acf-local_acf_views_view__js-code',
            ];
            $refreshAjax = 'acf_views__view_refresh';
        } else {
            $autocompleteVariables = $isPublished ?
                $this->cardFactory->getAutocompleteVariables($post->ID) :
                [];
            $textareaItemsToRefresh = [
                'acf-local_acf_views_acf-card-data__markup',
                'acf-local_acf_views_acf-card-data__css-code',
                'acf-local_acf_views_acf-card-data__js-code',
                'acf-local_acf_views_acf-card-data__query-preview',
            ];
            $refreshAjax = 'acf_views__card_refresh';
        }

        return [
            'autocompleteVariables' => $autocompleteVariables,
            'autocompleteFunctions' => $this->getAutocompleteFunctions(),
            'autocompleteFilters' => $this->getAutocompleteFilters(),
            'textareaItemsToRefresh' => $textareaItemsToRefresh,
            'refreshAjax' => $refreshAjax,
            'mods' => [
                '_twig' => [
                    'mode' => 'ace/mode/twig',
                ],
                '_css' => [
                    'mode' => 'ace/mode/css',
                ],
                '_js' => [
                    'mode' => 'ace/mode/javascript',
                ],
                '_php' => [
                    'mode' => 'ace/mode/php',
                ],
            ],
            'markupTextarea' => [
                [
                    'idSelector' => ViewData::getAcfFieldName(ViewData::FIELD_MARKUP),
                    'tabIdSelector' => ViewData::getAcfFieldName(ViewData::FIELD_TEMPLATE_TAB),
                    'isReadOnly' => true,
                    'mode' => '_twig',
                    'isWithVariableAutocomplete' => false,
                    'linkTitle' => __('Template Preview', 'acf-views'),
                ],
                [
                    'idSelector' => ViewData::getAcfFieldName(ViewData::FIELD_CUSTOM_MARKUP),
                    'tabIdSelector' => ViewData::getAcfFieldName(ViewData::FIELD_TEMPLATE_TAB),
                    'isReadOnly' => false,
                    'mode' => '_twig',
                    'isWithVariableAutocomplete' => true,
                    'linkTitle' => __('Custom Template', 'acf-views'),
                ],
                [
                    'idSelector' => ViewData::getAcfFieldName(ViewData::FIELD_CSS_CODE),
                    'tabIdSelector' => ViewData::getAcfFieldName(ViewData::FIELD_CSS_AND_JS_TAB),
                    'isReadOnly' => false,
                    'mode' => '_css',
                    'isWithVariableAutocomplete' => false,
                    'linkTitle' => __('CSS Code', 'acf-views'),
                ],
                [
                    'idSelector' => ViewData::getAcfFieldName(ViewData::FIELD_JS_CODE),
                    'tabIdSelector' => ViewData::getAcfFieldName(ViewData::FIELD_CSS_AND_JS_TAB),
                    'isReadOnly' => false,
                    'mode' => '_js',
                    'isWithVariableAutocomplete' => false,
                    'linkTitle' => __('JS Code', 'acf-views'),
                ],
                [
                    'idSelector' => ViewData::getAcfFieldName(ViewData::FIELD_PHP_VARIABLES),
                    'tabIdSelector' => ViewData::getAcfFieldName(ViewData::FIELD_TEMPLATE_TAB),
                    'isReadOnly' => false,
                    'mode' => '_php',
                    'isWithVariableAutocomplete' => false,
                    'linkTitle' => __('PHP Variables', 'acf-views'),
                ],
                [
                    'idSelector' => CardData::getAcfFieldName(CardData::FIELD_MARKUP),
                    'tabIdSelector' => CardData::getAcfFieldName(CardData::FIELD_TEMPLATE_TAB),
                    'isReadOnly' => true,
                    'mode' => '_twig',
                    'isWithVariableAutocomplete' => false,
                    'linkTitle' => __('Template Preview', 'acf-views'),
                ],
                [
                    'idSelector' => CardData::getAcfFieldName(CardData::FIELD_CUSTOM_MARKUP),
                    'tabIdSelector' => CardData::getAcfFieldName(CardData::FIELD_TEMPLATE_TAB),
                    'isReadOnly' => false,
                    'mode' => '_twig',
                    'isWithVariableAutocomplete' => true,
                    'linkTitle' => __('Custom Template', 'acf-views'),
                ],
                [
                    'idSelector' => CardData::getAcfFieldName(CardData::FIELD_CSS_CODE),
                    'tabIdSelector' => CardData::getAcfFieldName(CardData::FIELD_CSS_AND_JS_TAB),
                    'isReadOnly' => false,
                    'mode' => '_css',
                    'isWithVariableAutocomplete' => false,
                    'linkTitle' => __('CSS Code', 'acf-views'),
                ],
                [
                    'idSelector' => CardData::getAcfFieldName(CardData::FIELD_JS_CODE),
                    'tabIdSelector' => CardData::getAcfFieldName(CardData::FIELD_CSS_AND_JS_TAB),
                    'isReadOnly' => false,
                    'mode' => '_js',
                    'isWithVariableAutocomplete' => false,
                    'linkTitle' => __('JS Code', 'acf-views'),
                ],
                [
                    'idSelector' => CardData::getAcfFieldName(CardData::FIELD_QUERY_PREVIEW),
                    'tabIdSelector' => CardData::getAcfFieldName(CardData::FIELD_ADVANCED_TAB),
                    'isReadOnly' => true,
                    'mode' => '_twig',
                    'isWithVariableAutocomplete' => false,
                    'linkTitle' => __('Query Preview', 'acf-views'),
                ],
                [
                    'idSelector' => CardData::getAcfFieldName(CardData::FIELD_EXTRA_QUERY_ARGUMENTS),
                    'tabIdSelector' => CardData::getAcfFieldName(CardData::FIELD_ADVANCED_TAB),
                    'isReadOnly' => false,
                    'mode' => '_php',
                    'isWithVariableAutocomplete' => false,
                    'linkTitle' => __('Extra Query Arguments', 'acf-views'),
                ],
            ],
            'fieldSelect' => [
                [
                    'mainSelectId' => ItemData::getAcfFieldName(ItemData::FIELD_GROUP),
                    'subSelectId' => FieldData::getAcfFieldName(FieldData::FIELD_KEY),
                    'identifierInputId' => FieldData::getAcfFieldName(FieldData::FIELD_ID),
                ],
                [
                    'mainSelectId' => CardData::getAcfFieldName(
                        CardData::FIELD_ORDER_BY_META_FIELD_GROUP
                    ),
                    'subSelectId' => CardData::getAcfFieldName(CardData::FIELD_ORDER_BY_META_FIELD_KEY),
                    'identifierInputId' => '',
                ],
                [
                    'mainSelectId' => FieldData::getAcfFieldName(FieldData::FIELD_KEY),
                    'subSelectId' => RepeaterFieldData::getAcfFieldName(RepeaterFieldData::FIELD_KEY),
                    'identifierInputId' => RepeaterFieldData::getAcfFieldName(RepeaterFieldData::FIELD_ID),
                ],
                [
                    'mainSelectId' => MetaFieldData::getAcfFieldName(MetaFieldData::FIELD_GROUP),
                    'subSelectId' => MetaFieldData::getAcfFieldName(MetaFieldData::FIELD_FIELD_KEY),
                    'identifierInputId' => '',
                ],
                [
                    'mainSelectId' => TaxFieldData::getAcfFieldName(TaxFieldData::FIELD_TAXONOMY),
                    'subSelectId' => TaxFieldData::getAcfFieldName(TaxFieldData::FIELD_TERM),
                    'identifierInputId' => '',
                ],
            ],
            'viewPreview' => $this->getViewPreviewJsData(),
            'cardPreview' => $this->getCardPreviewJsData(),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'isWordpressComHosting' => $this->plugin->isWordpressComHosting(),
        ];
    }

    protected function enqueueAdminAssets(string $currentBase, array $jsData = []): void
    {
        switch ($currentBase) {
            // add, edit pages
            case 'post':
                $jsData = array_merge_recursive($jsData, $this->getJsDataForCptItemPage());

                $this->enqueueCodeEditor();

                wp_enqueue_style(
                    ViewsCpt::NAME . '_cpt-item',
                    $this->plugin->getAssetsUrl('admin/css/cpt-item.min.css'),
                    [],
                    $this->plugin->getVersion()
                );
                // jquery is necessary for select2 events
                wp_enqueue_script(
                    ViewsCpt::NAME . '_cpt-item',
                    $this->plugin->getAssetsUrl('admin/js/cpt-item.min.js'),
                    // make sure acf and ACE editor are loaded
                    ['jquery', 'acf-input', ViewsCpt::NAME . '_ace', 'wp-api-fetch',],
                    $this->plugin->getVersion(),
                    [
                        'in_footer' => true,
                        // in footer, so if we need to include others, like 'ace.js' we can include in header
                    ]
                );
                wp_localize_script(ViewsCpt::NAME . '_cpt-item', 'acf_views', $jsData);
                break;
            // 'edit' means 'list page'
            case 'edit':
                wp_enqueue_style(
                    ViewsCpt::NAME . '_list-page',
                    $this->plugin->getAssetsUrl('admin/css/list-page.min.css'),
                    [],
                    $this->plugin->getVersion()
                );
                break;
            case 'acf_views_page_acf-views-tools':
            case 'acf_views_page_acf-views-settings':
                wp_enqueue_style(
                    ViewsCpt::NAME . '_tools',
                    $this->plugin->getAssetsUrl('admin/css/tools.min.css'),
                    [],
                    $this->plugin->getVersion()
                );
                break;
        }

        // 'dashboard' for all the custom pages (but not for edit/add pages)
        if (0 === strpos($currentBase, 'acf_views_page_')) {
            wp_enqueue_style(
                ViewsCpt::NAME . '_page',
                $this->plugin->getAssetsUrl('admin/css/dashboard.min.css'),
                [],
                $this->plugin->getVersion()
            );
        }

        // plugin-header for all the pages without exception
        wp_enqueue_style(
            ViewsCpt::NAME . '_common',
            $this->plugin->getAssetsUrl('admin/css/common.min.css'),
            [],
            $this->plugin->getVersion()
        );
    }

    public function enqueueAdminScripts(): void
    {
        $currentScreen = get_current_screen();
        if (!$currentScreen ||
            (!in_array($currentScreen->id, [ViewsCpt::NAME, CardsCpt::NAME,], true) &&
                !in_array($currentScreen->post_type, [ViewsCpt::NAME, CardsCpt::NAME], true))) {
            return;
        }

        $this->enqueueAdminAssets($currentScreen->base);
    }

    public function setHooks(bool $isAdmin): void
    {
        if (!$isAdmin) {
            return;
        }

        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
    }
}
