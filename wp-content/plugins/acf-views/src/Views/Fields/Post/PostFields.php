<?php

namespace org\wplake\acf_views\Views\Fields\Post;

defined('ABSPATH') || exit;

class PostFields
{
    const GROUP_NAME = '$post$';
    // all fields have ids like 'field_x', so no conflicts possible
    const PREFIX = '_post_';

    public const FIELD_TITLE = '_post_title';
    public const FIELD_AUTHOR = '_post_author';
    public const FIELD_TITLE_LINK = '_post_title_link';
    public const FIELD_THUMBNAIL = '_post_thumbnail';
    public const FIELD_THUMBNAIL_LINK = '_post_thumbnail_link';
    public const FIELD_CONTENT = '_post_content';
    public const FIELD_EXCERPT = '_post_excerpt';
    public const FIELD_DATE = '_post_date';
    public const FIELD_MODIFIED = '_post_modified';
}
