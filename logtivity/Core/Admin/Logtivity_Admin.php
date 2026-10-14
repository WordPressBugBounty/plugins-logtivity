<?php

/**
 * @package   Logtivity
 * @contact   logtivity.io, hello@logtivity.io
 * @copyright 2024-2026 Logtivity. All rights reserved
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

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

class Logtivity_Admin
{
    /**
     * @var Logtivity_Options
     */
    protected Logtivity_Options $options;

    /**
     * @var bool
     */
    protected static bool $shouldHidePluginFromUI = false;

    public function __construct()
    {
        add_action('admin_menu', [$this, 'registerOptionsPage']);

        add_action('wp_ajax_logtivity_update_settings', [$this, 'update']);

        add_action('wp_ajax_logtivity_register_site', [$this, 'registerSite']);

        add_filter('logtivity_hide_from_menu', [$this, 'shouldHidePluginFromUI']);
        add_filter('all_plugins', [$this, 'maybeHideFromMenu']);

        $this->options = new Logtivity_Options();
    }

    /**
     * @return self
     */
    public static function init(): self
    {
        return new static();
    }

    /**
     * @param array $plugins
     *
     * @return array
     */
    public function maybeHideFromMenu(array $plugins): array
    {
        $name = (new Logtivity_Options())->customPluginName();
        if ($name) {
            if (isset($plugins['logtivity/logtivity.php'])) {
                $plugins['logtivity/logtivity.php']['Name'] = $name;
            }
        }

        if (!$this->shouldHidePluginFromUI()) {
            return $plugins;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- show_all only controls local admin list display.
        $shouldHide = !array_key_exists('show_all', $_GET);

        if ($shouldHide) {
            $hiddenPlugins = [
                'logtivity/logtivity.php',
            ];

            foreach ($hiddenPlugins as $hiddenPlugin) {
                unset($plugins[$hiddenPlugin]);
            }
        }
        return $plugins;
    }

    /**
     * @param bool $value
     *
     * @return bool
     */
    public function shouldHidePluginFromUI(bool $value = false): bool
    {
        static::$shouldHidePluginFromUI = (new Logtivity_Options())->isPluginHiddenFromUI();

        if (static::$shouldHidePluginFromUI) {
            return static::$shouldHidePluginFromUI;
        }

        return $value;
    }

    /**
     * Create the admin menus
     *
     * @return void
     */
    public function registerOptionsPage(): void
    {
        if (!apply_filters('logtivity_hide_from_menu', false)) {
            add_menu_page(
                ($this->options->isWhiteLabelMode() ? 'Logs' : 'Logtivity'),
                ($this->options->isWhiteLabelMode() ? 'Logs' : 'Logtivity'),
                Logtivity::ACCESS_LOGS,
                ($this->options->isWhiteLabelMode() ? 'lgtvy-logs' : 'logtivity'),
                [$this, 'showLogIndexPage'],
                'dashicons-chart-area',
                26
            );
        }

        if (!apply_filters('logtivity_hide_settings_page', false)) {
            add_submenu_page(
                $this->options->isWhiteLabelMode() ? 'lgtvy-logs' : 'logtivity',
                'Logtivity Settings',
                'Settings',
                Logtivity::ACCESS_SETTINGS,
                'logtivity-settings',
                [$this, 'showSettingsPage']
            );
        }

        if ($this->options->getApiKey() == false) {
            add_submenu_page(
                $this->options->isWhiteLabelMode() ? 'lgtvy-logs' : 'logtivity',
                'Register Site',
                'Register Site',
                Logtivity::ACCESS_SETTINGS,
                'logtivity-register-site',
                [$this, 'showRegisterSitePage']
            );
        }
    }

    /**
     * Show the admin log index
     *
     * @return void
     */
    public function showLogIndexPage(): void
    {
        if (!current_user_can(Logtivity::ACCESS_LOGS)) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'logtivity'));
        }

        $options = $this->options->getOptions();

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- logtivity_view() renders trusted plugin templates.
        echo logtivity_view('log-index', compact('options'));
    }

    /**
     * Show the admin settings template
     *
     * @return void
     */
    public function showSettingsPage(): void
    {
        if (!current_user_can(Logtivity::ACCESS_SETTINGS)) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'logtivity'));
        }

        $options = $this->options->getOptions();

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- logtivity_view() renders trusted plugin templates.
        echo logtivity_view('settings', compact('options'));
    }

    /**
     * Show the register by team API page
     *
     * @return void
     */
    public function showRegisterSitePage(): void
    {
        if (!current_user_can(Logtivity::ACCESS_SETTINGS)) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'logtivity'));
        }

        $options = $this->options->getOptions();

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- logtivity_view() renders trusted plugin templates.
        echo logtivity_view('register', compact('options'));
    }

    /**
     * @return void
     */
    public function update(): void
    {
        $nonce = isset($_POST['logtivity_update_settings'])
            ? sanitize_text_field(wp_unslash($_POST['logtivity_update_settings']))
            : '';

        if (
            !wp_verify_nonce(
                $nonce,
                'logtivity_update_settings'
            )
        ) {
            wp_safe_redirect($this->settingsPageUrl());
            exit();
        }

        if (!current_user_can(Logtivity::ACCESS_SETTINGS)) {
            wp_safe_redirect($this->settingsPageUrl());
            exit();
        }

        $this->options->update(
            [
                'logtivity_url_hash' => md5(home_url()),
            ],
            false
        );

        delete_transient('dismissed-logtivity-site-url-has-changed-notice');

        $this->options->update();

        if (!$this->options->getOption('logtivity_enable_debug_mode')) {
            (new Logtivity_Debug_Log())->delete();
        }

        (new Logtivity_Check_For_New_Settings())->checkForNewSettings();

        wp_safe_redirect($this->settingsPageUrl());
        exit();
    }

    /**
     * ajax endpoint for registering with a team API Key
     *
     * @return void
     */
    public function registerSite(): void
    {
        try {
            if (!current_user_can(Logtivity::ACCESS_SETTINGS)) {
                wp_send_json_error('Unauthorized');
            }

            $nonce = isset($_POST['logtivity_register_site'])
                ? sanitize_text_field(wp_unslash($_POST['logtivity_register_site']))
                : '';

            if (
                wp_verify_nonce(
                    $nonce,
                    'logtivity_register_site'
                )
            ) {
                $teamApi = isset($_POST['logtivity_team_api_key'])
                    ? sanitize_text_field(wp_unslash($_POST['logtivity_team_api_key']))
                    : '';

                $response = Logtivity::registerSite($teamApi);


                if ($response instanceof WP_Error) {
                    wp_send_json_error($response);
                } else {
                    wp_send_json_success($response);
                }
            } else {
                wp_send_json_error('Invalid Request');
            }
        } catch (Throwable $error) {
            wp_send_json_error($error->getMessage(), $error->getCode());
        }

        wp_die();
    }

    /**
     * @return string
     */
    public function settingsPageUrl(): string
    {
        return admin_url('admin.php?page=logtivity-settings');
    }
}
