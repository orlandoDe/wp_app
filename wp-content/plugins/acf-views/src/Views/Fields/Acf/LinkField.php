<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views\Fields\Acf;

use org\wplake\acf_views\Groups\FieldData;
use org\wplake\acf_views\Groups\ItemData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Views\FieldMeta;
use org\wplake\acf_views\Views\Fields\MarkupField;

defined('ABSPATH') || exit;

class LinkField extends MarkupField
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
        $markup = sprintf(
            '<a target="{{ %s.target }}" class="%s" href="{{ %1$s.value }}">',
            esc_html($fieldId),
            esc_html(
                $this->getFieldClass('link', $acfViewData, $fieldData, $isWithFieldWrapper, $isWithRowWrapper)
            )
        );
        $markup .= "\r\n" . str_repeat("\t", ++$tabsNumber);
        $markup .= sprintf('{{ %s.linkLabel|default(%1$s.title) }}', esc_html($fieldId));
        $markup .= "\r\n" . str_repeat("\t", --$tabsNumber);
        $markup .= '</a>';

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
            'value' => '',
            'target' => '_self',
            'title' => '',
            'linkLabel' => $field->getLinkLabelTranslation(),
        ];

        if ($isForValidation) {
            return array_merge($args, [
                'value' => 'https://wordpress.org/',
                'title' => 'wordpress.org',
            ]);
        }

        $notFormattedValue = $notFormattedValue ?
            (array)$notFormattedValue :
            [];

        if (!$notFormattedValue) {
            return $args;
        }

        $isTargetSet = isset($notFormattedValue['target']) && $notFormattedValue['target'];

        $args['value'] = (string)($notFormattedValue['url'] ?? '');
        $args['title'] = (string)($notFormattedValue['title'] ?? '');
        $args['target'] = $field->isLinkTargetBlank || $isTargetSet ?
            '_blank' :
            '_self';

        return $args;
    }

    public function isWithFieldWrapper(ViewData $acfViewData, FieldData $field, FieldMeta $fieldMeta): bool
    {
        return $acfViewData->isWithUnnecessaryWrappers;
    }
}
