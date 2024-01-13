<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views\Fields\Acf;

use org\wplake\acf_views\Assets\FrontAssets;
use org\wplake\acf_views\Groups\FieldData;
use org\wplake\acf_views\Groups\ItemData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Views\FieldMeta;
use org\wplake\acf_views\Views\Fields\ListField;

defined('ABSPATH') || exit;

class UserField extends ListField
{
    const LOOP_ITEM_NAME = 'user_item';

    protected LinkField $linkField;

    public function __construct(FrontAssets $frontAssets, LinkField $linkField)
    {
        parent::__construct($frontAssets);

        $this->linkField = $linkField;
    }

    protected function getUserInfo(int $id): array
    {
        $postInfo = [
            'url' => '',
            'title' => '',
        ];

        $user = get_user_by('ID', $id);

        if (!$user) {
            return $postInfo;
        }

        return [
            'url' => (string)get_author_posts_url($user->ID),
            'title' => $user->display_name,
        ];
    }

    protected function isMultiple(FieldMeta $fieldMeta): bool
    {
        return $fieldMeta->isMultiple();
    }

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
        return $this->linkField->getMarkup(
            $viewData,
            $fieldMeta->isMultiple() ?
                'user_item' :
                $fieldId,
            $itemData,
            $fieldData,
            $fieldMeta,
            $tabsNumber,
            $isWithFieldWrapper,
            $isWithRowWrapper
        );
    }

    protected function getItemTwigArgs(
        ViewData $viewData,
        ItemData $item,
        FieldData $field,
        FieldMeta $fieldMeta,
        $notFormattedValue,
        bool $isForValidation = false
    ): array {
        $linkArgs = $this->getUserInfo((int)$notFormattedValue);

        return $this->linkField->getTwigArgs(
            $viewData,
            $item,
            $field,
            $fieldMeta,
            $linkArgs,
            $linkArgs,
            $isForValidation
        );
    }
}
