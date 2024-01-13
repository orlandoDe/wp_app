<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Cards;

use org\wplake\acf_views\Common\CptDataStorage;
use org\wplake\acf_views\Groups\CardData;

defined('ABSPATH') || exit;

class CardsDataStorage extends CptDataStorage
{
    public function __construct(CardData $cardData)
    {
        parent::__construct($cardData);
    }

    // override just for settings the return type
    public function get(int $postId): CardData
    {
        return parent::get($postId);
    }
}
