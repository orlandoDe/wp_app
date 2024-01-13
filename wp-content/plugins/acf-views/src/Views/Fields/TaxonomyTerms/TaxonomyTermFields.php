<?php

namespace org\wplake\acf_views\Views\Fields\TaxonomyTerms;

defined('ABSPATH') || exit;

class TaxonomyTermFields
{
    const GROUP_NAME = '$taxonomy$';
    // all fields have ids like 'field_x', so no conflicts possible
    const PREFIX = '_taxonomy_';

    const FIELD_TERMS = '_taxonomy_terms';
}
