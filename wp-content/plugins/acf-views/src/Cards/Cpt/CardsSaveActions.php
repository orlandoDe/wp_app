<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Cards\Cpt;

use org\wplake\acf_views\Assets\FrontAssets;
use org\wplake\acf_views\Cards\CardFactory;
use org\wplake\acf_views\Cards\CardMarkup;
use org\wplake\acf_views\Cards\CardsDataStorage;
use org\wplake\acf_views\Cards\CardShortcode;
use org\wplake\acf_views\Cards\QueryBuilder;
use org\wplake\acf_views\Common\Cpt\SaveActions;
use org\wplake\acf_views\Common\Instance;
use org\wplake\acf_views\Groups\CardData;
use org\wplake\acf_views\Html;
use org\wplake\acf_views\Plugin;

defined('ABSPATH') || exit;

class CardsSaveActions extends SaveActions
{
    protected CardMarkup $cardMarkup;
    protected QueryBuilder $queryBuilder;
    protected Html $html;
    protected CardsMetaBoxes $cardsMetaBoxes;
    protected CardFactory $cardFactory;
    /**
     * @var CardData
     */
    protected $validationData;

    public function __construct(
        CardsDataStorage $cardsDataStorage,
        Plugin $plugin,
        CardData $cardData,
        FrontAssets $frontAssets,
        CardMarkup $cardMarkup,
        QueryBuilder $queryBuilder,
        Html $html,
        CardsMetaBoxes $cardsMetaBoxes,
        CardFactory $cardFactory
    ) {
        parent::__construct($cardsDataStorage, $plugin, $cardData, $frontAssets);

        $this->cardMarkup = $cardMarkup;
        $this->queryBuilder = $queryBuilder;
        $this->html = $html;
        $this->cardsMetaBoxes = $cardsMetaBoxes;
        $this->cardFactory = $cardFactory;
    }

    protected function getCptName(): string
    {
        return CardsCpt::NAME;
    }

    /**
     * @param CardData $cptData
     * @return array
     */
    protected function getTranslatableLabels($cptData): array
    {
        $labels = [];

        if ($cptData->noPostsFoundMessage) {
            $labels[] = $cptData->noPostsFoundMessage;
        }

        if ($cptData->loadMoreButtonLabel) {
            $labels[] = $cptData->loadMoreButtonLabel;
        }

        return $labels ?
            [
                $this->plugin->getThemeTextDomain() => $labels,
            ] :
            [];
    }

    protected function getCustomMarkupAcfFieldName(): string
    {
        return CardData::getAcfFieldName(CardData::FIELD_CUSTOM_MARKUP);
    }

    protected function makeValidationInstance(): Instance
    {
        return $this->cardFactory->make($this->validationData);
    }

    /**
     * @param CardData $cptData
     * @return void
     */
    protected function updateMarkup($cptData): void
    {
        $cptData->markup = $this->cardMarkup->getMarkup($cptData, false, true);
    }

    protected function updateQueryPreview(CardData $acfCardData): void
    {
        $acfCardData->queryPreview = print_r($this->queryBuilder->getQueryArgs($acfCardData, 1), true);
    }

    protected function addLayoutCSS(CardData $acfCardData): void
    {
        $layoutCSS = $this->cardMarkup->getLayoutCSS($acfCardData);

        if (!$layoutCSS) {
            return;
        }

        $acfCardData->cssCode = false === strpos($acfCardData->cssCode, '/*BEGIN LAYOUT_RULES*/') ?
            ($acfCardData->cssCode . "\n" . $layoutCSS . "\n") :
            preg_replace(
                '|\/\*BEGIN LAYOUT_RULES\*\/(.*\s)+\/\*END LAYOUT_RULES\*\/|',
                $layoutCSS,
                $acfCardData->cssCode
            );
    }


    public function performSaveActions($postId, bool $isSkipSave = false): ?CardData
    {
        // skip save, it'll be below
        $cardData = parent::performSaveActions($postId, true);

        // not just on null, but also on the type, for IDE
        if (!($cardData instanceof CardData)) {
            return null;
        }

        $this->maybeSetUniqueId($cardData, 'card_');
        $this->updateQueryPreview($cardData);
        $this->updateMarkup($cardData);
        $this->addLayoutCSS($cardData);
        $this->updateTranslationsFile($cardData);

        if (!$isSkipSave) {
            $cardData->saveToPostContent();
        }

        return $cardData;
    }

    public function refreshAjax(): void
    {
        $cardId = (int)($_POST['_postId'] ?? 0);

        $postType = get_post($cardId)->post_type ?? '';

        if ($this->getCptName() !== $postType) {
            echo "Post id is wrong";
            exit;
        }

        $response = [];

        /**
         * @var CardData $cardData
         */
        $cardData = $this->cptDataStorage->get($cardId);
        // ignore customMarkup (we need the preview)
        $markup = $this->cardMarkup->getMarkup($cardData, false, true);
        $shortcodes = $this->html->postboxShortcodes(
            $cardData->getUniqueId(true),
            false,
            CardShortcode::NAME,
            get_the_title($cardId),
            true
        );

        $response['textareaItems'] = [
            // id => value
            'acf-local_acf_views_acf-card-data__markup' => $markup,
            'acf-local_acf_views_acf-card-data__css-code' => $cardData->getCssCode(false),
            'acf-local_acf_views_acf-card-data__js-code' => $cardData->getJsCode(false),
            'acf-local_acf_views_acf-card-data__query-preview' => $cardData->queryPreview,
        ];

        $response['elements'] = [
            '#acf-cards_shortcode_cpt .inside' => $shortcodes,
            '#acf-cards_related_view .inside' => $this->cardsMetaBoxes->printRelatedAcfViewMetaBox(
                get_post($cardId),
                true
            ),
        ];

        $response['autocompleteData'] = $this->cardFactory->getAutocompleteVariables($cardId);

        echo json_encode($response);

        exit;
    }

    public function setHooks(bool $isAdmin): void
    {
        parent::setHooks($isAdmin);

        if (!$isAdmin) {
            return;
        }

        add_action('wp_ajax_acf_views__card_refresh', [$this, 'refreshAjax',]);
    }
}