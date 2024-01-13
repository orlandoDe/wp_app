<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views\Fields\Acf;

use org\wplake\acf_views\Assets\FrontAssets;
use org\wplake\acf_views\Groups\FieldData;
use org\wplake\acf_views\Groups\ItemData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Views\FieldMeta;
use org\wplake\acf_views\Views\Fields\MarkupField;

defined('ABSPATH') || exit;

class FileField extends MarkupField
{
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

        $notFormattedValue = $notFormattedValue ?
            (int)$notFormattedValue :
            0;

        if (!$notFormattedValue) {
            return $this->linkField->getTwigArgs($acfViewData, $item, $field, $fieldMeta, [], [], false);
        }

        $fieldArgs = [
            'url' => (string)wp_get_attachment_url($notFormattedValue),
            'title' => (string)(get_post($notFormattedValue)->post_title ?? ''),
        ];

        return $this->linkField->getTwigArgs($acfViewData, $item, $field, $fieldMeta, $fieldArgs, $fieldArgs, false);
    }

    public function isWithFieldWrapper(ViewData $acfViewData, FieldData $field, FieldMeta $fieldMeta): bool
    {
        return $this->linkField->isWithFieldWrapper($acfViewData, $field, $fieldMeta);
    }
}
