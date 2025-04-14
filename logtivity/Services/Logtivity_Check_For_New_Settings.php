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

class Logtivity_Check_For_New_Settings
{
    public function __construct()
    {
        add_action('after_setup_theme', [$this, 'maybeCheckForNewSettings']);
    }

    /**
     * @return void
     */
    public function maybeCheckForNewSettings(): void
    {
        if ($this->shouldCheckInWithApi()) {
            $this->checkForNewSettings();
        }
    }

    /**
     * @return void
     */
    public function checkForNewSettings(): void
    {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        update_option('logtivity_last_settings_check_in_at', ['date' => date('Y-m-d H:i:s')]);

        try {
            $api = new Logtivity_Api();

            $theme = wp_get_theme();

            global $wp_version;

            if (!function_exists('get_core_updates')) {
                require_once ABSPATH . 'wp-admin/includes/update.php';
            }

            $coreUpdates = get_core_updates();

            $latestWPVersion       = $coreUpdates[0]->current ?? null;
            $latestMinPhpVersion   = $coreUpdates[0]->php_version ?? null;
            $latestMinMySqlVersion = $coreUpdates[0]->mysql_version ?? null;

            $response = $api->post('/settings-check', [
                'php_version'                 => phpversion(),
                'plugins'                     => $this->getPluginsWithStatuses(),
                'theme_name'                  => $theme ? $theme->name : null,
                'theme_version'               => $theme ? $theme->version : null,
                'themes'                      => $this->getThemesListWithStatuses(),
                'wordpress_version'           => $wp_version,
                'latest_wp_version'           => $latestWPVersion,
                'latest_wp_min_php_version'   => $latestMinPhpVersion,
                'latest_wp_min_mysql_version' => $latestMinMySqlVersion,
            ]);

            if ($response) {
                $api->updateSettings($response);
            }

        } catch (Throwable $error) {
            // Ignore
        }
    }

    /**
     * @return array[]
     */
    private function getThemesListWithStatuses(): array
    {
        $themes = wp_get_themes();

        $themesDetails = [];

        foreach ($themes as $theme) {
            $themesDetails[] = [
                'name'    => $theme->name,
                'version' => $theme->version,
            ];
        }

        return $themesDetails;
    }

    /**
     * @return array[]
     */
    private function getPluginsWithStatuses(): array
    {
        $allPlugins        = get_plugins();
        $activePlugins     = get_option('active_plugins');
        $pluginsWithUpdate = get_site_transient('update_plugins');

        $pluginData = [];

        foreach ($allPlugins as $filePath => $data) {

            $pluginData[$filePath]['Name']      = $data['Name'];
            $pluginData[$filePath]['Version']   = $data['Version'];
            $pluginData[$filePath]['Author']    = $data['Author'];
            $pluginData[$filePath]['AuthorURI'] = $data['AuthorURI'];
            $pluginData[$filePath]['slug']      = $filePath;

            if (in_array($filePath, $activePlugins)) {
                $pluginData[$filePath]['is_active'] = true;
            } else {
                $pluginData[$filePath]['is_active'] = false;
            }

            if (!empty($pluginsWithUpdate->response)) {
                if (array_key_exists($filePath, $pluginsWithUpdate->response)) {
                    $pluginData[$filePath]['update_available'] = true;
                    $pluginData[$filePath]['new_version']      = $this->getPropertyIfExists(
                        $pluginsWithUpdate->response[$filePath],
                        'new_version'
                    );

                    $pluginData[$filePath]['new_version_requires_wp']  = $this->getPropertyIfExists(
                        $pluginsWithUpdate->response[$filePath],
                        'requires'
                    );
                    $pluginData[$filePath]['tested']                   = $this->getPropertyIfExists(
                        $pluginsWithUpdate->response[$filePath],
                        'tested'
                    );
                    $pluginData[$filePath]['new_version_requires_php'] = $this->getPropertyIfExists(
                        $pluginsWithUpdate->response[$filePath],
                        'requires_php'
                    );

                } else {
                    $pluginData[$filePath]['update_available']         = false;
                    $pluginData[$filePath]['new_version']              = null;
                    $pluginData[$filePath]['new_version_requires_wp']  = null;
                    $pluginData[$filePath]['tested']                   = null;
                    $pluginData[$filePath]['new_version_requires_php'] = null;
                }
            }
        }

        return $pluginData;
    }

    /**
     * @param object $object
     * @param string $property
     *
     * @return ?mixed
     */
    private function getPropertyIfExists(object $object, string $property)
    {
        return $object->{$property} ?? null;
    }

    /**
     * @return bool
     */
    public function shouldCheckInWithApi(): bool
    {
        $latestReponse = get_option('logtivity_last_settings_check_in_at');

        if (is_array($latestReponse) && isset($latestReponse['date'])) {
            return time() - strtotime($latestReponse['date']) > 60 * MINUTE_IN_SECONDS; // 60 minutes
        }

        return true;
    }
}

new Logtivity_Check_For_New_Settings();
