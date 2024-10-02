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

class Logtivity_Dismiss_Notice_Controller
{
	protected $notices = [
		'logtivity-site-url-has-changed-notice'
	];

	public function __construct()
	{
		add_action("wp_ajax_nopriv_logtivity_dismiss_notice", [$this, 'dismiss']);
		add_action("wp_ajax_logtivity_dismiss_notice", [$this, 'dismiss']);
	}

	public function dismiss()
	{
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		if (!in_array($_POST['type'], $this->notices)) {
			return;
		}

		if (isset($_POST['dismiss_until']) && $_POST['dismiss_until']) {
			set_transient(
				'dismissed-' . $_POST['type'],
				true, 
				(isset($_POST['dismiss_until']) ? intval($_POST['dismiss_until']) : 0)
			);
		} else {
		    update_option('dismissed-' . $_POST['type'], true);
		}

		wp_send_json(['message' => 'success']);
	}
}

new Logtivity_Dismiss_Notice_Controller;