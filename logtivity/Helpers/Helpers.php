<?php

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

/**
 *
 * Dump and Die variable or whatever
 *
 * @param $dump
 *
 */
function logtivity_dd($dump) {
	echo "<pre>";
	var_export($dump);
	echo "</pre>";
	die();
}

/**
 * Load a view and pass variables into it
 *
 * To ouput a view you would want to echo it
 * 
 * @param  string $fileName excluding file extension
 * @param  array  $vars
 * @return string
 */
function logtivity_view($fileName, $vars = array()) {

    foreach ($vars as $key => $value) {
        
        ${$key} = $value;

    }

    ob_start();

    include( dirname(__FILE__) . '/../views/' . str_replace('.', '/', $fileName) . '.php');

    return ob_get_clean();
}

/**
 * Get Site API Key
 * 
 * @return string
 */
function logtivity_get_api_key() {
    return sanitize_text_field(
        (new Logtivity_Options)->getOption('logtivity_site_api_key')
    );
}

function logtivity_get_the_title($post_id) {
    $wptexturize = remove_filter( 'the_title', 'wptexturize' );
    
    $title = get_the_title($post_id);

    if ( $wptexturize ) {
        add_filter( 'the_title', 'wptexturize' );
    }

    return $title;
}

function logtivity_get_api_url()
{
    if (defined('LOGTIVITY_API_URL')) {
        return LOGTIVITY_API_URL;
    }

    return 'https://api.logtivity.io';
}

function logtivity_has_site_url_changed()
{
    $hash = (new Logtivity_Options)->urlHash();

    if (!$hash) {
        return false;
    }

    return $hash !== md5(home_url());
}

function logtivity_get_error_levels()
{
    return [
        E_ALL => 'E_ALL',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        E_DEPRECATED => 'E_DEPRECATED',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_STRICT => 'E_STRICT',
        E_USER_NOTICE => 'E_USER_NOTICE',
        E_USER_WARNING => 'E_USER_WARNING',
        E_USER_ERROR => 'E_USER_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_CORE_WARNING => 'E_CORE_WARNING',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_NOTICE => 'E_NOTICE',
        E_PARSE => 'E_PARSE',
        E_WARNING => 'E_WARNING',
        E_ERROR => 'E_ERROR',
    ];
}