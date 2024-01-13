<?php

declare(strict_types=1);

namespace org\wplake\acf_views\FrontAsset;

use org\wplake\acf_views\Common\CptData;
use org\wplake\acf_views\Plugin;
use org\wplake\acf_views\Views\Cpt\ViewsCpt;

defined('ABSPATH') || exit;

abstract class FrontAsset
{
    protected array $jsHandles;
    protected array $cssHandles;
    /**
     * @var Plugin $plugin
     */
    protected $plugin;
    protected string $autoDiscoverName;
    protected bool $isWithWebComponent;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
        $this->jsHandles = [];
        $this->cssHandles = [];
        $this->autoDiscoverName = '';
        $this->isWithWebComponent = false;
    }

    abstract public function generateCode(CptData $cptData): array;

    abstract public function maybeActivate(CptData $cptData): void;

    abstract public function isWebComponentRequired(CptData $cptData): bool;

    protected function getCodePiece(string $name, string $piece): string
    {
        $heading = sprintf('/* %s : %s (auto-discover-begin) */', $this->autoDiscoverName, $name);

        $code = "\n\n";
        $code .= $heading . "\n\n";
        $code .= $piece . "\n\n";
        $code .= sprintf('/* %s : %s (auto-discover-end) */', $this->autoDiscoverName, $name);
        $code .= "\n\n";

        return $code;
    }

    protected function getAssetUrl(string $file): string
    {
        return $this->plugin->getAssetsUrl('front/' . $file);
    }

    protected function getJsCodePiece(
        string $name,
        string $piece,
        string $fieldSelector,
        bool $isMultiple
    ): string {
        if ($isMultiple) {
            $jsCode = sprintf("this.querySelectorAll('%s').forEach(item => {\n", $fieldSelector);
        } else {
            $jsCode = sprintf("var %s = this.querySelector('%s');\n", $name, $fieldSelector);
            $jsCode .= sprintf("if (%s) {\n", $name);
        }

        $jsCode .= $piece;
        $jsCode .= $isMultiple ?
            "\n});" :
            "\n}";

        return $this->getCodePiece($name, $jsCode);
    }

    protected function getWpHandle($handle): string
    {
        return ViewsCpt::NAME . '_' . $handle;
    }

    public function enqueueActive(): void
    {
        foreach ($this->jsHandles as $jsHandle => $isActive) {
            if (!$isActive) {
                continue;
            }

            wp_enqueue_script(
                $this->getWpHandle($jsHandle),
                $this->getAssetUrl('js/' . $jsHandle . '.min.js'),
                [],
                $this->plugin->getVersion(),
                [
                    'in_footer' => true,
                    'strategy' => 'defer',
                ]
            );
        }

        foreach ($this->cssHandles as $cssHandle => $isActive) {
            if (!$isActive) {
                continue;
            }

            wp_enqueue_style(
                $this->getWpHandle($cssHandle),
                $this->getAssetUrl('css/' . $cssHandle . '.min.css'),
                [],
                $this->plugin->getVersion()
            );
        }
    }

    public function getAutoDiscoverName(): string
    {
        return $this->autoDiscoverName;
    }
}
