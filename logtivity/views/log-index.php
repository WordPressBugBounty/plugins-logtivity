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

echo logtivity_view('_admin-header', compact('options'));
?>

<div class="postbox">
	<div class="logtivity-settings">

		<div class="inside">
			<h1 style="padding-top: 10px;">Logs</h1>
		</div>

		<form id="logtivity-log-index-search-form" method="GET" action="<?php echo admin_url( 'admin-ajax.php' ) ?>">
			<input type="hidden" name="action" value="logtivity_log_index_filter">
			<input id="logtivity_page" type="hidden" name="page">

			<div class="logtivity-row">
				<div class="logtivity-col-md-4">
					<label>
						<?php esc_html_e( 'Search', 'logtivity' ) ?>
					</label>
					<input type="search" name="search_action" class="large-text" placeholder="<?php esc_attr_e( 'User Created or %keyword%', 'logtivity' ) ?>">
				</div>
				<div class="logtivity-col-md-4">
					<label>
						<?php esc_html_e( 'Context', 'logtivity' ) ?>
					</label>
					<input type="search" name="search_context" class="large-text" placeholder="<?php esc_attr_e( 'admin or %keyword%', 'logtivity' ) ?>">
				</div>
				<div class="logtivity-col-md-4">
					<label>
						<?php esc_html_e( 'User', 'logtivity' ) ?>
					</label>
					<input type="search" name="action_user" class="large-text" placeholder="<?php esc_attr_e( 'User ID / Username / IP Address', 'logtivity' ) ?>">
				</div>
			</div>
		</form>

		<div style="padding-top: 15px" id="logtivity-log-index">
			<!-- Populated with AJAX -->
		</div>

	</div>
</div>

<?php echo logtivity_view('_admin-footer', compact('options')); ?>