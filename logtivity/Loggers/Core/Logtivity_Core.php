<?php

/**
 * @package   Logtivity
 * @contact   logtivity.io, hello@logtivity.io
 * @copyright 2024-2025 Logtivity. All rights reserved
 * @license   https://www.gnu.org/licenses/gpl.html GNU/GPL
 *
 * This file is part of Logtivity.
 *
 * Logtivity is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * Logtivity is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Logtivity.  If not, see <https://www.gnu.org/licenses/>.
 */

class Logtivity_Core extends Logtivity_Abstract_Logger
{
    /**
     * @var string[]
     */
    protected array $ignoredOptions = [
        '_edd_table_check',
        '_wp_suggested_policy_text_has_changed',
        'action_scheduler_lock_async-request-runner',
        'active_plugins',
        'admin_email_lifespan',
        'adminhash',
        'akismet_spam_count',
        'allowedthemes',
        'auto_plugin_theme_update_emails',
        'cron',
        'db_upgraded',
        'delete_blog_hash',
        'fresh_site',
        'ftp_credentials',
        'gmt_offset',
        'jetpack_next_sync_time_sync',
        'jetpack_plugin_api_action_links',
        'jetpack_protect_blocked_attempts',
        'jetpack_sync_settings_dedicated_sync_enabled',
        'jetpack_updates_sync_checksum',
        'jp_sync_last_success_sync',
        'jp_sync_retry_after_sync',
        'limit_login_retries',
        'mepr_coupons_expire_last_run',
        'mepr_groups_db_cleanup_last_run',
        'mepr_products_db_cleanup_last_run',
        'mepr_rules_db_cleanup_last_run',
        'post_views_count',
        'postman_state',
        'recently_activated',
        'recently_edited',
        'rewrite_rules',
        'rxpp_blocked_methods_count',
        'stats_cache',
        'uninstall_plugins',
        'woocommerce_marketplace_suggestions',
        'wordfence_syncAttackDataAttempts',
        'wp_all_export_pro_addons_not_included',
        'wp_force_deactivated_plugins',
        'wpcf7',
        'ws_form_css',
    ];

    /**
     * @var string[]
     */
    protected array $ignoredWildcardOptions = [
        'auto_updater',
        'cache',
        'cron',
        'edd_api',
        'edd_sl',
        'frm_',
        'last_run',
        'logtivity_api',
        'logtivity_app',
        'logtivity_custom_plugin_name',
        'logtivity_disable',
        'logtivity_enable',
        'logtivity_global',
        'logtivity_hide_plugin_from_ui',
        'logtivity_last_settings_check_in_at',
        'logtivity_latest_response',
        'logtivity_should',
        'logtivity_site_api_key',
        'logtivity_url_hash',
        'logtivity_version',
        'queue',
        'sync',
        'transient',
        'wpe',
    ];

    /**
     * @inheritDoc
     */
    protected function registerHooks(): void
    {
        add_action('upgrader_process_complete', [$this, 'upgradeProcessComplete'], 10, 2);
        add_action('wp_update_nav_menu', [$this, 'menuUpdated'], 10, 2);
        add_action('init', [$this, 'maybeSettingsUpdated'], 10, 1);
        add_action('permalink_structure_changed', [$this, 'permalinksUpdated'], 10, 2);
        add_action('update_option', [$this, 'optionUpdated'], 10, 3);

        add_filter('widget_update_callback', [$this, 'widgetUpdated'], 10, 4);
    }

    /**
     * @param WP_Upgrader $upgrader
     * @param array       $options
     *
     * @return void
     */
    public function upgradeProcessComplete(WP_Upgrader $upgrader, array $options): void
    {
        $type = $options['type'] ?? null;
        if ($type == 'core') {
            $action = $options['action'] ?? null;

            switch ($action) {
                case 'update':
                    Logtivity::log('WP Core Updated')->send();
                    break;

                case 'install':
                    Logtivity::log('WP Core Installed')->send();
                    break;
            }
        }
    }

    /**
     * @param int    $menuId
     * @param ?array $menuData
     *
     * @return void
     */
    public function menuUpdated(int $menuId, ?array $menuData = null): void
    {
        $menuName = $menuData['menu-name'] ?? null;

        if ($menuName) {
            Logtivity::log()
                ->setAction('Menu Updated')
                ->setContext($menuName)
                ->addMeta('Menu ID', $menuId)
                ->send();
        }
    }

    /**
     * @param array     $currentSettings
     * @param array     $newSettings
     * @param array     $oldSettings
     * @param WP_Widget $widget
     *
     * @return array
     */
    public function widgetUpdated(
        array $currentSettings,
        array $newSettings,
        array $oldSettings,
        WP_Widget $widget
    ): array {
        Logtivity::log()
            ->setAction('Widget Updated')
            ->setContext($widget->name)
            ->addMeta('New Content', $newSettings)
            ->addMeta('Old Content', $oldSettings)
            ->send();

        return $currentSettings;
    }

    /**
     * @TODO: When does this get called?
     *
     * @return void
     */
    public function maybeSettingsUpdated(): void
    {
        $optionPage = sanitize_text_field($_POST['option_page'] ?? '');
        $action     = sanitize_text_field($_POST['action'] ?? '');

        if ($optionPage && $action == 'update') {
            Logtivity::log()
                ->setAction('Settings Updated')
                ->setContext('Core:' . $optionPage)
                ->send();
        }
    }

    /**
     * @param string $option
     *
     * @return bool
     */
    protected function ignoreOption(string $option): bool
    {
        $ignore = in_array($option, $this->ignoredOptions) !== false;
        if ($ignore == false) {
            foreach ($this->ignoredWildcardOptions as $wildcard) {
                if (strpos($option, $wildcard) !== false) {
                    return true;
                }
            }
        }

        return $ignore;
    }

    /**
     * @param string $option
     * @param mixed  $oldValue
     * @param mixed  $newValue
     *
     * @return void
     */
    public function optionUpdated(string $option, $oldValue, $newValue): void
    {
        if (
            $this->getRequestMethod() != 'GET'
            && is_admin()
            && $oldValue !== $newValue
            && $this->ignoreOption($option) == false
            && get_option('logtivity_enable_options_table_logging')
        ) {
            Logtivity::log()
                ->setAction('Option Updated')
                ->setContext($option)
                ->addMetaIf(is_scalar($oldValue), 'Old Value', $oldValue)
                ->addMetaIf(is_scalar($newValue), 'New Value', $newValue)
                ->send();
        }
    }

    /**
     * @return string
     */
    private function getRequestMethod(): string
    {
        return sanitize_text_field($_SERVER['REQUEST_METHOD'] ?? '');
    }

    /**
     * @param string $oldStructure
     * @param string $newStructure
     *
     * @return void
     */
    public function permalinksUpdated(string $oldStructure, string $newStructure): void
    {
        Logtivity::log()
            ->setAction('Permalinks Updated')
            ->setContext($this->getPermalinkStructure($newStructure))
            ->addMeta('Old Structure', $this->getPermalinkStructure($oldStructure))
            ->send();
    }

    /**
     * @param string $structure
     *
     * @return string
     */
    private function getPermalinkStructure(string $structure): string
    {
        return $structure ?: 'Plain';
    }
}

new Logtivity_Core();
