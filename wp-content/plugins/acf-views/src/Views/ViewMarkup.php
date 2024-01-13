<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views;

use org\wplake\acf_views\Groups\ItemData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Html;
use org\wplake\acf_views\Views\Fields\Fields;

defined('ABSPATH') || exit;

class ViewMarkup
{
    // cache
    protected array $markups;
    protected Html $html;
    protected Fields $fields;

    public function __construct(Html $html, Fields $fields)
    {
        $this->html = $html;
        $this->fields = $fields;
        $this->markups = [];
    }

    protected function getRowMarkup(ViewData $acfViewData, FieldMeta $fieldMeta, ItemData $item): string
    {
        if (in_array($fieldMeta->getType(), ['repeater', 'group',], true) &&
            !$this->fields->isFieldInstancePresent($fieldMeta->getType())) {
            return '';
        }

        $fieldId = $item->field->getTwigFieldId();
        $orCondition = $item->field->isVisibleWhenEmpty || 'true_false' === $fieldMeta->getType() ?
            ' or true' :
            '';
        $rowTabsNumber = 2;

        $rowType = 'row';

        if (in_array($fieldMeta->getType(), ['repeater', 'group',], true)) {
            $rowType = $fieldMeta->getType();
        }

        return sprintf("\r\n\t{%% if %s.value%s %%}\r\n", esc_html($fieldId), $orCondition) .
            $this->fields->getRowMarkup(
                $rowType,
                '',
                $acfViewData,
                $item,
                $item->field,
                $fieldMeta,
                $rowTabsNumber,
                $fieldId
            ) .
            "\t{% endif %}\r\n\r\n";
    }

    protected function getMarkupFromCache(ViewData $view, bool $isSkipCache): string
    {
        if (key_exists($view->getSource(), $this->markups) &&
            !$isSkipCache) {
            return $this->markups[$view->getSource()];
        }

        $content = '';
        foreach ($view->items as $item) {
            $content .= $this->getRowMarkup(
                $view,
                $item->field->getFieldMeta(),
                $item
            );
        }

        return $this->html->view($content, $view->getBemName(), $view->getTagName());
    }

    public function getMarkup(
        ViewData $view,
        int $pageId,
        string $viewMarkup = '',
        bool $isSkipCache = false,
        bool $isIgnoreCustomMarkup = false
    ): string {
        $viewMarkup = ($viewMarkup ||
            $isIgnoreCustomMarkup) ?
            $viewMarkup :
            trim($view->customMarkup);

        $viewMarkup = $viewMarkup ?: $this->getMarkupFromCache($view, $isSkipCache);
        $this->markups[$view->getSource()] = $viewMarkup;

        return $viewMarkup;
    }
}
