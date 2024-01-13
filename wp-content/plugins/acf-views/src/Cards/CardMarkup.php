<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Cards;

use org\wplake\acf_views\Assets\FrontAssets;
use org\wplake\acf_views\Groups\CardData;
use org\wplake\acf_views\Groups\CardLayoutData;
use org\wplake\acf_views\Views\ViewShortcode;

defined('ABSPATH') || exit;

class CardMarkup
{
    protected QueryBuilder $queryBuilder;
    protected FrontAssets $frontAssets;

    public function __construct(QueryBuilder $queryBuilder, FrontAssets $frontAssets)
    {
        $this->queryBuilder = $queryBuilder;
        $this->frontAssets = $frontAssets;
    }

    protected function getExtraMarkup(CardData $acfCardData): string
    {
        return '';
    }

    protected function getItemsOpeningWrapper(CardData $cardData, int &$tabsNumber, string $class = ''): string
    {
        $classes = $cardData->getBemName() . '__items';
        $classes .= $class ?
            ' ' . $class :
            '';
        $external = $this->frontAssets->getCardItemsWrapperClass($cardData);
        $classes .= $external ?
            ' ' . $external :
            '';

        $markup = str_repeat("\t", ++$tabsNumber);
        $markup .= sprintf('<div class="%s">', esc_html($classes));
        $markup .= "\r\n";


        return $markup;
    }

    protected function printOpeningItemOuters(
        array $itemOuters,
        int &$tabsNumber
    ): string {
        $markup = '';
        foreach ($itemOuters as $outer) {
            $attrs = '';

            foreach ($outer['attrs'] as $attr => $value) {
                $attrs .= sprintf(' %s="%s"', esc_html($attr), esc_html($value));
            }

            $markup .= str_repeat("\t", ++$tabsNumber);
            $markup .= sprintf('<%s%s>', esc_html($outer['tag']), $attrs);
            $markup .= "\r\n";
        }

        return $markup;
    }

    protected function getItemsClosingWrapper(CardData $cardData, int &$tabsNumber): string
    {
        return str_repeat("\t", --$tabsNumber) . '</div>' . "\r\n";
    }

    protected function printClosingItemOuters(array $itemOuters, int &$tabsNumber): string
    {
        $markup = '';
        foreach ($itemOuters as $outer) {
            $markup .= str_repeat("\t", --$tabsNumber);
            $markup .= sprintf('</%s>', esc_html($outer['tag']));
            $markup .= "\r\n";
        }

        return $markup;
    }

    protected function getShortcode(CardData $cardData): string
    {
        $extraAttrs = '';

        $assetAttrs = $this->frontAssets->getCardShortcodeAttrs($cardData);

        foreach ($assetAttrs as $attr => $value) {
            $extraAttrs .= sprintf(' %s="%s"', esc_html($attr), esc_html($value));
        }

        return sprintf(
                '[%s view-id="{{ _card.view_id }}" object-id="{{ post_id }}"%s]',
                ViewShortcode::NAME,
                $extraAttrs
            ) . "\r\n";
    }

    public function getMarkup(
        CardData $cardData,
        bool $isLoadMore = false,
        bool $isIgnoreCustomMarkup = false
    ): string {
        if (!$isIgnoreCustomMarkup &&
            $cardData->customMarkup &&
            !$isLoadMore) {
            $customMarkup = trim($cardData->customMarkup);

            if ($customMarkup) {
                return $customMarkup;
            }
        }

        $markup = '';
        $tabsNumber = 1;
        $itemOuters = !$isLoadMore ?
            $this->frontAssets->getCardItemOuters($cardData) :
            [];


        if (!$isLoadMore) {
            $idClass = 'acf-card' === $cardData->getBemName() ?
                ' ' . sprintf('%s--id--{{ _card.id }}', esc_html($cardData->getBemName())) :
                '';
            $markup .= sprintf(
                '<%s class="{{ _card.classes }}%s">',
                esc_html($cardData->getTagName()),
                esc_html($cardData->getBemName() . $idClass),
            );
            $markup .= "\r\n\r\n";
            $markup .= str_repeat("\t", $tabsNumber);
            $markup .= "{% if _card.post_ids %}\r\n";
            $markup .= $this->getItemsOpeningWrapper($cardData, $tabsNumber);
            $markup .= $this->printOpeningItemOuters($itemOuters, $tabsNumber);
        }

        $markup .= str_repeat("\t", ++$tabsNumber);
        $markup .= "{% for post_id in _card.post_ids %}\r\n";
        $markup .= str_repeat("\t", ++$tabsNumber);
        $markup .= $this->getShortcode($cardData);
        $markup .= str_repeat("\t", --$tabsNumber);
        $markup .= "{% endfor %}\r\n";

        if (!$isLoadMore) {
            $markup .= $this->printClosingItemOuters($itemOuters, $tabsNumber);
            $markup .= $this->getItemsClosingWrapper($cardData, $tabsNumber);

            if ($cardData->noPostsFoundMessage) {
                $markup .= str_repeat("\t", --$tabsNumber);
                $markup .= "{% else %}\r\n";
                $markup .= str_repeat("\t", ++$tabsNumber);
                $markup .= sprintf(
                    '<div class="%s__no-posts-message">{{ _card.no_posts_found_message }}</div>',
                    esc_html($cardData->getBemName())
                );
                $markup .= "\r\n";
            }

            // endif in any case
            $markup .= str_repeat("\t", --$tabsNumber);
            $markup .= "{% endif %}\r\n";

            $markup .= $this->getExtraMarkup($cardData);

            $markup .= "\r\n" . sprintf('</%s>', $cardData->getTagName()) . "\r\n";
        }

        return $markup;
    }

    public function getLayoutCSS(CardData $acfCardData): string
    {
        if (!$acfCardData->isUseLayoutCss) {
            return '';
        }

        $message = __(
            "Manually edit these rules by disabling Layout Rules, otherwise these rules are updated every time you press the 'Update' button",
            'acf-views'
        );

        $css = "/*BEGIN LAYOUT_RULES*/\n";
        $css .= sprintf("/*%s*/\n", $message);

        $rules = [];

        foreach ($acfCardData->layoutRules as $layoutRule) {
            $screen = 0;
            switch ($layoutRule->screen) {
                case CardLayoutData::SCREEN_TABLET:
                    $screen = 576;
                    break;
                case CardLayoutData::SCREEN_DESKTOP:
                    $screen = 992;
                    break;
                case CardLayoutData::SCREEN_LARGE_DESKTOP:
                    $screen = 1400;
                    break;
            }

            $rule = [];

            $rule[] = ' display:grid;';

            switch ($layoutRule->layout) {
                case CardLayoutData::LAYOUT_ROW:
                    $rule[] = ' grid-auto-flow:column;';
                    $rule[] = sprintf(' grid-column-gap:%s;', $layoutRule->horizontalGap);
                    break;
                case CardLayoutData::LAYOUT_COLUMN:
                    // the right way is 1fr, but use "1fr" because CodeMirror doesn't recognize it, "1fr" should be replaced with 1fr on the output
                    $rule[] = ' grid-template-columns:"1fr";';
                    $rule[] = sprintf(' grid-row-gap:%s;', $layoutRule->verticalGap);
                    break;
                case CardLayoutData::LAYOUT_GRID:
                    $rule[] = sprintf(' grid-template-columns:repeat(%s, "1fr");', $layoutRule->amountOfColumns);
                    $rule[] = sprintf(' grid-column-gap:%s;', $layoutRule->horizontalGap);
                    $rule[] = sprintf(' grid-row-gap:%s;', $layoutRule->verticalGap);
                    break;
            }

            $rules[$screen] = $rule;
        }

        // order is important in media rules
        ksort($rules);

        foreach ($rules as $screen => $rule) {
            if ($screen) {
                $css .= sprintf("\n@media screen and (min-width:%spx) {", $screen);
            }

            $css .= "\n#card .acf-card__items {\n";
            $css .= join("\n", $rule);
            $css .= "\n}\n";

            if ($screen) {
                $css .= "}\n";
            }
        }

        $css .= "\n/*END LAYOUT_RULES*/";

        return $css;
    }
}
