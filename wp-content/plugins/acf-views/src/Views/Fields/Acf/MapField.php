<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views\Fields\Acf;

use org\wplake\acf_views\Groups\FieldData;
use org\wplake\acf_views\Groups\ItemData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Views\FieldMeta;
use org\wplake\acf_views\Views\Fields\MarkupField;

defined('ABSPATH') || exit;

class MapField extends MarkupField
{
    protected function isMultiple(FieldMeta $fieldMeta): bool
    {
        return 'google_map_multi' === $fieldMeta->getType() ||
            'open_street_map' === $fieldMeta->getType();
    }

    protected function getMapMarkerAttributes(
        ViewData $acfViewData,
        string $fieldId,
        string $itemFieldId,
        FieldData $field
    ): string {
        return sprintf(
            'class="%s" data-lat="{{ %s.lat }}" data-lng="{{ %2$s.lng }}"',
            esc_html($this->getItemClass('map-marker', $acfViewData, $field)),
            esc_html($itemFieldId),
        );
    }

    protected function getTwigArgsForGoogle(
        ViewData $acfViewData,
        ItemData $item,
        FieldData $field,
        FieldMeta $fieldMeta,
        $notFormattedValue,
        $formattedValue,
        bool $isForValidation = false
    ): array {
        $args = !$this->isMultiple($fieldMeta) ?
            [
                'value' => '',
                'lat' => 0,
                'lng' => 0,
            ] :
            [
                'value' => [],
            ];

        // common args
        $args = array_merge($args, [
            // set default values, so if the field has no markers, and showWhenEmpty flag,
            // then it can show the map in right position
            'zoom' => $fieldMeta->getZoom(),
            'center_lat' => $fieldMeta->getCenterLat(),
            'center_lng' => $fieldMeta->getCenterLng(),
        ]);

        if ($isForValidation) {
            $validationArgs = [
                'lat' => '1',
                'lng' => '1',
            ];

            if (!$this->isMultiple($fieldMeta)) {
                $validationArgs = array_merge($args, $validationArgs);

                return array_merge($validationArgs, [
                    'value' => '1',
                    'zoom' => '1',
                ]);
            }

            return array_merge($args, [
                'value' => [$validationArgs,],
            ]);
        }

        $notFormattedValue = $notFormattedValue ?
            (array)$notFormattedValue :
            [];

        if (!$notFormattedValue) {
            return $args;
        }


        if (!$this->isMultiple($fieldMeta)) {
            $args['value'] = !!($notFormattedValue['lat'] ?? '');
            $args['zoom'] = (string)($notFormattedValue['zoom'] ?? '16');
            $args['lat'] = (string)($notFormattedValue['lat'] ?? '');
            $args['lng'] = (string)($notFormattedValue['lng'] ?? '');
        } else {
            // the plugin doesn't support zoom, so use the default from the ACF field settings
            $args['zoom'] = $fieldMeta->getZoom();

            foreach ($notFormattedValue as $item) {
                $args['value'][] = [
                    'lat' => (string)($item['lat'] ?? ''),
                    'lng' => (string)($item['lng'] ?? ''),
                ];
            }
        }

        return $args;
    }

    protected function getTwigArgsForOS(
        ViewData $acfViewData,
        ItemData $item,
        FieldData $field,
        FieldMeta $fieldMeta,
        $notFormattedValue,
        $formattedValue,
        bool $isForValidation = false
    ): array {
        $args = [
            'value' => $field->isMapWithAddress ?
                [] :
                false,
            'map' => '',
        ];

        if ($isForValidation) {
            return array_merge($args, [
                'value' => $field->isMapWithAddress ?
                    [] :
                    true,
                'map' => '<iframe src="https://www.openstreetmap.org/export/embed.html?bbox=5.390371665521,50.7343356,14.857431134479,56.3593356&amp;marker=53.5500279,10.0136948" height="400" width="425" frameborder="0" scrolling="no" marginheight="0" marginwidth="0"></iframe>',
            ]);
        }

        switch ($fieldMeta->getReturnFormat()) {
            case 'leaflet';
            case 'osm':
                // used formatted value, as output already made by the plugin, and we just need to show it
                $args = array_merge($args, [
                    'map' => $formattedValue,
                ]);
                break;
        }

        // if withAddress, will be filled in the Pro class
        if (!$field->isMapWithAddress) {
            $markers = (array)($notFormattedValue['markers'] ?? []);
            $args['value'] = !!$markers;
        }

        return $args;
    }


    public function getMarkup(
        ViewData $acfViewData,
        string $fieldId,
        ItemData $item,
        FieldData $fieldData,
        FieldMeta $fieldMeta,
        int &$tabsNumber,
        bool $isWithFieldWrapper,
        bool $isWithRowWrapper
    ): string {
        if ('open_street_map' === $fieldMeta->getType()) {
            return sprintf('{{ %s.map|raw }}', esc_html($fieldId));
        }

        $currentTabsNumber = $tabsNumber;

        $markup = sprintf(
            '<div class="%s" style="width:100%%;height:400px;" data-zoom="{{ %s.zoom }}" data-center-lat="{{ %2$s.center_lat }}" data-center-lng="{{ %2$s.center_lng }}">',
            esc_html(
                $this->getFieldClass('map', $acfViewData, $fieldData, $isWithFieldWrapper, $isWithRowWrapper)
            ),
            esc_html($fieldId),
        );
        $markup .= "\r\n" . str_repeat("\t", ++$currentTabsNumber);

        if ($fieldData->isVisibleWhenEmpty &&
            !$this->isMultiple($fieldMeta)) {
            $markup .= sprintf('{%% if %s.value %%}', $fieldId);
            $markup .= "\r\n" . str_repeat("\t", ++$currentTabsNumber);
        }

        if ($this->isMultiple($fieldMeta)) {
            $markup .= sprintf("{%% for marker in %s.value %%}", esc_html($fieldId));
            $markup .= "\r\n" . str_repeat("\t", ++$currentTabsNumber);
        }

        $itemFieldId = !$this->isMultiple($fieldMeta) ?
            $fieldId :
            'marker';
        $markup .= sprintf(
            '<div %s></div>',
            $this->getMapMarkerAttributes($acfViewData, $fieldId, $itemFieldId, $fieldData)
        );

        if ($this->isMultiple($fieldMeta)) {
            $markup .= "\r\n";
            $markup .= str_repeat("\t", --$currentTabsNumber);
            $markup .= "{% endfor %}";
        }

        if ($fieldData->isVisibleWhenEmpty &&
            !$this->isMultiple($fieldMeta)) {
            $markup .= "\r\n" . str_repeat("\t", --$currentTabsNumber);
            $markup .= '{% endif %}';
        }

        $markup .= "\r\n" . str_repeat("\t", --$currentTabsNumber);
        $markup .= '</div>';

        return $markup;
    }

    public function getTwigArgs(
        ViewData $acfViewData,
        ItemData $item,
        FieldData $field,
        FieldMeta $fieldMeta,
        $notFormattedValue,
        $formattedValue,
        bool $isForValidation = false
    ): array {
        return 'open_street_map' !== $fieldMeta->getType() ?
            $this->getTwigArgsForGoogle(
                $acfViewData,
                $item,
                $field,
                $fieldMeta,
                $notFormattedValue,
                $formattedValue,
                $isForValidation
            ) :
            $this->getTwigArgsForOS(
                $acfViewData,
                $item,
                $field,
                $fieldMeta,
                $notFormattedValue,
                $formattedValue,
                $isForValidation
            );
    }


    public function isWithFieldWrapper(ViewData $acfViewData, FieldData $field, FieldMeta $fieldMeta): bool
    {
        return $acfViewData->isWithUnnecessaryWrappers ||
            'open_street_map' === $fieldMeta->getType();
    }
}
