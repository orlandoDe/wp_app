<?php
/**
 * Plugin Name: Advanced Views Lite
 * Plugin URI: https://wplake.org/acf-views/
 * Description: Smart templates to display your content easily.
 * Version: 2.4.7
 * Author: WPLake
 * Author URI: https://wplake.org/acf-views/
 * Text Domain: acf-views
 */

namespace org\wplake\acf_views;

use org\wplake\acf_views\AcfPro\AcfPro;
use org\wplake\acf_views\Assets\AdminAssets;
use org\wplake\acf_views\Assets\FrontAssets;
use org\wplake\acf_views\Cards\{CardFactory,
    CardMarkup,
    CardsDataStorage,
    CardShortcode,
    Cpt\CardsCpt,
    Cpt\CardsMetaBoxes,
    Cpt\CardsSaveActions,
    Cpt\CardsViewIntegration,
    QueryBuilder};
use org\wplake\acf_views\Dashboard\Dashboard;
use org\wplake\acf_views\Dashboard\DemoImport;
use org\wplake\acf_views\Dashboard\SettingsPage;
use org\wplake\acf_views\Dashboard\Tools;
use org\wplake\acf_views\Groups\{CardData,
    Integration\AcfIntegration,
    Integration\CardDataIntegration,
    Integration\FieldDataIntegration,
    Integration\ItemDataIntegration,
    Integration\MetaFieldDataIntegration,
    Integration\MountPointDataIntegration,
    Integration\TaxFieldDataIntegration,
    Integration\ToolsDataIntegration,
    ItemData,
    SettingsData,
    ToolsData,
    ViewData};
use org\wplake\acf_views\vendors\LightSource\AcfGroups\Creator;
use org\wplake\acf_views\vendors\LightSource\AcfGroups\Loader as GroupsLoader;
use org\wplake\acf_views\Views\{Cpt\ViewsCpt,
    Cpt\ViewsGroupIntegration,
    Cpt\ViewsMetaBoxes,
    Cpt\ViewsSaveActions,
    Fields\Fields,
    ViewFactory,
    ViewMarkup,
    ViewsDataStorage,
    ViewShortcode};

defined('ABSPATH') || exit;

$acfViews = new class {
    protected FieldDataIntegration $fieldIntegration;
    protected Html $html;
    protected CardsDataStorage $cardsDataStorage;
    protected ViewsDataStorage $viewsDataStorage;
    protected Twig $twig;
    protected Plugin $plugin;
    protected ItemData $item;
    protected Options $options;
    protected ViewsSaveActions $viewsSaveActions;
    protected CardsSaveActions $cardsSaveActions;
    protected ViewFactory $viewFactory;
    protected CardFactory $cardFactory;
    protected ViewData $viewData;
    protected CardData $cardData;
    protected Creator $groupCreator;
    protected Settings $settings;
    protected FrontAssets $frontAssets;
    protected ViewMarkup $viewMarkup;
    protected CardMarkup $cardMarkup;
    protected QueryBuilder $queryBuilder;

    protected function acfGroups(bool $isAdmin): void
    {
        $acfGroupsLoader = new GroupsLoader();
        $acfGroupsLoader->signUpGroups(
            'org\wplake\acf_views\Groups',
            __DIR__ . '/src/Groups'
        );

        $this->fieldIntegration = new FieldDataIntegration();
        $cardDataIntegration = new CardDataIntegration($this->fieldIntegration);
        $itemIntegration = new ItemDataIntegration();
        $metaFieldIntegration = new MetaFieldDataIntegration($this->fieldIntegration);
        $mountPointIntegration = new MountPointDataIntegration();
        $taxFieldIntegration = new TaxFieldDataIntegration();
        $toolsDataIntegration = new ToolsDataIntegration();

        $this->fieldIntegration->setHooks($isAdmin);
        $cardDataIntegration->setHooks($isAdmin);
        $itemIntegration->setHooks($isAdmin);
        $metaFieldIntegration->setHooks($isAdmin);
        $mountPointIntegration->setHooks($isAdmin);
        $taxFieldIntegration->setHooks($isAdmin);
        $toolsDataIntegration->setHooks($isAdmin);
    }

    protected function primary(bool $isAdmin): void
    {
        $this->groupCreator = new Creator();
        $this->viewData = $this->groupCreator->create(ViewData::class);
        $this->cardData = $this->groupCreator->create(CardData::class);
        $this->options = new Options();
        $this->html = new Html();
        $this->cardsDataStorage = new CardsDataStorage($this->cardData);
        $this->viewsDataStorage = new ViewsDataStorage($this->viewData);
        $this->settings = new Settings($this->options);
        $this->plugin = new Plugin($this->options, $this->settings);
        $this->twig = new Twig($this->settings, $this->plugin);
        $this->item = $this->groupCreator->create(ItemData::class);
        $this->frontAssets = new FrontAssets($this->plugin);

        // load right here, as used everywhere
        $this->settings->load();

        $this->plugin->setHooks($isAdmin);
        $this->twig->setHooks($isAdmin, __FILE__);
        $this->frontAssets->setHooks($isAdmin);
    }

    protected function acfViews(bool $isAdmin): void
    {
        $fields = new Fields($this->frontAssets);
        $this->viewMarkup = new ViewMarkup($this->html, $fields);
        $this->viewFactory = new ViewFactory(
            $this->frontAssets,
            $this->viewsDataStorage,
            $this->viewMarkup,
            $this->twig,
            $fields
        );
        $acfViewMetaBoxes = new ViewsMetaBoxes($this->html, $this->viewsDataStorage);
        $this->viewsSaveActions = new ViewsSaveActions(
            $this->viewsDataStorage,
            $this->plugin,
            $this->viewData,
            $this->frontAssets,
            $this->viewMarkup,
            $acfViewMetaBoxes,
            $this->html,
            $this->viewFactory
        );
        $acfViewsCpt = new ViewsCpt($this->viewsDataStorage, $this->viewsSaveActions, $this->html, $acfViewMetaBoxes);
        $acfViewGroupIntegration = new ViewsGroupIntegration(
            $this->item,
            $this->viewsDataStorage,
            $this->fieldIntegration,
            $this->viewsSaveActions,
            $this->viewFactory
        );
        $viewShortcode = new ViewShortcode($this->settings, $this->viewsDataStorage, $this->viewFactory);

        $acfViewMetaBoxes->setHooks($isAdmin);
        $acfViewsCpt->setHooks($isAdmin);
        $this->viewsSaveActions->setHooks($isAdmin);
        $acfViewGroupIntegration->setHooks($isAdmin);
        $viewShortcode->setHooks($isAdmin);
    }

    protected function acfCards(bool $isAdmin): void
    {
        $this->queryBuilder = new QueryBuilder();
        $this->cardMarkup = new CardMarkup($this->queryBuilder, $this->frontAssets);
        $this->cardFactory = new CardFactory(
            $this->frontAssets,
            $this->queryBuilder,
            $this->cardMarkup,
            $this->twig,
            $this->cardsDataStorage
        );
        $acfCardsMetaBoxes = new CardsMetaBoxes($this->html, $this->cardsDataStorage);
        $this->cardsSaveActions = new CardsSaveActions(
            $this->cardsDataStorage,
            $this->plugin,
            $this->cardData,
            $this->frontAssets,
            $this->cardMarkup,
            $this->queryBuilder,
            $this->html,
            $acfCardsMetaBoxes,
            $this->cardFactory
        );
        $acfCardsCpt = new CardsCpt($this->cardsDataStorage, $this->cardsSaveActions, $this->html, $acfCardsMetaBoxes);
        $acfCardsViewIntegration = new CardsViewIntegration(
            $this->cardsDataStorage,
            $this->viewsDataStorage,
            $this->cardsSaveActions
        );
        $cardShortcode = new CardShortcode($this->settings, $this->cardsDataStorage, $this->cardFactory);

        $acfCardsCpt->setHooks($isAdmin);
        $acfCardsMetaBoxes->setHooks($isAdmin);
        $this->cardsSaveActions->setHooks($isAdmin);
        $acfCardsViewIntegration->setHooks($isAdmin);
        $cardShortcode->setHooks($isAdmin);
    }

    protected function others(bool $isAdmin): void
    {
        $demoImport = new DemoImport(
            $this->cardsSaveActions,
            $this->viewsSaveActions,
            $this->cardsDataStorage,
            $this->viewsDataStorage,
            $this->settings,
            $this->item
        );
        $acfIntegration = new AcfIntegration();
        $dashboard = new Dashboard($this->plugin, $this->html, $demoImport, $acfIntegration);
        $acfPro = new AcfPro($this->plugin);
        $upgrades = new Upgrades(
            $this->plugin,
            $this->settings,
            $this->viewsDataStorage,
            $this->cardsDataStorage,
            $this->viewsSaveActions,
            $this->cardsSaveActions,
            $this->twig
        );
        $activeInstallations = new ActiveInstallations($this->plugin, $this->settings, $this->options);
        $tools = new Tools(
            new ToolsData($this->groupCreator),
            $this->cardsDataStorage,
            $this->viewsDataStorage,
            $this->plugin,
            $this->viewData,
            $this->cardData
        );
        $settings = new SettingsPage(new SettingsData($this->groupCreator), $this->settings);
        $adminAssets = new AdminAssets(
            $this->plugin,
            $this->cardsDataStorage,
            $this->viewsDataStorage,
            $this->viewFactory,
            $this->cardFactory,
            $this->settings
        );

        $dashboard->setHooks($isAdmin);
        $demoImport->setHooks($isAdmin);
        $acfPro->setHooks($isAdmin);
        $upgrades->setHooks($isAdmin);
        $activeInstallations->setHooks($isAdmin);
        $tools->setHooks($isAdmin);
        $adminAssets->setHooks($isAdmin);
        $settings->setHooks($isAdmin);
    }

    public function init(): void
    {
        // skip initialization if PRO already active
        if (class_exists(Plugin::class)) {
            return;
        }

        require_once __DIR__ . '/prefixed_vendors/vendor/scoper-autoload.php';

        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $isAdmin = is_admin() ||
            false !== strpos($requestUri, '/wp-json/');

        $this->acfGroups($isAdmin);
        $this->primary($isAdmin);
        $this->acfViews($isAdmin);
        $this->acfCards($isAdmin);
        $this->others($isAdmin);
    }
};

$acfViews->init();
