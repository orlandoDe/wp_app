<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Groups;

use org\wplake\acf_views\Common\CptData;
use org\wplake\acf_views\Views\Cpt\ViewsCpt;

defined('ABSPATH') || exit;

class ViewData extends CptData
{
    // to fix the group name in case class name changes
    const CUSTOM_GROUP_NAME = self::GROUP_NAME_PREFIX . 'view';
    const LOCATION_RULES = [
        [
            'post_type == ' . ViewsCpt::NAME,
        ],
    ];
    const FIELD_MARKUP = 'markup';
    const FIELD_CSS_CODE = 'cssCode';
    const FIELD_JS_CODE = 'jsCode';
    const FIELD_CUSTOM_MARKUP = 'customMarkup';
    const FIELD_PHP_VARIABLES = 'phpVariables';
    const FIELD_TEMPLATE_TAB = 'templateTab';
    const FIELD_CSS_AND_JS_TAB = 'cssAndJsTab';
    const POST_FIELD_IS_HAS_GUTENBERG = 'post_mime_type';
    // keep the WP format 'image/jpg' to use WP_Query without issues
    const POST_VALUE_IS_HAS_GUTENBERG = 'block/block';

    /**
     * @a-type tab
     * @label Fields
     */
    public bool $fieldsTab;
    /**
     * @item \org\wplake\acf_views\Groups\ItemData
     * @var ItemData[]
     * @label Fields
     * @instructions Assign Advanced Custom Fields (ACF) to your View. <br> Tip : hover mouse on the field number column and drag to reorder
     * @button_label Add Field
     * @collapsed local_acf_views_field__key
     * @a-no-tab 1
     */
    public array $items;

    /**
     * @a-type tab
     * @label Template
     */
    public bool $templateTab;
    /**
     * @a-type textarea
     * @new_lines br
     * @label Template Preview
     * @instructions Output preview of the generated <a target='_blank' href='https://docs.acfviews.com/templates/twig-templates'>Twig template</a>. <br> Important! Publish or Update your view to see the latest markup.
     * @disabled 1
     */
    public string $markup;
    /**
     * @a-type textarea
     * @label Custom Template
     * @instructions Write your own template with full control over the HTML markup. <br> You can copy the Template Preview field output and make your changes. <br><br> Powerful <a target='_blank' href='https://docs.acfviews.com/templates/twig-templates'>Twig features</a>, including <a target='_blank' href='https://docs.acfviews.com/templates/twig-templates#our-functions'>our functions</a>, are available for you. <br>Note: WordPress shortcodes inside the template are only supported in the Pro version. <br><br> Press Ctrl (Cmd) + Alt + L to format the code. Press Ctrl + F to search (or replace).
     */
    public string $customMarkup;
    /**
     * @label BEM Unique Name
     * @instructions Define a unique <a target='_blank' href='https://getbem.com/introduction/'>BEM name</a> for the element that will be used in the markup, or leave it empty to use the default ('acf-view').
     */
    public string $bemName;
    /**
     * @label CSS classes
     * @instructions Add a class name without a dot (e.g. “class-name”) or multiple classes with single space as a delimiter (e.g. “class-name1 class-name2”). <br> These classes are added to the wrapping HTML element. <a target='_blank' href='https://www.w3schools.com/cssref/sel_class.asp'>Learn more about CSS Classes</a>
     */
    public string $cssClasses;
    /**
     * @a-type true_false
     * @label Add classification classes to the markup
     * @instructions By default, the field name is added as a prefix to all inner classes. For example, the image within the 'avatar' field will have the '__avatar-image' class. <br> Enabling this setting adds the generic class as well, such as '__image'. This feature can be useful if you want to apply styles based on field types.
     */
    public bool $isWithCommonClasses;
    /**
     * @a-type true_false
     * @label Do not skip unused wrappers
     * @instructions By default, empty wrappers in the markup are skipped to optimize the output. For example, the '__row' wrapper will be skipped if there is no field label. <br> Enable this feature if you need all the wrappers in the output.
     */
    public bool $isWithUnnecessaryWrappers;
    /**
     * @a-type textarea
     * @label Custom Template Variables
     * @instructions Add custom variables to the template using this PHP code snippet. <br>The snippet must return an associative array of values, where keys are variable names. Names should be PHP compatible, which means only letters and underscores are allowed. <br> You can access these variables in the template just like others: '{{ your_variable }}'. <br> Press Ctrl (Cmd) + Alt + L to format the code. Press Ctrl + F to search (or replace). <br> In the snippet, the following variables are predefined: '&#36;_objectId' (current data post), '&#36;_viewId' (current view id),'&#36;_fields' (an associative field values array, where keys are field identifiers). <a target='_blank' href='https://docs.acfviews.com/templates/custom-variables-pro'>Read more</a>
     * @default_value <?php return [];
     * @a-pro The field must be not required or have default value!
     */
    public string $phpVariables;

    /**
     * @a-type tab
     * @label CSS & JS
     */
    public bool $cssAndJsTab;
    /**
     * @a-type textarea
     * @label CSS Code
     * @instructions Define your CSS style rules. <br> Rules defined here will be added within &lt;style&gt;&lt;/style&gt; tags ONLY to pages that have this view. <br><br> Press Ctrl (Cmd) + Alt + L to format the code. Press Ctrl + F to search (or replace). <br><br> Magic shortcuts are available (and will use the BEM Unique Name if defined) : <br><br> '#view' will be replaced with '.acf-view--id--X' (or '.bem-name'). <br><br> '#view__' will be replaced with '.acf-view--id--X .acf-view__' (or '.bem-name .bem-name__'). It means you can use '#view__row' and it'll be replaced with '.bem-name .bem-name__row'. <br><br> '#__' will be replaced with '.acf-view__' (or '.bem-name__')
     */
    public string $cssCode;
    /**
     * @a-type textarea
     * @label JS Code
     * @instructions Add your custom Javascript to your View. <br><br> By default, the View is a <a target='_blank' href='https://kinsta.com/blog/web-components/'>web component</a>, so this code will be executed once for every instance, and 'this', that refers to the current instance, is available. <br><br> The code snippet will be added within &lt;script&gt;&lt;/script&gt; tags ONLY to pages that have this View and also will be wrapped into an anonymous function to avoid name conflicts. <br><br> Press Ctrl (Cmd) + Alt + L to format the code. Press Ctrl + F to search (or replace). <br> Don't use inline comments ('//') inside the code, otherwise it'll break the snippet.
     */
    public string $jsCode;

    /**
     * @a-type tab
     * @label Options
     */
    public bool $optionsTab;
    /**
     * @a-type textarea
     * @label Description
     * @instructions Add a short description for your views’ purpose. <br> Note : This description is only seen on the admin Advanced Views list
     */
    public string $description;
    /**
     * @label With Gutenberg Block
     * @instructions If checked, a separate gutenberg block for this view will be available. <a target='_blank' href='https://docs.acfviews.com/display-content/custom-gutenberg-blocks-pro'>Read more</a>
     * @a-pro The field must be not required or have default value!
     * @a-acf-pro ACF PRO version is necessary for this feature
     */
    public bool $isHasGutenbergBlock;
    /**
     * @label Without Web Component
     * @instructions By default, every View is a <a target='_blank' href='https://kinsta.com/blog/web-components/'>web component</a>, which allows you to work easily with the element in the JS code field
     */
    public bool $isWithoutWebComponent;
    /**
     * @a-type true_false
     * @label Render template when it's empty
     * @instructions By default, if all the selected fields are empty, the Twig template won't be rendered. <br> Enable this option if you have specific logic inside the template and you want to render it even when all the fields are empty.
     */
    public bool $isRenderWhenEmpty;
    /**
     * @a-type true_false
     * @label Use the Post ID as the View ID in the markup
     * @instructions Note: For backward compatibility purposes only. Enable this option if you have external CSS selectors that rely on outdated digital IDs
     */
    public bool $isMarkupWithDigitalId;
    /**
     * @a-type true_false
     * @label Use the Post ID in the Gutenberg block's name
     * @instructions Note: For backward compatibility purposes only.
     * @a-deprecated IT'S INVISIBLE FIELD FOR BACK COMPATIBILITY ONLY
     */
    public bool $isGutenbergBlockWithDigitalId;

    /**
     * @a-type tab
     * @label Preview
     */
    public bool $previewTab;
    /**
     * @a-type post_object
     * @return_format 1
     * @allow_null 1
     * @label Preview Object
     * @instructions Select a data object (which field values will be used) and update the View. After reload the page to see the markup in the preview
     */
    public int $previewPost;
    /**
     * @label Preview
     * @instructions Here you can see the preview of the view and play with CSS rules. <a target='_blank' href='https://docs.acfviews.com/getting-started/introduction/plugin-interface#preview-1'>Read more</a><br>Important! Update the View after changes and reload the page to see the latest markup here. <br>Your changes to the preview won't be applied to the view automatically, if you want to keep them copy amended CSS to the 'CSS Code' field and press the 'Update' button. <br> Note: styles from your front page are included in the preview (some differences may appear)
     * @placeholder Loading... Please wait a few seconds
     * @disabled 1
     */
    public string $preview;

    public static function getGroupInfo(): array
    {
        return array_merge(parent::getGroupInfo(), [
            'title' => __('View settings', 'acf-views'),
        ]);
    }

    protected function getUsedItems(): array
    {
        $fieldGroups = [];

        foreach ($this->items as $item) {
            $fieldGroup = explode('|', $item->field->key)[0];

            // ignore 'magic' groups
            if (0 !== strpos($fieldGroup, '$')) {
                $fieldGroups[] = $fieldGroup;
            }

            foreach ($item->repeaterFields as $repeaterField) {
                $subFieldGroup = explode('|', $repeaterField->key)[0];

                // ignore 'magic' groups
                if (0 !== strpos($subFieldGroup, '$')) {
                    $fieldGroups[] = $subFieldGroup;
                }
            }
        }

        $fieldGroups = array_unique($fieldGroups);

        return $fieldGroups;
    }

    public function getCssCode(bool $isMinify = true, bool $isPreview = false): string
    {
        $cssCode = $this->cssCode;

        if ($isMinify) {
            $cssCode = $this->getMinifiedCss();

            $markupId = $this->getMarkupId();

            // do not use getBemName(), because it'll always return something
            $selector = $this->bemName ?: 'acf-view--id--' . $markupId;

            // magic shortcuts
            $cssCode = str_replace(
                '#view__',
                sprintf('.%s .%s__', $selector, $this->getBemName()),
                $cssCode
            );

            $cssCode = str_replace(
                '#view',
                sprintf('.%s', $selector),
                $cssCode
            );

            $cssCode = str_replace(
                '#__',
                sprintf('.%s__', $this->getBemName()),
                $cssCode
            );

            $cssCode = trim($cssCode);
        } elseif ($isPreview) {
            $cssCode = str_replace('#view__', sprintf('#view .%s__', $this->getBemName()), $cssCode);
        }

        return $cssCode;
    }

    public function saveToPostContent(array $postFields = [], bool $isSkipDefaults = false): bool
    {
        $isHasGutenberg = $this->isHasGutenbergBlock ?
            static::POST_VALUE_IS_HAS_GUTENBERG :
            '';

        $postFields = array_merge($postFields, [
            static::POST_FIELD_IS_HAS_GUTENBERG => $isHasGutenberg,
        ]);

        return parent::saveToPostContent($postFields, $isSkipDefaults);
    }

    public function getBemName(): string
    {
        $bemName = trim($this->bemName);

        if (!$bemName) {
            return 'acf-view';
        }

        return preg_replace('/[^a-z0-9\-_]/', '', $bemName);
    }

    public function getItemClass(string $suffix, FieldData $fieldData): string
    {
        $classes = [];

        $classes[] = $this->getBemName() . '__' . $fieldData->id . '-' . $suffix;

        if ($this->isWithCommonClasses) {
            $classes[] = $this->getBemName() . '__' . $suffix;
        }

        return implode(' ', $classes);
    }

    public function getTagName(string $prefix = ''): string
    {
        return parent::getTagName('acf-view');
    }

    /**
     * @return FieldData[]
     */
    public function getFieldsByType(string $type, bool $isGroupByLevel = false): array
    {
        $fitFields = [];
        $fitSubFields = [];

        foreach ($this->items as $item) {
            foreach ($item->repeaterFields as $repeaterField) {
                $isFit = $type === $repeaterField->getFieldMeta()->getType();

                if (!$isFit) {
                    continue;
                }

                $fitSubFields[] = $repeaterField;
            }

            $isFit = $type === $item->field->getFieldMeta()->getType();

            if (!$isFit) {
                continue;
            }

            $fitFields[] = $item->field;
        }

        $allFields = array_merge($fitFields, $fitSubFields);

        return !$isGroupByLevel ?
            $allFields :
            [
                'fields' => $fitFields,
                'subFields' => $fitSubFields,
                'all' => $allFields,
            ];
    }

    public function getItemSelector(
        FieldData $field,
        string $target,
        bool $isInnerTarget = false,
        bool $isSkipView = false
    ): string {
        $markupId = $this->getMarkupId();

        $selector = '';

        if (!$isSkipView) {
            $selector .= $this->bemName ?
                '.' . $this->bemName :
                sprintf(
                    '.%s--id--%s',
                    $this->getBemName(),
                    $markupId
                );
            $selector .= ' ';
        }

        $selector .= sprintf(
            '.%s__%s',
            esc_html($this->getBemName()),
            esc_html($field->id)
        );

        // target can be empty, in case we need the field itself
        if ((!$this->isWithUnnecessaryWrappers &&
                !$field->label &&
                !$isInnerTarget) ||
            !$target) {
            return $selector;
        }

        $selector = $this->isWithCommonClasses ?
            sprintf(
                '%s .%s__%s',
                esc_html($selector),
                esc_html($this->getBemName()),
                $target
            ) :
            sprintf(
                '%s .%s__%s-%s',
                esc_html($selector),
                esc_html($this->getBemName()),
                $field->id,
                $target
            );

        return $selector;
    }
}
