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

abstract class Logtivity_Abstract_Easy_Digital_Downloads
{
	public function getDownloadTitle($licenseOrSubscriptionOrDownloadId, $price_id = null)
	{
		$priceOptionName = false;

		if ($licenseOrSubscriptionOrDownloadId instanceof EDD_Subscription) {
			$download_id = $licenseOrSubscriptionOrDownloadId->product_id;
			$price_id = $licenseOrSubscriptionOrDownloadId->price_id;
		} elseif($licenseOrSubscriptionOrDownloadId instanceof EDD_SL_License) {
			$download_id = $licenseOrSubscriptionOrDownloadId->download_id;
			$price_id = $licenseOrSubscriptionOrDownloadId->price_id;
		} else {
			$download_id = $licenseOrSubscriptionOrDownloadId;
		}
		
		$prices = edd_get_variable_prices($download_id);

		if ($prices && count($prices) && $price_id) {
			if (isset($prices[$price_id])) {
				$priceOptionName = $prices[$price_id]['name'];
			}
		}

		return logtivity_get_the_title($download_id) . ( $priceOptionName ? ' - ' .$priceOptionName : '');
	}
}