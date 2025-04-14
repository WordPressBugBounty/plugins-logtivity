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

