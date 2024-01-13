<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views\Fields;

use org\wplake\acf_views\Groups\FieldData;
use org\wplake\acf_views\Groups\ItemData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Views\Cpt\ViewsCpt;
use org\wplake\acf_views\Views\FieldMeta;

defined('ABSPATH') || exit;

abstract class ListField extends MarkupField
{
    const LOOP_ITEM_NAME = 'item';

    abstract protected function isMultiple(FieldMeta $fieldMeta): bool;

    abstract protected function getItemTwigArgs(
        ViewData $acfViewData,
        ItemData $item,
        FieldData $field,
        FieldMeta $fieldMeta,
        $notFormattedValue,
        bool $isForValidation = false
    ): array;

    protected function getShortcode(FieldData $fieldData, array $attrs): string
    {
        $shortcodeAttrs = '';
        foreach ($attrs as $key => $value) {
            $shortcodeAttrs .= sprintf(' %s="%s"', esc_html($key), esc_html($value));
        }

        return sprintf(
            '[%s%s]',
            ViewsCpt::NAME,
            $shortcodeAttrs
        );
    }

    // separate method, so it can be overridden in the child classes
    protected function getNotFormattedValue(FieldMeta $fieldMeta, $notFormattedValue)
    {
        // case to string in the second case, as it can be 'option from the SelectField
        return $this->isMultiple($fieldMeta) ?
            (array)$notFormattedValue :
            (string)$notFormattedValue;
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
        $markup = '';

        if ($this->isMultiple($fieldMeta)) {
            $markup .= "\r\n" . str_repeat("\t", $tabsNumber);
            $markup .= sprintf("{%% for %s in %s.value %%}", static::LOOP_ITEM_NAME, esc_html($fieldId));

            $markup .= "\r\n" . str_repeat("\t", ++$tabsNumber);

            if ($fieldData->optionsDelimiter) {
                $markup .= "\r\n" . str_repeat("\t", $tabsNumber);
                $markup .= "{% if true != loop.first %}";

                $markup .= "\r\n" . str_repeat("\t", ++$tabsNumber);

                $markup .= sprintf(
                    '<span class="%s">',
                    esc_html($this->getItemClass('delimiter', $acfViewData, $fieldData))
                );
                $markup .= "\r\n" . str_repeat("\t", ++$tabsNumber);
                $markup .= sprintf("{{ %s.options_delimiter }}", esc_html($fieldId));
                $markup .= "\r\n" . str_repeat("\t", --$tabsNumber);
                $markup .= "</span>";

                $markup .= "\r\n" . str_repeat("\t", --$tabsNumber);

                $markup .= "{% endif %}\r\n\r\n" . str_repeat("\t", $tabsNumber);
            }
        }


        $markup .= $this->printItem(
            $acfViewData,
            $fieldId,
            static::LOOP_ITEM_NAME,
            $item,
            $fieldData,
            $fieldMeta,
            $tabsNumber,
            $isWithFieldWrapper,
            $isWithRowWrapper
        );

        if ($this->isMultiple($fieldMeta)) {
            $markup .= "\r\n";
            $markup .= str_repeat("\t", --$tabsNumber);
            $markup .= "{% endfor %}\r\n";
        }

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
            'options_delimiter' => $field->optionsDelimiter,
        ];

        if ($isForValidation) {
            $itemArgs = $this->getItemTwigArgs(
                $acfViewData,
                $item,
                $field,
                $fieldMeta,
                null,
                true
            );
            $item = [];

            if ($this->isMultiple($fieldMeta)) {
                $item[] = $itemArgs;

                return array_merge($args, [
                    'value' => $item,
                ]);
            }

            $item = $itemArgs;

            return array_merge($args, $item);
        }

        $notFormattedValue = $notFormattedValue ?
            $this->getNotFormattedValue($fieldMeta, $notFormattedValue) :
            null;

        if (!$notFormattedValue) {
            // it's a single item, so merge, not assign to the 'value' key
            if (!$this->isMultiple($fieldMeta)) {
                $args = array_merge(
                    $args,
                    $this->getItemTwigArgs($acfViewData, $item, $field, $fieldMeta, null)
                );
            }

            return $args;
        }

        if ($this->isMultiple($fieldMeta)) {
            foreach ($notFormattedValue as $value) {
                $args['value'][] = $this->getItemTwigArgs($acfViewData, $item, $field, $fieldMeta, $value);
            }
        } else {
            // it's a single item, so merge, not assign to the 'value' key
            $args = array_merge(
                $args,
                $this->getItemTwigArgs($acfViewData, $item, $field, $fieldMeta, $notFormattedValue)
            );
        }

        return $args;
    }

    public function isWithFieldWrapper(ViewData $acfViewData, FieldData $field, FieldMeta $fieldMeta): bool
    {
        return $acfViewData->isWithUnnecessaryWrappers ||
            $this->isMultiple($fieldMeta);
    }
}
