<?php

namespace org\wplake\acf_views\Views\Fields\MenuItem;

defined('ABSPATH') || exit;

class MenuItemFields
{
    const GROUP_NAME = '$menu_item$';
    // all fields have ids like 'field_x', so no conflicts possible
    const PREFIX = '_menu_item_';

    const FIELD_LINK = '_menu_item_link';
}
