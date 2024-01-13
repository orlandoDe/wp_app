<?php

namespace org\wplake\acf_views\Views\Fields\Menu;

defined('ABSPATH') || exit;

class MenuFields
{
    const GROUP_NAME = '$menu$';
    // all fields have ids like 'field_x', so no conflicts possible
    const PREFIX = '_menu_';

    const FIELD_ITEMS = '_menu_items';
}
