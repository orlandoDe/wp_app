<?php

declare(strict_types=1);

namespace org\wplake\acf_views\FrontAsset;

use org\wplake\acf_views\Common\CptData;
use org\wplake\acf_views\Groups\FieldData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Plugin;

defined('ABSPATH') || exit;

class AcfViewsMapsFrontAsset extends ViewFrontAsset
{
    protected array $maps;

    public function __construct(Plugin $plugin)
    {
        parent::__construct($plugin);

        $this->jsHandles = [
            'acf-views-maps' => false,
        ];

        $this->targetFieldTypes = [
            'google_map',
            'google_map_multi',
            'open_street_map',
        ];

        $this->maps = [];
    }

    protected function isGoogleMapSelectorInner(FieldData $fieldData): bool
    {
        return false;
    }

    public function isTargetField(FieldData $fieldData): bool
    {
        return parent::isTargetField($fieldData) &&
            !$fieldData->isMapWithoutGoogleMap;
    }

    public function enqueueActive(): void
    {
        parent::enqueueActive();

        if (!$this->jsHandles['acf-views-maps']) {
            return;
        }

        $apiData = apply_filters('acf/fields/google_map/api', []);
        $key = $apiData['key'] ?? '';
        $key = (!$key && function_exists('acf_get_setting')) ?
            acf_get_setting('google_api_key') :
            $key;

        wp_localize_script(
            $this->getWpHandle('acf-views-maps'),
            'acfViewsMaps',
            $this->maps
        );

        wp_enqueue_script(
            $this->getWpHandle('google-maps'),
            sprintf('https://maps.googleapis.com/maps/api/js?key=%s&callback=acfViewsGoogleMaps', $key),
            [
                // setup deps, to make sure loaded only after plugin's maps.min.js
                $this->getWpHandle('acf-views-maps'),
            ],
            null,
            [
                'in_footer' => true,
                'strategy' => 'defer',
            ]
        );
    }

    public function maybeActivate(CptData $cptData): void
    {
        if (!($cptData instanceof ViewData)) {
            return;
        }

        $targetFields = $this->getTargetFields($cptData)['all'];

        if (!$targetFields) {
            return;
        }

        $isWithGoogleMap = false;

        /**
         * @var FieldData $mapField
         */
        foreach ($targetFields as $mapField) {
            if ('open_street_map' === $mapField->getFieldMeta()->getType()) {
                continue;
            }

            $isWithGoogleMap = true;
            $isInnerTarget = $this->isGoogleMapSelectorInner($mapField);
            $this->maps[] = $cptData->getItemSelector($mapField, 'map', $isInnerTarget);
        }

        // only google map requires it
        if ($isWithGoogleMap) {
            $this->jsHandles['acf-views-maps'] = true;
        }
    }
}
