<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views\Fields\Post;

use org\wplake\acf_views\Groups\FieldData;
use org\wplake\acf_views\Groups\ItemData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Views\FieldMeta;
use org\wplake\acf_views\Views\Fields\Acf\UserField;
use org\wplake\acf_views\Views\Fields\CustomField;

defined('ABSPATH') || exit;

class PostAuthorField extends UserField
{
    use CustomField;

    protected function isMultiple(FieldMeta $fieldMeta): bool
    {
        return false;
    }

    protected function getPostAuthorId($postId): ?int
    {
        $post = $this->getPost($postId);

        if (!$post) {
            return null;
        }

        $authorId = get_post_field('post_author', $post);
        $author = $authorId ?
            get_user_by('ID', $authorId) :
            null;

        return $author->ID ?? null;
    }

    protected function getItemTwigArgs(
        ViewData $viewData,
        ItemData $item,
        FieldData $field,
        FieldMeta $fieldMeta,
        $notFormattedValue,
        bool $isForValidation = false
    ): array {
        if (!$isForValidation) {
            $notFormattedValue = $this->getPostAuthorId($notFormattedValue);
        }

        return parent::getItemTwigArgs(
            $viewData,
            $item,
            $field,
            $fieldMeta,
            $notFormattedValue,
            $isForValidation
        );
    }
}
