<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Groups;

use org\wplake\acf_views\Common\Group;

defined('ABSPATH') || exit;

class ItemData extends Group
{
    // to fix the group name in case class name changes
    const CUSTOM_GROUP_NAME = self::GROUP_NAME_PREFIX . 'item';
    const FIELD_GROUP = 'group';
    const FIELD_REPEATER_FIELDS_TAB = 'repeaterFieldsTab';
    const FIELD_FIELD = 'field';
    const FIELD_REPEATER_FIELDS = 'repeaterFields';

    /**
     * @a-type tab
     * @label Field
     * @a-order 2
     */
    public bool $fieldTab;
    /**
     * @a-type select
     * @return_format value
     * @required 1
     * @ui 1
     * @label Group
     * @instructions Select a target group
     * @a-order 2
     */
    public string $group;
    /**
     * @display seamless
     * @a-order 2
     * @a-no-tab 1
     */
    public FieldData $field;

    /**
     * @a-type tab
     * @placement top
     * @label Sub Fields
     * @a-order 3
     * @a-pro 1
     */
    public bool $repeaterFieldsTab;
    /**
     * @item \org\wplake\acf_views\Groups\RepeaterFieldData
     * @var RepeaterFieldData[]
     * @label Sub fields
     * @instructions Setup sub fields here
     * @button_label Add Sub Field
     * @layout block
     * @collapsed local_acf_views_field__key
     * @a-no-tab 1
     * @a-order 3
     * @a-pro The field must be not required or have default value!
     */
    public array $repeaterFields;
}
