<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views\Fields;

use org\wplake\acf_views\Assets\FrontAssets;
use org\wplake\acf_views\Groups\FieldData;
use org\wplake\acf_views\Groups\ItemData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Views\FieldMeta;
use org\wplake\acf_views\Views\Fields\Acf\{ColorPickerField,
    DatePickerField,
    FileField,
    GalleryField,
    ImageField,
    LinkField,
    MapField,
    PageLinkField,
    PostObjectField,
    SelectField,
    TaxonomyField,
    TrueFalseField,
    UrlField,
    UserField};
use org\wplake\acf_views\Views\Fields\Comment\{CommentAuthorEmailField,
    CommentAuthorNameField,
    CommentAuthorNameLinkField,
    CommentContentField,
    CommentDateField,
    CommentFields,
    CommentParentField,
    CommentStatusField,
    CommentUserField};
use org\wplake\acf_views\Views\Fields\CommentItems\{CommentItemFields, CommentItemsListField};
use org\wplake\acf_views\Views\Fields\Menu\MenuFields;
use org\wplake\acf_views\Views\Fields\Menu\MenuItemsField;
use org\wplake\acf_views\Views\Fields\MenuItem\MenuItemFields;
use org\wplake\acf_views\Views\Fields\MenuItem\MenuItemLinkField;
use org\wplake\acf_views\Views\Fields\Post\{PostAuthorField,
    PostContentField,
    PostDateField,
    PostExcerptField,
    PostFields,
    PostModifiedField,
    PostThumbnailField,
    PostThumbnailLinkField,
    PostTitleField,
    PostTitleLinkField};
use org\wplake\acf_views\Views\Fields\TaxonomyTerms\TaxonomyTermFields;
use org\wplake\acf_views\Views\Fields\TaxonomyTerms\TaxonomyTermsField;
use org\wplake\acf_views\Views\Fields\Term\{TermDescriptionField,
    TermFields,
    TermNameField,
    TermNameLinkField,
    TermSlugField};
use org\wplake\acf_views\Views\Fields\User\{UserAuthorLinkField,
    UserBioField,
    UserDisplayNameField,
    UserEmailField,
    UserFields,
    UserFirstNameField,
    UserLastNameField,
    UserWebsiteField};
use org\wplake\acf_views\Views\Fields\Woo\{WooFields,
    WooGalleryField,
    WooHeightField,
    WooLengthField,
    WooPriceField,
    WooRegularPriceField,
    WooSalePriceField,
    WooSkuField,
    WooStockStatusField,
    WooWeightField,
    WooWidthField};

defined('ABSPATH') || exit;

class Fields
{
    /**
     * @var MarkupField[]
     */
    protected array $fields;
    protected FrontAssets $frontAssets;

    public function __construct(FrontAssets $frontAssets)
    {
        $this->frontAssets = $frontAssets;

        $this->initFields();
    }

    protected function initFields(): void
    {
        $imageField = new ImageField($this->frontAssets);
        $selectField = new SelectField($this->frontAssets);
        $linkField = new LinkField($this->frontAssets);
        $postObjectField = new PostObjectField($this->frontAssets, $linkField);
        $datePickerField = new DatePickerField($this->frontAssets);
        $taxonomyField = new TaxonomyField($this->frontAssets, $linkField);
        $postContent = new PostContentField($this->frontAssets);
        $mapField = new MapField($this->frontAssets);

        $this->fields = [
            //// basic
            'url' => new UrlField($this->frontAssets, $linkField),

            //// content types
            'image' => $imageField,
            'file' => new FileField($this->frontAssets, $linkField),
            'gallery' => new GalleryField($this->frontAssets, $imageField),

            //// choice types
            'select' => $selectField,
            'checkbox' => $selectField,
            'radio' => $selectField,
            'button_group' => $selectField,
            'true_false' => new TrueFalseField($this->frontAssets),

            //// relational types
            'link' => $linkField,
            'page_link' => new PageLinkField($this->frontAssets, $linkField),
            'post_object' => $postObjectField,
            'relationship' => $postObjectField,
            'taxonomy' => $taxonomyField,
            'user' => new UserField($this->frontAssets, $linkField),

            //// jquery types
            'google_map' => $mapField,
            'google_map_multi' => $mapField,
            'open_street_map' => $mapField,
            'date_picker' => $datePickerField,
            'date_time_picker' => $datePickerField,
            'time_picker' => $datePickerField,
            'color_picker' => new ColorPickerField($this->frontAssets),

            PostFields::FIELD_TITLE => new PostTitleField($this->frontAssets),
            PostFields::FIELD_TITLE_LINK => new PostTitleLinkField($this->frontAssets, $linkField),
            PostFields::FIELD_THUMBNAIL => new PostThumbnailField($this->frontAssets, $imageField),
            PostFields::FIELD_THUMBNAIL_LINK => new PostThumbnailLinkField($this->frontAssets, $imageField),
            PostFields::FIELD_AUTHOR => new PostAuthorField($this->frontAssets, $linkField),
            PostFields::FIELD_DATE => new PostDateField($this->frontAssets),
            PostFields::FIELD_MODIFIED => new PostModifiedField($this->frontAssets),
            PostFields::FIELD_CONTENT => $postContent,
            PostFields::FIELD_EXCERPT => new PostExcerptField($this->frontAssets),

            TaxonomyTermFields::FIELD_TERMS => new TaxonomyTermsField($this->frontAssets, $linkField),

            TermFields::FIELD_NAME => new TermNameField($this->frontAssets),
            TermFields::FIELD_SLUG => new TermSlugField($this->frontAssets),
            TermFields::FIELD_DESCRIPTION => new TermDescriptionField($this->frontAssets),
            TermFields::FIELD_NAME_LINK => new TermNameLinkField($this->frontAssets, $linkField),

            UserFields::FIELD_FIRST_NAME => new UserFirstNameField($this->frontAssets),
            UserFields::FIELD_LAST_NAME => new UserLastNameField($this->frontAssets),
            UserFields::FIELD_DISPLAY_NAME => new UserDisplayNameField($this->frontAssets),
            UserFields::FIELD_EMAIL => new UserEmailField($this->frontAssets),
            UserFields::FIELD_BIO => new UserBioField($this->frontAssets),
            UserFields::FIELD_AUTHOR_LINK => new UserAuthorLinkField($this->frontAssets, $linkField),
            UserFields::FIELD_WEBSITE => new UserWebsiteField($this->frontAssets, $linkField),

            CommentItemFields::FIELD_LIST => new CommentItemsListField($this->frontAssets),

            CommentFields::FIELD_AUTHOR_EMAIL => new CommentAuthorEmailField($this->frontAssets),
            CommentFields::FIELD_AUTHOR_NAME => new CommentAuthorNameField($this->frontAssets),
            CommentFields::FIELD_AUTHOR_NAME_LINK => new CommentAuthorNameLinkField(
                $this->frontAssets, $linkField
            ),
            CommentFields::FIELD_CONTENT => new CommentContentField($this->frontAssets),
            CommentFields::FIELD_DATE => new CommentDateField($this->frontAssets),
            CommentFields::FIELD_STATUS => new CommentStatusField($this->frontAssets),
            CommentFields::FIELD_PARENT => new CommentParentField($this->frontAssets),
            CommentFields::FIELD_USER => new CommentUserField($this->frontAssets),

            MenuFields::FIELD_ITEMS => new MenuItemsField($this->frontAssets, $linkField),

            MenuItemFields::FIELD_LINK => new MenuItemLinkField($this->frontAssets, $linkField),

            WooFields::FIELD_PRICE => new WooPriceField($this->frontAssets),
            WooFields::FIELD_REGULAR_PRICE => new WooRegularPriceField($this->frontAssets),
            WooFields::FIELD_SALE_PRICE => new WooSalePriceField($this->frontAssets),
            WooFields::FIELD_SKU => new WooSkuField($this->frontAssets),
            WooFields::FIELD_STOCK_STATUS => new WooStockStatusField($this->frontAssets),
            WooFields::FIELD_GALLERY => new WooGalleryField($this->frontAssets, $imageField),
            WooFields::FIELD_WEIGHT => new WooWeightField($this->frontAssets),
            WooFields::FIELD_LENGTH => new WooLengthField($this->frontAssets),
            WooFields::FIELD_WIDTH => new WooWidthField($this->frontAssets),
            WooFields::FIELD_HEIGHT => new WooHeightField($this->frontAssets),
        ];
    }

    protected function applyFieldMarkupFilter(
        string $fieldMarkup,
        FieldMeta $fieldMeta,
        string $shortUniqueViewId
    ): string {
        $fieldMarkup = (string)apply_filters(
            'acf_views/view/field_markup',
            $fieldMarkup,
            $fieldMeta,
            $shortUniqueViewId
        );
        $fieldMarkup = (string)apply_filters(
            'acf_views/view/field_markup/name=' . $fieldMeta->getName(),
            $fieldMarkup,
            $fieldMeta,
            $shortUniqueViewId
        );

        if (!$fieldMeta->isCustomType()) {
            $fieldMarkup = (string)apply_filters(
                'acf_views/view/field_markup/type=' . $fieldMeta->getType(),
                $fieldMarkup,
                $fieldMeta,
                $shortUniqueViewId
            );
        }

        return (string)apply_filters(
            'acf_views/view/field_markup/view_id=' . $shortUniqueViewId,
            $fieldMarkup,
            $fieldMeta,
            $shortUniqueViewId
        );
    }

    protected function applyFieldDataFilter(array $fieldData, FieldMeta $fieldMeta, string $shortUniqueViewId): array
    {
        $fieldData = (array)apply_filters(
            'acf_views/view/field_data',
            $fieldData,
            $fieldMeta,
            $shortUniqueViewId
        );

        if (!$fieldMeta->isCustomType()) {
            $fieldData = (array)apply_filters(
                'acf_views/view/field_data/type=' . $fieldMeta->getType(),
                $fieldData,
                $fieldMeta,
                $shortUniqueViewId
            );
        }

        $fieldData = (array)apply_filters(
            'acf_views/view/field_data/name=' . $fieldMeta->getName(),
            $fieldData,
            $fieldMeta,
            $shortUniqueViewId
        );

        return (array)apply_filters(
            'acf_views/view/field_data/view_id=' . $shortUniqueViewId,
            $fieldData,
            $fieldMeta,
            $shortUniqueViewId
        );
    }

    protected function getFieldWrapper(
        string $fieldId,
        int &$tabsNumber,
        bool $isWithRowWrapper,
        ViewData $viewData,
        FieldData $fieldData,
        string $fieldNameClass,
        string $tag
    ): string {
        $fieldClasses = '';

        if ($isWithRowWrapper) {
            $fieldClasses .= $viewData->getBemName() . '__' . $fieldData->id . '-field';
            $fieldClasses .= $viewData->isWithCommonClasses ?
                ' ' . $viewData->getBemName() . '__field' :
                '';
        } else {
            $fieldClasses .= $fieldNameClass;

            if ($viewData->isWithCommonClasses) {
                $fieldClasses .= ' ' . $viewData->getBemName() . '__field';
            }
        }

        $attrsData = $this->frontAssets->getFieldWrapperAttrs($fieldData, $fieldId);

        $attrClass = $attrsData['class'] ?? '';
        unset($attrsData['class']);

        $attrs = '';

        foreach ($attrsData as $attr => $value) {
            $attrs .= sprintf(' %s="%s"', esc_html($attr), esc_html($value));
        }

        $fieldClasses .= $attrClass ?
            ' ' . $attrClass :
            '';

        $markup = str_repeat("\t", $tabsNumber) .
            sprintf(
                "<%s class=\"%s\"%s>",
                esc_html($tag),
                esc_html($fieldClasses),
                $attrs
            );
        $tabsNumber++;

        return $markup;
    }

    protected function getRowWrapper(
        string $fieldNameClass,
        ViewData $viewData,
        FieldData $fieldData,
        string $type,
        string $rowClass,
        int &$tabsNumber,
        string $tag
    ): string {
        $rowClasses = $fieldNameClass;

        if ($viewData->isWithCommonClasses) {
            $rowClasses .= ' ' . $viewData->getBemName() . '__' . $type;
        }
        $rowClasses .= $rowClass ?
            ' ' . $rowClass :
            '';

        $rowMarkup = str_repeat("\t", $tabsNumber);
        $rowMarkup .= sprintf("<%s class=\"%s\">", esc_html($tag), esc_html($rowClasses));
        $rowMarkup .= "\r\n";

        $tabsNumber++;

        return $rowMarkup;
    }

    protected function printOpeningFieldOuters(
        array $fieldOuters,
        int &$tabsNumber,
        ViewData $viewData,
        FieldData $fieldData
    ): string {
        $markup = '';
        foreach ($fieldOuters as $outer) {
            $attrs = '';

            foreach ($outer['attrs'] as $attr => $value) {
                $attrs .= sprintf(' %s="%s"', esc_html($attr), esc_html($value));
            }

            $markup .= "\r\n" . str_repeat("\t", $tabsNumber);
            $markup .= sprintf('<%s%s>', esc_html($outer['tag']), $attrs);
            $markup .= "\r\n";

            $tabsNumber++;
        }

        return $markup;
    }

    protected function printClosingFieldOuters(array $fieldOuters, int &$tabsNumber): string
    {
        $markup = '';
        foreach ($fieldOuters as $outer) {
            $markup .= str_repeat("\t", --$tabsNumber);
            $markup .= sprintf('</%s>', esc_html($outer['tag']));
            $markup .= "\r\n";
        }

        return $markup;
    }

    protected function printLabel(ViewData $viewData, FieldData $fieldData, int &$tabsNumber, string $fieldId): string
    {
        $rowMarkup = str_repeat("\t", $tabsNumber);

        $labelClass = $viewData->getBemName() . '__' . $fieldData->id . '-label';

        $labelClass .= $viewData->isWithCommonClasses ?
            ' ' . $viewData->getBemName() . '__label' :
            '';

        $rowMarkup .= sprintf("<div class=\"%s\">", esc_html($labelClass));
        $rowMarkup .= "\r\n" . str_repeat("\t", ++$tabsNumber);
        $rowMarkup .= sprintf('{{ %s.label }}', esc_html($fieldId));
        $rowMarkup .= "\r\n" . str_repeat("\t", --$tabsNumber);
        $rowMarkup .= "</div>";
        $rowMarkup .= "\r\n";

        return $rowMarkup;
    }

    // public, as used in Upgrades
    public function getFieldMarkup(
        ViewData $acfViewData,
        ItemData $item,
        FieldData $field,
        FieldMeta $fieldMeta,
        int &$tabsNumber,
        string $fieldId,
        bool $isWithOuterWrappers
    ): string {
        $fieldType = $fieldMeta->getType();

        if (!$fieldMeta->isFieldExist()) {
            return '';
        }

        $fieldMarkup = '';
        $isWithWrapper = $this->isWithFieldWrapper($acfViewData, $field, $fieldMeta, 'field');

        if (!isset($this->fields[$fieldType]) ||
            !$this->fields[$fieldType] instanceof MarkupField) {
            // disable Twig escaping for wysiwyg, oembed. HTML is expected there. For textarea it's '<br>'
            $filter = in_array($fieldType, ['wysiwyg', 'oembed', 'textarea',], true) ?
                '|raw' :
                '';

            $fieldMarkup .= "\r\n";
            $fieldMarkup .= str_repeat("\t", $tabsNumber);
            $fieldMarkup .= sprintf('{{ %s.value%s }}', esc_html($fieldId), esc_html($filter));
            $fieldMarkup .= "\r\n";
        } else {
            if ($isWithWrapper &&
                !$isWithOuterWrappers) {
                $fieldMarkup .= "\r\n";
            }

            $fieldMarkup .= str_repeat("\t", $tabsNumber) .
                $this->fields[$fieldType]->getMarkup(
                    $acfViewData,
                    $fieldId,
                    $item,
                    $field,
                    $fieldMeta,
                    $tabsNumber,
                    $isWithWrapper,
                    $this->isWithRowWrapper($acfViewData, $field, $fieldMeta)
                ) .
                "\r\n";
        }

        return $this->applyFieldMarkupFilter($fieldMarkup, $fieldMeta, $acfViewData->getUniqueId(true));
    }

    public function isWithFieldWrapper(
        ViewData $acfViewData,
        FieldData $field,
        FieldMeta $fieldMeta,
        string $rowType
    ): bool {
        $fieldType = $fieldMeta->getType();

        if (!$fieldMeta->isFieldExist()) {
            return false;
        }

        if (!isset($this->fields[$fieldType]) ||
            !$this->fields[$fieldType] instanceof MarkupField) {
            return true;
        }

        return $this->frontAssets->getFieldWrapperTag($field, $rowType) ||
            $this->fields[$fieldType]->isWithFieldWrapper($acfViewData, $field, $fieldMeta);
    }

    public function isWithRowWrapper(ViewData $acfViewData, FieldData $field, FieldMeta $fieldMeta): bool
    {
        return $acfViewData->isWithUnnecessaryWrappers ||
            $field->label ||
            in_array($fieldMeta->getType(), ['repeater', 'group',], true);
    }

    // $customFieldMarkup is used in RepeaterField
    public function getRowMarkup(
        string $rowType,
        string $rowSuffix,
        ViewData $viewData,
        ItemData $itemData,
        FieldData $fieldData,
        FieldMeta $fieldMeta,
        int &$tabsNumber,
        string $fieldId,
        string $customFieldMarkup = ''
    ): string {
        $rowMarkup = '';
        $rowTag = $this->frontAssets->getRowWrapperTag($fieldData, $rowType);
        $isWithRowWrapper = $this->isWithRowWrapper($viewData, $fieldData, $fieldMeta) ||
            !!$rowTag;
        $isWithFieldWrapper = $this->isWithFieldWrapper($viewData, $fieldData, $fieldMeta, $rowType);
        $fieldNameClass = $viewData->getBemName() . '__' . $fieldData->id . $rowSuffix;

        $rowTag = $rowTag ?: 'div';
        $fieldTag = $isWithFieldWrapper ?
            $this->frontAssets->getFieldWrapperTag($fieldData, $rowType) :
            '';
        $fieldTag = $fieldTag ?: 'div';

        if ($fieldData->label &&
            $this->frontAssets->isLabelOutOfRow($fieldData)) {
            $rowMarkup .= $this->printLabel($viewData, $fieldData, $tabsNumber, $fieldId);
        }

        if ($isWithRowWrapper) {
            $rowMarkup .= $this->getRowWrapper(
                $fieldNameClass,
                $viewData,
                $fieldData,
                $rowType,
                $this->frontAssets->getRowWrapperClass($fieldData, $rowType),
                $tabsNumber,
                $rowTag
            );
        }

        if ($fieldData->label &&
            !$this->frontAssets->isLabelOutOfRow($fieldData)) {
            $rowMarkup .= $this->printLabel($viewData, $fieldData, $tabsNumber, $fieldId);
        }

        if ($isWithFieldWrapper) {
            $rowMarkup .= $this->getFieldWrapper(
                $fieldId,
                $tabsNumber,
                $isWithRowWrapper,
                $viewData,
                $fieldData,
                $fieldNameClass,
                $fieldTag
            );
        }

        $fieldOuters = $this->frontAssets->getFieldOuters($viewData, $fieldData, $fieldId, $rowType);
        $isWithOuterWrappers = !!$fieldOuters;

        $rowMarkup .= $this->printOpeningFieldOuters($fieldOuters, $tabsNumber, $viewData, $fieldData);

        $rowMarkup .= !$customFieldMarkup ?
            $this->getFieldMarkup(
                $viewData,
                $itemData,
                $fieldData,
                $fieldMeta,
                $tabsNumber,
                $fieldId,
                $isWithOuterWrappers
            ) :
            $customFieldMarkup;

        $rowMarkup .= $this->printClosingFieldOuters($fieldOuters, $tabsNumber);

        if ($isWithFieldWrapper) {
            $rowMarkup .= str_repeat("\t", --$tabsNumber);
            $rowMarkup .= sprintf("</%s>", esc_html($fieldTag));
            $rowMarkup .= "\r\n";
        }

        if ($isWithRowWrapper) {
            $rowMarkup .= str_repeat("\t", --$tabsNumber);
            $rowMarkup .= sprintf("</%s>", esc_html($rowTag));
            $rowMarkup .= "\r\n";
        }

        return $rowMarkup;
    }

    public function getFieldTwigArgs(
        ViewData $acfViewData,
        ItemData $item,
        FieldData $field,
        FieldMeta $fieldMeta,
        $notFormattedValue,
        $formattedValue,
        bool $isForValidation = false
    ): array {
        $fieldType = $fieldMeta->getType();

        if (!isset($this->fields[$fieldType]) ||
            !$this->fields[$fieldType] instanceof MarkupField) {
            $formattedValue = (string)$formattedValue;

            $formattedValue = 'textarea' === $fieldType ?
                str_replace("\n", "<br/>", $formattedValue) :
                $formattedValue;

            if ($isForValidation) {
                $formattedValue = '1';
            }

            $fieldData = [
                'value' => $formattedValue,
            ];
        } else {
            $fieldData = $this->fields[$fieldType]->getTwigArgs(
                $acfViewData,
                $item,
                $field,
                $fieldMeta,
                $notFormattedValue,
                $formattedValue,
                $isForValidation
            );
        }

        return $this->applyFieldDataFilter($fieldData, $fieldMeta, $acfViewData->getUniqueId(true));
    }

    public function isFieldInstancePresent(string $fieldType): bool
    {
        return key_exists($fieldType, $this->fields);
    }
}
