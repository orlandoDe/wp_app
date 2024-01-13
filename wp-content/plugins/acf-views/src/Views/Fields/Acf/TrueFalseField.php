<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views\Fields\Acf;

use org\wplake\acf_views\Groups\FieldData;
use org\wplake\acf_views\Groups\ItemData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Views\FieldMeta;
use org\wplake\acf_views\Views\Fields\MarkupField;

defined('ABSPATH') || exit;

class TrueFalseField extends MarkupField
{
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
        $suffix = sprintf('true-false--state--{{ %s.state }}', esc_html($fieldId));

        return sprintf(
            '<div class="%s %s"></div>',
            esc_html(
                $this->getFieldClass(
                    'true-false',
                    $acfViewData,
                    $fieldData,
                    $isWithFieldWrapper,
                    $isWithRowWrapper
                )
            ),
            esc_html($this->getItemClass($suffix, $acfViewData, $fieldData)),
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
        $args = [
            'value' => !!$formattedValue,
            'state' => !!$formattedValue ?
                'checked' :
                'unchecked',
        ];

        // nothing for $isForValidation here

        return $args;
    }

    public function isWithFieldWrapper(ViewData $acfViewData, FieldData $field, FieldMeta $fieldMeta): bool
    {
        return $acfViewData->isWithUnnecessaryWrappers;
    }
}
