<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Common;

use org\wplake\acf_views\Twig;

defined('ABSPATH') || exit;

abstract class Instance
{
    protected string $html;
    protected array $twigVariables;
    protected Twig $twig;
    /**
     * @var CptData
     */
    protected $cptData;
    protected string $classes;

    public function __construct(Twig $twig, CptData $cptData, string $markup, string $classes = '')
    {
        $this->twig = $twig;
        $this->cptData = $cptData;
        $this->html = $markup;
        $this->twigVariables = [];
        $this->classes = $classes;
    }

    abstract protected function setTwigVariables(bool $isForValidation = false): void;

    abstract protected function renderTwig(bool $isForValidation = false): void;

    protected function getClasses(): string
    {
        $classes = '';
        $classes .= $this->classes ?
            $this->classes . ' ' :
            '';
        $classes .= $this->cptData->cssClasses ?
            $this->cptData->cssClasses . ' ' :
            '';

        return $classes;
    }

    public function getMarkupValidationError(): string
    {
        $this->setTwigVariables(true);

        $this->renderTwig(true);

        preg_match('/<span class="acf-views__error-message">(.*)$/', $this->html, $errorMessage);

        $errorMessage = $errorMessage[1] ?? '';
        $errorMessage = str_replace('</span>', '', $errorMessage);
        $errorMessage = trim($errorMessage);

        return $errorMessage;
    }

    public function getTwigVariablesForValidation(): array
    {
        $this->setTwigVariables(true);

        return $this->twigVariables;
    }
}
