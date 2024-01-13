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

class GalleryField extends MarkupField
{
    protected ImageField $imageField;

    public function __construct(FrontAssets $frontAssets, ImageField $imageField)
    {
        parent::__construct($frontAssets);

        $this->imageField = $imageField;
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
        return $this->imageField->getMarkup(
            $viewData,
            'image_item',
            $itemData,
            $fieldData,
            $fieldMeta,
            $tabsNumber,
            true,
            $isWithRowWrapper
        );
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
        $markup = "\r\n" . str_repeat("\t", $tabsNumber);
        $markup .= sprintf("{%% for image_item in %s.value %%}", esc_html($fieldId));
        $markup .= "\r\n";
        $markup .= str_repeat("\t", ++$tabsNumber);
        $markup .= $this->printItem(
            $acfViewData,
            $fieldId,
            'image_item',
            $item,
            $fieldData,
            $fieldMeta,
            $tabsNumber,
            $isWithFieldWrapper,
            $isWithRowWrapper
        );
        $markup .= "\r\n";
        $markup .= str_repeat("\t", --$tabsNumber);
        $markup .= "{% endfor %}\r\n";

        return $markup;
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
        $args = [
            'value' => [],
        ];

        if ($isForValidation) {
            $value = [];
            $value[] = $this->imageField->getTwigArgs(
                $acfViewData,
                $item,
                $field,
                $fieldMeta,
                $notFormattedValue,
                $formattedValue,
                true
            );

            return array_merge($args, [
                'value' => $value,
            ]);
        }

        $notFormattedValue = $notFormattedValue ?
            (array)$notFormattedValue :
            [];

        if (!$notFormattedValue) {
            return $args;
        }

        foreach ($notFormattedValue as $image) {
            $args['value'][] = $this->imageField->getTwigArgs(
                $acfViewData,
                $item,
                $field,
                $fieldMeta,
                $image,
                $image,
                false
            );
        }

        return $args;
    }

    public function isWithFieldWrapper(ViewData $acfViewData, FieldData $field, FieldMeta $fieldMeta): bool
    {
        return true;
    }
}
