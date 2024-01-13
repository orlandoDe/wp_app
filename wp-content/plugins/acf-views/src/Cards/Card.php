<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Cards;

use org\wplake\acf_views\Common\Instance;
use org\wplake\acf_views\Groups\CardData;
use org\wplake\acf_views\Twig;

defined('ABSPATH') || exit;

class Card extends Instance
{
    /**
     * @var CardData
     */
    protected $cptData;
    protected QueryBuilder $queryBuilder;
    protected CardMarkup $cardMarkup;
    protected int $pagesAmount;
    protected array $postIds;

    public function __construct(
        Twig $twig,
        CardData $cptData,
        QueryBuilder $queryBuilder,
        CardMarkup $cardMarkup,
        string $classes = ''
    ) {
        parent::__construct($twig, $cptData, '', $classes);

        $this->queryBuilder = $queryBuilder;
        $this->cardMarkup = $cardMarkup;
        $this->pagesAmount = 0;
        $this->postIds = [];
    }

    protected function setTwigVariables(bool $isForValidation = false): void
    {
        $this->twigVariables = [
            '_card' => [
                'id' => $this->cptData->getMarkupId(),
                'view_id' => $this->cptData->acfViewId,
                'no_posts_found_message' => $this->cptData->getNoPostsFoundMessageTranslation(),
                'post_ids' => $this->postIds,
                'classes' => $this->getClasses(),
            ],
        ];
    }

    protected function renderTwig(bool $isForValidation = false): void
    {
        $this->html = $this->twig->render(
            $this->cptData->getUniqueId(),
            $this->html,
            $this->twigVariables,
            $isForValidation
        );
        // render the shortcodes
        $this->html = do_shortcode($this->html);
    }

    public function queryPostsAndInsertData(
        int $pageNumber,
        bool $isMinifyMarkup = true,
        bool $isLoadMore = false
    ): void {
        if ($isMinifyMarkup) {
            // remove special symbols that used in the markup for a preview
            // exactly here, before the fields are inserted, to avoid affecting them
            $this->html = str_replace(["\t", "\n", "\r"], '', $this->html);
        }

        $postsData = $this->queryBuilder->getPostsData($this->cptData, $pageNumber);
        $this->pagesAmount = $postsData['pagesAmount'];
        $this->postIds = $postsData['postIds'];

        $this->html = $this->cardMarkup->getMarkup($this->cptData, $isLoadMore);

        $this->setTwigVariables();
        $this->renderTwig();
    }

    public function getHTML(): string
    {
        return $this->html;
    }

    public function getCardData(): CardData
    {
        return $this->cptData;
    }

    public function getMarkupValidationError(): string
    {
        $this->html = $this->cardMarkup->getMarkup($this->cptData);

        return parent::getMarkupValidationError();
    }
}