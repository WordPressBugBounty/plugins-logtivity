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

$white_label = true;
if(! isset($options['logtivity_enable_white_label_mode']) || $options['logtivity_enable_white_label_mode'] != '1') {
	$white_label = false;
}
?>
<div class="wrap">

	<?php if (! $white_label): ?>
		<div class="logtivity-header">
			<img alt="Logtivity" src="<?php echo plugin_dir_url('logtivity.php') ?>logtivity/assets/logtivity-logo-white.svg">
		</div>
	<?php endif ?>

	<div id="poststuff">

		<div id="post-body" class="metabox-holder<?php echo ! $white_label ? ' columns-2' : '' ?>">

			<div id="post-body-content">