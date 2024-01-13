<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Groups;

use org\wplake\acf_views\Common\Group;

defined('ABSPATH') || exit;

class MetaFilterData extends Group
{
    // to fix the group name in case class name changes
    const CUSTOM_GROUP_NAME = self::GROUP_NAME_PREFIX . 'meta-filter';

    /**
     * @a-type select
     * @ui 1
     * @required 1
     * @label Relation
     * @instructions Controls how meta rules will be joined within the meta query
     * @choices {"AND":"AND","OR":"OR"}
     * @default_value AND
     * @conditional_logic [[{"field": "local_acf_views_meta-filter__rules","operator": ">","value": "1"}]]
     * @a-pro The field must be not required or have default value!
     */
    public string $relation;
    /**
     * @var MetaRuleData[]
     * @item \org\wplake\acf_views\Groups\MetaRuleData
     * @label Rules
     * @instructions Rules for the meta query. Multiple rules are supported. <a target='_blank' href='https://docs.acfviews.com/query-content/meta-filters-pro'>Read more</a> <br>If you want to see the query that was created by your input, update the Card and reload the page. After have a look at the 'Query Preview' field in the 'Advanced' tab
     * @button_label Add Rule
     * @a-no-tab 1
     * @layout block
     * @a-pro The field must be not required or have default value!
     */
    public array $rules;
}