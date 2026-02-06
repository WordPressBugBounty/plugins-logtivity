<?php

if (function_exists('logtivity_dd') == false) {
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

    /**
     *
     * Dump and Die variable or whatever
     *
     * @param $dump
     *
     */
    function logtivity_dd($dump)
    {
        echo '<pre>';
        var_export($dump);
        echo '</pre>';
        die();
    }

    /**
     * Global function for easy use in themes/plugins for logging custom actions
     *
     * @param ?string $action
     * @param ?string $meta
     * @param ?string $user_id
     *
     * @return ?mixed
     * @deprecated v3.1.8 - use Logtivity::log() instead
     */
    function logtivity_log(?string $action = null, ?string $meta = null, ?string $user_id = null): void
    {
        Logtivity::log($action, $meta, $user_id)->send();
    }

    /**
     * Load a view and pass variables into it
     *
     * To output a view you would want to echo it
     *
     * @param string $fileName excluding file extension
     * @param array  $vars
     *
     * @return string
     */
    function logtivity_view(string $fileName, array $vars = []): string
    {
        extract($vars);
        unset($vars);

        ob_start();

        include(__DIR__ . '/../views/' . str_replace('.', '/', $fileName) . '.php');

        return ob_get_clean();
    }

    /**
     * @param int $postId
     *
     * @return string
     */
    function logtivity_get_the_title(int $postId): string
    {
        $wpTexturize = remove_filter('the_title', 'wptexturize');

        $title = get_the_title($postId);

        if ($wpTexturize) {
            add_filter('the_title', 'wptexturize');
        }

        return $title;
    }

    /**
     * @return string
     */
    function logtivity_get_api_url(): string
    {
        if (defined('LOGTIVITY_API_URL')) {
            $customUrl = rtrim(sanitize_url(LOGTIVITY_API_URL), '/');
        }

        return $customUrl ?? 'https://api.logtivity.io';
    }

    /**
     * @return string
     */
    function logtivity_get_app_url(): string
    {
        if (defined('LOGTIVITY_APP_URL')) {
            $customUrl = rtrim(sanitize_url(LOGTIVITY_APP_URL), '/');
        }

        return $customUrl ?? 'https://app.logtivity.io';
    }

    /**
     * @return bool
     */
    function logtivity_has_site_url_changed(): bool
    {
        $hash = (new Logtivity_Options())->urlHash();

        return $hash != md5(home_url());
    }

    /**
     * @return array
     */
    function logtivity_get_error_levels(): array
    {
        static $errorLevels = null;
        if ($errorLevels === null) {
            $errorLevels = [];
            $allCodes    = get_defined_constants();
            foreach ($allCodes as $code => $constant) {
                if (strpos($code, 'E_') === 0) {
                    $errorLevels[$constant] = $code;
                }
            }
        }

        return $errorLevels;
    }

    /**
     * @param array $array
     *
     * @return bool
     */
    function logtivity_is_associative(array $array): bool
    {
        foreach ($array as $key => $value) {
            if (is_string($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all known capabilities
     *
     * @return array
     */
    function logtivity_get_capabilities(): array
    {
        $capabilities = [];
        if ($roles = wp_roles()) {
            foreach ($roles->roles as $role) {
                $capabilities = array_merge($capabilities, $role['capabilities']);
            }
        }

        return $capabilities;
    }

    /**
     * @param string $label
     *
     * @return string
     */
    function logtivity_logger_label(string $label): string
    {
        global $wpdb;

        return ucwords(
            str_replace(
                ['_', '-'], ' ',
                str_replace($wpdb->get_blog_prefix(), '', $label)
            )
        );
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    function logtivity_logger_value($value): string
    {
        $value = is_string($value) ? maybe_unserialize($value) : $value;

        switch (gettype($value)) {
            case 'NULL':
                return 'NULL';

            case 'object':
                $value = get_object_vars($value);
            // fall through to array type
            case 'array':
                return print_r($value, true);

            case 'boolean':
                return $value ? 'True' : 'False';

            default:
                return empty($value) ? '[Empty]' : $value;
        }
    }

    /**
     * @param string $time
     *
     * @return string
     */
    function logtivity_datetime(string $time): string
    {
        $format = get_option('date_format') . ' ' . get_option('time_format');

        return get_date_from_gmt($time, $format);
    }
}
