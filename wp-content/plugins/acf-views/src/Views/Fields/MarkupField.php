<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views\Fields;

use org\wplake\acf_views\Assets\FrontAssets;
use org\wplake\acf_views\Groups\FieldData;
use org\wplake\acf_views\Groups\ItemData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Views\FieldMeta;

defined('ABSPATH') || exit;

abstract class MarkupField
{
    protected FrontAssets $frontAssets;

    public function __construct(FrontAssets $frontAssets)
    {
        $this->frontAssets = $frontAssets;
    }

    abstract public function getMarkup(
        ViewData $acfViewData,
        string $fieldId,
        ItemData $item,
        FieldData $fieldData,
        FieldMeta $fieldMeta,
        int &$tabsNumber,
        bool $isWithFieldWrapper,
        bool $isWithRowWrapper
    ): string;

    abstract public function getTwigArgs(
        ViewData $acfViewData,
        ItemData $item,
        FieldData $field,
        FieldMeta $fieldMeta,
        $notFormattedValue,
        $formattedValue,
        bool $isForValidation = false
    ): array;

    abstract public function isWithFieldWrapper(ViewData $acfViewData, FieldData $field, FieldMeta $fieldMeta): bool;

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
        return '';
    }

    protected function printOpeningItemOuters(
        array $itemOuters,
        int &$tabsNumber,
        ViewData $viewData,
        FieldData $fieldData
    ): string {
        $markup = '';
        foreach ($itemOuters as $outer) {
            $attrs = '';
            $attrClass = $outer['attrs']['class'] ?? '';
            $class = $attrClass ?: $this->getItemClass('item', $viewData, $fieldData);
            // trick to add class as the first key
            $outer['attrs'] = array_merge(['class' => $class], $outer['attrs']);

            foreach ($outer['attrs'] as $attr => $value) {
                $attrs .= sprintf(' %s="%s"', esc_html($attr), esc_html($value));
            }

            $markup .= sprintf('<%s%s>', esc_html($outer['tag']), $attrs);
            $markup .= "\r\n" . str_repeat("\t", ++$tabsNumber);
        }

        return $markup;
    }

    protected function printClosingItemOuters(array $itemOuters, int &$tabsNumber): string
    {
        $markup = '';
        foreach ($itemOuters as $outer) {
            $markup .= "\r\n" . str_repeat("\t", --$tabsNumber);
            $markup .= sprintf('</%s>', esc_html($outer['tag']));
        }

        return $markup;
    }

    protected function printItem(
        ViewData $viewData,
        string $fieldId,
        string $itemId,
        ItemData $item,
        FieldData $field,
        FieldMeta $fieldMeta,
        int &$tabsNumber,
        bool $isWithFieldWrapper,
        bool $isWithRowWrapper
    ): string {
        $itemOuters = $this->frontAssets->getItemOuters($viewData, $field, $fieldId, $itemId);
        $markup = $this->printOpeningItemOuters($itemOuters, $tabsNumber, $viewData, $field);
        $markup .= $this->getItemMarkup(
            $viewData,
            $fieldId,
            $itemId,
            $item,
            $field,
            $fieldMeta,
            $tabsNumber,
            $isWithFieldWrapper,
            $isWithRowWrapper
        );
        $markup .= $this->printClosingItemOuters($itemOuters, $tabsNumber);

        return $markup;
    }

    protected function getFieldClass(
        string $suffix,
        ViewData $acfViewData,
        FieldData $field,
        bool $isWithFieldWrapper,
        bool $isWithRowWrapper
    ): string {
        $classes = [];
        $isFirstTag = !$isWithRowWrapper &&
            !$isWithFieldWrapper;

        if ($isFirstTag) {
            $classes[] = $acfViewData->getBemName() . '__' . $field->id;

            if (!$acfViewData->isWithCommonClasses) {
                return implode(' ', $classes);
            }
        }

        $classes[] = $this->getItemClass($suffix, $acfViewData, $field);

        if (!$isWithFieldWrapper &&
            $acfViewData->isWithCommonClasses) {
            $classes[] = $acfViewData->getBemName() . '__field';
        }

        return implode(' ', $classes);
    }

    // method is kept for backward compatibility, use the View->getItemClass() instead
    protected function getItemClass(string $suffix, ViewData $acfViewData, FieldData $field): string
    {
        return $acfViewData->getItemClass($suffix, $field);
    }

    public function isWithRowWrapper(ViewData $acfViewData, FieldData $field, FieldMeta $fieldMeta): bool
    {
        return $acfViewData->isWithUnnecessaryWrappers ||
            $field->label;
    }

}
