<?php

/**
 * Plugin Name:       Logtivity
 * Plugin URI:        https://logtivity.io
 * Description:       Record activity logs and errors logs across all your WordPress sites.
 * Author:            Logtivity
 * Version:           3.2.0
 * Text Domain:       logtivity
 * Requires at least: 4.7
 * Requires PHP:      7.4
 */

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

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

class Logtivity
{
    public const ACCESS_LOGS     = 'view_logs';
    public const ACCESS_SETTINGS = 'view_log_settings';

    /**
     * @var string
     */
    protected string $version = '3.2.0';

    /**
     * List all classes here with their file paths. Keep class names the same as filenames.
     * Ordering of this list matters!
     * @TODO: Implement psr-0 autoloading
     *
     * @var string[]
     */
    private array $dependencies = [
        'Helpers/Compatibility',
        'Helpers/Helpers',
        'Helpers/Logtivity_Wp_User',
        'Admin/Logtivity_Log_Index_Controller',
        'Admin/Logtivity_Dismiss_Notice_Controller',
        'Admin/Logtivity_Options',
        'Admin/Logtivity_Response',
        'Admin/Logtivity_Admin',
        'Services/Logtivity_User_Logger_Trait',
        'Services/Logtivity_Api',
        'Services/Logtivity_Logger',
        'Services/Logtivity_Register_Site',
        'Helpers/Logtivity_Log_Global_Function',
        'Logs/Logtivity_Abstract_Logger',
        'Services/Logtivity_Check_For_Disabled_Individual_Logs',
        'Services/Logtivity_Check_For_New_Settings',
        'Services/Logtivity_Rest_Endpoints',
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

        add_action('upgrader_process_complete', [$this, 'upgradeProcessComplete'], 10, 2);
        add_action('activated_plugin', [$this, 'setLogtivityToLoadFirst']);
        add_action('admin_notices', [$this, 'welcomeMessage']);
        add_action('admin_notices', [$this, 'checkForSiteUrlChange']);
        add_action('admin_enqueue_scripts', [$this, 'loadScripts']);
        add_action('admin_init', [$this, 'redirect_on_activate']);

        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'addSettingsLinkFromPluginsPage']);

        register_activation_hook(__FILE__, [$this, 'activated']);
    }

    /**
     * @return void
     */
    public function loadDependencies(): void
    {
        foreach ($this->dependencies as $filePath) {
            $this->loadFile($filePath);
        }

        add_action('plugins_loaded', function () {
            $this->updateCheck();

            if ($this->defaultLoggingDisabled()) {
                return;
            }

            $this->maybeLoadLogClasses();

            $this->loadIntegrationDependencies();
        });
    }

    /**
     * @param string $filePath
     *
     * @return void
     */
    public function loadFile(string $filePath): void
    {
        require_once plugin_dir_path(__FILE__) . $filePath . '.php';
    }

    /**
     * Review updates based on version
     *
     * @return void
     */
    public function updateCheck(): void
    {
        $currentVersion = get_option('logtivity_version');

        if (version_compare($currentVersion, '3.1.6', '<=')) {
            static::checkCapabilities();
        }

        if ($currentVersion && version_compare($currentVersion, '3.1.7', '<=')) {
            // Default for updating sites should be no behavior change
            update_option('logtivity_app_verify_url', 0);
        }

        update_option('logtivity_version', $this->version);
    }

    /**
     * Custom capabilities added prior to v3.1.7
     *
     * @return void
     */
    public static function checkCapabilities(): void
    {
        $capabilities = array_filter(
            array_keys(logtivity_get_capabilities()),
            function (string $capability): bool {
                return in_array($capability, [Logtivity::ACCESS_LOGS, Logtivity::ACCESS_SETTINGS]);
            }
        );

        if ($capabilities == false) {
            // Make sure at least admins can access us
            if ($role = get_role('administrator')) {
                if ($role->has_cap(Logtivity::ACCESS_LOGS) == false) {
                    $role->add_cap(Logtivity::ACCESS_LOGS);
                }
                if ($role->has_cap(Logtivity::ACCESS_SETTINGS) == false) {
                    $role->add_cap(Logtivity::ACCESS_SETTINGS);
                }
            }
        }
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

    /**
     * @return void
     */
    public function maybeLoadLogClasses(): void
    {
        foreach ($this->logClasses as $filePath) {
            $this->loadFile($filePath);
        }
    }

    /**
     * @return void
     */
    public function loadIntegrationDependencies(): void
    {
        foreach ($this->integrationDependencies as $key => $value) {
            if (class_exists($key)) {
                foreach ($value as $filePath) {
                    $this->loadFile($filePath);
                }
            }
        }
    }

    /**
     * Main entry for registering a site using the team API Key
     *
     * @param ?string $teamApi
     * @param ?string $teamName
     * @param ?string $siteName
     * @param ?string $url
     *
     * @return null|Logtivity_Response|WP_Error
     */
    public static function registerSite(
        ?string $teamApi,
        ?string $teamName = null,
        ?string $siteName = null,
        ?string $url = null
    ) {
        $logtivityOptions = new Logtivity_Options();

        if ($logtivityOptions->getApiKey()) {
            $response = new WP_Error(
                'logtivity_register_site_error',
                __('You have already entered an API Key for this site.', 'logtivity')
            );

        } elseif ($teamApi) {
            $request = [
                'method'   => 'POST',
                'timeout'  => 6,
                'blocking' => true,
                'body'     => [
                    'team_name' => $teamName,
                    'name'      => $siteName ?: get_bloginfo('name'),
                    'url'       => $url ?: home_url(),
                ],
                'cookies'  => [],
            ];

            $response = new Logtivity_Response($teamApi, '/sites', $request);
            if ($response->code == 200 && $response->error == false) {
                $apikey   = $response->body['api_key'] ?? null;
                $teamName = $response->body['team_name'] ?? '*unknown*';
                $created  = $response->body['created_at'] ?? null;
                $isNew    = $response->body['is_new'] ?? null;

                if ($apikey) {
                    $logtivityOptions->update(['logtivity_site_api_key' => $apikey]);

                    if ($isNew) {
                        $response->message = sprintf(
                            'This site has been created on <a href="%s" target="_blank">Logtivity</a> for team \'%s\'. Logging is now enabled.',
                            logtivity_get_app_url(),
                            $teamName
                        );

                    } else {
                        if ($created) {
                            $createdTimestamp = strtotime($created);
                            $creationText     = sprintf(
                                'It was created on %s at %s ',
                                wp_date(get_option('date_format'), $createdTimestamp),
                                wp_date(get_option('time_format'), $createdTimestamp)
                            );
                        }
                        $response->message = sprintf(
                            'This site was found on <a href="%s" target="_blank">Logtivity</a>. %sfor the team \'%s\'. Logging is now enabled.',
                            logtivity_get_app_url(),
                            $creationText ?? '',
                            $teamName
                        );
                    }
                }
            }

        } else {
            $response = new WP_Error('logtivity_missing_data', 'Team API Key is required.');
        }

        return $response;
    }

    /**
     * @param ?string $action
     * @param ?array  $meta
     * @param ?int    $userId
     *
     * @return Logtivity_Logger
     */
    public static function log(?string $action = null, ?array $meta = null, ?int $userId = null): Logtivity_Logger
    {
        return Logtivity_Logger::log($action, $meta, $userId);
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
     * @param WP_Upgrader $upgraderObject
     * @param array       $options
     *
     * @return void
     */
    public function upgradeProcessComplete(WP_Upgrader $upgraderObject, array $options): void
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
        add_option('logtivity_activate', true);

        static::checkCapabilities();

        if (apply_filters('logtivity_hide_settings_page', false)) {
            return;
        }

        set_transient('logtivity-welcome-notice', true, 5);
    }

    /**
     * Redirect to Settings page
     *
     * @return void
     * @since 3.1.11
     *
     */
    public function redirect_on_activate()
    {
        if (get_option('logtivity_activate')) {
            delete_option('logtivity_activate');

            if (!isset($_GET['activate-multi'])) {
                wp_redirect(admin_url('admin.php?page=logtivity'));
                exit;
            }
        }
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
            current_user_can(static::ACCESS_SETTINGS)
            && logtivity_has_site_url_changed()
            && (new Logtivity_Options())->isWhiteLabelMode() == false
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
