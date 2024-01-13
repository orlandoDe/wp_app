<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views\Fields\Post;

use org\wplake\acf_views\Assets\FrontAssets;
use org\wplake\acf_views\Groups\FieldData;
use org\wplake\acf_views\Groups\ItemData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Views\FieldMeta;
use org\wplake\acf_views\Views\Fields\Acf\LinkField;
use org\wplake\acf_views\Views\Fields\CustomField;
use org\wplake\acf_views\Views\Fields\MarkupField;

defined('ABSPATH') || exit;

class PostTitleLinkField extends MarkupField
{
    use CustomField;

    protected LinkField $linkField;

    public function __construct(FrontAssets $frontAssets, LinkField $linkField)
    {
        parent::__construct($frontAssets);

        $this->linkField = $linkField;
    }

    public function getMarkup(
        ViewData $acfViewData,
        string $fieldId,
        ItemData $item,
        FieldData $fieldData,
        FieldMeta $fieldMeta,
        int &$tabsNumber,
        bool $isWithFieldWrapper,
        bool $isWithRowWrapper
    ): string {
        return $this->linkField->getMarkup(
            $acfViewData,
            $fieldId,
            $item,
            $fieldData,
            $fieldMeta,
            $tabsNumber,
            $isWithFieldWrapper,
            $isWithRowWrapper
        );
    }

    public function getTwigArgs(
        ViewData $acfViewData,
        ItemData $item,
        FieldData $field,
        FieldMeta $fieldMeta,
        $notFormattedValue,
        $formattedValue,
        bool $isForValidation = false
    ): array {
        if ($isForValidation) {
            return $this->linkField->getTwigArgs(
                $acfViewData,
                $item,
                $field,
                $fieldMeta,
                $notFormattedValue,
                $formattedValue,
                true
            );
        }

        $post = $this->getPost($notFormattedValue);

        if (!$post) {
            return $this->linkField->getTwigArgs($acfViewData, $item, $field, $fieldMeta, [], []);
        }

        $fieldArgs = [
            'url' => (string)get_the_permalink($post),
            // decode to avoid double encoding in Twig
            'title' => html_entity_decode(get_the_title($post), ENT_QUOTES),
        ];

        return $this->linkField->getTwigArgs($acfViewData, $item, $field, $fieldMeta, $fieldArgs, $fieldArgs);
    }

    public function isWithFieldWrapper(ViewData $acfViewData, FieldData $field, FieldMeta $fieldMeta): bool
    {
        return $acfViewData->isWithUnnecessaryWrappers ||
            $this->linkField->isWithFieldWrapper($acfViewData, $field, $fieldMeta);
    }
}
