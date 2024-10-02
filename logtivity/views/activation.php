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
<div class="updated notice is-dismissible">

	<img style="margin: 20px 0 20px; display: block; width: 200px; height: auto;" src="<?php echo plugin_dir_url('logtivity.php') ?>logtivity/assets/logtivity-logo.svg" alt="Logtivity">

	<p><strong>Welcome to Logtivity!</strong> Here's how it works:</p>

	<ol>
		<li>Make sure you have an account set up with <a target="_blank" href="https://logtivity.io/">Logtivity</a></li>
		<li><a target="_blank" href="https://logtivity.io/docs/connect-your-site-to-logtivity/">Create a site</a> inside the Logtivity app and copy your API key.</li>
		<li>Paste your API key in your <a href="<?php echo admin_url( 'admin.php?page=logtivity-settings' ) ?>">plugin settings page</a>.</li>
		<li>Configure your options and you're good to go!</li>
	</ol>

	<p>For more information getting started and what you can do with Logtivity <a href="http://logtivity.io/docs" target="_blank">read this guide here.</a></p>

</div>