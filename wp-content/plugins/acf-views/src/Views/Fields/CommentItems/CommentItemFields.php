<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views\Fields\CommentItems;

defined('ABSPATH') || exit;

class CommentItemFields
{
    const GROUP_NAME = '$comment_items$';
    // all fields have ids like 'field_x', so no conflicts possible
    const PREFIX = '_comment_items_';

    public const FIELD_LIST = '_comment_items_list';
}
