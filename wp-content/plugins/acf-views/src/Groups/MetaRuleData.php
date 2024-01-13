<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Groups;

use org\wplake\acf_views\Common\Group;

defined('ABSPATH') || exit;

class MetaRuleData extends Group
{
    // to fix the group name in case class name changes
    const CUSTOM_GROUP_NAME = self::GROUP_NAME_PREFIX . 'meta-rule';

    /**
     * @a-type select
     * @ui 1
     * @required 1
     * @label Relation
     * @instructions Controls how the meta fields will be joined within the meta rule
     * @choices {"AND":"AND","OR":"OR"}
     * @default_value AND
     * @conditional_logic [[{"field": "local_acf_views_meta-rule__fields","operator": ">","value": "1"}]]
     */
    public string $relation;
    /**
     * @var MetaFieldData[]
     * @item \org\wplake\acf_views\Groups\MetaFieldData
     * @button_label Add Field
     * @label Fields
     * @instructions Fields for the meta rule. Multiple fields are supported
     * @a-no-tab 1
     * @required 1
     */
    public array $fields;
}