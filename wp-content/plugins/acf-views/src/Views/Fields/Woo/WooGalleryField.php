<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views\Fields\Woo;

use org\wplake\acf_views\Groups\FieldData;
use org\wplake\acf_views\Groups\ItemData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Views\FieldMeta;
use org\wplake\acf_views\Views\Fields\Acf\GalleryField;
use org\wplake\acf_views\Views\Fields\CustomField;

defined('ABSPATH') || exit;

class WooGalleryField extends GalleryField
{
    use CustomField;

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
            $value = [];
            $value[] = $this->imageField->getTwigArgs(
                $acfViewData,
                $item,
                $field,
                $fieldMeta,
                $notFormattedValue,
                $formattedValue,
                true
            );

            return array_merge($args, [
                'value' => $value,
            ]);
        }

        $product = $this->getProduct($notFormattedValue);

        if (!$product) {
            return $args;
        }

        $imageIds = $product->get_gallery_image_ids();

        foreach ($imageIds as $imageId) {
            $args['value'][] = $this->imageField->getTwigArgs(
                $acfViewData,
                $item,
                $field,
                $fieldMeta,
                $imageId,
                $imageId
            );
        }

        return $args;
    }
}
