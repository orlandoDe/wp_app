<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Common;

defined('ABSPATH') || exit;

interface HooksInterface
{
    public function setHooks(bool $isAdmin): void;
}
