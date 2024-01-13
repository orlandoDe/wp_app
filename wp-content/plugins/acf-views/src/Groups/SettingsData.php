<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Groups;

use org\wplake\acf_views\Common\Group;
use org\wplake\acf_views\Dashboard\SettingsPage;

defined('ABSPATH') || exit;

class SettingsData extends Group
{
    // to fix the group name in case class name changes
    const CUSTOM_GROUP_NAME = self::GROUP_NAME_PREFIX . 'settings-data';

    const FIELD_IS_DEV_MODE = 'isDevMode';

    /**
     * @a-type tab
     * @label General
     */
    public bool $general;
    /**
     * @label Development mode
     * @instructions Enable to display quick access links on the front and make error messages more detailed (for admins only).
     */
    public bool $isDevMode;

    protected static function getLocationRules(): array
    {
        return [
            [
                'options_page == ' . SettingsPage::SLUG,
            ]
        ];
    }

    public static function getGroupInfo(): array
    {
        $groupInfo = parent::getGroupInfo();

        return array_merge($groupInfo, [
            'title' => __('Settings', 'acf-views'),
            'style' => 'seamless',
        ]);
    }
}
