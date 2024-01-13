<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Cards;

use org\wplake\acf_views\Assets\FrontAssets;
use org\wplake\acf_views\Common\InstanceFactory;
use org\wplake\acf_views\Groups\CardData;
use org\wplake\acf_views\Twig;

defined('ABSPATH') || exit;

class CardFactory extends InstanceFactory
{
    protected QueryBuilder $queryBuilder;
    protected CardMarkup $cardMarkup;
    protected Twig $twig;
    protected CardsDataStorage $cardsDataStorage;

    public function __construct(
        FrontAssets $frontAssets,
        QueryBuilder $queryBuilder,
        CardMarkup $cardMarkup,
        Twig $twig,
        CardsDataStorage $cardsDataStorage
    ) {
        parent::__construct($frontAssets);

        $this->queryBuilder = $queryBuilder;
        $this->cardMarkup = $cardMarkup;
        $this->twig = $twig;
        $this->cardsDataStorage = $cardsDataStorage;
    }

    protected function getTwigVariablesForValidation(int $id): array
    {
        return $this->make($this->cardsDataStorage->get($id))->getTwigVariablesForValidation();
    }

    public function make(CardData $acfCardData, string $classes = ''): Card
    {
        return new Card($this->twig, $acfCardData, $this->queryBuilder, $this->cardMarkup, $classes);
    }

    public function makeAndGetHtml(
        CardData $acfCardData,
        int $pageNumber,
        bool $isMinifyMarkup = true,
        bool $isLoadMore = false,
        string $classes = ''
    ): string {
        $acfCard = $this->make($acfCardData, $classes);
        $acfCard->queryPostsAndInsertData($pageNumber, $isMinifyMarkup, $isLoadMore);

        $cardData = $acfCard->getCardData();

        $this->addUsedCptData($cardData);

        return $acfCard->getHTML();
    }
}
