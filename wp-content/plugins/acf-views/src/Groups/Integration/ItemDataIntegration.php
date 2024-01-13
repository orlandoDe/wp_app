<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Groups\Integration;

use org\wplake\acf_views\Groups\ItemData;

defined('ABSPATH') || exit;

class ItemDataIntegration extends AcfIntegration
{
    protected function setFieldChoices(): void
    {
        add_filter(
            'acf/load_field/name=' . ItemData::getAcfFieldName(ItemData::FIELD_GROUP),
            function (array $field) {
                $field['choices'] = $this->getGroupChoices();

                return $field;
            }
        );
    }
}