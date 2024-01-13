<?php

declare(strict_types=1);

namespace org\wplake\acf_views\FrontAsset;

use org\wplake\acf_views\Common\CptData;
use org\wplake\acf_views\Groups\FieldData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Plugin;

defined('ABSPATH') || exit;

abstract class ViewFrontAsset extends FrontAsset
{
    protected array $targetFieldTypes;

    public function __construct(Plugin $plugin)
    {
        parent::__construct($plugin);

        $this->targetFieldTypes = [];
    }

    protected function getTargetFields(ViewData $viewData): array
    {
        $targetFields = [
            'all' => [],
            'fields' => [],
            'subFields' => [],
        ];

        foreach ($this->targetFieldTypes as $targetFieldType) {
            $typeFields = $viewData->getFieldsByType($targetFieldType, true);

            $fields = array_filter($typeFields['fields'], function ($field) {
                return $this->isTargetField($field);
            });
            $subFields = array_filter($typeFields['subFields'], function ($field) {
                return $this->isTargetField($field);
            });

            $targetFields['fields'] = array_merge($targetFields['fields'], $fields);
            $targetFields['subFields'] = array_merge($targetFields['subFields'], $subFields);
            $targetFields['all'] = array_merge($targetFields['all'], $fields, $subFields);
        }

        return $targetFields;
    }

    protected function generateCssCode(
        string $fieldSelector,
        FieldData $fieldData,
        ViewData $viewData
    ): string {
        return '';
    }

    protected function generateJsCode(
        string $varName,
        FieldData $fieldData,
        ViewData $viewData
    ): string {
        return '';
    }

    protected function getItemSelector(
        ViewData $viewData,
        FieldData $fieldData,
        bool $isFull,
        bool $isWithMagicSelector,
        string $target = 'field'
    ): string {
        if ($this->isLabelOutOfRow($fieldData)) {
            $target = '';
        }

        $itemSelector = $viewData->getItemSelector(
            $fieldData,
            $target,
            false,
            !$isFull
        );

        // short version isn't available when common classes are used
        // e.g. ".acf-view__name .acf-view__field" required full
        if (!$isFull &&
            !$viewData->isWithCommonClasses) {
            $itemSelector = explode(' ', $itemSelector);
            $itemSelector = $itemSelector[count($itemSelector) - 1];
        }

        if ($isWithMagicSelector) {
            $bemPrefix = '.' . $viewData->getBemName() . '__';
            $itemSelector = '#view__' . substr($itemSelector, strlen($bemPrefix));
        }

        return $itemSelector;
    }

    public function generateCode(CptData $cptData): array
    {
        $code = [
            'css' => [],
            'js' => [],
        ];

        if (!($cptData instanceof ViewData)) {
            return $code;
        }

        $targetFields = $this->getTargetFields($cptData);

        /**
         * @var FieldData $field
         */
        foreach ($targetFields['fields'] as $field) {
            $jsFieldSelector = $this->getItemSelector($cptData, $field, false, false);
            $cssFieldSelector = $this->getItemSelector($cptData, $field, false, true);

            $varName = $field->getTwigFieldId();

            $jsCode = $this->generateJsCode($varName, $field, $cptData);
            $cssCode = $this->generateCssCode($cssFieldSelector, $field, $cptData);

            if ($jsCode) {
                $code['js'][$varName] = $this->getJsCodePiece($varName, $jsCode, $jsFieldSelector, false);
            }

            if ($cssCode) {
                $code['css'][$varName] = $this->getCodePiece($varName, $cssCode);
            }
        }

        /**
         * @var FieldData $field
         */
        foreach ($targetFields['subFields'] as $field) {
            $jsFieldSelector = $this->getItemSelector($cptData, $field, false, false);
            $cssFieldSelector = $this->getItemSelector($cptData, $field, false, true);

            $jsCode = $this->generateJsCode('item', $field, $cptData);
            $cssCode = $this->generateCssCode($cssFieldSelector, $field, $cptData);
            $varName = $field->getTwigFieldId();

            if ($jsCode) {
                $code['js'][$varName] = $this->getJsCodePiece($varName, $jsCode, $jsFieldSelector, true);
            }

            if ($cssCode) {
                $code['css'][$varName] = $this->getCodePiece($varName, $cssCode);
            }
        }

        return $code;
    }

    public function getRowWrapperClass(FieldData $fieldData, string $rowType): string
    {
        return '';
    }

    public function getRowWrapperTag(FieldData $fieldData, string $rowType): string
    {
        return '';
    }

    public function getFieldWrapperTag(FieldData $fieldData, string $rowType): string
    {
        return '';
    }

    public function getFieldWrapperAttrs(FieldData $fieldData, string $fieldId): array
    {
        return [];
    }

    public function getFieldOuters(ViewData $viewData, FieldData $fieldData, string $fieldId, string $rowType): array
    {
        return [];
    }

    public function getItemOuters(ViewData $viewData, FieldData $fieldData, string $fieldId, string $itemId): array
    {
        return [];
    }

    public function getInnerAttributes(FieldData $fieldData, string $fieldId): string
    {
        return '';
    }

    public function isLabelOutOfRow(FieldData $fieldData): bool
    {
        return false;
    }

    public function isWebComponentRequired(CptData $cptData): bool
    {
        if (!($cptData instanceof ViewData) ||
            !$this->isWithWebComponent) {
            return false;
        }

        return !!$this->getTargetFields($cptData)['all'];
    }

    public function isTargetField(FieldData $fieldData): bool
    {
        return in_array($fieldData->getFieldMeta()->getType(), $this->targetFieldTypes, true);
    }
}
