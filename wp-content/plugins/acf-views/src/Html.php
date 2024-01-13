<?php

declare(strict_types=1);

namespace org\wplake\acf_views;

defined('ABSPATH') || exit;

class Html
{
    protected function render(string $name, array $args = []): string
    {
        $pathToView = __DIR__ . '/html/' . $name . '.php';

        if (!file_exists($pathToView)) {
            return '';
        }

        $view = $args;
        ob_start();
        include $pathToView;

        return ob_get_clean();
    }

    public function postboxShortcodes(
        string $viewUniqueId,
        bool $isShort,
        string $shortcodeName,
        string $entryName,
        bool $isSingle
    ): string {
        $description = '';
        $idArgument = '';

        if ($isSingle) {
            $description .= __(
                'displays the card, posts will be queried according to the filters and displayed according to the selected View.',
                'acf-views'
            );
            $description .= ' ';
            $description .= sprintf(
                '<a target="_blank" href="https://docs.acfviews.com/shortcode-attributes/card-shortcode">%s</a>',
                __('Read more', 'acf-views')
            );
            $idArgument = 'card-id';
        } else {
            $description .= __(
                'displays the view, chosen ACF fields should be filled at the same object where the shortcode is pasted (post/page).',
                'acf-views'
            );
            $description .= ' ';
            $description .= sprintf(
                '<a target="_blank" href="https://docs.acfviews.com/shortcode-attributes/view-shortcode">%s</a>',
                __('Read more', 'acf-views')
            );
            $idArgument = 'view-id';
        }

        return $this->render('postbox/shortcodes', [
            'isShort' => $isShort,
            'idArgument' => $idArgument,
            'shortcodeName' => $shortcodeName,
            'entryName' => $entryName,
            'viewId' => $viewUniqueId,
            'isSingle' => $isSingle,
            'description' => $description,
            'typeName' => $isSingle ? 'Card' : 'View',
        ]);
    }

    public function postboxReview(): string
    {
        return $this->render('postbox/review');
    }

    public function postboxSupport(): string
    {
        return $this->render('postbox/support');
    }

    public function view(string $content, string $bemName, string $tag): string
    {
        return $this->render('view/view', [
            'content' => $content,
            'bemName' => $bemName,
            'tag' => $tag,
        ]);
    }

    // $tabs : [ [isActive, url, label,] ]
    public function dashboardHeader(string $name, string $version, array $tabs): string
    {
        return $this->render('dashboard/header', [
            'name' => $name,
            'version' => $version,
            'tabs' => $tabs,
        ]);
    }

    public function dashboardImport(bool $isHasDemoObjects, string $formNonce, string $formMessage): string
    {
        return $this->render('dashboard/import', [
            'isHasDemoObjects' => $isHasDemoObjects,
            'formNonce' => $formNonce,
            'formMessage' => $formMessage,
        ]);
    }

    public function proBanner(string $link, string $image): string
    {
        return $this->render('postbox/pro-banner', [
            'link' => $link,
            'image' => $image,
        ]);
    }

    public function getProBanner(string $link, string $image): array
    {
        return [
            'link' => $link,
            'image' => $image,
        ];
    }
}
