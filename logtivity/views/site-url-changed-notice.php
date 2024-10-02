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

?>
<div class="updated notice is-dismissible" notice="logtivity-site-url-has-changed-notice" dismiss-until="<?php echo 60 * 60 * 24 * 7 ?>">

	<img style="margin: 20px 0 20px; display: block; width: 200px; height: auto;" src="<?php echo plugin_dir_url('logtivity.php') ?>logtivity/assets/logtivity-logo.svg" alt="Logtivity">

	<h2 style="margin-top: 0" class="title">We've detected a change in your site URL.</h2>
	<p>Is this a dev or staging environment? As a precaution, we've stopped logging. To start recording logs, again click the 'Update Settings' button on the <a href="<?php echo admin_url( 'admin.php?page=logtivity-settings' ) ?>">settings page</a>.</p>

</div>