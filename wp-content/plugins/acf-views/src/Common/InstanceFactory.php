<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Common;

use org\wplake\acf_views\Assets\FrontAssets;

defined('ABSPATH') || exit;

abstract class InstanceFactory
{
    protected FrontAssets $frontAssets;

    public function __construct(FrontAssets $frontAssets)
    {
        $this->frontAssets = $frontAssets;
    }

    abstract protected function getTwigVariablesForValidation(int $id): array;

    protected function addUsedCptData(CptData $cptData): void
    {
        $this->frontAssets->addAssets($cptData);
    }

    public function getAutocompleteVariables(int $id, ?array $twigVariables = null): array
    {
        $twigVariablesForValidation = null !== $twigVariables ?
            $twigVariables :
            $this->getTwigVariablesForValidation($id);

        foreach ($twigVariablesForValidation as $key => $value) {
            if (is_array($value)) {
                $twigVariablesForValidation[$key] = $this->getAutocompleteVariables($id, $value);
                continue;
            }

            // override the default value, we don't need to transfer 'fake' data to the front
            $twigVariablesForValidation[$key] = 'value';
        }

        return $twigVariablesForValidation;
    }
}
