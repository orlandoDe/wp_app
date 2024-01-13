<?php


declare(strict_types=1);

namespace org\wplake\acf_views\Dashboard;

use org\wplake\acf_views\Common\HooksInterface;
use org\wplake\acf_views\Groups\SettingsData;
use org\wplake\acf_views\Settings;
use org\wplake\acf_views\Views\Cpt\ViewsCpt;

defined('ABSPATH') || exit;

class SettingsPage implements HooksInterface
{
    const SLUG = 'acf-views-settings';

    protected array $values;
    protected SettingsData $settingsData;
    protected Settings $settings;

    public function __construct(SettingsData $settingsData, Settings $settings)
    {
        $this->settingsData = $settingsData;
        $this->settings = $settings;
    }

    protected function isMySource($postId): bool
    {
        $screen = get_current_screen();

        return !!$screen &&
            'acf_views_page_acf-views-settings' === $screen->id &&
            (!$postId || 'options' === $postId);
    }

    public function addPage(): void
    {
        // do not use 'acf_add_options_page', as the global options-related functions may be unavailable
        // (in case of the manual include)
        if (!function_exists('acf_options_page')) {
            return;
        }

        $updatedMessage = __('Settings successfully updated.', 'acf-views');

        acf_options_page()->add_page([
            'slug' => self::SLUG,
            'page_title' => __('Settings', 'acf-views'),
            'menu_title' => __('Settings', 'acf-views'),
            'parent_slug' => sprintf('edit.php?post_type=%s', ViewsCpt::NAME),
            'position' => 2,
            'update_button' => __('Save changes', 'acf-views'),
            'updated_message' => $updatedMessage,
        ]);
    }

    public function maybeCatchValues($postId)
    {
        if (!$this->isMySource($postId)) {
            return;
        }

        add_filter('acf/pre_update_value', function ($isUpdated, $value, $postId, array $field): bool {
            // extra check, as probably it's about another post
            if (!$this->isMySource($postId)) {
                return $isUpdated;
            }

            $fieldName = $field['name'] ?? '';

            $this->values[$fieldName] = $value;

            // avoid saving to the postmeta
            return true;
        }, 10, 4);
    }

    public function maybeInjectValues()
    {
        if (!$this->isMySource(0)) {
            return;
        }

        // values are cache here, to avoid call instanceData->getFieldValues() every time
        // as it takes resources (to go through all inner objects)
        $values = [];

        add_filter('acf/pre_load_value', function ($value, $postId, $field) use ($values) {
            // extra check, as probably it's about another post
            if (!$this->isMySource($postId)) {
                return $value;
            }
            $fieldName = $field['name'];
            $value = '';

            switch ($fieldName) {
                case SettingsData::getAcfFieldName(SettingsData::FIELD_IS_DEV_MODE):
                    $value = $this->settings->isDevMode();
                    break;
            }

            return $value;
        }, 10, 3);
    }

    public function maybeProcess($postId): void
    {
        if (!$this->isMySource($postId) ||
            !$this->values) {
            return;
        }

        $this->settingsData->load(false, '', $this->values);

        $this->settings->setIsDevMode($this->settingsData->isDevMode);

        $this->settings->save();
    }

    public function setHooks(bool $isAdmin): void
    {
        if (!$isAdmin) {
            return;
        }

        // init, not acf/init, as the method uses 'get_edit_post_link' which will be available only since this hook
        // (because we sign up the CPTs in this hook)
        add_action('init', [$this, 'addPage',]);
        add_action('acf/save_post', [$this, 'maybeCatchValues',]);
        // priority 20, as it's after the ACF's save_post hook
        add_action('acf/save_post', [$this, 'maybeProcess',], 20);
        add_action('acf/input/admin_head', [$this, 'maybeInjectValues']);
    }
}
