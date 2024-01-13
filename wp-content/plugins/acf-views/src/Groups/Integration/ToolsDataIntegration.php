<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Groups\Integration;

use org\wplake\acf_views\Cards\Cpt\CardsCpt;
use org\wplake\acf_views\Groups\ToolsData;
use WP_Query;

defined('ABSPATH') || exit;

class ToolsDataIntegration extends AcfIntegration
{
    protected function getAcfCardChoices(): array
    {
        $acfCards = new WP_Query([
            'post_type' => CardsCpt::NAME,
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ]);
        $acfCards = $acfCards->get_posts();

        $acfCardChoices = [];

        foreach ($acfCards as $acfCard) {
            $acfCardChoices[$acfCard->post_name] = $acfCard->post_title;
        }

        return $acfCardChoices;
    }

    protected function setFieldChoices(): void
    {
        add_filter(
            'acf/load_field/name=' . ToolsData::getAcfFieldName(ToolsData::FIELD_EXPORT_VIEWS),
            function (array $field) {
                $field['choices'] = $this->getAcfViewChoices();

                return $field;
            }
        );

        add_filter(
            'acf/load_field/name=' . ToolsData::getAcfFieldName(ToolsData::FIELD_EXPORT_CARDS),
            function (array $field) {
                $field['choices'] = $this->getAcfCardChoices();

                return $field;
            }
        );
    }
}