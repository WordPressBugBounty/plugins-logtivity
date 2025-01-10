<?php

/**
 * Plugin Name: Logtivity
 * Plugin URI:  https://logtivity.io
 * Description: Record activity logs and errors logs across all your WordPress sites.
 * Version:     3.1.4
 * Author:      Logtivity
 * Text Domain: logtivity
 */

/**
 * @package   Logtivity
 * @contact   logtivity.io, hello@logtivity.io
 * @copyright 2024 Logtivity. All rights reserved
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

class Logtivity
{
    /**
     * @var string
     */
    protected string $version = '3.1.4';

    /**
     * List all classes here with their file paths. Keep class names the same as filenames.
     *
     * @var string[]
     */
    private array $dependencies = [
        'Helpers/Helpers',
        'Helpers/Logtivity_Wp_User',
        'Admin/Logtivity_Log_Index_Controller',
        'Admin/Logtivity_Dismiss_Notice_Controller',
        'Admin/Logtivity_Options',
        'Admin/Logtivity_Admin',
        'Services/Logtivity_User_Logger_Trait',
        'Services/Logtivity_Api',
        'Services/Logtivity_Logger',
        'Services/Logtivity_Register_Site',
        'Helpers/Logtivity_Log_Global_Function',
        'Logs/Logtivity_Abstract_Logger',
        'Services/Logtivity_Check_For_Disabled_Individual_Logs',
        'Services/Logtivity_Check_For_New_Settings',
        /**
         * Error logging
         */
        'Errors/Logtivity_Stack_Trace_Snippet',
        'Errors/Logtivity_Stack_Trace',
        'Errors/Logtivity_Error_Logger',
        'Errors/Logtivity_Error_Log',
    ];

    /**
     * @var string[]
     */
    private array $logClasses = [
        /**
         * Activity logging
         */
        'Logs/Core/Logtivity_Post',
        'Logs/Core/Logtivity_User',
        'Logs/Core/Logtivity_Core',
        'Logs/Core/Logtivity_Theme',
        'Logs/Core/Logtivity_Plugin',
        'Logs/Core/Logtivity_Comment',
        'Logs/Core/Logtivity_Term',
        'Logs/Core/Logtivity_Meta',
    ];

    /**
     * List all integration dependencies
     *
     * @var array[]
     */
    private array $integrationDependencies = [
        'WP_DLM'                 => [
            'Logs/Download_Monitor/Logtivity_Download_Monitor',
        ],
        'MeprCtrlFactory'        => [
            'Logs/Memberpress/Logtivity_Memberpress',
        ],
        'Easy_Digital_Downloads' => [
            'Logs/Easy_Digital_Downloads/Logtivity_Abstract_Easy_Digital_Downloads',
            'Logs/Easy_Digital_Downloads/Logtivity_Easy_Digital_Downloads',
        ],
        'EDD_Software_Licensing' => [
            'Logs/Easy_Digital_Downloads/Logtivity_Easy_Digital_Downloads_Software_Licensing',
        ],
        'EDD_Recurring'          => [
            'Logs/Easy_Digital_Downloads/Logtivity_Easy_Digital_Downloads_Recurring',
        ],
        'FrmHooksController'     => [
            'Logs/Formidable/Logtivity_FrmEntryFormatter',
            'Logs/Formidable/Logtivity_Formidable',
        ],
        'PMXI_Plugin'            => [
            'Logs/WP_All_Import/Logtivity_WP_All_Import',
        ],
        '\Code_Snippets\Plugin'  => [
            'Logs/Code_Snippets/Logtivity_Code_Snippets',
        ],
    ];

    public function __construct()
    {
        $this->loadDependencies();

        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'addSettingsLinkFromPluginsPage']);

        register_activation_hook(__FILE__, [$this, 'activated']);

        add_action('upgrader_process_complete', [$this, 'upgradeProcessComplete'], 10, 2);

        add_action('activated_plugin', [$this, 'setLogtivityToLoadFirst']);

        add_action('admin_notices', [$this, 'welcomeMessage']);

        add_action('admin_notices', [$this, 'checkForSiteUrlChange']);

        add_action('admin_enqueue_scripts', [$this, 'loadScripts']);
    }

    public function loadDependencies()
    {
        foreach ($this->dependencies as $filePath) {
            $this->loadFile($filePath);
        }

        add_action('plugins_loaded', function () {
            if ($this->defaultLoggingDisabled()) {
                return;
            }

            $this->maybeLoadLogClasses();

            $this->loadIntegrationDependencies();
        });
    }

    public function loadFile($filePath)
    {
        require_once plugin_dir_path(__FILE__) . $filePath . '.php';
    }

    /**
     * Is the default Event logging from within the plugin enabled
     *
     * @return bool
     */
    public function defaultLoggingDisabled(): bool
    {
        return (bool)(new Logtivity_Options())->getOption('logtivity_disable_default_logging');
    }

    public function maybeLoadLogClasses()
    {
        foreach ($this->logClasses as $filePath) {
            $this->loadFile($filePath);
        }
    }

    public function loadIntegrationDependencies()
    {
        foreach ($this->integrationDependencies as $key => $value) {
            if (class_exists($key)) {
                foreach ($value as $filePath) {
                    $this->loadFile($filePath);
                }
            }
        }
    }

    public static function log($action = null, $meta = null, $user_id = null)
    {
        return Logtivity_Logger::log($action, $meta, $user_id);
    }

    /**
     * @param array $error
     *
     * @return Logtivity_Error_Logger
     */
    public static function logError(array $error): Logtivity_Error_Logger
    {
        return new Logtivity_Error_Logger($error);
    }

    /**
     * @param $upgraderObject
     * @param $options
     *
     * @return null|void
     */
    public function upgradeProcessComplete($upgraderObject, $options)
    {
        $type   = $options['type'] ?? null;
        $action = $options['action'] ?? null;

        if ($type == 'plugin' && $action == 'update') {
            $this->setLogtivityToLoadFirst();
        }
    }

    /**
     * @return void
     */
    public function setLogtivityToLoadFirst(): void
    {
        $path = str_replace(WP_PLUGIN_DIR . '/', '', __FILE__);

        if ($plugins = get_option('active_plugins')) {
            if ($key = array_search($path, $plugins)) {
                array_splice($plugins, $key, 1);
                array_unshift($plugins, $path);
                update_option('active_plugins', $plugins);
            }
        }
    }

    /**
     * @param array $links
     *
     * @return string[]
     */
    public function addSettingsLinkFromPluginsPage(array $links): array
    {
        if (apply_filters('logtivity_hide_settings_page', false)) {
            return $links;
        }

        return array_merge(
            [
                sprintf('<a href="%s">Settings</a>', admin_url('admin.php?page=logtivity-settings')),
            ],
            $links
        );
    }

    /**
     * @return void
     */
    public function activated(): void
    {
        if (apply_filters('logtivity_hide_settings_page', false)) {
            return;
        }

        set_transient('logtivity-welcome-notice', true, 5);
    }

    /**
     * @return void
     */
    public function welcomeMessage(): void
    {
        if (get_transient('logtivity-welcome-notice')) {
            echo logtivity_view('activation');

            delete_transient('logtivity-welcome-notice');
        }
    }

    /**
     * @return void
     */
    public function checkForSiteUrlChange(): void
    {
        if (
            current_user_can('manage_options')
            && logtivity_has_site_url_changed()
            && !get_transient('dismissed-logtivity-site-url-has-changed-notice')
        ) {
            echo logtivity_view('site-url-changed-notice');
        }
    }

    /**
     * @return void
     */
    public function loadScripts(): void
    {
        wp_enqueue_style(
            'logtivity_google_font_admin_css',
            'https://fonts.googleapis.com/css?family=IBM+Plex+Sans:400,500',
            false,
            $this->version
        );
        wp_enqueue_style(
            'logtivity_admin_css',
            plugin_dir_url(__FILE__) . 'assets/admin.css',
            ['logtivity_google_font_admin_css'],
            $this->version
        );
        wp_enqueue_script(
            'logtivity_admin_js',
            plugin_dir_url(__FILE__) . 'assets/app.js',
            false,
            $this->version
        );
    }
}

$logtivity = new Logtivity();
