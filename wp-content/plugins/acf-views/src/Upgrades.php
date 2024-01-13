<?php

declare(strict_types=1);

namespace org\wplake\acf_views;

use org\wplake\acf_views\Cards\CardsDataStorage;
use org\wplake\acf_views\Cards\Cpt\CardsCpt;
use org\wplake\acf_views\Cards\Cpt\CardsSaveActions;
use org\wplake\acf_views\Common\HooksInterface;
use org\wplake\acf_views\Groups\FieldData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Views\Cpt\ViewsCpt;
use org\wplake\acf_views\Views\Cpt\ViewsSaveActions;
use org\wplake\acf_views\Views\Fields\CommentItems\CommentItemFields;
use org\wplake\acf_views\Views\Fields\Menu\MenuFields;
use org\wplake\acf_views\Views\Fields\MenuItem\MenuItemFields;
use org\wplake\acf_views\Views\Fields\Post\PostFields;
use org\wplake\acf_views\Views\ViewsDataStorage;
use WP_Post;
use WP_Query;

defined('ABSPATH') || exit;

class Upgrades implements HooksInterface
{
    protected Plugin $plugin;
    protected Settings $settings;
    protected ViewsDataStorage $viewsDataStorage;
    protected CardsDataStorage $cardsDataStorage;
    protected ViewsSaveActions $acfViewsSaveActions;
    protected CardsSaveActions $acfCardsSaveActions;
    protected Twig $twig;
    protected string $logData;

    public function __construct(
        Plugin $plugin,
        Settings $settings,
        ViewsDataStorage $viewsDataStorage,
        CardsDataStorage $cardsDataStorage,
        ViewsSaveActions $acfViewsSaveActions,
        CardsSaveActions $acfCardsSaveActions,
        Twig $twig
    ) {
        $this->plugin = $plugin;
        $this->settings = $settings;
        $this->viewsDataStorage = $viewsDataStorage;
        $this->cardsDataStorage = $cardsDataStorage;
        $this->acfViewsSaveActions = $acfViewsSaveActions;
        $this->acfCardsSaveActions = $acfCardsSaveActions;
        $this->twig = $twig;
        $this->logData = '';
    }

    protected function log(string $message): void
    {
        // todo enable when testing upgrades (manually)
        //$this->logData .= $message . "\r\n";
    }

    protected function isVersionLower(string $version, string $targetVersion): bool
    {
        // empty means the very first run, no data is available, nothing to fix
        if (!$version) {
            return false;
        }

        $currentVersion = explode('.', $version);
        $targetVersion = explode('.', $targetVersion);

        // versions are broken
        if (3 !== count($currentVersion) ||
            3 !== count($targetVersion)) {
            return false;
        }

        //// convert to int

        foreach ($currentVersion as &$part) {
            $part = (int)$part;
        }
        foreach ($targetVersion as &$part) {
            $part = (int)$part;
        }

        //// compare

        // major
        if ($currentVersion[0] > $targetVersion[0]) {
            return false;
        } elseif ($currentVersion[0] < $targetVersion[0]) {
            return true;
        }

        // minor
        if ($currentVersion[1] > $targetVersion[1]) {
            return false;
        } elseif ($currentVersion[1] < $targetVersion[1]) {
            return true;
        }

        // patch
        if ($currentVersion[2] >= $targetVersion[2]) {
            return false;
        }

        return true;
    }

    protected function moveViewAndCardMetaToPostContentJson(): void
    {
        $queryArgs = [
            'post_type' => [ViewsCpt::NAME, CardsCpt::NAME,],
            'post_status' => ['publish', 'draft', 'trash',],
            'posts_per_page' => -1,
        ];
        $myPosts = new WP_Query($queryArgs);
        $myPosts = $myPosts->get_posts();

        global $wpdb;

        foreach ($myPosts as $myPost) {
            $postId = $myPost->ID;

            $data = ViewsCpt::NAME === $myPost->post_type ?
                $this->viewsDataStorage->get($postId) :
                $this->cardsDataStorage->get($postId);

            $data->load($myPost->ID);

            $data->saveToPostContent();

            $wpdb->delete($wpdb->prefix . 'postmeta', [
                'post_id' => $postId,
            ]);
        }
    }

    protected function moveOptionsToSettings(): void
    {
        $license = (string)get_option(Options::PREFIX . 'license', '');
        $licenceExpiration = (string)get_option(Options::PREFIX . 'license_expiration', '');
        $demoImport = (array)get_option(Options::PREFIX . 'demo_import', []);

        $this->settings->setLicense($license);
        $this->settings->setLicenseExpiration($licenceExpiration);
        $this->settings->setDemoImport($demoImport);

        $this->settings->save();

        ////

        delete_option(Options::PREFIX . 'license');
        delete_option(Options::PREFIX . 'license_expiration');
        delete_option(Options::PREFIX . 'demo_import');
    }

    // it was for 1.5.10, when versions weren't available
    protected function firstRun(): bool
    {
        // skip upgrading as hook won't be fired and data is not available
        if (!$this->plugin->isAcfPluginAvailable()) {
            return false;
        }

        add_action('acf/init', function () {
            $this->moveViewAndCardMetaToPostContentJson();
            $this->moveOptionsToSettings();
        });

        return true;
    }

    protected function fixMultipleSlashesInPostContentJson(): void
    {
        global $wpdb;

        // don't use 'get_post($id)->post_content' / 'wp_update_post()'
        // to avoid the kses issue https://core.trac.wordpress.org/ticket/38715

        $myPosts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->posts} WHERE post_type IN (%s,%s) AND post_content != ''",
                ViewsCpt::NAME,
                CardsCpt::NAME
            )
        );

        foreach ($myPosts as $myPost) {
            $content = str_replace('\\\\\\', '\\', $myPost->post_content);

            $wpdb->update($wpdb->posts, ['post_content' => $content], ['ID' => $myPost->ID]);
        }
    }

    protected function replacePostIdentifiers(): void
    {
        global $wpdb;

        $queryForThumbnail = "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, '\$post\$|_thumbnail_id', '\$post\$|_post_thumbnail') WHERE post_type = 'acf_views'";
        $queryForThumbnailLink = "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, '\$post\$|_thumbnail_id_link', '\$post\$|_post_thumbnail_link') WHERE post_type = 'acf_views'";

        $res1 = $wpdb->get_results($queryForThumbnail);
        $res2 = $wpdb->get_results($queryForThumbnailLink);

        $this->log('replacePostIdentifiers, Basic.' . print_r($res1, true) . print_r($res2, true));
    }

    /**
     * @return WP_Post[]
     */
    protected function getAllViews(): array
    {
        $queryArgs = [
            'post_type' => ViewsCpt::NAME,
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft', 'trash',],
        ];
        $query = new WP_Query($queryArgs);
        return $query->posts;
    }

    protected function getAllCards(): array
    {
        $queryArgs = [
            'post_type' => CardsCpt::NAME,
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft', 'trash',],
        ];
        $query = new WP_Query($queryArgs);
        return $query->posts;
    }

    protected function triggerSaveForAllViews(): int
    {
        $posts = $this->getAllViews();

        foreach ($posts as $post) {
            $this->acfViewsSaveActions->performSaveActions($post->ID);
        }

        return count($posts);
    }

    protected function triggerSaveForAllCards(): int
    {
        $posts = $this->getAllCards();

        foreach ($posts as $post) {
            $this->acfCardsSaveActions->performSaveActions($post->ID);
        }

        return count($posts);
    }

    protected function replaceViewIdToUniqueIdInView(ViewData $acfViewData): bool
    {
        $isChanged = false;

        foreach ($acfViewData->items as $item) {
            $oldId = $item->field->acfViewId;

            if (!$oldId) {
                continue;
            }

            $newId = $this->viewsDataStorage->getPostIdByUniqueId($oldId, ViewsCpt::NAME);

            if (!$newId) {
                continue;
            }

            $isChanged = true;
            $item->field->acfViewId = $this->viewsDataStorage->get($newId)->getUniqueId();
        }

        return $isChanged;
    }

    protected function extraUpgrade(string $previousVersion): void
    {
        // stub for Pro
    }

    protected function disableWebComponentsForExistingViewsAndCards(): void
    {
        $cptDataItems = array_merge($this->getAllViews(), $this->getAllCards());

        foreach ($cptDataItems as $cptDataItem) {
            $cptData = ViewsCpt::NAME === $cptDataItem->post_type ?
                $this->viewsDataStorage->get($cptDataItem->ID) :
                $this->cardsDataStorage->get($cptDataItem->ID);

            $cptData->isWithoutWebComponent = true;

            $cptData->saveToPostContent();
        }
    }

    protected function setupLightBoxSimpleFromOldCheckbox()
    {
        $views = $this->getAllViews();

        foreach ($views as $view) {
            $viewData = $this->viewsDataStorage->get($view->ID);
            $isWithChange = false;

            foreach ($viewData->items as $item) {
                foreach ($item->repeaterFields as $repeaterField) {
                    if (!$repeaterField->galleryWithLightBox) {
                        continue;
                    }

                    $repeaterField->lightboxType = 'simple';
                    $isWithChange = true;
                }

                if ($item->field->galleryWithLightBox) {
                    $item->field->lightboxType = 'simple';
                    $isWithChange = true;
                }
            }

            if (!$isWithChange) {
                continue;
            }

            $viewData->saveToPostContent();
        }
    }

    protected function replacePostCommentsAndMenuLinkFieldsToSeparate(): void
    {
        $views = $this->getAllViews();

        $oldCommentsKey = FieldData::createKey(PostFields::GROUP_NAME, '_post_comments');
        $newCommentsKey = FieldData::createKey(CommentItemFields::GROUP_NAME, CommentItemFields::FIELD_LIST);

        $oldMenuLinkKey = FieldData::createKey(MenuFields::GROUP_NAME, '_menu_link');
        $newMenuLinkKey = FieldData::createKey(MenuItemFields::GROUP_NAME, MenuItemFields::FIELD_LINK);

        foreach ($views as $view) {
            $viewData = $this->viewsDataStorage->get($view->ID);
            $isWithChange = false;

            foreach ($viewData->items as $item) {
                $newKey = '';
                $newGroup = '';

                switch ($item->field->key) {
                    case $oldCommentsKey:
                        $newKey = $newCommentsKey;
                        $newGroup = CommentItemFields::GROUP_NAME;
                        break;
                    case $oldMenuLinkKey:
                        $newKey = $newMenuLinkKey;
                        $newGroup = MenuItemFields::GROUP_NAME;
                        break;
                }

                if (!$newKey) {
                    continue;
                }

                $isWithChange = true;
                $item->field->key = $newKey;
                $item->group = $newGroup;
            }

            if (!$isWithChange) {
                continue;
            }

            $viewData->saveToPostContent();
        }
    }

    protected function enableNameBackCompatibilityCheckboxForViewsWithGutenberg(): void
    {
        $views = $this->getAllViews();

        foreach ($views as $view) {
            $viewData = $this->viewsDataStorage->get($view->ID);

            if (!$viewData->isHasGutenbergBlock) {
                continue;
            }

            $viewData->isGutenbergBlockWithDigitalId = true;

            $viewData->saveToPostContent();
        }
    }

    public function setDigitalIdForMarkupFlagForViewsAndCards()
    {
        $queryArgs = [
            'post_type' => [ViewsCpt::NAME, CardsCpt::NAME,],
            'post_status' => ['publish', 'draft', 'trash',],
            'posts_per_page' => -1,
        ];
        $posts = new WP_Query($queryArgs);
        $posts = $posts->get_posts();

        foreach ($posts as $post) {
            $cptData = ViewsCpt::NAME === $post->post_type ?
                $this->viewsDataStorage->get($post->ID) :
                $this->cardsDataStorage->get($post->ID);

            $cptData->isMarkupWithDigitalId = true;

            $cptData->saveToPostContent();
        }
    }

    public function recreatePostSlugs(): void
    {
        $queryArgs = [
            'post_type' => [ViewsCpt::NAME, CardsCpt::NAME,],
            'post_status' => ['publish', 'draft', 'trash',],
            'posts_per_page' => -1,
        ];
        $query = new WP_Query($queryArgs);
        $posts = $query->get_posts();

        foreach ($posts as $post) {
            $prefix = ViewsCpt::NAME === $post->post_type ?
                'view_' :
                'card_';

            $postName = uniqid($prefix);

            wp_update_post([
                'ID' => $post->ID,
                'post_name' => $postName,
            ]);

            // to make sure ids are unique (uniqid based on the time)
            usleep(1);
        }
    }

    public function replaceViewIdToUniqueIdInCards(): void
    {
        $acfCards = new WP_Query([
            'post_type' => CardsCpt::NAME,
            'post_status' => ['publish', 'draft', 'trash',],
            'posts_per_page' => -1,
        ]);
        $acfCards = $acfCards->get_posts();

        foreach ($acfCards as $acfCard) {
            $acfCardData = $this->cardsDataStorage->get($acfCard->ID);

            $viewId = $this->viewsDataStorage->getPostIdByUniqueId($acfCardData->acfViewId, ViewsCpt::NAME);

            if (!$viewId) {
                continue;
            }

            $acfViewData = $this->viewsDataStorage->get($viewId);

            $acfCardData->acfViewId = $acfViewData->getUniqueId();

            $acfCardData->saveToPostContent();
        }
    }

    public function replaceViewIdToUniqueIdInViewRelationships(): void
    {
        $acfViews = new WP_Query([
            'post_type' => ViewsCpt::NAME,
            'post_status' => ['publish', 'draft', 'trash',],
            'posts_per_page' => -1,
        ]);
        $acfViews = $acfViews->get_posts();

        foreach ($acfViews as $acfView) {
            $acfViewData = $this->viewsDataStorage->get($acfView->ID);

            if (!$this->replaceViewIdToUniqueIdInView($acfViewData)) {
                continue;
            }

            $acfViewData->saveToPostContent();
        }
    }

    public function enableWithCommonClassesAndUnnecessaryWrappersForAllViews(): void
    {
        $queryArgs = [
            'post_type' => ViewsCpt::NAME,
            'post_status' => ['publish', 'draft', 'trash',],
            'posts_per_page' => -1,
        ];
        $query = new WP_Query($queryArgs);
        $posts = $query->posts;

        foreach ($posts as $post) {
            $acfViewData = $this->viewsDataStorage->get($post->ID);

            $acfViewData->isWithCommonClasses = true;
            $acfViewData->isWithUnnecessaryWrappers = true;

            $this->acfViewsSaveActions->performSaveActions($post->ID);
        }
    }

    public function updateMarkupIdentifiers(): void
    {
        $queryArgs = [
            'post_type' => ViewsCpt::NAME,
            'post_status' => ['publish', 'draft', 'trash',],
            'posts_per_page' => -1,
        ];
        $query = new WP_Query($queryArgs);
        $posts = $query->posts;

        foreach ($posts as $post) {
            $acfViewData = $this->viewsDataStorage->get($post->ID);

            // replace identifiers for Views without Custom Markup
            if (!trim($acfViewData->customMarkup) &&
                $acfViewData->cssCode) {
                foreach ($acfViewData->items as $item) {
                    $oldClass = '.' . $item->field->id;
                    $newClass = '.acf-view__' . $item->field->id;

                    $acfViewData->cssCode = str_replace($oldClass . ' ', $newClass . ' ', $acfViewData->cssCode);
                    $acfViewData->cssCode = str_replace($oldClass . '{', $newClass . '{', $acfViewData->cssCode);
                    $acfViewData->cssCode = str_replace($oldClass . ',', $newClass . ',', $acfViewData->cssCode);

                    foreach ($item->repeaterFields as $repeaterField) {
                        $oldClass = '.' . $repeaterField->id;
                        $newClass = '.acf-view__' . $repeaterField->id;

                        $acfViewData->cssCode = str_replace($oldClass . ' ', $newClass . ' ', $acfViewData->cssCode);
                        $acfViewData->cssCode = str_replace($oldClass . '{', $newClass . '{', $acfViewData->cssCode);
                        $acfViewData->cssCode = str_replace($oldClass . ',', $newClass . ',', $acfViewData->cssCode);
                    }
                }
                // don't call the 'saveToPostContent()' method, as it'll be called in the 'performSaveActions()' method
            }

            // update markup field for all
            $this->acfViewsSaveActions->performSaveActions($post->ID);
        }
    }

    public function upgrade(): void
    {
        // all versions since 1.6.0 has a version
        // empty means the very first run, no data is available, nothing to fix
        $previousVersion = $this->settings->getVersion();

        // NOTE: do not call methods directly (only via init or other hooks)
        // some plugins, like WPFastestCache can use global functions, which won't be defined yet

        if ('1.6.0' === $previousVersion) {
            $this->fixMultipleSlashesInPostContentJson();
        }

        if ($this->isVersionLower($previousVersion, '1.7.0')) {
            add_action('acf/init', [$this, 'updateMarkupIdentifiers']);
        }

        // twig markup
        if ($this->isVersionLower($previousVersion, '2.0.0')) {
            $this->log('Upgrading to 2.0.0, Basic');

            $this->replacePostIdentifiers();
            // trigger save to refresh the markup preview
            add_action('acf/init', function () {
                $viewsCount = $this->triggerSaveForAllViews();
                $cardsCount = $this->triggerSaveForAllCards();

                $this->log('triggered save for all Views, Basic.' . $viewsCount);
                $this->log('triggered save for all Cards, Basic.' . $cardsCount);
            });
        }

        if ($this->isVersionLower($previousVersion, '2.1.0')) {
            add_action('acf/init', [$this, 'enableWithCommonClassesAndUnnecessaryWrappersForAllViews']);
        }

        if ($this->isVersionLower($previousVersion, '2.2.0')) {
            add_action('acf/init', [$this, 'recreatePostSlugs',]);
            add_action('acf/init', [$this, 'replaceViewIdToUniqueIdInCards',]);
            add_action('acf/init', [$this, 'replaceViewIdToUniqueIdInViewRelationships',]);
        }

        if ($this->isVersionLower($previousVersion, '2.2.2')) {
            add_action('acf/init', [$this, 'setDigitalIdForMarkupFlagForViewsAndCards']);
        }

        if ($this->isVersionLower($previousVersion, '2.2.3')) {
            // related Views/Cards in post_content_filtered appeared, filled during the save action
            add_action('acf/init', function () {
                $this->triggerSaveForAllViews();
                $this->triggerSaveForAllCards();
            });
        }

        if ($this->isVersionLower($previousVersion, '2.3.0')) {
            add_action('init', function () {
                $this->twig->createTemplatesDir();
            });
        }

        if ($this->isVersionLower($previousVersion, '2.4.0')) {
            add_action('acf/init', function () {
                $this->disableWebComponentsForExistingViewsAndCards();
                // add acf-views-masonry CSS to the Views' CSS
                $this->triggerSaveForAllViews();
                $this->setupLightBoxSimpleFromOldCheckbox();
            });
        }

        if ($this->isVersionLower($previousVersion, '2.4.2')) {
            add_action('acf/init', function () {
                $this->replacePostCommentsAndMenuLinkFieldsToSeparate();
            });
        }

        if ($this->isVersionLower($previousVersion, '2.4.5')) {
            add_action('acf/init', function () {
                $this->enableNameBackCompatibilityCheckboxForViewsWithGutenberg();
            });
        }

        $this->extraUpgrade($previousVersion);

        $this->settings->setVersion($this->plugin->getVersion());
        $this->settings->save();
    }

    public function setHooks(bool $isAdmin): void
    {
        // don't use 'upgrader_process_complete' hook, as user can update the plugin manually by FTP
        $dbVersion = $this->settings->getVersion();
        $codeVersion = $this->plugin->getVersion();

        // run upgrade if version in the DB is different from the code version
        if ($dbVersion !== $codeVersion) {
            // only at this hook can be sure that other plugin's functions are available
            add_action('plugins_loaded', [$this, 'upgrade']);
            /*add_action('admin_init', function () {
                file_put_contents(__DIR__ . '/log.txt', $this->logData);
            });*/
        }
    }
}
