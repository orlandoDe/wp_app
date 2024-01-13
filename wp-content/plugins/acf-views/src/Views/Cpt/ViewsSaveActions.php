<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views\Cpt;

use org\wplake\acf_views\Assets\FrontAssets;
use org\wplake\acf_views\Common\Cpt\SaveActions;
use org\wplake\acf_views\Common\Instance;
use org\wplake\acf_views\Groups\ItemData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Html;
use org\wplake\acf_views\Plugin;
use org\wplake\acf_views\Views\FieldMeta;
use org\wplake\acf_views\Views\Post;
use org\wplake\acf_views\Views\ViewFactory;
use org\wplake\acf_views\Views\ViewMarkup;
use org\wplake\acf_views\Views\ViewsDataStorage;
use org\wplake\acf_views\Views\ViewShortcode;

defined('ABSPATH') || exit;

class ViewsSaveActions extends SaveActions
{
    protected ViewMarkup $viewMarkup;
    protected ViewsMetaBoxes $viewsMetaBoxes;
    protected Html $html;
    /**
     * @var ViewData
     */
    protected $validationData;
    protected ViewFactory $viewFactory;

    public function __construct(
        ViewsDataStorage $viewsDataStorage,
        Plugin $plugin,
        ViewData $viewData,
        FrontAssets $frontAssets,
        ViewMarkup $viewMarkup,
        ViewsMetaBoxes $viewsMetaBoxes,
        Html $html,
        ViewFactory $viewFactory
    ) {
        parent::__construct($viewsDataStorage, $plugin, $viewData, $frontAssets);

        $this->viewMarkup = $viewMarkup;
        $this->viewsMetaBoxes = $viewsMetaBoxes;
        $this->html = $html;
        $this->viewFactory = $viewFactory;
    }

    protected function getCptName(): string
    {
        return ViewsCpt::NAME;
    }

    /**
     * @param $cptData
     * @return array
     */
    protected function getTranslatableLabels($cptData): array
    {
        $labels = [];

        /**
         * @var ItemData $item
         */
        foreach ($cptData->items as $item) {
            if ($item->field->label) {
                $labels[] = $item->field->label;
            }
            if ($item->field->linkLabel) {
                $labels[] = $item->field->linkLabel;
            }
            if ($item->field->mapMarkerIconTitle) {
                $labels[] = $item->field->mapMarkerIconTitle;
            }
        }

        return $labels ?
            [
                Plugin::getThemeTextDomain() => array_unique($labels),
            ] :
            [];
    }

    protected function getCustomMarkupAcfFieldName(): string
    {
        return ViewData::getAcfFieldName(ViewData::FIELD_CUSTOM_MARKUP);
    }

    protected function makeValidationInstance(): Instance
    {
        return $this->viewFactory->make(new Post(0), $this->getAcfAjaxPostId(), 0, $this->validationData);
    }

    /**
     * @param ViewData $viewData
     * @return void
     */
    public function updateMarkup($viewData): void
    {
        // pageId 0, so without CSS, also skipCache and customMarkup
        $viewMarkup = $this->viewMarkup->getMarkup($viewData, 0, '', true, true);

        $viewData->markup = $viewMarkup;
    }

    protected function updateIdentifiers(ViewData $acfViewData): void
    {
        foreach ($acfViewData->items as $item) {
            $item->field->id = ($item->field->id &&
                !preg_match('/^[a-zA-Z0-9_\-]+$/', $item->field->id)) ?
                '' :
                $item->field->id;

            if ($item->field->id &&
                $item->field->id === $this->getUniqueFieldId($acfViewData, $item, $item->field->id)) {
                continue;
            }

            $fieldMeta = new FieldMeta($item->field->getAcfFieldId());
            if (!$fieldMeta->isFieldExist()) {
                continue;
            }

            // $Post$ fields have '_' prefix, remove it, otherwise looks bad in the markup
            $name = ltrim($fieldMeta->getName(), '_');
            // transform '_' to '-' to follow the BEM standard (underscore only as a delimiter)
            $name = str_replace('_', '-', $name);
            $item->field->id = $this->getUniqueFieldId($acfViewData, $item, $name);
        }
    }

    // public for tests
    public function getUniqueFieldId(ViewData $acfViewData, $excludeObject, string $name): string
    {
        $isUnique = true;

        foreach ($acfViewData->items as $item) {
            if ($item === $excludeObject ||
                $item->field->id !== $name) {
                continue;
            }

            $isUnique = false;
            break;
        }

        return $isUnique ?
            $name :
            $this->getUniqueFieldId($acfViewData, $excludeObject, $name . '2');
    }

    public function performSaveActions($postId, bool $isSkipSave = false): ?ViewData
    {
        // do not save, it'll be below
        $viewData = parent::performSaveActions($postId, true);

        // not just check on null, but also on the type, for IDE
        if (!($viewData instanceof ViewData)) {
            return null;
        }

        // setting up ID should be the first, as it's used in markup
        $this->maybeSetUniqueId($viewData, 'view_');
        $this->updateIdentifiers($viewData);
        $this->updateMarkup($viewData);
        $this->updateTranslationsFile($viewData);

        if (!$isSkipSave) {
            // it'll also update post fields, like 'comment_count'
            $viewData->saveToPostContent();
        }

        return $viewData;
    }

    public function refreshAjax(): void
    {
        $viewId = (int)($_POST['_postId'] ?? 0);

        $postType = get_post($viewId)->post_type ?? '';

        if ($this->getCptName() !== $postType) {
            echo "Post id is wrong";
            exit;
        }

        $viewData = $this->cptDataStorage->get($viewId);
        $shortcodes = $this->html->postboxShortcodes(
            $viewData->getUniqueId(true),
            false,
            ViewShortcode::NAME,
            get_the_title($viewId),
            false
        );
        $response = [];
        // ignore customMarkup (we need the preview)
        $markup = $this->viewMarkup->getMarkup(
            $viewData,
            0,
            '',
            false,
            true
        );

        $response['textareaItems'] = [
            // id => value
            'acf-local_acf_views_view__markup' => $markup,
            'acf-local_acf_views_view__css-code' => $viewData->getCssCode(false),
            'acf-local_acf_views_view__js-code' => $viewData->getJsCode(false),
        ];
        $response['elements'] = [
            '#acf-views_shortcode .inside' => $shortcodes,
            '#acf-views_related_groups .inside' => $this->viewsMetaBoxes->printRelatedAcfGroupsMetaBox(
                get_post($viewId),
                true
            ),
            '#acf-views_related_views .inside' => $this->viewsMetaBoxes->printRelatedViewsMetaBox(
                get_post($viewId),
                true
            ),
            '#acf-views_related_cards .inside' => $this->viewsMetaBoxes->getRelatedAcfCardsMetaBox(get_post($viewId)),
        ];

        $response['autocompleteData'] = $this->viewFactory->getAutocompleteVariables($viewId);

        echo json_encode($response);

        exit;
    }

    public function setHooks(bool $isAdmin): void
    {
        parent::setHooks($isAdmin);

        if (!$isAdmin) {
            return;
        }

        add_action('wp_ajax_acf_views__view_refresh', [$this, 'refreshAjax',]);
    }
}
