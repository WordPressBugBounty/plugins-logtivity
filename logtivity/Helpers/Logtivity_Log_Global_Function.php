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
 * Global function for easy use in themes/plugins for logging custom actions
 * 
 * @param  string $action
 * @param  string $meta
 * @param  string $user_id
 * @return Logtivity_Logger::send()
 */
function logtivity_log($action = null, $meta = null, $user_id = null)
{
	$Logtivity_logger = new Logtivity_Logger;

	if(func_num_args() == 0) {

		return new $Logtivity_logger;

	}

	return Logtivity_Logger::log($action, $meta, $user_id);
}