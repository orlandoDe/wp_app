<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views\Fields\Acf;

use DateTime;
use org\wplake\acf_views\Groups\FieldData;
use org\wplake\acf_views\Groups\ItemData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Views\FieldMeta;
use org\wplake\acf_views\Views\Fields\MarkupField;

defined('ABSPATH') || exit;

class DatePickerField extends MarkupField
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
        return sprintf('{{ %s.value }}', esc_html($fieldId));
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
            'value' => '',
        ];

        if ($isForValidation) {
            $now = new DateTime();

            return array_merge($args, [
                'value' => $now->format($fieldMeta->getDisplayFormat()),
            ]);
        }

        $notFormattedValue = $notFormattedValue ?
            (string)$notFormattedValue :
            [];

        if (!$notFormattedValue) {
            return $args;
        }

        $date = false;

        switch ($fieldMeta->getType()) {
            case 'date_picker':
                $date = DateTime::createFromFormat('Ymd', $notFormattedValue);
                break;
            case 'date_time_picker':
                $date = DateTime::createFromFormat('Y-m-d H:i:s', $notFormattedValue);
                break;
            case 'time_picker':
                $date = DateTime::createFromFormat('H:i:s', $notFormattedValue);
                break;
        }

        if (false === $date) {
            return $args;
        }

        // date_i18n() unlike the '$date->format($displayFormat)' supports different languages
        $args['value'] = date_i18n($fieldMeta->getDisplayFormat(), $date->getTimestamp());

        return $args;
    }

    public function isWithFieldWrapper(ViewData $acfViewData, FieldData $field, FieldMeta $fieldMeta): bool
    {
        return true;
    }
}
