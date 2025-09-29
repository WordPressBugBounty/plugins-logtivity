<?php

/**
 * Plugin Name:       Logtivity
 * Plugin URI:        https://logtivity.io
 * Description:       Record activity logs and errors logs across all your WordPress sites.
 * Author:            Logtivity
 * Version:           3.3.0
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
    protected string $version = '3.3.0';

    /**
     * Integrations with other plugins
     *
     * @var array[]
     */
    private array $integrations = [
        WP_DLM::class                 => 'Download_Monitor',
        MeprCtrlFactory::class        => 'Memberpress',
        Easy_Digital_Downloads::class => 'Easy_Digital_Downloads',
        EDD_Software_Licensing::class => 'Easy_Digital_Downloads/Licensing',
        EDD_Recurring::class          => 'Easy_Digital_Downloads/Recurring',
        FrmHooksController::class     => 'Formidable',
        PMXI_Plugin::class            => 'WP_All_Import',
        \Code_Snippets\Plugin::class  => 'Code_Snippets',
    ];

    public function __construct()
    {
        $this->loadCore();
        $this->activateLoggers();

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
     * @return self
     */
    public static function init(): self
    {
        return new static();
    }

    /**
     * @return void
     */
    protected function loadCore(): void
    {
        $requires = array_merge(
            $this->getFiles(__DIR__ . '/functions'),
            $this->getFiles(__DIR__ . '/Base')
        );
        foreach ($requires as $file) {
            require_once $file;
        }

        $coreFiles   = $this->getFiles(__DIR__ . '/Core');
        $initClasses = [];
        foreach ($coreFiles as $file) {
            require_once $file;
            $className = basename($file, '.php');
            if (is_callable([$className, 'init'])) {
                $initClasses[] = $className;
            }
        }
        foreach ($initClasses as $class) {
            call_user_func([$class, 'init']);
        }
    }

    protected function activateLoggers(): void
    {
        add_action('plugins_loaded', function () {
            $this->updateCheck();

            if ($this->defaultLoggingDisabled() == false) {
                $this->loadCoreLoggers();
                $this->loadIntegrations();
            }
        });

    }

    /**
     * @param string $path
     * @param bool   $recurse
     * @param string $extension
     *
     * @return array
     */
    protected function getFiles(string $path, bool $recurse = true, string $extension = 'php'): array
    {
        if (is_dir($path)) {
            $files = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
        } elseif (is_file($path)) {
            return [realpath($path)];
        } else {
            return [];
        }

        $list = [];
        foreach ($files as $file) {
            if ($file->isFile()) {
                if ($file->getExtension() == $extension) {
                    $list[] = $file->getRealPath();
                }

            } elseif ($recurse) {
                $list = array_merge($list, $this->getFiles($file->getRealPath(), $recurse, $extension));
            }
        }

        return $list;
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
            $this->checkCapabilities();
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
    protected function checkCapabilities(): void
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
    protected function defaultLoggingDisabled(): bool
    {
        return (bool)(new Logtivity_Options())->getOption('logtivity_disable_default_logging');
    }

    /**
     * @return void
     */
    protected function loadCoreLoggers(): void
    {
        $coreLoggers = $this->getFiles(__DIR__ . '/Loggers/Core');
        foreach ($coreLoggers as $logger) {
            require_once $logger;
        }
    }

    /**
     * @return void
     */
    protected function loadIntegrations(): void
    {
        $loggerFolder = __DIR__ . '/Loggers/';

        foreach ($this->integrations as $key => $folder) {
            $integrationFolder = $loggerFolder . $folder;
            if (class_exists($key)) {
                if (is_dir($integrationFolder . '/Base')) {
                    // Load any base classes
                    $baseFiles = $this->getFiles($integrationFolder . '/Base');
                    foreach ($baseFiles as $file) {
                        require_once $file;
                    }
                }

                $files = $this->getFiles($integrationFolder, false);
                foreach ($files as $file) {
                    require_once $file;
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

        $this->checkCapabilities();

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

Logtivity::init();
