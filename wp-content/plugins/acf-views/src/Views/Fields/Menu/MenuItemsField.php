<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views\Fields\Menu;

use org\wplake\acf_views\Assets\FrontAssets;
use org\wplake\acf_views\Groups\FieldData;
use org\wplake\acf_views\Groups\ItemData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Views\FieldMeta;
use org\wplake\acf_views\Views\Fields\Acf\LinkField;
use org\wplake\acf_views\Views\Fields\CustomField;
use org\wplake\acf_views\Views\Fields\MarkupField;
use WP_Post;

defined('ABSPATH') || exit;

class MenuItemsField extends MarkupField
{
    use CustomField;

    protected LinkField $linkField;

    public function __construct(FrontAssets $frontAssets, LinkField $linkField)
    {
        parent::__construct($frontAssets);

        $this->linkField = $linkField;
    }

    protected function getItemMarkup(
        ViewData $viewData,
        string $fieldId,
        string $itemId,
        ItemData $item,
        FieldData $fieldData,
        FieldMeta $fieldMeta,
        int &$tabsNumber,
        bool $isWithFieldWrapper,
        bool $isWithRowWrapper
    ): string {
        return $this->linkField->getMarkup(
            $viewData,
            $itemId,
            $item,
            $fieldData,
            $fieldMeta,
            $tabsNumber,
            $isWithFieldWrapper,
            $isWithRowWrapper
        );
    }


    public function getMarkup(
        ViewData $acfViewData,
        string $fieldId,
        ItemData $item,
        FieldData $field,
        FieldMeta $fieldMeta,
        int &$tabsNumber,
        bool $isWithFieldWrapper,
        bool $isWithRowWrapper
    ): string {
        $markup = '';

        $markup .= "\r\n" . str_repeat("\t", $tabsNumber);
        $markup .= sprintf("{%% for menu_item in %s.value %%}", esc_html($fieldId));
        $markup .= "\r\n" . str_repeat("\t", ++$tabsNumber);

        $markup .= sprintf(
            '<li class="%s{%% if menu_item.isActive or menu_item.isChildActive %%} %s{%% endif %%}">',
            $this->getItemClass('menu-item', $acfViewData, $field),
            $this->getItemClass('menu-item--active', $acfViewData, $field)
        );
        $markup .= "\r\n\r\n" . str_repeat("\t", ++$tabsNumber);

        $markup .= $this->getItemMarkup(
            $acfViewData,
            $fieldId,
            'menu_item',
            $item,
            $field,
            $fieldMeta,
            $tabsNumber,
            $isWithFieldWrapper,
            $isWithRowWrapper
        );

        $markup .= "\r\n\r\n" . str_repeat("\t", $tabsNumber);
        $markup .= "{% if menu_item.children %}";
        $markup .= "\r\n" . str_repeat("\t", ++$tabsNumber);

        $markup .= sprintf(
            '<ul class="%s">',
            $this->getItemClass('sub-menu', $acfViewData, $field)
        );
        $markup .= "\r\n\r\n" . str_repeat("\t", ++$tabsNumber);

        $markup .= "{% for sub_menu_item in menu_item.children %}";
        $markup .= "\r\n" . str_repeat("\t", ++$tabsNumber);
        $markup .= sprintf(
            '<li class="%s{%% if sub_menu_item.isActive %%} %s{%% endif %%}">',
            $this->getItemClass('sub-menu-item', $acfViewData, $field),
            $this->getItemClass('sub-menu-item--active', $acfViewData, $field)
        );
        $markup .= "\r\n" . str_repeat("\t", ++$tabsNumber);

        $markup .= $this->getItemMarkup(
            $acfViewData,
            $fieldId,
            'sub_menu_item',
            $item,
            $field,
            $fieldMeta,
            $tabsNumber,
            $isWithFieldWrapper,
            $isWithRowWrapper
        );

        $markup .= "\r\n" . str_repeat("\t", --$tabsNumber);
        $markup .= '</li>';
        $markup .= "\r\n" . str_repeat("\t", --$tabsNumber);
        $markup .= "{% endfor %}";

        $markup .= "\r\n\r\n" . str_repeat("\t", --$tabsNumber);
        $markup .= '</ul>';

        $markup .= "\r\n" . str_repeat("\t", --$tabsNumber);
        $markup .= "{% endif %}";

        $markup .= "\r\n\r\n" . str_repeat("\t", --$tabsNumber);
        $markup .= '</li>';

        $markup .= "\r\n" . str_repeat("\t", --$tabsNumber);
        $markup .= "{% endfor %}\r\n";

        return $markup;
    }

    protected function isActiveItem(WP_Post $menuItem): bool
    {
        $postsPageId = (int)get_option('page_for_posts');
        $objectId = (int)($menuItem->object_id ?? 0);

        // active if the current menu is for current page, or
        // the current menu for blog and the current page is post or
        // the current menu for blog and the current page is author page
        // the current menu for blog and the current page is category page

        if (($objectId && get_queried_object_id() === $objectId) ||
            ($objectId === $postsPageId && is_singular('post')) ||
            ($objectId === $postsPageId && is_author()) ||
            ($objectId === $postsPageId && is_category())) {
            return true;
        }

        return false;
    }

    /**
     * @param WP_Post|null $menuItem
     * @param WP_Post[] $children
     * @param ViewData $acfViewData
     * @param ItemData $item
     * @param FieldData $field
     * @param FieldMeta $fieldMeta
     * @param bool $isForValidation
     * @return array
     */
    protected function getItemTwigArgs(
        ?WP_Post $menuItem,
        array $children,
        ViewData $acfViewData,
        ItemData $item,
        FieldData $field,
        FieldMeta $fieldMeta,
        bool $isForValidation = false
    ): array {
        $linkArgs = $menuItem ?
            $this->getMenuItemInfo($menuItem) :
            [];

        $args = $this->linkField->getTwigArgs(
            $acfViewData,
            $item,
            $field,
            $fieldMeta,
            $linkArgs,
            $linkArgs,
            $isForValidation
        );
        $args = array_merge($args, [
            'isActive' => false,
            'isChildActive' => false,
            'children' => []
        ]);

        if ($isForValidation) {
            $childArgs = $this->linkField->getTwigArgs(
                $acfViewData,
                $item,
                $field,
                $fieldMeta,
                $linkArgs,
                $linkArgs,
                $isForValidation
            );

            $args['children'][] = array_merge($childArgs, [
                'isActive' => false,
            ]);
            return $args;
        }

        $isChildActive = false;

        foreach ($children as $childMenuItem) {
            $linkArgs = $this->getMenuItemInfo($childMenuItem);

            $childArgs = $this->linkField->getTwigArgs(
                $acfViewData,
                $item,
                $field,
                $fieldMeta,
                $linkArgs,
                $linkArgs,
                $isForValidation
            );

            $isSubActive = $this->isActiveItem($childMenuItem);
            $args['children'][] = array_merge($childArgs, [
                'isActive' => $isSubActive,
            ]);
            $isChildActive = $isChildActive || $isSubActive;
        }

        return array_merge($args, [
            'isActive' => $this->isActiveItem($menuItem),
            'isChildActive' => $isChildActive,
        ]);
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
            $itemArgs = $this->getItemTwigArgs(
                null,
                [],
                $acfViewData,
                $item,
                $field,
                $fieldMeta,
                true
            );

            return array_merge($args, [
                'value' => [
                    $itemArgs,
                ]
            ]);
        }

        if (!$notFormattedValue) {
            return $args;
        }

        $menu = $this->getTerm($notFormattedValue, 'nav_menu');

        if (!$menu) {
            return $args;
        }

        $menuItems = wp_get_nav_menu_items($menu->term_id);

        $children = [];
        foreach ($menuItems as $menuItem) {
            if (!$menuItem->menu_item_parent) {
                continue;
            }

            $children[$menuItem->menu_item_parent][] = $menuItem;
        }

        foreach ($menuItems as $menuItem) {
            // top level only
            if ($menuItem->menu_item_parent) {
                continue;
            }

            $args['value'][] = $this->getItemTwigArgs(
                $menuItem,
                $children[$menuItem->ID] ?? [],
                $acfViewData,
                $item,
                $field,
                $fieldMeta,
                $isForValidation
            );
        }

        return $args;
    }

    public function isWithFieldWrapper(ViewData $acfViewData, FieldData $field, FieldMeta $fieldMeta): bool
    {
        return true;
    }
}