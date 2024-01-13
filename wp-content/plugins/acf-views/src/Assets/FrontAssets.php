<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Assets;

use org\wplake\acf_views\Common\CptData;
use org\wplake\acf_views\Common\HooksInterface;
use org\wplake\acf_views\FrontAsset\AcfViewsMapsFrontAsset;
use org\wplake\acf_views\FrontAsset\CommonFrontAsset;
use org\wplake\acf_views\FrontAsset\FrontAsset;
use org\wplake\acf_views\FrontAsset\ViewFrontAsset;
use org\wplake\acf_views\Groups\CardData;
use org\wplake\acf_views\Groups\FieldData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Plugin;
use org\wplake\acf_views\Views\Fields\Menu\MenuFields;

defined('ABSPATH') || exit;

class FrontAssets implements HooksInterface
{
    protected Plugin $plugin;
    protected int $bufferLevel;
    /**
     * @var FrontAsset[]
     */
    protected array $assets;
    protected array $inlineJsCode;
    protected array $inlineCssCode;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
        $this->bufferLevel = 0;

        $this->assets = [];
        $this->inlineJsCode = [];
        $this->inlineCssCode = [];

        $this->addAsset('AcfViewsMapsFrontAsset');
    }

    protected function addAsset(string $name): void
    {
        switch ($name) {
            case 'AcfViewsMapsFrontAsset':
                $this->assets[] = new AcfViewsMapsFrontAsset($this->plugin);
                break;
        }
    }

    protected function getWebComponentJs(CptData $cptData, string $jsCode): string
    {
        $tagName = $cptData->getTagName();
        $isWebComponent = 'div' !== $tagName;

        if (!$isWebComponent) {
            return $jsCode;
        }

        // dashes to camelCase
        $componentName = preg_replace_callback(
            '/-([a-z0-9])/',
            function ($matches) {
                return strtoupper($matches[1]);
            },
            $tagName
        );

        return sprintf(
            'class %s extends HTMLElement{connectedCallback(){"loading"===document.readyState?document.addEventListener("DOMContentLoaded",this.setup.bind(this)):this.setup()}setup(){%s}}customElements.define("%s", %s);',
            esc_html($componentName),
            $jsCode,
            esc_html($tagName),
            esc_html($componentName),
        );
    }

    public function startBuffering(): void
    {
        ob_start();
        $this->bufferLevel = ob_get_level();
    }

    public function printStylesStub(): void
    {
        echo '<!--acf-views-styles-->';
    }

    public function addAssets(CptData $cptData): void
    {
        $cssCode = $cptData->getCssCode();
        $jsCode = $cptData->getJsCode();

        if ($cssCode) {
            $this->inlineCssCode[$cptData->getUniqueId()] = $cssCode;
        }

        if ($jsCode) {
            $this->inlineJsCode[$cptData->getUniqueId()] = $this->getWebComponentJs($cptData, $jsCode);
        }

        foreach ($this->assets as $asset) {
            $asset->maybeActivate($cptData);
        }
    }

    public function printCustomAssets(): void
    {
        $allJsCode = '';
        $allCssCode = '';

        foreach ($this->inlineCssCode as $name => $cssCode) {
            // no escaping, it's a CSS code, so e.g '.a > .b' shouldn't be escaped

            $allCssCode .= sprintf("\n/*%s*/\n%s", $name, $cssCode);
        }
        foreach ($this->inlineJsCode as $name => $jsCode) {
            $allJsCode .= sprintf("\n/*%s*/\n%s", $name, $jsCode);
        }

        if (!$allCssCode &&
            !$allJsCode) {
            // do not close the buffer, if it's not ours
            // (then ours will be closed automatically with the end of script execution)
            if (ob_get_level() === $this->bufferLevel) {
                echo ob_get_clean();
            }

            return;
        }

        // close previous buffers. Some plugins may not close, if detect that ob_get_level() is another than was
        // e.g. 'lightbox-photoswipe'
        while (ob_get_level() > $this->bufferLevel) {
            echo ob_get_clean();
        }

        $pageContent = ob_get_clean();
        $cssTag = $allCssCode ?
            sprintf("<style data-acf-views-css='css'>%s</style>", $allCssCode) :
            '';
        $pageContent = str_replace('<!--acf-views-styles-->', $cssTag, $pageContent);

        echo $pageContent;

        if ($allJsCode) {
            printf("<script data-acf-views-js='js'>(function (){%s}())</script>", $allJsCode);
        }
    }

    public function generateCode(CptData $cptData): array
    {
        $code = [];

        foreach ($this->assets as $asset) {
            $assetCode = $asset->generateCode($cptData);

            if (!$assetCode['js'] &&
                !$assetCode['css']) {
                continue;
            }

            $code[$asset->getAutoDiscoverName()] = $assetCode;
        }

        return $code;
    }

    public function isWebComponentRequired(CptData $cptData): bool
    {
        foreach ($this->assets as $asset) {
            if (!$asset->isWebComponentRequired($cptData)) {
                continue;
            }
            return true;
        }

        return false;
    }

    public function getRowWrapperClass(FieldData $fieldData, string $type): string
    {
        $classes = [];
        foreach ($this->assets as $asset) {
            if (!($asset instanceof ViewFrontAsset) ||
                !$asset->isTargetField($fieldData)) {
                continue;
            }

            $class = $asset->getRowWrapperClass($fieldData, $type);

            if (!$class) {
                continue;
            }

            $classes[] = $class;
        }

        return implode(' ', $classes);
    }

    public function isLabelOutOfRow(FieldData $fieldData): bool
    {
        foreach ($this->assets as $asset) {
            if (!($asset instanceof ViewFrontAsset) ||
                !$asset->isTargetField($fieldData)) {
                continue;
            }

            if ($asset->isLabelOutOfRow($fieldData)) {
                return true;
            }
        }

        return false;
    }

    public function getRowWrapperTag(FieldData $fieldData, string $rowType): string
    {
        foreach ($this->assets as $asset) {
            if (!($asset instanceof ViewFrontAsset) ||
                !$asset->isTargetField($fieldData)) {
                continue;
            }

            $tag = $asset->getRowWrapperTag($fieldData, $rowType);

            if (!$tag) {
                continue;
            }

            return $tag;
        }

        return '';
    }

    public function getFieldWrapperTag(FieldData $fieldData, string $rowType): string
    {
        foreach ($this->assets as $asset) {
            if (!($asset instanceof ViewFrontAsset) ||
                !$asset->isTargetField($fieldData)) {
                continue;
            }

            $tag = $asset->getFieldWrapperTag($fieldData, $rowType);

            if (!$tag) {
                continue;
            }

            return $tag;
        }

        switch ($fieldData->getFieldMeta()->getType()) {
            case MenuFields::FIELD_ITEMS:
                return 'ul';
        }

        return '';
    }

    public function getFieldWrapperAttrs(FieldData $fieldData, string $fieldId): array
    {
        $attrs = [];

        foreach ($this->assets as $asset) {
            if (!($asset instanceof ViewFrontAsset) ||
                !$asset->isTargetField($fieldData)) {
                continue;
            }

            $attrs = array_merge($attrs, $asset->getFieldWrapperAttrs($fieldData, $fieldId));
        }

        return $attrs;
    }

    public function getFieldOuters(ViewData $viewData, FieldData $fieldData, string $fieldId, string $rowType): array
    {
        $outers = [];

        foreach ($this->assets as $asset) {
            if (!($asset instanceof ViewFrontAsset) ||
                !$asset->isTargetField($fieldData)) {
                continue;
            }

            $assetOuters = $asset->getFieldOuters($viewData, $fieldData, $fieldId, $rowType);

            if (!$assetOuters) {
                continue;
            }

            $counter = 0;

            foreach ($assetOuters as $assetOuter) {
                $currentAttrs = $outers[$counter]['attrs'] ?? [];
                $outers[$counter] = [
                    // override, as we need some consensus
                    'tag' => $assetOuter['tag'],
                    // merge, so all necessary are present
                    'attrs' => array_merge($currentAttrs, $assetOuter['attrs']),
                ];

                $counter++;
            }
        }

        return $outers;
    }

    public function getItemOuters(ViewData $viewData, FieldData $fieldData, string $fieldId, string $itemId): array
    {
        $outers = [];

        foreach ($this->assets as $asset) {
            if (!($asset instanceof ViewFrontAsset) ||
                !$asset->isTargetField($fieldData)) {
                continue;
            }

            $assetOuters = $asset->getItemOuters($viewData, $fieldData, $fieldId, $itemId);

            if (!$assetOuters) {
                continue;
            }

            $counter = 0;

            foreach ($assetOuters as $assetOuter) {
                $currentAttrs = $outers[$counter]['attrs'] ?? [];
                $outers[$counter] = [
                    // override, as we need some consensus
                    'tag' => $assetOuter['tag'],
                    // merge, so all necessary are present
                    'attrs' => array_merge($currentAttrs, $assetOuter['attrs']),
                ];

                $counter++;
            }
        }

        return $outers;
    }

    public function getInnerAttributes(FieldData $fieldData, string $fieldId): string
    {
        $attributes = [];

        foreach ($this->assets as $asset) {
            if (!($asset instanceof ViewFrontAsset) ||
                !$asset->isTargetField($fieldData)) {
                continue;
            }

            $attribute = $asset->getInnerAttributes($fieldData, $fieldId);

            if (!$attribute) {
                continue;
            }

            $attributes[] = $attribute;
        }

        return implode(' ', $attributes);
    }

    public function getCardItemsWrapperClass(CardData $cardData): string
    {
        $classes = [];

        foreach ($this->assets as $asset) {
            if (!($asset instanceof CommonFrontAsset) ||
                !$asset->isTargetCard($cardData)) {
                continue;
            }

            $class = $asset->getCardItemsWrapperClass($cardData);

            if (!$class) {
                continue;
            }

            $classes[] = $class;
        }

        return implode(' ', $classes);
    }

    public function getCardItemOuters(CardData $cardData): array
    {
        $outers = [];

        foreach ($this->assets as $asset) {
            if (!($asset instanceof CommonFrontAsset) ||
                !$asset->isTargetCard($cardData)) {
                continue;
            }

            $assetOuters = $asset->getCardItemOuters($cardData);

            if (!$assetOuters) {
                continue;
            }

            $counter = 0;

            foreach ($assetOuters as $assetOuter) {
                $currentAttrs = $outers[$counter]['attrs'] ?? [];
                $outers[$counter] = [
                    // override, as we need some consensus
                    'tag' => $assetOuter['tag'],
                    // merge, so all necessary are present
                    'attrs' => array_merge($currentAttrs, $assetOuter['attrs']),
                ];

                $counter++;
            }
        }

        return $outers;
    }

    public function getCardShortcodeAttrs(CardData $cardData): array
    {
        $attrs = [];

        foreach ($this->assets as $asset) {
            if (!($asset instanceof CommonFrontAsset) ||
                !$asset->isTargetCard($cardData)) {
                continue;
            }

            $attrs = array_merge($attrs, $asset->getCardShortcodeAttrs($cardData));
        }

        return $attrs;
    }

    public function enqueueAssets(): void
    {
        foreach ($this->assets as $asset) {
            $asset->enqueueActive();
        }
    }

    public function setHooks(bool $isAdmin): void
    {
        if ($isAdmin) {
            return;
        }

        add_action('wp_footer', [$this, 'enqueueAssets']);
        // printCustomAssets() contains ob_get_clean, so must be executed after all other scripts
        add_action('wp_footer', [$this, 'printCustomAssets'], 9999);
        add_action('wp_head', [$this, 'printStylesStub']);
        // don't use 'get_header', as it doesn't work in blocks theme
        add_action('template_redirect', [$this, 'startBuffering']);
    }
}
