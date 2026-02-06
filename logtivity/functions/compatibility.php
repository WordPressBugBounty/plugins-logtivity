<?php

/**
 * @package   Logtivity
 * @copyright 2025 Logtivity.io. All rights reserved
 * @contact   logtivity.io, hello@logtivity.io
 *
 * This file is part of Logtivity
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
    function get_user( $user_id ) {
        return get_user_by( 'id', $user_id );
    }
}
