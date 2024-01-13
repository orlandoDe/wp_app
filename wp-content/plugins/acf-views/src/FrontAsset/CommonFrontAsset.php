<?php


declare(strict_types=1);

namespace org\wplake\acf_views\FrontAsset;

use org\wplake\acf_views\Common\CptData;
use org\wplake\acf_views\Groups\CardData;
use org\wplake\acf_views\Groups\FieldData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Plugin;

defined('ABSPATH') || exit;

abstract class CommonFrontAsset extends ViewFrontAsset
{
    protected string $cardFieldId;

    public function __construct(Plugin $plugin)
    {
        parent::__construct($plugin);

        $this->cardFieldId = '';
    }

    abstract protected function generateCommonJsCode(string $varName): string;

    abstract protected function generateCommonCssCode(string $fieldSelector, CptData $cptData): string;

    abstract public function isTargetCard(CardData $cardData): bool;

    protected function isWebComponentRequiredForCard(CardData $cardData): bool
    {
        return $this->isWithWebComponent &&
            $this->isTargetCard($cardData);
    }

    protected function generateJsCode(string $varName, FieldData $fieldData, ViewData $viewData): string
    {
        return $this->generateCommonJsCode($varName);
    }

    protected function generateCssCode(string $fieldSelector, FieldData $fieldData, ViewData $viewData): string
    {
        return $this->generateCommonCssCode($fieldSelector, $viewData);
    }

    public function getCardItemsWrapperClass(CardData $cardData): string
    {
        return '';
    }

    public function getCardItemOuters(CardData $cardData): array
    {
        return [];
    }

    public function getCardShortcodeAttrs(CardData $cardData): array
    {
        return [];
    }

    public function isWebComponentRequired(CptData $cptData): bool
    {
        return $cptData instanceof CardData ?
            $this->isWebComponentRequiredForCard($cptData) :
            parent::isWebComponentRequired($cptData);
    }

    public function generateCode(CptData $cptData): array
    {
        $code = [
            'css' => [],
            'js' => [],
        ];

        if (!($cptData instanceof CardData)) {
            return parent::generateCode($cptData);
        }

        if (!$this->isTargetCard($cptData)) {
            return $code;
        }

        $cssCode = $this->generateCommonCssCode('#card', $cptData);
        $jsCode = $this->generateCommonJsCode($this->cardFieldId);
        $selector = '.' . $cptData->getBemName() . '__' . $this->cardFieldId;

        if ($cssCode) {
            $code['css'][$this->cardFieldId] = $this->getCodePiece($this->cardFieldId, $cssCode);
        }

        if ($jsCode) {
            $code['js'][$this->cardFieldId] = $this->getJsCodePiece(
                $this->cardFieldId,
                $jsCode,
                $selector,
                false
            );
        }

        return $code;
    }
}