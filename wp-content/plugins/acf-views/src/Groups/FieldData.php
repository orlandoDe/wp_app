<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Groups;

use org\wplake\acf_views\Common\Group;
use org\wplake\acf_views\Plugin;
use org\wplake\acf_views\vendors\LightSource\AcfGroups\Interfaces\CreatorInterface;
use org\wplake\acf_views\Views\FieldMeta;

defined('ABSPATH') || exit;

class FieldData extends Group
{
    // to fix the group name in case class name changes
    const CUSTOM_GROUP_NAME = self::GROUP_NAME_PREFIX . 'field';
    const FIELD_KEY = 'key';
    const FIELD_ID = 'id';
    const FIELD_LINK_LABEL = 'linkLabel';
    const FIELD_IS_LINK_TARGET_BLANK = 'isLinkTargetBlank';
    const FIELD_IMAGE_SIZE = 'imageSize';
    const FIELD_ACF_VIEW_ID = 'acfViewId';
    const FIELD_GALLERY_TYPE = 'galleryType';
    const FIELD_MASONRY_ROW_MIN_HEIGHT = 'masonryRowMinHeight';
    const FIELD_MASONRY_GUTTER = 'masonryGutter';
    const FIELD_MASONRY_MOBILE_GUTTER = 'masonryMobileGutter';
    const FIELD_LIGHTBOX_TYPE = 'lightboxType';
    const FIELD_SLIDER_TYPE = 'sliderType';
    const FIELD_GALLERY_WITH_LIGHT_BOX = 'galleryWithLightBox';
    const FIELD_MAP_MARKER_ICON = 'mapMarkerIcon';
    const FIELD_MAP_MARKER_ICON_TITLE = 'mapMarkerIconTitle';
    const FIELD_MAP_ADDRESS_FORMAT = 'mapAddressFormat';
    const FIELD_IS_MAP_WITH_ADDRESS = 'isMapWithAddress';
    const FIELD_IS_MAP_WITHOUT_GOOGLE_MAP = 'isMapWithoutGoogleMap';
    const FIELD_OPTIONS_DELIMITER = 'optionsDelimiter';

    /**
     * @a-type select
     * @return_format value
     * @required 1
     * @label Field
     * @instructions Select a target field
     * @a-order 2
     */
    public string $key;
    /**
     * @label Label
     * @instructions If filled will be added to the markup as a prefix label of the field above
     * @a-order 2
     */
    public string $label;
    /**
     * @label Link Label
     * @instructions You can set the link label here. Leave empty to use the default
     * @a-order 2
     */
    public string $linkLabel;
    /**
     * @label Image Size
     * @instructions Controls the size of the image, it changes the image src
     * @a-type select
     * @default_value full
     * @a-order 2
     */
    public string $imageSize;
    /**
     * @a-type select
     * @ui 1
     * @allow_null 1
     * @label View
     * @instructions If filled then data within this field will be displayed using the selected View. <a target='_blank' href='https://docs.acfviews.com/display-acf-fields/relational-group/relationship#display-fields-from-related-post-pro-feature'>Read more</a>
     * @a-order 2
     * @a-pro The field must be not required or have default value!
     */
    public string $acfViewId;
    /**
     * @a-type select
     * @label Gallery layout
     * @instructions Select the gallery layout type. Customize the layout after saving, by editing the JS Code in the CSS & JS tab
     * @choices {"plain":"None","macy_v2":"Classic Masonry (macy v2, 10.6KB js)", "masonry":"Flat Masonry (acf-views, 4.9KB js)", "lightgallery_v2":"Inline-Gallery (lightgallery v2, 47.1KB js, 9.2KB css)"}
     * @default_value plain
     * @a-order 2
     * @a-pro The field must be not required or have default value!
     */
    public string $galleryType;
    /**
     * @a-type select
     * @label Enable Lightbox
     * @instructions Select the lightbox library to enable. Customize the lightbox after saving, by editing the JS Code in the CSS & JS tab
     * @choices {"none":"None","lightgallery_v2":"LightGallery v2 (47.1KB js, 9.2KB css)", "simple":"Simple (no settings, 5.2KB js)"}
     * @default_value none
     * @a-order 2
     * @a-pro The field must be not required or have default value!
     */
    public string $lightboxType;
    /**
     * @a-type select
     * @label Enable Slider
     * @instructions Select the slider library to enable. <br> Customize the slider after saving, by editing the JS Code in the CSS & JS tab
     * @choices {"none":"None","splide_v4":"Splide v4 (29.8KB js, 5KB css)"}
     * @default_value none
     * @a-order 2
     * @a-pro The field must be not required or have default value!
     */
    public string $sliderType;

    /**
     * @a-type tab
     * @label Field Options
     * @a-order 4
     */
    public bool $advancedTab;
    /**
     * @label Identifier
     * @instructions Used in the markup. <br> Allowed symbols : letters, numbers, underline and dash. <br> Important! Should be unique within the View
     * @a-order 6
     */
    public string $id;
    /**
     * @label Default Value
     * @instructions Set up default value, only used when the field is empty
     * @a-order 6
     */
    public string $defaultValue;
    /**
     * @label Show When Empty
     * @instructions By default, empty fields are hidden. <br> Turn on to show even when field has no value
     * @a-order 6
     */
    public bool $isVisibleWhenEmpty;
    /**
     * @label Open link in a new tab
     * @instructions By default, this setting is inherited from ACF, if available. Turn it on to always open in a new tab
     * @a-order 6
     */
    public bool $isLinkTargetBlank;
    /**
     * @label Map Marker Icon
     * @instructions Customize the Map Marker by using your own icon or uploading an image from <a target='_blank' href='https://www.flaticon.com/free-icons/google-maps'>Flaticon</a> (.png, .jpg allowed). <br> Dimensions of 32x32px is recommended
     * @a-type image
     * @return_format id
     * @a-order 6
     * @a-pro The field must be not required or have default value!
     */
    public int $mapMarkerIcon;
    /**
     * @label Map Marker icon title
     * @instructions Shown when mouse hovers on Map Marker
     * @a-order 6
     * @a-pro The field must be not required or have default value!
     */
    public string $mapMarkerIconTitle;
    /**
     * @label Hide Map
     * @instructions The Map is shown by default. Turn this on to hide the map
     * @a-order 6
     * @a-pro The field must be not required or have default value!
     */
    public bool $isMapWithoutGoogleMap;
    /**
     * @label Show address from the map
     * @instructions The address is hidden by default. Turn this on to show the address from the map
     * @a-order 6
     * @a-pro The field must be not required or have default value!
     */
    public bool $isMapWithAddress;
    /**
     * @label Values delimiter
     * @instructions If multiple values are chosen, you can define their delimiter here. HTML is supported
     * @a-order 6
     */
    public string $optionsDelimiter;

    // DO NOT USE THESE FIELDS, THEY'RE DEPRECATED!
    /**
     * @label Map address format
     * @instructions Use these variables to format your map address: <br> &#36;street_number&#36;, &#36;street_name&#36;, &#36;city&#36;, &#36;state&#36;, &#36;post_code&#36;, &#36;country&#36; <br> HTML is also supported. If left empty the address is not shown.
     * @a-order 6
     * @a-pro The field must be not required or have default value!
     * @a-deprecated DO NOT USE THIS FIELD, IT'S DEPRECATED!
     */
    public string $mapAddressFormat;
    /**
     * @label Masonry: Row Min Height
     * @instructions Minimum height of a row in px
     * @a-order 6
     * @a-pro The field must be not required or have default value!
     * @a-deprecated DO NOT USE THIS FIELD, IT'S DEPRECATED!
     */
    public int $masonryRowMinHeight;
    /**
     * @label Masonry: Gutter
     * @instructions Margin between items in px
     * @a-order 6
     * @a-pro The field must be not required or have default value!
     * @a-deprecated DO NOT USE THIS FIELD, IT'S DEPRECATED!
     */
    public int $masonryGutter;
    /**
     * @label Masonry: Mobile Gutter
     * @instructions Margin between items on mobile in px
     * @a-order 6
     * @a-pro The field must be not required or have default value!
     * @a-deprecated DO NOT USE THIS FIELD, IT'S DEPRECATED!
     */
    public int $masonryMobileGutter;
    /**
     * @label With Lightbox
     * @instructions If enabled, image(s) will include a zoom icon on hover, and when clicked, a popup with a larger image will appear
     * @a-order 2
     * @a-pro The field must be not required or have default value!
     * @a-deprecated DO NOT USE THIS FIELD, IT'S DEPRECATED!
     */
    public bool $galleryWithLightBox;

    // cache
    private string $labelTranslation;
    private string $linkLabelTranslation;
    private ?FieldMeta $fieldMeta;

    public function __construct(CreatorInterface $creator)
    {
        parent::__construct($creator);

        $this->labelTranslation = '';
        $this->linkLabelTranslation = '';
        $this->fieldMeta = null;
    }

    public static function getAcfFieldIdByKey(string $key): string
    {
        $fieldId = explode('|', $key);

        // group, field, [subField]
        return 3 === count($fieldId) ?
            $fieldId[2] :
            ($fieldId[1] ?? '');
    }

    public static function createKey(string $group, string $field, string $subField = ''): string
    {
        $fullFieldId = $group . '|' . $field;

        $fullFieldId .= $subField ?
            '|' . $subField :
            '';

        return $fullFieldId;
    }

    public function getAcfFieldId(): string
    {
        return self::getAcfFieldIdByKey($this->key);
    }

    public function getTwigFieldId(): string
    {
        return str_replace('-', '_', $this->id);
    }

    public function getLabelTranslation(): string
    {
        if ($this->label &&
            !$this->labelTranslation) {
            $this->labelTranslation = Plugin::getLabelTranslation($this->label);
        }

        return $this->labelTranslation;
    }

    public function getLinkLabelTranslation(): string
    {
        if ($this->linkLabel &&
            !$this->linkLabelTranslation) {
            $this->linkLabelTranslation = Plugin::getLabelTranslation($this->linkLabel);
        }

        return $this->linkLabelTranslation;
    }

    public function getFieldMeta(): FieldMeta
    {
        if (!$this->fieldMeta) {
            $this->fieldMeta = new FieldMeta($this->getAcfFieldId());
        }

        return $this->fieldMeta;
    }

    // note: for tests only!
    public function setFieldsMeta(FieldMeta $fieldMeta): void
    {
        $this->fieldMeta = $fieldMeta;
    }
}
