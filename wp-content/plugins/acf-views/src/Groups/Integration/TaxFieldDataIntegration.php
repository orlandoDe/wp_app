<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Groups\Integration;

use org\wplake\acf_views\Groups\TaxFieldData;

defined('ABSPATH') || exit;

class TaxFieldDataIntegration extends AcfIntegration
{
    protected function getTaxonomyChoices(): array
    {
        $taxChoices = [
            '' => __('Select', 'acf-views'),
        ];

        $taxonomies = get_taxonomies([], 'objects');

        foreach ($taxonomies as $taxonomy) {
            $taxChoices[$taxonomy->name] = $taxonomy->label;
        }

        return $taxChoices;
    }

    protected function getTermChoices(): array
    {
        $termChoices = [
            '' => __('Select', 'acf-views'),
            '$current$' => __('$current$ (archive and category pages)', 'acf-views'),
        ];

        $taxonomyNames = get_taxonomies([]);
        foreach ($taxonomyNames as $taxonomyName) {
            $terms = get_terms([
                'taxonomy' => $taxonomyName,
                'hide_empty' => false,
            ]);
            foreach ($terms as $term) {
                $fullTaxId = TaxFieldData::createKey($taxonomyName, $term->term_id);
                $termChoices[$fullTaxId] = $term->name;
            }
        }

        return $termChoices;
    }

    protected function setFieldChoices(): void
    {
        add_filter(
            'acf/load_field/name=' . TaxFieldData::getAcfFieldName(TaxFieldData::FIELD_TAXONOMY),
            function (array $field) {
                $field['choices'] = $this->getTaxonomyChoices();

                return $field;
            }
        );

        add_filter(
            'acf/load_field/name=' . TaxFieldData::getAcfFieldName(TaxFieldData::FIELD_TERM),
            function (array $field) {
                $field['choices'] = $this->getTermChoices();

                return $field;
            }
        );
    }
}
