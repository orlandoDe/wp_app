<?php

declare(strict_types=1);

namespace org\wplake\acf_views;

use Exception;
use org\wplake\acf_views\Cards\Cpt\CardsCpt;
use org\wplake\acf_views\vendors\Twig\Environment;
use org\wplake\acf_views\vendors\Twig\Loader\FilesystemLoader;
use org\wplake\acf_views\Views\Cpt\ViewsCpt;

defined('ABSPATH') || exit;

class Twig
{
    protected ?FilesystemLoader $loader;
    protected ?Environment $twig;
    protected Settings $settings;
    protected Plugin $plugin;

    public function __construct(Settings $settings, Plugin $plugin)
    {
        $this->settings = $settings;
        $this->plugin = $plugin;
        $this->loader = null;
        $this->twig = null;
    }

    protected function initTwig(): void
    {
        $this->loader = new FilesystemLoader($this->getTemplatesDir());
        $this->twig = new Environment($this->loader, [
            // will generate exception if a var doesn't exist instead of replace to NULL
            'strict_variables' => true,
            // 'html' by default, just highlight that it's secure to not escape TWIG variable values in PHP
            'autoescape' => 'html',
        ]);
    }

    protected function getTwig(): ?Environment
    {
        if (!$this->twig) {
            if (!is_dir($this->getTemplatesDir())) {
                return null;
            }

            $this->initTwig();
        }

        return $this->twig;
    }

    protected function getErrorMessage(string $uniqueViewId, string $errorMessage): string
    {
        return sprintf(
            '<p style="color:red;" class="acf-views__error">Advanced Views (%s) template: <span class="acf-views__error-message">%s</span></p>',
            esc_html($uniqueViewId),
            esc_html($errorMessage)
        );
    }

    protected function getTemplatesDir(): string
    {
        // there is no sense to make difference for basic & pro versions
        return wp_upload_dir()['basedir'] . '/acf-views';
    }

    protected function isTemplatesDirWritable(): bool
    {
        $templatesDir = $this->getTemplatesDir();

        if (!is_dir($templatesDir)) {
            return false;
        }

        $testFile = $templatesDir . '/test.txt';

        // the best way to check is to make test write
        // (check of permissions or 'is_writable' is not enough, as it can be set to 777, but the folder can be owned by another user)

        $isWrote = false !== file_put_contents($testFile, 'test');

        if (!$isWrote) {
            return false;
        }

        $content = file_get_contents($testFile);

        $isWritable = 'test' === $content;

        unlink($testFile);

        return $isWritable;
    }

    public function render(string $uniqueViewId, string $template, array $args, bool $isValidation = false): string
    {
        $html = '';
        $twig = $this->getTwig();

        if (!$twig) {
            $html .= $this->getErrorMessage($uniqueViewId, 'Templates folder is not writable');

            return $html;
        }

        // emulate the template file for every View.
        // as Twig generates a PHP class for every template file
        // so if you use the same, it'll have HTML of the very first View

        $templateName = sprintf("%s.twig", $uniqueViewId);
        $templateFile = $this->getTemplatesDir() . '/' . $templateName;

        $isWritten = false !== file_put_contents($templateFile, $template);

        if (!$isWritten) {
            $html .= $this->getErrorMessage($uniqueViewId, 'Can\'t write template file');

            return $html;
        }

        try {
            $html = $twig->render($templateName, $args);
        } catch (Exception $e) {
            $isAdmin = in_array('administrator', wp_get_current_user()->roles, true);
            $debugMode = $isAdmin && $this->settings->isDevMode();

            $errorMessage = $e->getMessage();
            // the right line number is available only for unminified template (for validation during saving)
            if ($isValidation) {
                $errorMessage .= ' Line ' . $e->getLine();
            }

            $html .= $this->getErrorMessage($uniqueViewId, $errorMessage);

            // do not include in case of the validation, it doesn't have sense + breaks the error grep regex
            if ($debugMode &&
                !$isValidation) {
                $html .= '<pre>' . print_r($args, true) . '</pre>';
            }
        }

        unlink($templateFile);

        return $html;
    }

    public function createTemplatesDir(): void
    {
        $templatesDir = $this->getTemplatesDir();

        // skip if already exists
        if (is_dir($templatesDir)) {
            return;
        }

        $isCreatedDir = mkdir($templatesDir, 0755);

        if (!$isCreatedDir) {
            return;
        }

        file_put_contents(
            $templatesDir . '/readme.txt',
            'This directory is used by the Advanced Views plugin to temporarily store Twig templates during execution.'
        );
        file_put_contents($templatesDir . '/index.php', '<?php // Silence is golden.');
    }

    public function removeTemplatesDir(): void
    {
        // do not remove if switching versions.
        // Because activation hooks won't be called, so dir will be missing
        if ($this->plugin->isSwitchingVersions()) {
            return;
        }

        $templatesDir = $this->getTemplatesDir();

        if (!is_dir($templatesDir)) {
            return;
        }

        // remove readme.txt & index.php
        array_map('unlink', glob("$templatesDir/*.*"));

        // remove the dir
        rmdir($templatesDir);
    }

    public function showTemplatesDirIsNotWritableWarning(): void
    {
        $screen = get_current_screen();

        // show only on the list pages of Views & Cards
        if (!$screen ||
            !in_array($screen->post_type, [ViewsCpt::NAME, CardsCpt::NAME,], true) ||
            'edit' !== $screen->base) {
            return;
        }

        if ($this->isTemplatesDirWritable()) {
            return;
        }

        $message = __('The templates directory is not writable.', 'acf-views');
        $message .= ' (path = ' . $this->getTemplatesDir() . ')<br>';
        $message .= __('Most likely, the WordPress uploads directory is not writable.', 'acf-views') . '<br>';
        $message .= __(
            'Check and fix file permissions, then deactivate and activate back the Advanced Views plugin. If the issue persists, contact support.',
            'acf-views'
        );

        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            $message,
        );
    }

    public function setHooks(bool $isAdmin, string $file): void
    {
        if (!$isAdmin) {
            return;
        }

        // do not use $plugin->getSlug(), it won't work in tests
        register_activation_hook($file, [$this, 'createTemplatesDir']);
        register_deactivation_hook($file, [$this, 'removeTemplatesDir']);

        add_action('admin_notices', [$this, 'showTemplatesDirIsNotWritableWarning']);
    }
}
