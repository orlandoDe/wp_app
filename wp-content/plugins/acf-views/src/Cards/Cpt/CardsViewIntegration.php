<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Cards\Cpt;

use org\wplake\acf_views\Cards\CardsDataStorage;
use org\wplake\acf_views\Common\HooksInterface;
use org\wplake\acf_views\Views\Cpt\ViewsCpt;
use org\wplake\acf_views\Views\ViewsDataStorage;

defined('ABSPATH') || exit;

class CardsViewIntegration implements HooksInterface
{
    protected CardsDataStorage $cardsDataStorage;
    protected ViewsDataStorage $viewsDataStorage;
    protected CardsSaveActions $cardsSaveActions;

    public function __construct(
        CardsDataStorage $cardsDataStorage,
        ViewsDataStorage $viewsDataStorage,
        CardsSaveActions $acfCardsSaveActions
    ) {
        $this->cardsDataStorage = $cardsDataStorage;
        $this->viewsDataStorage = $viewsDataStorage;
        $this->cardsSaveActions = $acfCardsSaveActions;
    }

    public function maybeCreateCardForView(): void
    {
        $screen = get_current_screen();
        $from = (int)($_GET['_from'] ?? 0);
        $fromPost = $from ?
            get_post($from) :
            null;

        $isAddScreen = 'post' === $screen->base &&
            'add' === $screen->action;


        if (CardsCpt::NAME !== $screen->post_type ||
            !$isAddScreen ||
            !$fromPost ||
            ViewsCpt::NAME !== $fromPost->post_type ||
            'publish' !== $fromPost->post_status) {
            return;
        }

        $cardId = wp_insert_post([
            'post_type' => CardsCpt::NAME,
            'post_status' => 'publish',
            'post_title' => $fromPost->post_title,
        ]);

        if (is_wp_error($cardId)) {
            return;
        }

        $acfViewData = $this->viewsDataStorage->get($fromPost->ID);
        $acfCardData = $this->cardsDataStorage->get($cardId);

        $acfCardData->acfViewId = $acfViewData->getUniqueId();
        $acfCardData->postTypes[] = 'post';

        $this->cardsSaveActions->performSaveActions($cardId);

        wp_redirect(get_edit_post_link($cardId, 'redirect'));
        exit;
    }

    public function setHooks(bool $isAdmin): void
    {
        if (!$isAdmin) {
            return;
        }

        add_action('current_screen', [$this, 'maybeCreateCardForView']);
    }
}