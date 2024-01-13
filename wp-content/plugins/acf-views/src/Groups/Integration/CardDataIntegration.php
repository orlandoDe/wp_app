<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Groups\Integration;

use org\wplake\acf_views\Groups\CardData;

defined('ABSPATH') || exit;

class CardDataIntegration extends AcfIntegration
{
    protected FieldDataIntegration $fieldIntegration;

    public function __construct(FieldDataIntegration $fieldIntegration)
    {
        $this->fieldIntegration = $fieldIntegration;
    }

    protected function getPostStatusChoices(): array
    {
        return get_post_statuses();
    }

    protected function setFieldChoices(): void
    {
        add_filter(
            'acf/load_field/name=' . CardData::getAcfFieldName(CardData::FIELD_ORDER_BY_META_FIELD_GROUP),
            function (array $field) {
                $field['choices'] = $this->getGroupChoices(false);

                return $field;
            }
        );

        add_filter(
            'acf/load_field/name=' . CardData::getAcfFieldName(CardData::FIELD_ORDER_BY_META_FIELD_KEY),
            function (array $field) {
                $field['choices'] = $this->fieldIntegration->getFieldChoices(false);

                return $field;
            }
        );

        add_filter(
            'acf/load_field/name=' . CardData::getAcfFieldName(CardData::FIELD_POST_TYPES),
            function (array $field) {
                $field['choices'] = $this->getPostTypeChoices();

                return $field;
            }
        );

        add_filter(
            'acf/load_field/name=' . CardData::getAcfFieldName(CardData::FIELD_POST_STATUSES),
            function (array $field) {
                $field['choices'] = $this->getPostStatusChoices();

                return $field;
            }
        );

        add_filter(
            'acf/load_field/name=' . CardData::getAcfFieldName(CardData::FIELD_POST_STATUSES),
            function (array $field) {
                $field['choices'] = $this->getPostStatusChoices();

                return $field;
            }
        );

        add_filter(
            'acf/load_field/name=' . CardData::getAcfFieldName(CardData::FIELD_ACF_VIEW_ID),
            function (array $field) {
                $field['choices'] = $this->getAcfViewChoices();

                return $field;
            }
        );
    }
}
