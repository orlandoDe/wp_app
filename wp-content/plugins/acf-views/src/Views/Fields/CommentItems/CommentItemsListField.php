<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views\Fields\CommentItems;

use org\wplake\acf_views\Groups\FieldData;
use org\wplake\acf_views\Groups\ItemData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Views\FieldMeta;
use org\wplake\acf_views\Views\Fields\CustomField;
use org\wplake\acf_views\Views\Fields\MarkupField;
use WP_Comment;

defined('ABSPATH') || exit;

class CommentItemsListField extends MarkupField
{
    use CustomField;

    protected function getItemMarkup(
        ViewData $viewData,
        string $fieldId,
        string $itemId,
        ItemData $itemData,
        FieldData $fieldData,
        FieldMeta $fieldMeta,
        int &$tabsNumber,
        bool $isWithFieldWrapper,
        bool $isWithRowWrapper
    ): string {
        $markup = '';

        // opening 'comment' div
        $markup .= sprintf(
            '<div class="%s">',
            esc_html(
                $this->getFieldClass('comment', $viewData, $fieldData, $isWithFieldWrapper, $isWithRowWrapper)
            ),
        );
        $markup .= "\r\n" . str_repeat("\t", ++$tabsNumber);

        // comment author name
        $markup .= sprintf(
            '<div class="%s">',
            esc_html(
                $this->getFieldClass(
                    'comment-author-name',
                    $viewData,
                    $fieldData,
                    $isWithFieldWrapper,
                    $isWithRowWrapper
                )
            )
        );
        $markup .= "\r\n" . str_repeat("\t", ++$tabsNumber);
        $markup .= sprintf('{{ %s.author_name }}', esc_html($itemId));
        $markup .= "\r\n" . str_repeat("\t", --$tabsNumber);
        $markup .= '</div>';

        // comment author email
        $markup .= "\r\n" . str_repeat("\t", $tabsNumber);
        $markup .= sprintf(
            '<div class="%s">',
            esc_html(
                $this->getFieldClass(
                    'comment-content',
                    $viewData,
                    $fieldData,
                    $isWithFieldWrapper,
                    $isWithRowWrapper
                )
            )
        );
        $markup .= "\r\n" . str_repeat("\t", ++$tabsNumber);
        $markup .= '{{ comment_item.content|raw }}';
        $markup .= "\r\n" . str_repeat("\t", --$tabsNumber);
        $markup .= '</div>';

        // closing 'comment' div
        $markup .= "\r\n" . str_repeat("\t", --$tabsNumber);
        $markup .= '</div>';

        return $markup;
    }

    protected function getItemTwigArgs(
        ?WP_Comment $comment,
        FieldData $fieldData,
        bool $isForValidation = false
    ): array {
        if ($isForValidation ||
            !$comment) {
            return [
                'author_name' => 'Name',
                'content' => 'Comment content',
            ];
        }

        return [
            // avoid double encoding in Twig
            'author_name' => html_entity_decode($comment->comment_author, ENT_QUOTES),
            'content' => html_entity_decode($comment->comment_content, ENT_QUOTES),
        ];
    }

    /**
     * @param WP_Comment[] $comments
     * @return array
     */
    protected function groupCommentsByParent(array $comments): array
    {
        $groupedComments = [];

        $getCommentById = function ($commentId) use ($comments): ?WP_Comment {
            // search commend in array by id

            foreach ($comments as $comment) {
                if ($comment->comment_ID !== $commentId) {
                    continue;
                }

                return $comment;
            }

            return null;
        };

        foreach ($comments as $comment) {
            $topComment = $comment->comment_parent ?
                $getCommentById($comment->comment_parent) :
                null;

            while ($topComment) {
                if (!$topComment->comment_parent) {
                    break;
                }

                $topComment = $getCommentById($topComment->comment_parent);
            }

            $commentKey = $topComment->comment_ID ?? $comment->comment_ID;
            $groupedComments[$commentKey] = $groupedComments[$commentKey] ?? [];
            $groupedComments[$commentKey][] = $comment;
        }

        $grouped = [];
        foreach ($groupedComments as $comments) {
            // reverse 'one conversation messages', to reflect the historic order
            $comments = array_reverse($comments);
            $grouped = array_merge($grouped, $comments);
        }

        return $grouped;
    }

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
        $markup = '';

        $markup .= "\r\n" . str_repeat("\t", $tabsNumber);
        $markup .= sprintf("{%% for comment_item in %s.value %%}", esc_html($fieldId));
        $markup .= "\r\n" . str_repeat("\t", ++$tabsNumber);

        $markup .= $this->printItem(
            $acfViewData,
            $fieldId,
            'comment_item',
            $item,
            $fieldData,
            $fieldMeta,
            $tabsNumber,
            $isWithFieldWrapper,
            $isWithRowWrapper
        );

        $markup .= "\r\n" . str_repeat("\t", --$tabsNumber);
        $markup .= "{% endfor %}\r\n";

        return $markup;
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
            'value' => [],
        ];

        if ($isForValidation) {
            return array_merge($args, [
                'value' => [
                    $this->getItemTwigArgs(null, $field, true),
                ],
            ]);
        }

        $post = $this->getPost($notFormattedValue);

        if (!$post) {
            return $args;
        }

        // get all post comments
        $comments = get_comments([
            'post_id' => $post->ID,
            'status' => 'approve',
        ]);

        $comments = $this->groupCommentsByParent($comments);

        foreach ($comments as $comment) {
            $args['value'][] = $this->getItemTwigArgs($comment, $field);
        }

        return $args;
    }

    public function isWithFieldWrapper(ViewData $acfViewData, FieldData $field, FieldMeta $fieldMeta): bool
    {
        return true;
    }
}
