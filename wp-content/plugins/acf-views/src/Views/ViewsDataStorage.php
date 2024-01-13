<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views;

use org\wplake\acf_views\Common\CptDataStorage;
use org\wplake\acf_views\Groups\ViewData;

defined('ABSPATH') || exit;

class ViewsDataStorage extends CptDataStorage
{
    public function __construct(ViewData $viewData)
    {
        parent::__construct($viewData);
    }

    // override just for settings the return type
    public function get(int $postId): ViewData
    {
        return parent::get($postId);
    }
}
