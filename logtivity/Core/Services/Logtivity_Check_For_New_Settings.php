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

class Logtivity_Check_For_New_Settings
{
    public function __construct()
    {
        add_action('after_setup_theme', [$this, 'maybeCheckForNewSettings']);
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
    public function maybeCheckForNewSettings(): void
    {
        if ($this->shouldCheckInWithApi()) {
            $this->checkForNewSettings();
        }
    }

    /**
     * @return bool
     */
    public function shouldCheckInWithApi(): bool
    {
        return (new Logtivity_Options())->shouldCheckInWithApi();
    }

    /**
     * @return void
     */
    public function checkForNewSettings(): void
    {
        global $wp_version;

        if (!function_exists('get_core_updates')) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }

        try {
            $coreUpdates = get_core_updates();

            $latestWPVersion       = $coreUpdates[0]->current ?? null;
            $latestMinPhpVersion   = $coreUpdates[0]->php_version ?? null;
            $latestMinMySqlVersion = $coreUpdates[0]->mysql_version ?? null;

            $api = new Logtivity_Api();

            $response = $api
                ->ignoreStatus()
                ->waitForResponse()
                ->makeRequest(
                    '/settings-check',
                    array_merge(
                        (new Logtivity_Site_Inventory())->get(),
                        [
                            'php_version'                 => phpversion(),
                            'wordpress_version'           => $wp_version,
                            'latest_wp_version'           => $latestWPVersion,
                            'latest_wp_min_php_version'   => $latestMinPhpVersion,
                            'latest_wp_min_mysql_version' => $latestMinMySqlVersion,
                        ]
                    )
                );

            $settings = $response['body']['settings'] ?? null;
            if ($settings) {
                $api->updateSettings($settings);
            }
        } catch (Throwable $error) {
            // Ignore
            unset($error);
        }
    }
}
