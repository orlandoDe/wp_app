<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views\Fields\Term;

use org\wplake\acf_views\Groups\FieldData;
use org\wplake\acf_views\Groups\ItemData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Views\FieldMeta;
use org\wplake\acf_views\Views\Fields\CustomField;
use org\wplake\acf_views\Views\Fields\MarkupField;

defined('ABSPATH') || exit;

class TermSlugField extends MarkupField
{
    use CustomField;

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
        return sprintf(
            "{{ %s.value }}",
            esc_html($fieldId),
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
            'value' => $field->defaultValue,
        ];

        if ($isForValidation) {
            return array_merge($args, [
                'value' => 'slug',
            ]);
        }

        $term = $this->getTerm($notFormattedValue);

        if (!$term) {
            return $args;
        }

        // decode to avoid double encoding in Twig
        $args['value'] = html_entity_decode($term->slug, ENT_QUOTES) ?: $args['value'];

        return $args;
    }

    public function isWithFieldWrapper(ViewData $acfViewData, FieldData $field, FieldMeta $fieldMeta): bool
    {
        return true;
    }
}
