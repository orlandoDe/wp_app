<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Cards;

use org\wplake\acf_views\Cards\Cpt\CardsCpt;
use org\wplake\acf_views\Common\Shortcode;
use org\wplake\acf_views\Settings;

defined('ABSPATH') || exit;

class CardShortcode extends Shortcode
{
    const NAME = CardsCpt::NAME;

    protected CardFactory $cardFactory;

    public function __construct(Settings $settings, CardsDataStorage $cardsDataStorage, CardFactory $cardFactory)
    {
        parent::__construct($settings, $cardsDataStorage);

        $this->cardFactory = $cardFactory;
    }

    public function render($attrs): string
    {
        $attrs = $attrs ?
            (array)$attrs :
            [];

        if (!$this->isShortcodeAvailableForUser(wp_get_current_user()->roles, $attrs)) {
            return '';
        }

        $cardId = (string)($attrs['card-id'] ?? 0);
        $cardId = $this->cptDataStorage->getPostIdByUniqueId($cardId, CardsCpt::NAME);

        if (!$cardId) {
            return $this->getErrorMarkup(
                self::NAME,
                $attrs,
                __('card-id attribute is missing or wrong', 'acf-views')
            );
        }

        $classes = (string)($attrs['class'] ?? '');

        $acfCardData = $this->cptDataStorage->get($cardId);

        $html = $this->cardFactory->makeAndGetHtml($acfCardData, 1, true, false, $classes);

        return $this->maybeAddQuickLink($html, $cardId);
    }
}
