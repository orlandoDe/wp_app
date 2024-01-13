<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views\Fields\Post;

use org\wplake\acf_views\Groups\FieldData;
use org\wplake\acf_views\Groups\ItemData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Views\FieldMeta;
use org\wplake\acf_views\Views\Fields\CustomField;
use org\wplake\acf_views\Views\Fields\MarkupField;

defined('ABSPATH') || exit;

class PostExcerptField extends MarkupField
{
    use CustomField;

    // custom modification to avoid issues (see body for the details)
    protected function getExcerpt($text = '', $post = null)
    {
        $raw_excerpt = $text;

        if ('' === trim($text)) {
            $post = get_post($post);
            $text = get_the_content('', false, $post);

            $text = strip_shortcodes($text);
            $text = excerpt_remove_blocks($text);
            $text = excerpt_remove_footnotes($text);

            /*
             * Temporarily unhook wp_filter_content_tags() since any tags
             * within the excerpt are stripped out. Modifying the tags here
             * is wasteful and can lead to bugs in the image counting logic.
             */
            //$filter_removed = remove_filter( 'the_content', 'wp_filter_content_tags' );

            // DO NOT APPLY THIS, otherwise it causes issues (IDK why, maybe because 'the_content' is called within the content of another post)
            /** This filter is documented in wp-includes/post-template.php */
            //$text = apply_filters( 'the_content', $text );

            $text = str_replace(']]>', ']]&gt;', $text);

            /**
             * Only restore the filter callback if it was removed above. The logic
             * to unhook and restore only applies on the default priority of 10,
             * which is generally used for the filter callback in WordPress core.
             */
            //if ( $filter_removed ) {
            //add_filter( 'the_content', 'wp_filter_content_tags' );
            //}

            /* translators: Maximum number of words used in a post excerpt. */
            $excerpt_length = (int)_x('55', 'excerpt_length');

            /**
             * Filters the maximum number of words in a post excerpt.
             *
             * @param int $number The maximum number of words. Default 55.
             * @since 2.7.0
             *
             */
            $excerpt_length = (int)apply_filters('excerpt_length', $excerpt_length);

            /**
             * Filters the string in the "more" link displayed after a trimmed excerpt.
             *
             * @param string $more_string The string shown within the more link.
             * @since 2.9.0
             *
             */
            $excerpt_more = apply_filters('excerpt_more', ' ' . '[&hellip;]');
            $text = wp_trim_words($text, $excerpt_length, $excerpt_more);
        }

        /**
         * Filters the trimmed excerpt string.
         *
         * @param string $text The trimmed text.
         * @param string $raw_excerpt The text prior to trimming.
         * @since 2.8.0
         *
         */
        return apply_filters('wp_trim_excerpt', $text, $raw_excerpt);
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
            'value' => $field->defaultValue,
        ];

        if ($isForValidation) {
            return array_merge($args, [
                'value' => 'excerpt',
            ]);
        }

        $post = $this->getPost($notFormattedValue);

        // it's important to check if the post type supports the excerpt (overall)
        // do not use has_excerpt(), because it checks only the 'excerpt' post field, while user may want to see the wp generated excerpt (first sentences)
        if (!$post ||
            !post_type_supports($post->post_type, 'excerpt')) {
            return $args;
        }

        // custom modification to avoid issues (see body for the details)
        $excerpt = $this->getExcerpt($post->post_excerpt, $post);
        // to avoid double escaping
        $excerpt = html_entity_decode($excerpt, ENT_QUOTES);


        $args['value'] = $excerpt ?: $args['value'];

        return $args;
    }

    public function isWithFieldWrapper(ViewData $acfViewData, FieldData $field, FieldMeta $fieldMeta): bool
    {
        return true;
    }
}
