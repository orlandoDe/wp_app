<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Groups\Integration;

use org\wplake\acf_views\Groups\MetaFieldData;
use org\wplake\acf_views\Views\Fields\Woo\WooFields;

defined('ABSPATH') || exit;

class MetaFieldDataIntegration extends AcfIntegration
{
    protected FieldDataIntegration $fieldIntegration;

    public function __construct(FieldDataIntegration $fieldIntegration)
    {
        $this->fieldIntegration = $fieldIntegration;
    }

    protected function setFieldChoices(): void
    {
        add_filter(
            'acf/load_field/name=' . MetaFieldData::getAcfFieldName(MetaFieldData::FIELD_GROUP),
            function (array $field) {
                // should include Woo group
                $field['choices'] = $this->getGroupChoices(true, [
                    WooFields::GROUP_NAME,
                ]);

                return $field;
            }
        );

        add_filter(
            'acf/load_field/name=' . MetaFieldData::getAcfFieldName(MetaFieldData::FIELD_FIELD_KEY),
            function (array $field) {
                // with all extra, as there is no way to define 'woo' only
                // it's not a problem, as related groups are excluded in the FIELD_GROUP filter,
                // so they won't be available for select anyway
                $field['choices'] = $this->fieldIntegration->getFieldChoices(true);

                return $field;
            }
        );
    }
}