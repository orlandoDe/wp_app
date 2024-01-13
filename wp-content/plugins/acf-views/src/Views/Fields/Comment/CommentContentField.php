<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views\Fields\Comment;

use org\wplake\acf_views\Groups\FieldData;
use org\wplake\acf_views\Groups\ItemData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Views\FieldMeta;
use org\wplake\acf_views\Views\Fields\CustomField;
use org\wplake\acf_views\Views\Fields\MarkupField;

defined('ABSPATH') || exit;

class CommentContentField extends MarkupField
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
            "{{ %s.value|raw }}",
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
            'value' => $field->defaultValue,
        ];

        if ($isForValidation) {
            return array_merge($args, [
                'value' => 'content',
            ]);
        }

        $comment = $this->getComment($notFormattedValue);

        if (!$comment) {
            return $args;
        }

        // 1. avoid double encoding
        // 2. do not use get_comment_text() because it adds 'In reply to' text
        $args['value'] = html_entity_decode($comment->comment_content, ENT_QUOTES) ?: $args['value'];

        return $args;
    }

    public function isWithFieldWrapper(ViewData $acfViewData, FieldData $field, FieldMeta $fieldMeta): bool
    {
        return true;
    }
}
