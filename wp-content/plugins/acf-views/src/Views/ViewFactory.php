<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views;

use org\wplake\acf_views\Assets\FrontAssets;
use org\wplake\acf_views\Common\InstanceFactory;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Twig;
use org\wplake\acf_views\Views\Fields\Fields;

defined('ABSPATH') || exit;

class ViewFactory extends InstanceFactory
{
    protected ViewsDataStorage $viewsDataStorage;
    protected ViewMarkup $viewMarkup;
    protected Twig $twig;
    protected Fields $fields;

    public function __construct(
        FrontAssets $frontAssets,
        ViewsDataStorage $viewsDataStorage,
        ViewMarkup $viewMarkup,
        Twig $twig,
        Fields $fields
    ) {
        parent::__construct($frontAssets);

        $this->viewsDataStorage = $viewsDataStorage;
        $this->viewMarkup = $viewMarkup;
        $this->twig = $twig;
        $this->fields = $fields;
    }

    protected function getTwigVariablesForValidation(int $id): array
    {
        return $this->make(new Post(0), $id, 0)->getTwigVariablesForValidation();
    }

    public function make(
        Post $dataPost,
        int $viewId,
        int $pageId,
        ViewData $viewData = null,
        string $classes = ''
    ): View {
        $viewData = $viewData ?: $this->viewsDataStorage->get($viewId);

        $viewMarkup = $this->viewMarkup->getMarkup($viewData, $pageId);

        return new View($this->twig, $viewMarkup, $viewData, $dataPost, $this->fields, $pageId, $classes);
    }

    public function makeAndGetHtml(
        Post $dataPost,
        int $viewId,
        int $pageId,
        bool $isMinifyMarkup = true,
        string $classes = ''
    ): string {
        $acfView = $this->make($dataPost, $viewId, $pageId, null, $classes);
        $acfView->insertFields($isMinifyMarkup);

        $html = $acfView->getHTML();

        // mark as rendered, only if is not empty
        // 'makeAndGetHtml' used as the primary. 'make' used for the specific cases, like validationInstance
        if ($html) {
            $this->addUsedCptData($acfView->getViewData());
        }

        return $html;
    }
}
