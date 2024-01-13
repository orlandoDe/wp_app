<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views\Fields\Acf;

use org\wplake\acf_views\Assets\FrontAssets;
use org\wplake\acf_views\Groups\FieldData;
use org\wplake\acf_views\Groups\ItemData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Views\FieldMeta;
use org\wplake\acf_views\Views\Fields\ListField;

defined('ABSPATH') || exit;

class PageLinkField extends ListField
{
    const LOOP_ITEM_NAME = 'link_item';

    protected LinkField $linkField;

    public function __construct(FrontAssets $frontAssets, LinkField $linkField)
    {
        parent::__construct($frontAssets);

        $this->linkField = $linkField;
    }

    protected function getPostInfo(string $idOrUrl): array
    {
        $postInfo = [
            'url' => '',
            'title' => '',
        ];

        if (is_numeric($idOrUrl)) {
            $post = get_post($idOrUrl);
        } else {
            $postSlug = str_replace(get_site_url(), '', $idOrUrl);
            $postSlug = trim($postSlug, '/');
            $post = get_page_by_path($postSlug, OBJECT, [
                'post',
                'page',
            ]);
        }

        if (!$post) {
            return $postInfo;
        }

        $title = get_the_title($post);

        return [
            'url' => (string)get_permalink($post->ID),
            // avoid double encoding in Twig
            'title' => html_entity_decode($title, ENT_QUOTES),
        ];
    }

    protected function isMultiple(FieldMeta $fieldMeta): bool
    {
        return $fieldMeta->isMultiple();
    }

    protected function getItemTwigArgs(
        ViewData $acfViewData,
        ItemData $item,
        FieldData $field,
        FieldMeta $fieldMeta,
        $notFormattedValue,
        bool $isForValidation = false
    ): array {
        $linkArgs = $this->getPostInfo((string)$notFormattedValue);

        return $this->linkField->getTwigArgs(
            $acfViewData,
            $item,
            $field,
            $fieldMeta,
            $linkArgs,
            $linkArgs,
            $isForValidation
        );
    }

    protected function getItemMarkup(
        ViewData $viewData,
        string $fieldId,
        string $itemId,
        ItemData $itemData,
        FieldData $fieldData,
        FieldMeta $fieldMeta,
        int &$tabsNumber,
        bool $isWithFieldWrapper,
        bool $isWithRowWrapper
    ): string {
        return $this->linkField->getMarkup(
            $viewData,
            $fieldMeta->isMultiple() ?
                'link_item' :
                $fieldId,
            $itemData,
            $fieldData,
            $fieldMeta,
            $tabsNumber,
            $fieldMeta->isMultiple() || $isWithFieldWrapper,
            $isWithRowWrapper
        );
    }
}
