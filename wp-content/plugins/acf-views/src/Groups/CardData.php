<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Groups;

use org\wplake\acf_views\Cards\Cpt\CardsCpt;
use org\wplake\acf_views\Common\CptData;
use org\wplake\acf_views\Plugin;
use org\wplake\acf_views\vendors\LightSource\AcfGroups\Interfaces\CreatorInterface;

defined('ABSPATH') || exit;

class CardData extends CptData
{
    // to fix the group name in case class name changes
    const CUSTOM_GROUP_NAME = self::GROUP_NAME_PREFIX . 'acf-card-data';
    const LOCATION_RULES = [
        [
            'post_type == ' . CardsCpt::NAME,
        ],
    ];

    const FIELD_MARKUP = 'markup';
    const FIELD_CSS_CODE = 'cssCode';
    const FIELD_JS_CODE = 'jsCode';
    const FIELD_QUERY_PREVIEW = 'queryPreview';
    const FIELD_EXTRA_QUERY_ARGUMENTS = 'extraQueryArguments';
    const FIELD_POST_TYPES = 'postTypes';
    const FIELD_POST_STATUSES = 'postStatuses';
    const FIELD_ORDER_BY_META_FIELD_GROUP = 'orderByMetaFieldGroup';
    const FIELD_ORDER_BY_META_FIELD_KEY = 'orderByMetaFieldKey';
    const FIELD_CUSTOM_MARKUP = 'customMarkup';
    const FIELD_ACF_VIEW_ID = 'acfViewId';
    const FIELD_ADVANCED_TAB = 'advancedTab';
    const FIELD_TEMPLATE_TAB = 'templateTab';
    const FIELD_CSS_AND_JS_TAB = 'cssAndJsTab';

    const PAGINATION_TYPE_LOAD_MORE_BUTTON = 'load_more_button';
    const PAGINATION_TYPE_INFINITY = 'infinity_scroll';
    const PAGINATION_TYPE_PAGE_NUMBERS = 'page_numbers';

    /**
     * @a-type tab
     * @label Basic
     */
    public bool $basicTab;
    /**
     * @a-type select
     * @ui 1
     * @allow_null 1
     * @label View
     * @required 1
     * @instructions Assigned View is used to display every post from the query results
     */
    public string $acfViewId;
    /**
     * @a-type select
     * @required 1
     * @multiple 1
     * @ui 1
     * @label Post Type
     * @instructions Filter by post type. You can select multiple items
     */
    public array $postTypes;
    /**
     * @a-type select
     * @required 1
     * @multiple 1
     * @ui 1
     * @label Post Status
     * @instructions Filter by post status. You can select multiple items
     * @default_value ["publish"]
     */
    public array $postStatuses;
    /**
     * @required 1
     * @label Maximum number of posts
     * @instructions Use '-1' to set 'unlimited'
     * @default_value -1
     */
    public int $limit;
    /**
     * @a-type select
     * @required 1
     * @label Sort by
     * @instructions Select which field results should be sorted by. ‘Default’ keeps the default order (latest first, sticky options may affect it)
     * @choices {"none":"Default","ID":"ID","menu_order":"Menu order","meta_value":"Meta value","meta_value_num":"Meta value numeric","author":"Author","title":"Title","name":"Name","type":"Type","date":"Date","modified":"Modified","parent":"Parent","rand":"Random","comment_count":"Comment count","post__in":"Pool of posts"}
     * @default_value none
     */
    public string $orderBy;
    /**
     * @a-type select
     * @return_format value
     * @required 1
     * @ui 1
     * @label Sort by Meta Field Group
     * @instructions Select a target group
     * @conditional_logic [[{"field": "local_acf_views_acf-card-data__order-by","operator": "==","value": "meta_value"}],[{"field": "local_acf_views_acf-card-data__order-by","operator": "==","value": "meta_value_num"}]]
     */
    public string $orderByMetaFieldGroup;
    /**
     * @a-type select
     * @return_format value
     * @label Sort by Meta Field
     * @required 1
     * @instructions Select a target field
     * @conditional_logic [[{"field": "local_acf_views_acf-card-data__order-by","operator": "==","value": "meta_value"}],[{"field": "local_acf_views_acf-card-data__order-by","operator": "==","value": "meta_value_num"}]]
     */
    public string $orderByMetaFieldKey;
    /**
     * @a-type select
     * @required 1
     * @label Sort order
     * @instructions Defines the sorting order of posts
     * @choices {"ASC":"Ascending","DESC":"Descending"}
     * @default_value ASC
     */
    public string $order;

    /**
     * @a-type tab
     * @label Advanced
     */
    public bool $advancedTab;
    /**
     * @a-type textarea
     * @label Description
     * @instructions Add a short description for your views’ purpose. Only seen on the admin Cards list
     */
    public string $description;
    /**
     * @label No Posts Found Message
     * @instructions This message will be displayed in case there are no posts found. Leave empty to not show a message
     * @default_value No posts found
     */
    public string $noPostsFoundMessage;
    /**
     * @a-type post_object
     * @return_format id
     * @label Pool of posts
     * @multiple 1
     * @instructions Here you can manually assign specific posts. If set then the query will be limited to posts ONLY from this pool. It means the result will consist ONLY from posts from this pool, which also fit all other filters. If you want to have the same order of results like here, please choose the 'Pool of posts' option in the Sort tab
     */
    public array $postIn;
    /**
     * @a-type post_object
     * @return_format id
     * @label Exclude posts
     * @instructions  Here you can manually exclude specific posts from the query. It means the query will ignore posts from this list, even if they fit the filters. Warning : this field can't be used together with 'Pool of posts'
     * @multiple 1
     */
    public array $postNotIn;
    /**
     * @label Ignore Sticky Posts
     * @instructions If unchecked then sticky posts will be at the top of results. <a target='_blank' href='https://wordpress.org/support/article/sticky-posts/'>Learn more about Sticky Posts</a>
     */
    public bool $isIgnoreStickyPosts;
    /**
     * @label Without Web Component
     * @instructions By default, every Card is a <a target='_blank' href='https://kinsta.com/blog/web-components/'>web component</a>, which allows you to work easily with the element in the JS code field
     */
    public bool $isWithoutWebComponent;
    /**
     * @a-type true_false
     * @label Use the Post ID as the Card ID in the markup
     * @instructions Note: For backward compatibility purposes only. Enable this option if you have external CSS selectors that rely on outdated digital IDs
     */
    public bool $isMarkupWithDigitalId;
    /**
     * @a-type textarea
     * @label Query Preview
     * @instructions For debug purposes. Here you can see the query that will be executed to get posts for this card. Important! Publish or Update your card and reload the page to see the latest query
     */
    public string $queryPreview;
    /**
     * @a-type textarea
     * @label Extra Query Arguments
     * @instructions Add extra arguments to the <a target='_blank' href='https://developer.wordpress.org/reference/classes/wp_query/#parameters'>WP_Query instance</a> that missing in the UI using this PHP code snippet. <br>The snippet must return an associative array, which will be merged with the settings from the UI. <br> Press Ctrl (Cmd) + Alt + L to format the code. Press Ctrl + F to search (or replace). <br> In the snippet, the following variables are predefined: '&#36;_args' (current query args), '&#36;_pageNumber' (useful if the pagination feature is active)
     * @default_value <?php return [];
     * @a-pro The field must be not required or have default value!
     */
    public string $extraQueryArguments;

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
     * @instructions Write your own template with full control over the HTML markup. <br> You can copy the Template Preview field output and make your changes, such as adding an extra heading. <br><br> Powerful <a target='_blank' href='https://docs.acfviews.com/templates/twig-templates'>Twig features</a>, including <a target='_blank' href='https://docs.acfviews.com/templates/twig-templates#our-functions'>our functions</a>, are available for you. <br><br> Press Ctrl (Cmd) + Alt + L to format the code. Press Ctrl + F to search (or replace). <br><br> Make sure you've retained all the default classes; otherwise, pagination won't work.
     */
    public string $customMarkup;
    /**
     * @label BEM Unique Name
     * @instructions Define a unique <a target='_blank' href='https://getbem.com/introduction/'>BEM name</a> for the element that will be used in the markup, or leave it empty to use the default ('acf-card').
     */
    public string $bemName;
    /**
     * @label CSS classes
     * @instructions Add a class name without a dot (e.g. 'class-name') or multiple classes with single space as a delimiter (e.g. 'class-name1 class-name2'). These classes are added to the wrapping HTML element. <a target='_blank' href='https://www.w3schools.com/cssref/sel_class.asp'>Learn more about CSS Classes</a>
     */
    public string $cssClasses;

    /**
     * @a-type tab
     * @label CSS & JS
     */
    public bool $cssAndJsTab;
    /**
     * @a-type textarea
     * @label CSS Code
     * @instructions Define your CSS style rules. <br> This will be added within &lt;style&gt;&lt;/style&gt; tags ONLY to pages that have this card. <br><br> Press Ctrl (Cmd) + Alt + L to format the code. Press Ctrl + F to search (or replace). <br><br> Don't style view fields here, View has its own CSS field for this goal. <br><br> Magic shortcuts are available (and will use the BEM Unique Name if defined) : <br><br> '#card' will be replaced with '.acf-card--id--X' (or '.bem-name').<br><br> '#card__' will be replaced with '.acf-card--id--X .acf-card__' (or '.bem-name .bem-name__'). <br><br> '#__' will be replaced with '.acf-card__' (or '.bem-name__'). <br><br> To match items wrapper you should use '#card__items' selector, to match single item you should use '#card .acf-view' selector
     * /
     */
    public string $cssCode;
    /**
     * @a-type textarea
     * @label JS Code
     * @instructions Add your custom Javascript to your Card.<br><br> By default, the Card is a <a target='_blank' href='https://kinsta.com/blog/web-components/'>web component</a>, so this code will be executed once for every instance, and 'this', that refers to the current instance, is available. <br><br> The code snippet will be added within &lt;script&gt;&lt;/script&gt; tags ONLY to pages that have this Card and also will be wrapped into an anonymous function to avoid name conflicts. <br><br> Press Ctrl (Cmd) + Alt + L to format the code. Press Ctrl + F to search (or replace). <br> Don't use inline comments ('//') inside the code, otherwise it'll break the snippet.
     */
    public string $jsCode;

    /**
     * @a-type tab
     * @label Layout
     */
    public bool $layoutTab;
    /**
     * @a-type select
     * @label Enable Slider
     * @instructions Select the slider library to enable. <br> Customize the slider after saving, by editing the JS Code in the CSS & JS tab
     * @choices {"none":"None","splide_v4":"Splide v4 (29.8KB js, 5KB css)"}
     * @default_value none
     * @a-pro The field must be not required or have default value!
     */
    public string $sliderType;
    /**
     * @label Enable Layout rules
     * @instructions When enabled CSS layout styles are added to CSS Code in the Advanced tab. These styles are automatically updated each time. <br>Tip: If you’d like to edit the Layout CSS manually, simply disable this here. Disabling this does not remove the previously added CSS Code
     */
    public bool $isUseLayoutCss;
    /**
     * @var CardLayoutData[]
     * @item \org\wplake\acf_views\Groups\CardLayoutData
     * @label Layout Rules
     * @instructions The rules control layout of card items. <br>Note: These rules are inherited from small to large. For example: If you’ve set up 'Mobile' and 'Desktop' screen rules, then 'Tablet' will have the same rules as 'Mobile' and 'Large Desktop' will have the same rules as 'Desktop'
     * @button_label Add Rule
     * @a-no-tab 1
     */
    public array $layoutRules;

    /**
     * @a-type tab
     * @label Meta Filters
     * @a-pro 1
     */
    public bool $metaFiltersTab;
    /**
     * @a-no-tab 1
     * @display seamless
     */
    public MetaFilterData $metaFilter;

    /**
     * @a-type tab
     * @label Taxonomy Filters
     * @a-pro 1
     */
    public bool $taxFiltersTab;
    /**
     * @label Rules
     * @a-no-tab 1
     * @display seamless
     */
    public TaxFilterData $taxFilter;

    /**
     * @a-type tab
     * @label Pagination
     * @a-pro 1
     */
    public bool $paginationTab;
    /**
     * @label With Pagination
     * @instructions If enabled then instead of displaying all posts from query results, only the limited number of posts will be shown and user will be able to load more. <a target='_blank' href='https://docs.acfviews.com/query-content/pagination-pro'>Read more</a>
     * @a-pro The field must be not required or have default value!
     */
    public bool $isWithPagination;
    /**
     * @a-type select
     * @required 1
     * @label Pagination Type
     * @instructions Defines a way in which user can load more. For 'Load More Button' and 'Page Numbers' cases a special markup will be added to the card automatically, you can style it (using the 'CSS Code' field in the 'Advanced' tab)
     * @choices {"load_more_button":"Load More Button","infinity_scroll":"Infinity Scroll","page_numbers":"Page Numbers"}
     * @default_value load_more_button
     * @a-pro The field must be not required or have default value!
     */
    public string $paginationType;
    /**
     * @label Label for the 'Load More' button
     * @required 1
     * @default_value Load more
     * @conditional_logic [[{"field": "local_acf_views_acf-card-data__pagination-type","operator": "==","value": "load_more_button"}]]
     * @a-pro The field must be not required or have default value!
     */
    public string $loadMoreButtonLabel;
    /**
     * @label Posts Per Page
     * @instructions Controls how many posts will be displayed initially and how many posts will be appended every time when user triggers 'Load More'. Total amount of post is limited by the 'Maximum amount of posts' field in the 'Filter' tab
     * @required 1
     * @default_value 9
     * @a-pro The field must be not required or have default value!
     */
    public int $paginationPerPage;

    /**
     * @a-type tab
     * @label Preview
     */
    public bool $previewTab;
    /**
     * @label Preview
     * @instructions See an output preview of your Card, where you can test some CSS styles. <a target='_blank' href='https://docs.acfviews.com/getting-started/introduction/plugin-interface#preview-1'>Read more</a> <br> Styles from your front page are included in the preview (some differences may appear). <br>Note: Press 'Update' if you have changed Custom Markup (in the Advanced tab) to see the latest preview. <br> Important! Don't style your View here, instead use the CSS Code field in your View for this goal. <br> After testing: Copy and paste the Card styles to the CSS Code field.
     * @placeholder Loading... Please wait a few seconds
     * @disabled 1
     */
    public string $preview;

    // cache
    private string $noPostsFoundMessageTranslation;
    private string $loadMoreButtonLabelTranslation;

    public function __construct(CreatorInterface $creator)
    {
        parent::__construct($creator);

        $this->noPostsFoundMessageTranslation = '';
        $this->loadMoreButtonLabelTranslation = '';
    }

    public static function getGroupInfo(): array
    {
        return array_merge(parent::getGroupInfo(), [
            'title' => __('Card settings', 'acf-views'),
        ]);
    }

    protected function getUsedItems(): array
    {
        return [$this->acfViewId,];
    }

    public function getOrderByMetaAcfFieldId(): string
    {
        return FieldData::getAcfFieldIdByKey($this->orderByMetaFieldKey);
    }

    public function getCssCode(bool $isMinify = true, bool $isPreview = false): string
    {
        $cssCode = $this->cssCode;

        if ($isMinify) {
            $cssCode = $this->getMinifiedCss();

            $markupId = $this->getMarkupId();

            // do not use getBemName(), because it'll always return something
            $selector = $this->bemName ?: 'acf-card--id--' . $markupId;

            // magic shortcuts
            $cssCode = str_replace(
                '#card__',
                sprintf('.%s .%s__', $selector, $this->getBemName()),
                $cssCode
            );

            $cssCode = str_replace(
                '#card',
                sprintf('.%s', $selector),
                $cssCode
            );

            $cssCode = str_replace(
                '#__',
                sprintf('.%s__', $this->getBemName()),
                $cssCode
            );
        } elseif ($isPreview) {
            $cssCode = str_replace('#card__', sprintf('#card .%s__', $this->getBemName()), $cssCode);
        }

        // back the right way, as before it was hack for CodeMirror
        $cssCode = str_replace('"1fr"', '1fr', $cssCode);
        $cssCode = trim($cssCode);

        return $cssCode;
    }

    public function getBemName(): string
    {
        $bemName = trim($this->bemName);

        if (!$bemName) {
            return 'acf-card';
        }

        return preg_replace('/[^a-z0-9\-_]/', '', $bemName);
    }

    public function getNoPostsFoundMessageTranslation(): string
    {
        if ($this->noPostsFoundMessage &&
            !$this->noPostsFoundMessageTranslation) {
            $this->noPostsFoundMessageTranslation = Plugin::getLabelTranslation($this->noPostsFoundMessage);
        }

        return $this->noPostsFoundMessageTranslation;
    }

    public function getLoadMoreButtonLabelTranslation(): string
    {
        if ($this->loadMoreButtonLabel &&
            !$this->loadMoreButtonLabelTranslation) {
            $this->loadMoreButtonLabelTranslation = Plugin::getLabelTranslation($this->loadMoreButtonLabel);
        }

        return $this->loadMoreButtonLabelTranslation;
    }

    public function getTagName(string $prefix = ''): string
    {
        return parent::getTagName('acf-card');
    }
}
