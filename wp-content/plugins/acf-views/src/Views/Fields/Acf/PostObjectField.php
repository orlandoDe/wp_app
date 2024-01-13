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

class PostObjectField extends ListField
{
    const LOOP_ITEM_NAME = 'post_item';

    protected LinkField $linkField;

    public function __construct(FrontAssets $frontAssets, LinkField $linkField)
    {
        parent::__construct($frontAssets);

        $this->linkField = $linkField;
    }

    protected function getPostInfo(int $id): array
    {
        $postInfo = [
            'url' => '',
            'title' => '',
        ];

        $post = get_post($id);

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
        return 'relationship' === $fieldMeta->getType() || $fieldMeta->isMultiple();
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
            $this->isMultiple($fieldMeta) ?
                'post_item' :
                $fieldId,
            $itemData,
            $fieldData,
            $fieldMeta,
            $tabsNumber,
            $this->isMultiple($fieldMeta) || $isWithFieldWrapper,
            $isWithRowWrapper
        );
    }

    protected function getItemTwigArgs(
        ViewData $acfViewData,
        ItemData $item,
        FieldData $field,
        FieldMeta $fieldMeta,
        $notFormattedValue,
        bool $isForValidation = false
    ): array {
        $linkArgs = $this->getPostInfo((int)$notFormattedValue);
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
}
