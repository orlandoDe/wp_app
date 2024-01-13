<?php

declare(strict_types=1);

namespace org\wplake\acf_views\Common\Cpt;

use org\wplake\acf_views\Assets\FrontAssets;
use org\wplake\acf_views\Common\CptData;
use org\wplake\acf_views\Common\CptDataStorage;
use org\wplake\acf_views\Common\Group;
use org\wplake\acf_views\Common\HooksInterface;
use org\wplake\acf_views\Common\Instance;
use org\wplake\acf_views\Groups\CardData;
use org\wplake\acf_views\Groups\ViewData;
use org\wplake\acf_views\Plugin;
use org\wplake\acf_views\Views\Cpt\ViewsCpt;

defined('ABSPATH') || exit;

abstract class SaveActions implements HooksInterface
{
    protected CptDataStorage $cptDataStorage;
    protected Plugin $plugin;
    protected array $fieldValues;
    /**
     * @var CptData
     */
    protected $validationData;
    protected array $availableAcfFields;
    protected array $validatedInputNames;
    protected FrontAssets $frontAssets;

    public function __construct(
        CptDataStorage $cptDataStorage,
        Plugin $plugin,
        CptData $cptData,
        FrontAssets $frontAssets
    ) {
        $this->cptDataStorage = $cptDataStorage;
        $this->plugin = $plugin;
        $this->validationData = $cptData->getDeepClone();
        $this->frontAssets = $frontAssets;
        $this->availableAcfFields = array_keys($this->validationData->getFieldValues());
        $this->fieldValues = [];
        $this->validatedInputNames = [];
    }

    abstract protected function getCptName(): string;

    abstract protected function getTranslatableLabels($cptData): array;

    abstract protected function getCustomMarkupAcfFieldName(): string;

    abstract protected function makeValidationInstance(): Instance;

    /**
     * @param CptData $cptData
     * @return void
     */
    abstract protected function updateMarkup($cptData): void;

    protected function syncCode(string $code, array $actualPieces): string
    {
        // ungreedy, so that we can grep all the pieces
        preg_match_all(
            '/\/\*([^*]*):([^*]+)\(auto-discover-begin\)[^*]+\*\/[\d\D]+\/\*[^*]*\(auto-discover-end\)[^*]+\*\//U',
            $code,
            $currentPieces,
            PREG_SET_ORDER
        );

        // 1. remove present pieces from the actual list (to avoid override)
        // 2. remove absent pieces from the code
        foreach ($currentPieces as $currentPiece) {
            if (count($currentPiece) < 3) {
                continue;
            }

            $type = trim($currentPiece[1]);
            $name = trim($currentPiece[2]);
            $pieceId = $type . ':' . $name;

            if (key_exists($pieceId, $actualPieces)) {
                unset($actualPieces[$pieceId]);

                continue;
            }

            $code = str_replace($currentPiece[0], '', $code);
        }

        // remove empty lines (after removals)
        $code = trim($code);

        // 3. add new pieces
        foreach ($actualPieces as $actualPiece) {
            $code .= $actualPiece;
        }

        // remove empty lines (after additions)
        $code = trim($code);

        return $code;
    }

    public function performSaveActions($postId, bool $isSkipSave = false): ?CptData
    {
        if (!$this->isMyPost($postId)) {
            return null;
        }

        $cptData = $this->cptDataStorage->get($postId);

        // it must be before the frontAssets generation, otherwise CSS may already be not empty even for the first save
        if (!$cptData->cssCode &&
            !$cptData->isWithoutWebComponent) {
            // by default, Web component is inline, which is wrong, we expect it to be block
            $id = ViewsCpt::NAME === $this->getCptName() ?
                'view' :
                'card';
            $cptData->cssCode = sprintf("#%s {\n\tdisplay: block;\n}\n", $id);
        }

        $code = $this->frontAssets->generateCode($cptData);

        $jsCode = [];
        $cssCode = [];

        foreach ($code as $autoDiscoverName => $codes) {
            foreach ($codes['js'] as $fieldId => $fieldJsCode) {
                $jsCode[$autoDiscoverName . ':' . $fieldId] = $fieldJsCode;
            }
            foreach ($codes['css'] as $fieldId => $fieldCssCode) {
                $cssCode[$autoDiscoverName . ':' . $fieldId] = $fieldCssCode;
            }
        }

        $cptData->jsCode = $this->syncCode($cptData->jsCode, $jsCode);
        $cptData->cssCode = $this->syncCode($cptData->cssCode, $cssCode);

        if (!$isSkipSave) {
            $cptData->saveToPostContent();
        }

        return $cptData;
    }

    protected function getTranslationsFromMarkup(array $translations, string $markup): array
    {
        $textDomains = [];

        // __("Some data") or __("Some data", "my-theme")
        preg_match_all(
            '/__\([ ]*["]([^"]+)["]([, ]+["]([^"]+)["])*[ ]*\)/',
            $markup,
            $functionsWithDoubleQuotes,
            PREG_SET_ORDER
        );

        // __('Some data') or __('Some data', 'my-theme')
        preg_match_all(
            "/__\([ ]*[']([^']+)[']([, ]+[']([^']+)['])*[ ]*\)/",
            $markup,
            $functionsWithSingleQuotes,
            PREG_SET_ORDER
        );

        // "Some data"|translate or "Some data"|translate("my-theme")
        preg_match_all(
            '/["]([^"]+)["]\|translate(\([ ]*["]([^"]+)["][ ]*\))*/',
            $markup,
            $filtersWithDoubleQuotes,
            PREG_SET_ORDER
        );

        // 'Some data'|translate or 'Some data'|translate('my-theme')
        preg_match_all(
            "/[']([^']+)[']\|translate(\([ ]*[']([^']+)['][ ]*\))*/",
            $markup,
            $filtersWithSingleQuotes,
            PREG_SET_ORDER
        );

        $functions = array_merge($functionsWithDoubleQuotes, $functionsWithSingleQuotes);
        $filters = array_merge($filtersWithDoubleQuotes, $filtersWithSingleQuotes);
        $matches = array_merge($functions, $filters);

        foreach ($matches as $match) {
            $label = $match[1] ?? '';
            $textDomain = $match[3] ?? $this->plugin->getThemeTextDomain();

            $translations[$textDomain] = $translations[$textDomain] ?? [];
            $translations[$textDomain][] = $label;

            $textDomains[] = $textDomain;
        }

        $textDomains = array_unique($textDomains);
        foreach ($textDomains as $textDomain) {
            $translations[$textDomain] = array_unique($translations[$textDomain]);
        }

        return $translations;
    }

    protected function getAcfAjaxPostId(): int
    {
        $postId = $_POST['post_id'] ?? 0;

        if (!is_numeric($postId) ||
            !$postId) {
            return 0;
        }

        return (int)$postId;
    }

    protected function addValidationError(string $fieldKey, string $message)
    {
        $inputName = $this->validatedInputNames[$fieldKey] ?? '';
        acf_add_validation_error($inputName, $message);
    }

    protected function validateCustomMarkup(): void
    {
        $isWithCustomMarkup = !!(trim($this->validationData->customMarkup));

        if (!$isWithCustomMarkup) {
            return;
        }

        // it's necessary to update the markupPreview before the validation
        // as the validation uses the markupPreview as 'canonical' for the 'array' type validation
        $this->updateMarkup($this->validationData);
        $markupValidationError = $this->makeValidationInstance()->getMarkupValidationError();

        if (!$markupValidationError) {
            return;
        }

        $this->addValidationError(
            $this->getCustomMarkupAcfFieldName(),
            $markupValidationError
        );
    }

    protected function validateWebComponentSetting(): void
    {
        if (!$this->validationData->isWithoutWebComponent ||
            !$this->frontAssets->isWebComponentRequired($this->validationData)) {
            return;
        }

        $fieldName = ViewsCpt::NAME === $this->getCptName() ?
            ViewData::getAcfFieldName(ViewData::FIELD_IS_WITHOUT_WEB_COMPONENT) :
            CardData::getAcfFieldName(CardData::FIELD_IS_WITHOUT_WEB_COMPONENT);

        $this->addValidationError(
            $fieldName,
            __('Web Component is required for this setup.', 'acf-views')
        );
    }

    protected function validateSubmission()
    {
        $this->validateCustomMarkup();
        $this->validateWebComponentSetting();
    }

    protected function loadValidationDataInstanceFromCurrentValues($postId): void
    {
        // remove slashes added by WP, as it's wrong to have slashes so early
        // (corrupts next data processing, like markup generation (will be \&quote; instead of &quote; due to this escaping)
        // in the 'saveToPostContent()' method using $wpdb that also has 'addslashes()',
        // it means otherwise \" will be replaced with \\\" and it'll create double slashing issue (every saving amount of slashes before " will be increasing)

        $fieldValues = array_map('stripslashes_deep', $this->fieldValues);

        $this->validationData->load($postId, '', $fieldValues);
    }

    protected function saveValidationInstanceToStorage($postId): void
    {
        // to avoid changing fields for Unlicensed users
        if ($this->plugin->isProFieldLocked()) {
            $this->validationData->resetProFields($this->cptDataStorage->get($postId));
        }

        $this->cptDataStorage->replace($postId, $this->validationData);
    }

    protected function saveCaughtFields($postId)
    {
        $this->saveValidationInstanceToStorage($postId);

        $this->performSaveActions($postId);
    }

    public function saveMetaField($value, array $field): void
    {
        $fieldName = $field['name'] ?? '';
        $validationInstance = $this->validationData;

        // convert repeater format. don't check simply 'is_array(value)' as not every array is a repeater
        // also check to make sure it's array (can be empty string)
        if (in_array($fieldName, $validationInstance->getRepeaterFieldNames(), true) &&
            is_array($value)) {
            $value = Group::convertRepeaterFieldValues($fieldName, $value);
        }

        // the difference that this code is called in different hooks, which require different approach
        if ($this->plugin->isWordpressComHosting()) {
            // convert clone format
            // also check to make sure it's array (can be empty string)
            if (in_array($fieldName, $validationInstance->getCloneFieldNames(), true) &&
                is_array($value)) {
                $newValue = Group::convertCloneField($fieldName, $value);
                $this->fieldValues = array_merge($this->fieldValues, $newValue);

                return;
            }
        } else {
            // convert the clone sub-fields
            // note: in the 'acf/validate_value' filter which is in use,
            // they presented as separate fields, unlike the grouped array presentation in case of the 'acf/pre_update_value' filter
            $cloneFieldNames = $validationInstance->getCloneFieldNames();
            foreach ($cloneFieldNames as $cloneFieldName) {
                $clonePrefix = $cloneFieldName . '_';

                if (0 !== strpos($fieldName, $clonePrefix)) {
                    continue;
                }

                // pass as an array as the second argument, as we use the 'acf/validate_value' filter
                $newValue = Group::convertCloneField($cloneFieldName, [$fieldName => $value]);
                $this->fieldValues = array_merge($this->fieldValues, $newValue);

                return;
            }
        }

        $this->fieldValues[$fieldName] = $value;
    }

    public function getAcfFieldFromInstance($value, int $postId, array $field, array $values)
    {
        $fieldName = $field['name'] ?? '';

        // skip sub-fields or fields from other groups
        if (!key_exists($fieldName, $values)) {
            return $value;
        }

        $value = $values[$fieldName];
        $instanceData = $this->cptDataStorage->get($postId);

        // convert repeater format. don't check simply 'is_array(value)' as not every array is a repeater
        // also check to make sure it's array (can be empty string)
        $value = in_array($fieldName, $instanceData->getRepeaterFieldNames(), true) &&
        is_array($value) ?
            Group::convertRepeaterFieldValues($fieldName, $value, false) :
            $value;

        // convert clone format
        $cloneFieldNames = $instanceData->getCloneFieldNames();
        foreach ($cloneFieldNames as $cloneFieldName) {
            $clonePrefix = $cloneFieldName . '_';

            if (0 !== strpos($fieldName, $clonePrefix)) {
                continue;
            }

            // can be string field
            if (!is_array($value)) {
                break;
            }

            $fieldNameWithoutClonePrefix = substr($fieldName, strlen($clonePrefix));

            $value = Group::convertCloneField($fieldNameWithoutClonePrefix, $value, false);

            break;
        }

        return $value;
    }

    public function updateTranslationsFile(CptData $cptData): void
    {
        $folderInTheme = get_stylesheet_directory() . '/acf-views-labels';

        if (!is_dir($folderInTheme)) {
            return;
        }

        $translations = $this->getTranslatableLabels($cptData);

        if (!$translations) {
            return;
        }

        $translationFile = sprintf('%s/%s.php', $folderInTheme, $cptData->getUniqueId());
        $translationFileLines = [];

        foreach ($translations as $textDomain => $labels) {
            foreach ($labels as $label) {
                // to avoid breaking the PHP string
                $label = str_replace("'", "&#039;", $label);
                $label = str_replace('"', "&quot;", $label);

                $translationFileLines[] = sprintf("__('%s', '%s');", $label, $textDomain);
            }
        }

        $fileContent = "<?php\n" .
            "// " . get_the_title($cptData->getSource()) .
            "\n\n" .
            join("\n", $translationFileLines);

        // always overwrite the file
        file_put_contents($translationFile, $fileContent);
    }

    public function maybeSetUniqueId(CptData $acfCptData, string $prefix): bool
    {
        // do not check just for empty, because WP autofills the slug
        if (0 === strpos($acfCptData->getUniqueId(), $prefix)) {
            return false;
        }

        $uniqueId = uniqid($prefix);

        wp_update_post([
            'ID' => $acfCptData->getSource(),
            'post_name' => $uniqueId,
        ]);

        return true;
    }

    /**
     * @param int|string $postId Can be string, e.g. 'options'
     *
     * @return bool
     */
    public function isMyPost($postId): bool
    {
        // for 'site-settings' and similar
        if (!is_numeric($postId) ||
            !$postId) {
            return false;
        }

        $post = get_post($postId);

        if (!$post ||
            $this->getCptName() !== $post->post_type ||
            wp_is_post_revision($postId)) {
            return false;
        }

        return true;
    }

    /**
     * @param bool|string $valid
     * @param mixed $value
     * @param array $field
     * @param string $inputName
     * @return bool|string
     */
    public function catchFieldValue($valid, $value, array $field, string $inputName)
    {
        $postId = $this->getAcfAjaxPostId();

        if (true !== $valid ||
            !in_array($field['key'], $this->availableAcfFields, true) ||
            !$this->isMyPost($postId)) {
            return $valid;
        }

        $this->validatedInputNames[$field['key']] = $inputName;

        $this->saveMetaField($value, $field);

        return true;
    }

    public function customValidation()
    {
        $postId = $this->getAcfAjaxPostId();

        if (acf_get_validation_errors() ||
            !$this->isMyPost($postId)) {
            return;
        }

        $this->loadValidationDataInstanceFromCurrentValues($postId);

        $this->validateSubmission();

        if (acf_get_validation_errors()) {
            return;
        }

        // save right within this hook, to avoid extra saving request
        $this->saveCaughtFields($postId);
    }

    public function skipSavingToPostMeta($postId)
    {
        if (!$this->isMyPost($postId)) {
            return;
        }

        add_filter('acf/pre_update_value', function ($isUpdated, $value, int $postId, array $field): bool {
            // extra check, as probably it's about another post
            if (!$this->isMyPost($postId)) {
                return $isUpdated;
            }

            if ($this->plugin->isWordpressComHosting()) {
                $this->saveMetaField($value, $field);
            }

            // avoid saving to the postmeta
            return true;
        }, 10, 4);

        if (!$this->plugin->isWordpressComHosting()) {
            return;
        }

        // priority is 20, as current is with 10
        add_action('acf/save_post', function ($postId) {
            // check again, as probably it's about another post
            if (!$this->isMyPost($postId)) {
                return;
            }

            $this->loadValidationDataInstanceFromCurrentValues($postId);
            $this->saveCaughtFields($postId);
        }, 20);
    }

    public function loadFieldsFromPostContent()
    {
        global $post;
        $postId = $post->ID ?? 0;

        if (!$this->isMyPost($postId)) {
            return;
        }

        // values are cache here, to avoid call instanceData->getFieldValues() every time
        // as it takes resources (to go through all inner objects)
        $values = [];

        add_filter('acf/pre_load_value', function ($value, $postId, $field) use ($values) {
            // extra check, as probably it's about another post
            if (!$this->isMyPost($postId)) {
                return $value;
            }

            if (!key_exists($postId, $values)) {
                $instanceData = $this->cptDataStorage->get($postId);

                $values[$postId] = $instanceData->getFieldValues();
            }

            return $this->getAcfFieldFromInstance($value, $postId, $field, $values[$postId]);
        }, 10, 3);
    }

    // by tests, json in post_meta in 13 times quicker than ordinary postMeta way (30ms per 10 objects vs 400ms)
    public function setHooks(bool $isAdmin): void
    {
        if (!$isAdmin) {
            return;
        }

        // for some reason, ACF ajax form validation doesn't work on the wordpress.com hosting
        if (!$this->plugin->isWordpressComHosting()) {
            // priority is 20, to make sure it's run after the ACF's code
            add_action('acf/validate_value', [$this, 'catchFieldValue'], 20, 4);
            add_action('acf/validate_save_post', [$this, 'customValidation'], 20, 4);
        }

        add_action('acf/save_post', [$this, 'skipSavingToPostMeta']);

        add_action('acf/input/admin_head', [$this, 'loadFieldsFromPostContent']);
    }
}