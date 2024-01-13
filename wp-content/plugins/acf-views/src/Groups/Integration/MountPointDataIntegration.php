<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Groups\Integration;

use org\wplake\acf_views\Groups\MountPointData;

defined('ABSPATH') || exit;

class MountPointDataIntegration extends AcfIntegration
{
    protected function setFieldChoices(): void
    {
        add_filter(
            'acf/load_field/name=' . MountPointData::getAcfFieldName(MountPointData::FIELD_POST_TYPES),
            function (array $field) {
                $field['choices'] = $this->getPostTypeChoices();

                return $field;
            }
        );
    }

}