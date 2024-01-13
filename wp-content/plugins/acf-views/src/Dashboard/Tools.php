<?php


declare(strict_types=1);

namespace org\wplake\acf_views\Dashboard;

use org\wplake\acf_views\Cards\CardsDataStorage;
use org\wplake\acf_views\Cards\Cpt\CardsCpt;
use org\wplake\acf_views\Common\HooksInterface;
use org\wplake\acf_views\Groups\CardData;
use org\wplake\acf_views\Groups\ToolsData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Plugin;
use org\wplake\acf_views\Views\Cpt\ViewsCpt;
use org\wplake\acf_views\Views\ViewsDataStorage;
use WP_Query;

defined('ABSPATH') || exit;

class Tools implements HooksInterface
{
    const SLUG = 'acf-views-tools';

    protected array $values;
    protected ToolsData $toolsData;
    protected CardsDataStorage $cardsDataStorage;
    protected ViewsDataStorage $viewsDataStorage;
    protected Plugin $plugin;
    protected ViewData $acfViewData;
    protected CardData $acfCardData;
    protected array $exportData;
    protected bool $isImportSuccessfull;
    protected string $importResultMessage;

    public function __construct(
        ToolsData $toolsData,
        CardsDataStorage $cardsDataStorage,
        ViewsDataStorage $viewsDataStorage,
        Plugin $plugin,
        ViewData $acfViewData,
        CardData $acfCardData
    ) {
        $this->toolsData = $toolsData;
        $this->cardsDataStorage = $cardsDataStorage;
        $this->viewsDataStorage = $viewsDataStorage;
        $this->plugin = $plugin;
        $this->acfViewData = $acfViewData;
        $this->acfCardData = $acfCardData;
        $this->values = [];
        $this->exportData = [];
        $this->isImportSuccessfull = false;
        $this->importResultMessage = '';
    }

    protected function isMySource($postId): bool
    {
        $screen = get_current_screen();

        return !!$screen &&
            'acf_views_page_acf-views-tools' === $screen->id &&
            'options' === $postId;
    }

    protected function getPosts(string $postType, array $slugs): array
    {
        $queryArgs = [
            'fields' => 'ids',
            'post_type' => $postType,
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ];

        if ($slugs) {
            $queryArgs['post_name__in'] = $slugs;
        }

        $query = new WP_Query($queryArgs);

        // already only IDs
        return $query->get_posts();
    }

    protected function export(): void
    {
        $isViewsInExport = $this->toolsData->isExportAllViews ||
            $this->toolsData->exportViews;
        $isCardsInExport = $this->toolsData->isExportAllCards ||
            $this->toolsData->exportCards;

        $viewIds = $isViewsInExport ?
            $this->getPosts(ViewsCpt::NAME, $this->toolsData->exportViews) :
            [];
        $cardIds = $isCardsInExport ?
            $this->getPosts(CardsCpt::NAME, $this->toolsData->exportCards) :
            [];

        foreach ($viewIds as $viewId) {
            $acfViewData = $this->viewsDataStorage->get($viewId);
            $viewUniqueId = $acfViewData->getUniqueId();

            $this->exportData[$viewUniqueId] = array_merge($acfViewData->getFieldValues(), [
                '_post_title' => get_the_title($viewId),
                '_post_excerpt' => get_post_field('post_excerpt', $viewId),
                '_post_content_filtered' => get_post_field('post_content_filtered', $viewId),
            ]);
        }

        foreach ($cardIds as $cardId) {
            $acfCardData = $this->cardsDataStorage->get($cardId);
            $cardUniqueId = $acfCardData->getUniqueId();

            $this->exportData[$cardUniqueId] = array_merge($acfCardData->getFieldValues(), [
                '_post_title' => get_the_title($cardId),
                '_post_excerpt' => get_post_field('post_excerpt', $cardId),
                '_post_content_filtered' => get_post_field('post_content_filtered', $cardId),
            ]);
        }
    }

    protected function getExistingItems(array $jsonData): array
    {
        $items = [];
        $query = new WP_Query([
            'post_type' => [ViewsCpt::NAME, CardsCpt::NAME],
            'post_name__in' => array_keys($jsonData),
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft', 'trash',],
        ]);
        $posts = $query->get_posts();

        foreach ($posts as $post) {
            $items[$post->post_name] = $post->ID;
        }

        return $items;
    }

    protected function getImportResultMessage(
        array $successViewIds,
        array $successCardIds,
        array $failViewUniqueIds,
        array $failCardUniqueIds
    ): string {
        $viewsInfo = [];
        $cardsInfo = [];
        $importResultMessage = '';

        foreach ($successViewIds as $successViewId) {
            $viewsInfo[] = sprintf(
                '<a href="%s" target="_blank">%s</a>',
                (string)get_edit_post_link($successViewId),
                get_the_title($successViewId)
            );
        }

        foreach ($successCardIds as $successCardId) {
            $cardsInfo[] = sprintf(
                '<a href="%s" target="_blank">%s</a>',
                get_edit_post_link($successCardId),
                get_the_title($successCardId)
            );
        }


        if (!$failViewUniqueIds &&
            !$failCardUniqueIds) {
            $this->isImportSuccessfull = true;

            $importResultMessage .= sprintf(
                __('Successfully imported %d Views and %d Cards.', 'acf-views'),
                count($successViewIds),
                count($successCardIds)
            );
            $importResultMessage .= '<br>';
        } else {
            $importResultMessage .= sprintf(
                __('Something went wrong. Imported %d from %d Views and %d from %d Cards.', 'acf-views'),
                count($successViewIds),
                count($successViewIds) + count($failViewUniqueIds),
                count($successCardIds),
                count($successCardIds) + count($failCardUniqueIds)
            );
            $importResultMessage .= '<br>';
        }

        if ($viewsInfo) {
            $viewsLabel = __('Imported Views', 'acf-views');
            $importResultMessage .= sprintf(
                '%s: %s.',
                $viewsLabel,
                implode(', ', $viewsInfo)
            );
            $importResultMessage .= '<br>';
        }

        if ($cardsInfo) {
            $cardsLabel = __('Imported Cards', 'acf-views');
            $importResultMessage .= sprintf(
                '%s: %s.',
                $cardsLabel,
                implode(', ', $cardsInfo)
            );
            $importResultMessage .= '<br>';
        }

        if ($failViewUniqueIds) {
            $viewsLabel = __('Wrong Views', 'acf-views');
            $importResultMessage .= sprintf(
                '%s: %s.',
                $viewsLabel,
                implode(', ', $failViewUniqueIds)
            );
            $importResultMessage .= '<br>';
        }

        if ($failCardUniqueIds) {
            $cardsLabel = __('Wrong Cards', 'acf-views');
            $importResultMessage .= sprintf(
                '%s: %s.',
                $cardsLabel,
                implode(', ', $failCardUniqueIds)
            );
            $importResultMessage .= '<br>';
        }

        return $importResultMessage;
    }

    protected function importOrUpdateItems(array $jsonData): void
    {
        $successViewIds = [];
        $successCardIds = [];
        $failViewUniqueIds = [];
        $failCardUniqueIds = [];

        $existingItems = $this->getExistingItems($jsonData);

        foreach ($jsonData as $uniqueId => $details) {
            $postType = false !== strpos($uniqueId, 'view_') ?
                ViewsCpt::NAME :
                CardsCpt::NAME;
            $isExistingItem = key_exists($uniqueId, $existingItems);
            $postTitle = $details['_post_title'] ?? '';
            $postExcerpt = $details['_post_excerpt'] ?? '';
            $postContentFiltered = $details['_post_content_filtered'] ?? '';

            if (isset($details['_post_title'])) {
                unset($details['_post_title']);
            }

            if (isset($details['_post_excerpt'])) {
                unset($details['_post_excerpt']);
            }

            if (isset($details['_post_content_filtered'])) {
                unset($details['_post_content_filtered']);
            }

            $postId = !$isExistingItem ?
                wp_insert_post([
                    'post_type' => $postType,
                    'post_status' => 'publish',
                    'post_name' => $uniqueId,
                    'post_title' => $postTitle,
                    'post_excerpt' => $postExcerpt,
                    'post_content_filtered' => $postContentFiltered,
                ]) :
                $existingItems[$uniqueId];

            if (is_wp_error($postId)) {
                if (ViewsCpt::NAME === $postType) {
                    $failViewUniqueIds[] = $uniqueId;
                } else {
                    $failCardUniqueIds[] = $uniqueId;
                }
                continue;
            }

            $postFields = [];

            if ($isExistingItem) {
                $postFields = array_merge($postFields, [
                    'post_title' => $postTitle,
                    'post_excerpt' => $postExcerpt,
                    'post_content_filtered' => $postContentFiltered,
                ]);
            }

            $cptData = ViewsCpt::NAME === $postType ?
                $this->acfViewData->getDeepClone() :
                $this->acfCardData->getDeepClone();

            // pass through loading, instead of the DB query to update the post_content
            // it some kind of verification process, because only the right fields will be picked up from json and saved
            $cptData->load($postId, '', $details);
            $cptData->saveToPostContent($postFields);

            // there is no sense to call the 'performSaveActions' method
            // just clean the cache (if somebody uses Redis or something like that)

            if ($isExistingItem) {
                clean_post_cache($postId);
            }

            if (ViewsCpt::NAME === $postType) {
                $successViewIds[] = $postId;
            } else {
                $successCardIds[] = $postId;
            }
        }

        $resultMessage = [];

        $resultMessage[] = implode(',', $successViewIds);
        $resultMessage[] = implode(',', $successCardIds);
        $resultMessage[] = implode(',', $failViewUniqueIds);
        $resultMessage[] = implode(',', $failCardUniqueIds);

        $this->importResultMessage = implode(';', $resultMessage);
    }

    protected function import(): void
    {
        $pathToFile = (string)get_attached_file($this->toolsData->importFile);

        if (!$pathToFile ||
            !file_exists($pathToFile)) {
            $this->importResultMessage = __('Import file not found.', 'acf-views');
            return;
        }

        $fileContent = file_get_contents($pathToFile);
        // remove the prefix, that was added to avoid WP Media library JSON detection
        $fileContent = str_replace('Advanced Views:', '', $fileContent);

        $jsonData = json_decode($fileContent, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            $this->importResultMessage = __('Import file is not a valid JSON.', 'acf-views');
            return;
        }

        $this->importOrUpdateItems($jsonData);

        wp_delete_attachment($this->toolsData->importFile, true);

        $url = $this->plugin->getAdminUrl(self::SLUG) .
            sprintf(
                '&message=1&type=import&isSuccess=%s&resultMessage=%s',
                $this->isImportSuccessfull,
                $this->importResultMessage
            );
        wp_redirect($url);
        exit;
    }

    public function maybeEchoExportFile($postId): void
    {
        if (!$this->isMySource($postId) ||
            !$this->exportData) {
            return;
        }

        $ids = array_keys($this->exportData);
        $viewIds = array_filter($ids, function ($id) {
            return false !== strpos($id, 'view_');
        });
        $countOfViewIds = count($viewIds);
        $cardIds = array_filter($ids, function ($id) {
            return false !== strpos($id, 'card_');
        });
        $countOfCardIds = count($cardIds);

        $redirectUrl = $this->plugin->getAdminUrl(self::SLUG) .
            sprintf('&message=1&type=export&_views=%s&_cards=%s', $countOfViewIds, $countOfCardIds);
        ?>
        <script>
            (function () {
                function save() {
                    const data = <?php echo json_encode($this->exportData); ?>;

                    let date = new Date().toISOString().slice(0, 10);
                    // .txt to pass WP Media library
                    let fileName = `advanced-views-export-${date}.txt`;
                    // add some text prefix to avoid WP Media library to think it's a JSON
                    let content = "Advanced Views:" + JSON.stringify(data);

                    const file = new File([content], fileName, {
                        type: 'application/json',
                    })

                    let toolsUrl = "<?php echo $redirectUrl?>";

                    const a = document.createElement('a');

                    a.href = URL.createObjectURL(file);
                    a.download = fileName;
                    a.click();

                    window.location.href = toolsUrl;
                }

                'loading' === document.readyState ?
                    window.document.addEventListener('DOMContentLoaded', save) :
                    save();
            }())
        </script>
        <?php
        exit;
    }

    public function addPage(): void
    {
        // do not use 'acf_add_options_page', as the global options-related functions may be unavailable
        // (in case of the manual include)
        if (!function_exists('acf_options_page')) {
            return;
        }

        $type = sanitize_text_field($_GET['type'] ?? '');
        $isExport = 'export' === $type;
        $isImport = 'import' === $type;

        $updatedMessage = '';

        if ($isExport) {
            $viewsCount = (int)($_GET['_views'] ?? 0);
            $cardsCount = (int)($_GET['_cards'] ?? 0);

            $updatedMessage = __('Success! There were %d Views and %d Cards exported.', 'acf-views');
            $updatedMessage = sprintf($updatedMessage, $viewsCount, $cardsCount);
        }

        if ($isImport) {
            $resultMessage = sanitize_text_field($_GET['resultMessage'] ?? '');
            $resultMessage = esc_html($resultMessage);

            $successViewIds = explode(';', $resultMessage)[0] ?? '';
            $successViewIds = $successViewIds ?
                explode(',', $successViewIds) :
                [];

            $successCardIds = explode(';', $resultMessage)[1] ?? '';
            $successCardIds = $successCardIds ?
                explode(',', $successCardIds) :
                [];

            $failViewUniqueIds = explode(';', $resultMessage)[2] ?? '';
            $failViewUniqueIds = $failViewUniqueIds ?
                explode(',', $failViewUniqueIds) :
                [];

            $failCardUniqueId = explode(';', $resultMessage)[3] ?? '';
            $failCardUniqueId = $failCardUniqueId ?
                explode(',', $failCardUniqueId) :
                [];

            $updatedMessage = $this->getImportResultMessage(
                $successViewIds,
                $successCardIds,
                $failViewUniqueIds,
                $failCardUniqueId
            );
        }

        acf_options_page()->add_page([
            'slug' => self::SLUG,
            'page_title' => __('Tools', 'acf-views'),
            'menu_title' => __('Tools', 'acf-views'),
            'parent_slug' => sprintf('edit.php?post_type=%s', ViewsCpt::NAME),
            'position' => 3,
            'update_button' => __('Process', 'acf-views'),
            'updated_message' => $updatedMessage,
        ]);
    }

    public function maybeCatchValues($postId)
    {
        if (!$this->isMySource($postId)) {
            return;
        }

        add_filter('acf/pre_update_value', function ($isUpdated, $value, $postId, array $field): bool {
            // extra check, as probably it's about another post
            if (!$this->isMySource($postId)) {
                return $isUpdated;
            }

            $fieldName = $field['name'] ?? '';

            $this->values[$fieldName] = $value;

            // avoid saving to the postmeta
            return true;
        }, 10, 4);
    }

    public function maybeProcess($postId): void
    {
        if (!$this->isMySource($postId) ||
            !$this->values) {
            return;
        }

        $this->toolsData->load(false, '', $this->values);

        $isExport = $this->toolsData->isExportAllViews ||
            $this->toolsData->isExportAllCards ||
            $this->toolsData->exportViews ||
            $this->toolsData->exportCards;
        $isImport = !!$this->toolsData->importFile;

        if ($isExport) {
            $this->export();
        }

        if ($isImport) {
            $this->import();
        }
    }

    public function setHooks(bool $isAdmin): void
    {
        if (!$isAdmin) {
            return;
        }

        // init, not acf/init, as the method uses 'get_edit_post_link' which will be available only since this hook
        // (because we sign up the CPTs in this hook)
        add_action('init', [$this, 'addPage',]);
        add_action('acf/save_post', [$this, 'maybeCatchValues',]);
        // priority 20, as it's after the ACF's save_post hook
        add_action('acf/save_post', [$this, 'maybeProcess',], 20);
        // priority 30, after the process action
        add_action('acf/save_post', [$this, 'maybeEchoExportFile',], 30);
    }
}
