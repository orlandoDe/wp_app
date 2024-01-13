<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Groups;

use org\wplake\acf_views\Common\Group;
use org\wplake\acf_views\Dashboard\Tools;

defined('ABSPATH') || exit;

class ToolsData extends Group
{
    // to fix the group name in case class name changes
    const CUSTOM_GROUP_NAME = self::GROUP_NAME_PREFIX . 'tools-data';

    const FIELD_EXPORT_VIEWS = 'exportViews';
    const FIELD_EXPORT_CARDS = 'exportCards';

    /**
     * @a-type tab
     * @label Export
     */
    public bool $export;
    /**
     * @a-type message
     * @message Note: Related ACF Groups won't be included, you must handle them separately.
     */
    public string $exportMessage;
    /**
     * @a-type true_false
     * @label Export All Views
     */
    public bool $isExportAllViews;
    /**
     * @a-type true_false
     * @label Export All Cards
     */
    public bool $isExportAllCards;

    /**
     * @a-type checkbox
     * @multiple 1
     * @label Export Views
     * @instructions Select Views to be exported
     * @conditional_logic [[{"field": "local_acf_views_tools-data__is-export-all-views","operator": "!=","value": "1"}]]
     */
    public array $exportViews;

    /**
     * @a-type checkbox
     * @multiple 1
     * @label Export Cards
     * @instructions Select Cards to be exported
     * @conditional_logic [[{"field": "local_acf_views_tools-data__is-export-all-cards","operator": "!=","value": "1"}]]
     */
    public array $exportCards;

    /**
     * @a-type tab
     * @label Import
     */
    public bool $import;

    /**
     * @a-type message
     * @message Important! First import the related ACF Field Groups in ACF/Tools then import your Views and Cards here.
     */
    public string $importMessage;

    /**
     * @a-type file
     * @return_format id
     * @mime_types .txt
     * @label Select a file to import
     * @instructions Note: Views and Cards with the same IDs are overridden.
     */
    public int $importFile;

    protected static function getLocationRules(): array
    {
        return [
            [
                'options_page == ' . Tools::SLUG,
            ]
        ];
    }

    public static function getGroupInfo(): array
    {
        $groupInfo = parent::getGroupInfo();

        // remove label for the 'message'
        unset($groupInfo['fields'][1]['label']);
        unset($groupInfo['fields'][7]['label']);


        return array_merge($groupInfo, [
            'title' => __('Tools', 'acf-views'),
            'style' => 'seamless',
        ]);
    }
}
