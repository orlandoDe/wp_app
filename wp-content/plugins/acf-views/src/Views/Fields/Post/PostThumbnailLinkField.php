<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views\Fields\Post;

use org\wplake\acf_views\Assets\FrontAssets;
use org\wplake\acf_views\Groups\FieldData;
use org\wplake\acf_views\Groups\ItemData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Views\FieldMeta;
use org\wplake\acf_views\Views\Fields\Acf\ImageField;
use org\wplake\acf_views\Views\Fields\CustomField;
use org\wplake\acf_views\Views\Fields\MarkupField;

defined('ABSPATH') || exit;

class PostThumbnailLinkField extends MarkupField
{
    use CustomField;

    protected ImageField $imageField;

    public function __construct(FrontAssets $frontAssets, ImageField $imageField)
    {
        parent::__construct($frontAssets);

        $this->imageField = $imageField;
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
        $markup = sprintf(

            '<a target="{{ %s.target }}" class="%s" href="{{ %1$s.href }}">',
            esc_html($fieldId),
            esc_html(
                $this->getFieldClass('link', $acfViewData, $fieldData, $isWithFieldWrapper, $isWithRowWrapper)
            )
        );
        $markup .= "\r\n" . str_repeat("\t", ++$tabsNumber);
        $markup .= $this->imageField->getMarkup(
            $acfViewData,
            $fieldId,
            $item,
            $fieldData,
            $fieldMeta,
            $tabsNumber,
            true,
            $isWithRowWrapper
        );
        $markup .= "\r\n" . str_repeat("\t", --$tabsNumber);
        $markup .= '</a>';

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
            'target' => '_self',
            'href' => '',
        ];

        if ($isForValidation) {
            $linkArgs = $this->imageField->getTwigArgs(
                $acfViewData,
                $item,
                $field,
                $fieldMeta,
                $notFormattedValue,
                $formattedValue,
                true
            );

            return array_merge($args, $linkArgs);
        }

        $post = $this->getPost($notFormattedValue);

        if (!$post) {
            return array_merge(
                $args,
                $this->imageField->getTwigArgs($acfViewData, $item, $field, $fieldMeta, 0, 0)
            );
        }

        $args['href'] = (string)get_the_permalink($post);
        $imageId = (int)get_post_thumbnail_id($post);

        return array_merge(
            $args,
            $this->imageField->getTwigArgs($acfViewData, $item, $field, $fieldMeta, $imageId, $imageId)
        );
    }

    public function isWithFieldWrapper(ViewData $acfViewData, FieldData $field, FieldMeta $fieldMeta): bool
    {
        return $acfViewData->isWithUnnecessaryWrappers;
    }
}
