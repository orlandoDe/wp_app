<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Views;

use org\wplake\acf_views\Common\Instance;
use org\wplake\acf_views\Groups\ItemData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Twig;
use org\wplake\acf_views\Views\Fields\Fields;

defined('ABSPATH') || exit;

class View extends Instance
{
    /**
     * @var ViewData
     */
    protected $cptData;
    protected Post $dataPost;
    protected Fields $fields;
    protected int $pageId;
    protected array $fieldValues;

    public function __construct(
        Twig $twig,
        string $markup,
        ViewData $viewData,
        Post $dataPost,
        Fields $fields,
        int $pageId,
        string $classes = ''
    ) {
        parent::__construct($twig, $viewData, $markup, $classes);

        $this->dataPost = $dataPost;
        $this->pageId = $pageId;
        $this->fields = $fields;
        $this->fieldValues = [];
    }

    protected function getTwigArgsForVariable(
        ItemData $item,
        FieldMeta $fieldMeta,
        $notFormattedValue,
        $formattedValue,
        bool $isForValidation
    ): array {
        if (in_array($fieldMeta->getType(), ['repeater', 'group',], true) &&
            !$this->fields->isFieldInstancePresent($fieldMeta->getType())) {
            return [];
        }

        $twigArgs = $this->fields->getFieldTwigArgs(
            $this->cptData,
            $item,
            $item->field,
            $fieldMeta,
            $notFormattedValue,
            $formattedValue,
            $isForValidation
        );

        return [
            $item->field->getTwigFieldId() => array_merge(
                $twigArgs,
                [
                    'label' => $item->field->getLabelTranslation(),
                ]
            ),
        ];
    }

    protected function renderTwig(bool $isForValidation = false): void
    {
        if (!$this->cptData->isRenderWhenEmpty &&
            !$isForValidation) {
            $isEmpty = true;

            foreach ($this->twigVariables as $twigVariableName => $twigVariableValue) {
                $isEmptyValue = is_array($twigVariableValue) &&
                    key_exists('value', $twigVariableValue) &&
                    empty($twigVariableValue['value']);

                // ignore the system variables
                if (!$twigVariableValue ||
                    '_view' === $twigVariableName ||
                    $isEmptyValue) {
                    continue;
                }

                $isEmpty = false;
                break;
            }

            if ($isEmpty) {
                $this->html = '';
                // do not render, as Twig saves template in cache
                // so if it's first, then it'll use the empty one for all next calls of this view
                return;
            }
        }

        $this->html = $this->twig->render(
            $this->cptData->getUniqueId(),
            $this->html,
            $this->twigVariables,
            $isForValidation
        );
    }

    protected function setTwigVariables(bool $isForValidation = false): void
    {
        $objectId = !$isForValidation ?
            strval($this->dataPost->getId()) :
            '0';

        $this->fieldValues = [];
        // internal variables
        $this->twigVariables = [
            '_view' => [
                'classes' => $this->getClasses(),
                'id' => $this->cptData->getMarkupId(),
                // replace for others: term_6 to term-6
                'object_id' => str_replace('_', '-', $objectId),
            ],
        ];

        foreach ($this->cptData->items as $item) {
            $fieldMeta = $item->field->getFieldMeta();
            $fieldValue = !$isForValidation ?
                $this->dataPost->getFieldValue($fieldMeta->getFieldId()) :
                ['1', '1',];

            list($notFormattedFieldValue, $formattedFieldValue) = $fieldValue;

            // 1. default value from our plugin. Note: custom field types don't support default values
            if (!$notFormattedFieldValue &&
                !$fieldMeta->isCustomType()) {
                $notFormattedFieldValue = $formattedFieldValue = $item->field->defaultValue;
            }

            // 2. default value from ACF. Note: custom field types don't support default values
            if (!$notFormattedFieldValue &&
                !$fieldMeta->isCustomType()) {
                $notFormattedFieldValue = $formattedFieldValue = $fieldMeta->getDefaultValue();
            }

            $this->fieldValues[$item->field->id] = $formattedFieldValue;

            $this->twigVariables = array_merge(
                $this->twigVariables,
                $this->getTwigArgsForVariable(
                    $item,
                    $fieldMeta,
                    $notFormattedFieldValue,
                    $formattedFieldValue,
                    $isForValidation
                )
            );
        }
    }

    protected function getArrayFieldNamesFromMarkup(string $markup): array
    {
        preg_match_all(
            '/{% for [a-z0-9_]+ in ([a-z0-9_]+)\.value %}/',
            $markup,
            $arraysInfo,
            PREG_OFFSET_CAPTURE | PREG_SET_ORDER
        );

        $arrayFieldNames = [];

        foreach ($arraysInfo as $arrayInfo) {
            $charPosition = $arrayInfo[0][1] ?? -1;
            $arrayName = $arrayInfo[1][0] ?? '';
            if (!$arrayName ||
                -1 === $charPosition) {
                continue;
            }

            $lineNumber = substr_count(mb_substr($markup, 0, $charPosition), PHP_EOL) + 1;

            $arrayFieldNames[$arrayName] = $lineNumber;
        }

        return $arrayFieldNames;
    }

    protected function getArrayFieldNames(array $variables): array
    {
        return array_keys(
            array_filter($variables, function ($fieldValue) {
                return is_array($fieldValue) && key_exists('value', $fieldValue) && is_array($fieldValue['value']);
            })
        );
    }

    protected function getArrayExpectationErrors(array $canonicalArrayFieldNames, array $presentArrayFields): string
    {
        $unexpectedArrays = array_diff(array_keys($presentArrayFields), $canonicalArrayFieldNames);
        $errors = '';

        foreach ($unexpectedArrays as $unexpectedArray) {
            $lineNumber = $presentArrayFields[$unexpectedArray];
            $errors .= sprintf(
                __('The "%s" field is incorrectly expected to be an array. Line %d', 'acf-views'),
                $unexpectedArray,
                $lineNumber
            );
        }

        return $errors;
    }

    protected function getMissingArrayErrors(
        array $canonicalArrayFieldNames,
        array $presentArrayFields,
        string $customMarkup
    ): string {
        $missingArrays = array_diff($canonicalArrayFieldNames, array_keys($presentArrayFields));
        $errors = '';

        foreach ($missingArrays as $missingArray) {
            $fieldPosition = strpos($customMarkup, $missingArray . '.value');

            // skip error if the field is not used in the markup (e.g. newly added)
            // our goal is only to validate the existing markup
            if (false === $fieldPosition) {
                continue;
            }

            $lineNumber = substr_count(mb_substr($customMarkup, 0, $fieldPosition), PHP_EOL) + 1;

            $errors .= sprintf(
                __('The "%s" field is incorrectly expected to be a string. Line %d', 'acf-views'),
                $missingArray,
                $lineNumber
            );
        }

        return $errors;
    }

    public function insertFields(bool $isMinifyMarkup = true): void
    {
        if ($isMinifyMarkup) {
            // remove special symbols that used in the markup for a preview
            // exactly here, before the fields are inserted, to avoid affecting them
            $this->html = str_replace(["\t", "\n", "\r"], '', $this->html);
        }

        $this->setTwigVariables();
        $this->renderTwig();
    }

    public function getHTML(): string
    {
        return $this->html;
    }

    public function getViewData(): ViewData
    {
        return $this->cptData;
    }

    public function getMarkupValidationError(): string
    {
        $markupValidationError = parent::getMarkupValidationError();
        $customMarkup = trim($this->cptData->customMarkup);

        if ($markupValidationError ||
            !$customMarkup) {
            return $markupValidationError;
        }

        $canonicalArrayFieldNames = $this->getArrayFieldNames($this->twigVariables);
        $presentArrayFields = $this->getArrayFieldNamesFromMarkup($customMarkup);

        $markupValidationError .= $this->getArrayExpectationErrors($canonicalArrayFieldNames, $presentArrayFields);
        $markupValidationError .= $this->getMissingArrayErrors(
            $canonicalArrayFieldNames,
            $presentArrayFields,
            $customMarkup
        );

        return $markupValidationError;
    }
}
