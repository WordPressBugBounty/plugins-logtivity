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

if (function_exists('array_is_list') == false) {
    /**
     * php 8.1 function added to WordPress 6.5.0,
     * but we claim compatibility with earlier WordPress
     */
    function array_is_list(array $arr)
    {
        if ($arr === []) {
            return true;
        }

        return array_keys($arr) === range(0, count($arr) - 1);
    }
}

if (function_exists('get_user') == false) {
    /**
     * Compatibility with WP 6.6
     * See v6.7 of wp-includes/user.php
     *
     * @param int $user_id User ID.
     *
     * @return WP_User|false WP_User object on success, false on failure.
     */
    function get_user($user_id)
    {
        return get_user_by('id', $user_id);
    }
}
