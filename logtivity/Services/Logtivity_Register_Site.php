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

/**
 * @TODO: Review. Can be used to register sites via team api
 */
class Logtivity_Register_Site
{
	public static function execute($data = [])
	{
		$logtivity_options = new Logtivity_Options;

		if ($logtivity_options->getApiKey()) {
		    return new WP_Error(
		    	'logtivity_register_site_error',
		    	__('You cannot register a site that already has an API Key.', 'logtivity')
		    );
		}

		$response = json_decode(
			(new Logtivity_Api)->setApiKey($data['team_api_key'])
				->post('/sites', [
					'name' => $data['name'] ?? get_bloginfo('name'),
					'url' => $data['url'] ?? home_url(),
				])
		);

		if ($response && property_exists($response, 'message')) {
			return wp_send_json(['response' => $response->message]);
		}

		$logtivity_options->update(
			array_merge(
				$data,
				[
					'logtivity_site_api_key' => $response->api_key
				]
			)
		);
	}
}
