<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views;

use org\wplake\acf_views\Views\Fields\TaxonomyTerms\TaxonomyTermFields;

defined('ABSPATH') || exit;

class FieldMeta implements FieldMetaInterface
{
    private string $fieldId;
    private string $name;
    private string $type;
    private string $returnFormat;
    private array $choices;
    private array $fieldData;
    private bool $isFieldExist;
    private string $displayFormat;
    private bool $isMultiple;
    private string $appearance;
    /**
     * @var array|string
     */
    private $defaultValue;
    private array $customArgs;
    private int $zoom;
    private string $centerLat;
    private string $centerLng;

    public function __construct(string $fieldId, array $fieldData = [])
    {
        $this->fieldId = $fieldId;
        $this->name = '';
        $this->type = '';
        $this->returnFormat = '';
        $this->choices = [];
        $this->fieldData = $fieldData;
        $this->isFieldExist = false;
        $this->displayFormat = '';
        $this->isMultiple = false;
        $this->appearance = '';
        $this->defaultValue = '';
        $this->customArgs = [];
        $this->zoom = 0;
        $this->centerLat = '';
        $this->centerLng = '';

        $this->read();
    }

    protected function getFieldData(): array
    {
        if ($this->fieldData) {
            return $this->fieldData;
        }

        if (0 === strpos($this->fieldId, TaxonomyTermFields::PREFIX)) {
            return [
                'type' => TaxonomyTermFields::FIELD_TERMS,
                // name is necessary for the identifier and markup generation
                'name' => str_replace(TaxonomyTermFields::PREFIX, '', $this->fieldId),
                // 'field_type' is an alias of the 'appearance'.
                // it's necessary to define, as the custom field will include the Taxonomy field, which waits for this setting
                'field_type' => 'checkbox',
            ];
        }

        if (0 === strpos($this->fieldId, '_')) {
            return [
                'type' => $this->fieldId,
                // name is necessary for the identifier and markup generation
                'name' => $this->fieldId,
            ];
        }

        if (!function_exists('get_field_object')) {
            return $this->fieldData;
        }

        $fieldData = get_field_object($this->fieldId);

        return $fieldData ?
            (array)$fieldData :
            [];
    }

    protected function read(): void
    {
        $fieldData = $this->getFieldData();
        $this->name = (string)($fieldData['name'] ?? '');
        $this->type = (string)($fieldData['type'] ?? '');
        $this->returnFormat = (string)($fieldData['return_format'] ?? '');
        $this->choices = (array)($fieldData['choices'] ?? '');
        $this->displayFormat = (string)($fieldData['display_format'] ?? '');
        $this->isMultiple = (bool)($fieldData['multiple'] ?? false);
        $this->appearance = (string)($fieldData['field_type'] ?? '');
        $this->zoom = (int)($fieldData['zoom'] ?? 0);
        $this->centerLat = (string)($fieldData['center_lat'] ?? '');
        $this->centerLng = (string)($fieldData['center_lng'] ?? '');

        if (key_exists('default_value', $fieldData)) {
            if (is_array($fieldData['default_value'])) {
                $this->defaultValue = (array)$fieldData['default_value'];
            } else {
                $this->defaultValue = (string)$fieldData['default_value'];
            }
        }

        $this->isFieldExist = !!$this->type;
    }

    public function isFieldExist(): bool
    {
        return $this->isFieldExist;
    }

    public function getFieldId(): string
    {
        return $this->fieldId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isCustomType(): bool
    {
        return 0 === strpos($this->type, '_');
    }

    public function getCustomArg(string $argName)
    {
        return $this->customArgs[$argName] ?? null;
    }

    public function setCustomArg(string $argName, $argValue): void
    {
        $this->customArgs[$argName] = $argValue;
    }

    public function unsetCustomArg(string $argName): void
    {
        if (!key_exists($argName, $this->customArgs)) {
            return;
        }

        unset($this->customArgs[$argName]);
    }

    public function getReturnFormat(): string
    {
        return $this->returnFormat;
    }

    public function getDisplayFormat(): string
    {
        return $this->displayFormat;
    }

    public function getChoices(): array
    {
        return $this->choices;
    }

    public function isMultiple(): bool
    {
        return $this->isMultiple;
    }

    public function getAppearance(): string
    {
        return $this->appearance;
    }

    /**
     * @return array|string
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    public function getZoom(): int
    {
        return $this->zoom;
    }

    public function getCenterLat(): string
    {
        return $this->centerLat;
    }

    public function getCenterLng(): string
    {
        return $this->centerLng;
    }
}
