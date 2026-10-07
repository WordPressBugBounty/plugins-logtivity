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

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

class Logtivity_Site_Inventory
{
    /**
     * Snapshot of installed plugins and themes, as sent to the API for
     * comparison against its stored copy.
     *
     * @return array
     */
    public function get(): array
    {
        if (function_exists('get_plugins') == false) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        /*
         * The plugin/theme lists are cached per request and WordPress does not
         * always clear them before its post-change hooks fire (e.g. background
         * auto-updates and plugin deletion), so force a fresh read. Never clear
         * the update transients here: deleting update_themes fires the hook used
         * to detect theme deletions.
         */
        wp_clean_plugins_cache(false);
        wp_clean_themes_cache(false);

        $theme = wp_get_theme();

        return [
            'plugins'             => $this->getPlugins(),
            'theme_name'          => $theme ? $theme->name : null,
            'theme_version'       => $theme ? $theme->version : null,
            'themes'              => $this->getThemes(),
            'inventory_timestamp' => microtime(true),
        ];
    }

    /**
     * @return array[]
     */
    public function getPlugins(): array
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
     * @return array[]
     */
    public function getThemes(): array
    {
        $themesDetails = [];

        foreach (wp_get_themes() as $theme) {
            $themesDetails[] = [
                'name'    => $theme->name,
                'version' => $theme->version,
            ];
        }

        return $themesDetails;
    }

    /**
     * @param object $object
     * @param string $property
     *
     * @return ?mixed
     */
    protected function getPropertyIfExists(object $object, string $property)
    {
        return $object->{$property} ?? null;
    }
}
