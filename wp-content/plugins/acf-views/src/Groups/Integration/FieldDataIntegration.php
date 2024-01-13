<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Groups\Integration;

use org\wplake\acf_views\Groups\FieldData;
use org\wplake\acf_views\Groups\ItemData;
use org\wplake\acf_views\Groups\RepeaterFieldData;
use org\wplake\acf_views\Views\Fields\Comment\CommentFields;
use org\wplake\acf_views\Views\Fields\CommentItems\CommentItemFields;
use org\wplake\acf_views\Views\Fields\Menu\MenuFields;
use org\wplake\acf_views\Views\Fields\MenuItem\MenuItemFields;
use org\wplake\acf_views\Views\Fields\Post\PostFields;
use org\wplake\acf_views\Views\Fields\TaxonomyTerms\TaxonomyTermFields;
use org\wplake\acf_views\Views\Fields\Term\TermFields;
use org\wplake\acf_views\Views\Fields\User\UserFields;
use org\wplake\acf_views\Views\Fields\Woo\WooFields;

defined('ABSPATH') || exit;

class FieldDataIntegration extends AcfIntegration
{
    protected function setConditionalRulesForField(
        array $field,
        string $targetField,
        array $notEqualValues
    ): array {
        // multiple calls of this method are allowed
        if (!isset($field['conditional_logic']) ||
            !is_array($field['conditional_logic'])) {
            $field['conditional_logic'] = [];
        }

        foreach ($notEqualValues as $notEqualValue) {
            // using exactly AND rule (so all rules in one array) and '!=' comparison,
            // otherwise if there are no such fields the field will be visible
            $field['conditional_logic'][] = [
                'field' => $targetField,
                'operator' => '!=',
                'value' => $notEqualValue,
            ];
        }

        return $field;
    }

    protected function addConditionalFilter(
        string $fieldName,
        array $notFieldTypes,
        bool $isSubField = false,
        array $includeFields = []
    ): void {
        $acfFieldName = !$isSubField ?
            FieldData::getAcfFieldName($fieldName) :
            RepeaterFieldData::getAcfFieldName($fieldName);
        $acfKey = !$isSubField ?
            FieldData::getAcfFieldName(FieldData::FIELD_KEY) :
            RepeaterFieldData::getAcfFieldName(RepeaterFieldData::FIELD_KEY);

        add_filter(
            'acf/load_field/name=' . $acfFieldName,
            function (array $field) use ($acfKey, $notFieldTypes, $includeFields, $isSubField) {
                // using exactly the negative (excludeTypes) filter,
                // otherwise if there are no such fields the field will be visible
                $notRightFields = !$isSubField ?
                    $this->getFieldChoices(true, $notFieldTypes) :
                    $this->getSubFieldChoices($notFieldTypes);

                foreach ($includeFields as $includeField) {
                    unset($notRightFields[$includeField]);
                }

                return $this->setConditionalRulesForField(
                    $field,
                    $acfKey,
                    array_keys($notRightFields)
                );
            }
        );
    }

    protected function getSubFieldChoices(array $excludeTypes = []): array
    {
        $subFieldChoices = [
            '' => 'Select',
        ];

        $supportedFieldTypes = $this->getFieldTypes();

        $groups = $this->getGroups();
        foreach ($groups as $group) {
            $fields = acf_get_fields($group);

            foreach ($fields as $groupField) {
                $subFields = (array)($groupField['sub_fields'] ?? []);

                if (!in_array($groupField['type'], ['repeater', 'group',], true) ||
                    !$subFields) {
                    continue;
                }

                foreach ($subFields as $subField) {
                    // inner complex types, like repeater or group aren't allowed
                    if (!in_array($subField['type'], $supportedFieldTypes, true) ||
                        in_array($subField['type'], ['repeater', 'group',], true) ||
                        ($excludeTypes && in_array($subField['type'], $excludeTypes, true))) {
                        continue;
                    }

                    $fullFieldId = FieldData::createKey(
                        $group['key'],
                        $groupField['key'],
                        $subField['key']
                    );
                    $subFieldChoices[$fullFieldId] = $subField['label'] . ' (' . $subField['type'] . ')';
                }
            }
        }

        return $subFieldChoices;
    }

    protected function getExtraFieldChoices(array $excludeTypes): array
    {
        $fieldChoices = [];

        $postFields = [
            PostFields::FIELD_TITLE => [
                FieldData::createKey(PostFields::GROUP_NAME, PostFields::FIELD_TITLE),
                __('Title', 'acf-views')
            ],
            PostFields::FIELD_TITLE_LINK => [
                FieldData::createKey(PostFields::GROUP_NAME, PostFields::FIELD_TITLE_LINK),
                __('Title with link', 'acf-views')
            ],
            PostFields::FIELD_CONTENT => [
                FieldData::createKey(PostFields::GROUP_NAME, PostFields::FIELD_CONTENT),
                __('Content', 'acf-views')
            ],
            PostFields::FIELD_EXCERPT => [
                FieldData::createKey(PostFields::GROUP_NAME, PostFields::FIELD_EXCERPT),
                __('Excerpt', 'acf-views')
            ],
            PostFields::FIELD_THUMBNAIL => [
                FieldData::createKey(PostFields::GROUP_NAME, PostFields::FIELD_THUMBNAIL),
                __('Featured Image', 'acf-views')
            ],
            PostFields::FIELD_THUMBNAIL_LINK => [
                FieldData::createKey(PostFields::GROUP_NAME, PostFields::FIELD_THUMBNAIL_LINK),
                __('Featured Image with link', 'acf-views')
            ],
            PostFields::FIELD_AUTHOR => [
                FieldData::createKey(PostFields::GROUP_NAME, PostFields::FIELD_AUTHOR),
                __('Author', 'acf-views')
            ],
            PostFields::FIELD_DATE => [
                FieldData::createKey(PostFields::GROUP_NAME, PostFields::FIELD_DATE),
                __('Published date', 'acf-views')
            ],
            PostFields::FIELD_MODIFIED => [
                FieldData::createKey(PostFields::GROUP_NAME, PostFields::FIELD_MODIFIED),
                __('Modified date', 'acf-views')
            ],
        ];

        $userFields = [
            UserFields::FIELD_FIRST_NAME => [
                FieldData::createKey(UserFields::GROUP_NAME, UserFields::FIELD_FIRST_NAME),
                __('First Name', 'acf-views')
            ],
            UserFields::FIELD_LAST_NAME => [
                FieldData::createKey(UserFields::GROUP_NAME, UserFields::FIELD_LAST_NAME),
                __('Last Name', 'acf-views')
            ],
            UserFields::FIELD_DISPLAY_NAME => [
                FieldData::createKey(UserFields::GROUP_NAME, UserFields::FIELD_DISPLAY_NAME),
                __('Display Name', 'acf-views')
            ],
            UserFields::FIELD_BIO => [
                FieldData::createKey(UserFields::GROUP_NAME, UserFields::FIELD_BIO),
                __('Bio', 'acf-views')
            ],
            UserFields::FIELD_EMAIL => [
                FieldData::createKey(UserFields::GROUP_NAME, UserFields::FIELD_EMAIL),
                __('Email', 'acf-views')
            ],
            UserFields::FIELD_AUTHOR_LINK => [
                FieldData::createKey(UserFields::GROUP_NAME, UserFields::FIELD_AUTHOR_LINK),
                __('Author link', 'acf-views')
            ],
            UserFields::FIELD_WEBSITE => [
                FieldData::createKey(UserFields::GROUP_NAME, UserFields::FIELD_WEBSITE),
                __('Website', 'acf-views')
            ],
        ];

        $commentItemFields = [
            CommentItemFields::FIELD_LIST => [
                FieldData::createKey(CommentItemFields::GROUP_NAME, CommentItemFields::FIELD_LIST),
                __('List', 'acf-views')
            ],
        ];

        $commentFields = [
            CommentFields::FIELD_AUTHOR_EMAIL => [
                FieldData::createKey(CommentFields::GROUP_NAME, CommentFields::FIELD_AUTHOR_EMAIL),
                __('Author Email', 'acf-views')
            ],
            CommentFields::FIELD_AUTHOR_NAME => [
                FieldData::createKey(CommentFields::GROUP_NAME, CommentFields::FIELD_AUTHOR_NAME),
                __('Author Name', 'acf-views')
            ],
            CommentFields::FIELD_AUTHOR_NAME_LINK => [
                FieldData::createKey(CommentFields::GROUP_NAME, CommentFields::FIELD_AUTHOR_NAME_LINK),
                __('Author Name link', 'acf-views')
            ],
            CommentFields::FIELD_CONTENT => [
                FieldData::createKey(CommentFields::GROUP_NAME, CommentFields::FIELD_CONTENT),
                __('Content', 'acf-views')
            ],
            CommentFields::FIELD_DATE => [
                FieldData::createKey(CommentFields::GROUP_NAME, CommentFields::FIELD_DATE),
                __('Date', 'acf-views')
            ],
            CommentFields::FIELD_STATUS => [
                FieldData::createKey(CommentFields::GROUP_NAME, CommentFields::FIELD_STATUS),
                __('Status', 'acf-views')
            ],
            CommentFields::FIELD_PARENT => [
                FieldData::createKey(CommentFields::GROUP_NAME, CommentFields::FIELD_PARENT),
                __('Parent', 'acf-views')
            ],
            CommentFields::FIELD_USER => [
                FieldData::createKey(CommentFields::GROUP_NAME, CommentFields::FIELD_USER),
                __('User', 'acf-views')
            ],
        ];

        $wooFields = [
            WooFields::FIELD_GALLERY => [
                FieldData::createKey(WooFields::GROUP_NAME, WooFields::FIELD_GALLERY),
                __('Gallery', 'acf-views')
            ],
            WooFields::FIELD_PRICE => [
                FieldData::createKey(WooFields::GROUP_NAME, WooFields::FIELD_PRICE),
                __('Price', 'acf-views')
            ],
            WooFields::FIELD_REGULAR_PRICE => [
                FieldData::createKey(WooFields::GROUP_NAME, WooFields::FIELD_REGULAR_PRICE),
                __('Regular price', 'acf-views')
            ],
            WooFields::FIELD_SALE_PRICE => [
                FieldData::createKey(WooFields::GROUP_NAME, WooFields::FIELD_SALE_PRICE),
                __('Sale price', 'acf-views')
            ],
            WooFields::FIELD_SKU => [
                FieldData::createKey(WooFields::GROUP_NAME, WooFields::FIELD_SKU),
                __('SKU', 'acf-views')
            ],
            WooFields::FIELD_STOCK_STATUS => [
                FieldData::createKey(WooFields::GROUP_NAME, WooFields::FIELD_STOCK_STATUS),
                __('Stock status', 'acf-views')
            ],
            WooFields::FIELD_WEIGHT => [
                FieldData::createKey(WooFields::GROUP_NAME, WooFields::FIELD_WEIGHT),
                __('Weight', 'acf-views')
            ],
            WooFields::FIELD_LENGTH => [
                FieldData::createKey(WooFields::GROUP_NAME, WooFields::FIELD_LENGTH),
                __('Length', 'acf-views')
            ],
            WooFields::FIELD_WIDTH => [
                FieldData::createKey(WooFields::GROUP_NAME, WooFields::FIELD_WIDTH),
                __('Width', 'acf-views')
            ],
            WooFields::FIELD_HEIGHT => [
                FieldData::createKey(WooFields::GROUP_NAME, WooFields::FIELD_HEIGHT),
                __('Height', 'acf-views')
            ],
        ];

        $termFields = [
            TermFields::FIELD_NAME => [
                FieldData::createKey(TermFields::GROUP_NAME, TermFields::FIELD_NAME),
                __('Name', 'acf-views')
            ],
            TermFields::FIELD_SLUG => [
                FieldData::createKey(TermFields::GROUP_NAME, TermFields::FIELD_SLUG),
                __('Slug', 'acf-views')
            ],
            TermFields::FIELD_DESCRIPTION => [
                FieldData::createKey(TermFields::GROUP_NAME, TermFields::FIELD_DESCRIPTION),
                __('Description', 'acf-views')
            ],
            TermFields::FIELD_NAME_LINK => [
                FieldData::createKey(TermFields::GROUP_NAME, TermFields::FIELD_NAME_LINK),
                __('Name link', 'acf-views')
            ],
        ];

        $menuFields = [
            MenuFields::FIELD_ITEMS => [
                FieldData::createKey(MenuFields::GROUP_NAME, MenuFields::FIELD_ITEMS),
                __('Items', 'acf-views')
            ],
        ];

        $menuItemFields = [
            MenuItemFields::FIELD_LINK => [
                FieldData::createKey(MenuItemFields::GROUP_NAME, MenuItemFields::FIELD_LINK),
                __('Link', 'acf-views')
            ],
        ];

        $extraFields = array_merge(
            $postFields,
            $userFields,
            $commentItemFields,
            $commentFields,
            $wooFields,
            $termFields,
            $menuFields,
            $menuItemFields,
        );

        foreach ($extraFields as $fieldName => $fieldInfo) {
            if (in_array($fieldName, $excludeTypes, true)) {
                continue;
            }

            $fieldChoices[$fieldInfo[0]] = $fieldInfo[1];
        }

        if (!in_array(TaxonomyTermFields::GROUP_NAME, $excludeTypes, true)) {
            $taxonomies = get_taxonomies([], 'objects');

            foreach ($taxonomies as $taxonomy) {
                $itemName = FieldData::createKey(
                    TaxonomyTermFields::GROUP_NAME,
                    TaxonomyTermFields::PREFIX . $taxonomy->name
                );
                $fieldChoices[$itemName] = $taxonomy->label;
            }
        }

        return $fieldChoices;
    }

    protected function setConditionalFieldsRulesByValues(): void
    {
        //// Masonry fields

        $masonryFields = [
            FieldData::FIELD_MASONRY_ROW_MIN_HEIGHT,
            FieldData::FIELD_MASONRY_GUTTER,
            FieldData::FIELD_MASONRY_MOBILE_GUTTER,
        ];

        foreach ($masonryFields as $masonryField) {
            add_filter(
                'acf/load_field/name=' . FieldData::getAcfFieldName($masonryField),
                function (array $field) {
                    return $this->setConditionalRulesForField(
                        $field,
                        FieldData::getAcfFieldName(FieldData::FIELD_GALLERY_TYPE),
                        ['', 'plain',],
                    );
                }
            );
        }

        $masonryRepeaterFields = [
            RepeaterFieldData::FIELD_MASONRY_ROW_MIN_HEIGHT,
            RepeaterFieldData::FIELD_MASONRY_GUTTER,
            RepeaterFieldData::FIELD_MASONRY_MOBILE_GUTTER,
        ];

        foreach ($masonryRepeaterFields as $masonryRepeaterField) {
            add_filter(
                'acf/load_field/name=' . RepeaterFieldData::getAcfFieldName($masonryRepeaterField),
                function (array $field) {
                    return $this->setConditionalRulesForField(
                        $field,
                        RepeaterFieldData::getAcfFieldName(RepeaterFieldData::FIELD_GALLERY_TYPE),
                        ['', 'plain',],
                    );
                }
            );
        }

        //// repeaterFields tab ('repeater' + 'group')

        add_filter(
            'acf/load_field/name=' . ItemData::getAcfFieldName(ItemData::FIELD_REPEATER_FIELDS_TAB),
            function (array $field) {
                // using exactly the negative (excludeTypes) filter,
                // otherwise if there are no such fields the field will be visible
                $notRepeaterFields = $this->getFieldChoices(true, ['repeater', 'group',]);

                return $this->setConditionalRulesForField(
                    $field,
                    FieldData::getAcfFieldName(FieldData::FIELD_KEY),
                    array_keys($notRepeaterFields)
                );
            }
        );
    }

    protected function setConditionalFieldRules(): void
    {
        $linkFields = [
            'url',
            'file',
            'link',
            'page_link',
            'post_object',
            'relationship',
            'taxonomy',
            'user',
            PostFields::FIELD_TITLE_LINK,
            PostFields::FIELD_AUTHOR,
            TermFields::FIELD_NAME_LINK,
            UserFields::FIELD_AUTHOR_LINK,
            UserFields::FIELD_WEBSITE,
            MenuItemFields::FIELD_LINK,
        ];
        $imageFields = [
            'image',
            'gallery',
            PostFields::FIELD_THUMBNAIL,
            PostFields::FIELD_THUMBNAIL_LINK,
            WooFields::FIELD_GALLERY,
        ];
        $mapFields = [
            'google_map',
            'google_map_multi',
            'open_street_map',
        ];

        $fieldsWithViewOption = [
            'post_object',
            'relationship',
            'user',
            'taxonomy',
            PostFields::FIELD_AUTHOR,
            CommentItemFields::FIELD_LIST,
            TaxonomyTermFields::GROUP_NAME,
            MenuFields::FIELD_ITEMS,
        ];

        $fieldsWithSlider = [
            'post_object',
            'relationship',
            'user',
            'taxonomy',
            CommentItemFields::FIELD_LIST,
            TaxonomyTermFields::GROUP_NAME,
            'repeater',
        ];

        $fieldsWithOptionDelimiter = [
            'select',
            'post_object',
            'page_link',
            'relationship',
            'taxonomy',
            'user',
            TaxonomyTermFields::GROUP_NAME,
        ];

        $fieldRules = [
            FieldData::FIELD_LINK_LABEL => $linkFields,
            FieldData::FIELD_IS_LINK_TARGET_BLANK => array_merge($linkFields, [
                MenuFields::FIELD_ITEMS,
                TaxonomyTermFields::GROUP_NAME,
            ]),
            FieldData::FIELD_IMAGE_SIZE => $imageFields,
            FieldData::FIELD_ACF_VIEW_ID => $fieldsWithViewOption,
            FieldData::FIELD_GALLERY_TYPE => [
                'gallery',
                WooFields::FIELD_GALLERY,
            ],
            FieldData::FIELD_LIGHTBOX_TYPE => array_diff($imageFields, [
                // it's a link, shouldn't be lightboxed
                PostFields::FIELD_THUMBNAIL_LINK,
            ]),
            FieldData::FIELD_GALLERY_WITH_LIGHT_BOX => array_diff($imageFields, [
                // it's a link, shouldn't be lightboxed
                PostFields::FIELD_THUMBNAIL_LINK,
            ]),
            FieldData::FIELD_SLIDER_TYPE => $fieldsWithSlider,
            FieldData::FIELD_MAP_MARKER_ICON => $mapFields,
            FieldData::FIELD_MAP_MARKER_ICON_TITLE => $mapFields,
            FieldData::FIELD_MAP_ADDRESS_FORMAT => $mapFields,
            FieldData::FIELD_IS_MAP_WITH_ADDRESS => $mapFields,
            FieldData::FIELD_IS_MAP_WITHOUT_GOOGLE_MAP => $mapFields,
            FieldData::FIELD_OPTIONS_DELIMITER => $fieldsWithOptionDelimiter,
        ];

        foreach ($fieldRules as $fieldName => $conditionalFields) {
            $this->addConditionalFilter($fieldName, $conditionalFields);
            $this->addConditionalFilter($fieldName, $conditionalFields, true);
        }

        $this->setConditionalFieldsRulesByValues();
    }

    protected function getImageSizes(): array
    {
        $imageSizeChoices = [];
        $imageSizes = get_intermediate_image_sizes();

        foreach ($imageSizes as $imageSize) {
            $imageSizeChoices[$imageSize] = ucfirst($imageSize);
        }

        $imageSizeChoices['full'] = __('Full', 'acf-views');

        return $imageSizeChoices;
    }

    protected function setFieldChoices(): void
    {
        add_filter(
            'acf/load_field/name=' . FieldData::getAcfFieldName(FieldData::FIELD_KEY),
            function (array $field) {
                $field['choices'] = $this->getFieldChoices();

                return $field;
            }
        );

        add_filter(
            'acf/load_field/name=' . RepeaterFieldData::getAcfFieldName(RepeaterFieldData::FIELD_KEY),
            function (array $field) {
                $field['choices'] = $this->getSubFieldChoices();

                return $field;
            }
        );

        add_filter(
            'acf/load_field/name=' . FieldData::getAcfFieldName(FieldData::FIELD_IMAGE_SIZE),
            function (array $field) {
                $field['choices'] = $this->getImageSizes();

                return $field;
            }
        );

        add_filter(
            'acf/load_field/name=' . RepeaterFieldData::getAcfFieldName(RepeaterFieldData::FIELD_IMAGE_SIZE),
            function (array $field) {
                $field['choices'] = $this->getImageSizes();

                return $field;
            }
        );

        add_filter(
            'acf/load_field/name=' . FieldData::getAcfFieldName(FieldData::FIELD_ACF_VIEW_ID),
            function (array $field) {
                $field['choices'] = $this->getAcfViewChoices();

                return $field;
            }
        );

        add_filter(
            'acf/load_field/name=' . RepeaterFieldData::getAcfFieldName(RepeaterFieldData::FIELD_ACF_VIEW_ID),
            function (array $field) {
                $field['choices'] = $this->getAcfViewChoices();

                return $field;
            }
        );
    }

    public function getFieldChoices(bool $isWithExtra = true, array $excludeTypes = []): array
    {
        $fieldChoices = [];

        if (!function_exists('acf_get_fields')) {
            return $fieldChoices;
        }

        $fieldChoices = [
            '' => 'Select',
        ];

        if ($isWithExtra) {
            $fieldChoices = array_merge($fieldChoices, $this->getExtraFieldChoices($excludeTypes));
        }

        $supportedFieldTypes = $this->getFieldTypes();

        $groups = $this->getGroups();
        foreach ($groups as $group) {
            $fields = acf_get_fields($group);

            foreach ($fields as $groupField) {
                if (!in_array($groupField['type'], $supportedFieldTypes, true) ||
                    ($excludeTypes && in_array($groupField['type'], $excludeTypes, true))) {
                    continue;
                }

                $fullFieldId = FieldData::createKey($group['key'], $groupField['key']);
                $fieldChoices[$fullFieldId] = $groupField['label'] . ' (' . $groupField['type'] . ')';
            }
        }

        return $fieldChoices;
    }

    public function getFieldTypes(): array
    {
        $fieldTypes = [];
        $groupedFieldTypes = $this->getGroupedFieldTypes();
        foreach ($groupedFieldTypes as $group => $fields) {
            $fieldTypes = array_merge($fieldTypes, $fields);
        }

        return $fieldTypes;
    }
}