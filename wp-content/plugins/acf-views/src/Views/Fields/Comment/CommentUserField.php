<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views\Fields\Comment;

use org\wplake\acf_views\Groups\FieldData;
use org\wplake\acf_views\Groups\ItemData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Views\FieldMeta;
use org\wplake\acf_views\Views\Fields\CustomField;
use org\wplake\acf_views\Views\Fields\MarkupField;
use WP_User;

defined('ABSPATH') || exit;

class CommentUserField extends MarkupField
{
    use CustomField;

    public function getMarkup(
        ViewData $acfViewData,
        string $fieldId,
        ItemData $item,
        FieldData $fieldData,
        FieldMeta $fieldMeta,
        int &$tabsNumber,
        bool $isWithFieldWrapper,
        bool $isWithRowWrapper
    ): string {
        return sprintf(
            "{{ %s.value }}",
            esc_html($fieldId),
        );
    }

    public function getTwigArgs(
        ViewData $acfViewData,
        ItemData $item,
        FieldData $field,
        FieldMeta $fieldMeta,
        $notFormattedValue,
        $formattedValue,
        bool $isForValidation = false
    ): array {
        $args = [
            'value' => '',
        ];

        if ($isForValidation) {
            return array_merge($args, [
                'value' => '1',
            ]);
        }

        $comment = $this->getComment($notFormattedValue);

        if (!$comment) {
            return $args;
        }

        $user = get_user_by('email', $comment->comment_author_email);

        if (!($user instanceof WP_User)) {
            return $args;
        }

        $args['value'] = $user->ID;

        return $args;
    }

    public function isWithFieldWrapper(ViewData $acfViewData, FieldData $field, FieldMeta $fieldMeta): bool
    {
        return true;
    }
}
