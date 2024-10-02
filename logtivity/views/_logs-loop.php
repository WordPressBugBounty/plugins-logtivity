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
<table class="form-table logtivity-table">
	<thead>
		<tr>
			<th>Action</th>
			<th>Context</th>
			<th>User</th>
			<th colspan="2">Date</th>
		</tr>
	</thead>
	<tbody>
		
		<?php if (count($logs)): ?>
			<?php foreach ($logs as $key => $log): ?>

				<tr>
					<td><?php echo sanitize_text_field($log->action) ?></td>
					<td><?php echo sanitize_text_field($log->context) ?></td>
					<td class="break-long">
						<div style="margin-bottom: 5px;"><?php echo sanitize_text_field($log->action_username ?? $log->action_user_id) ?></div>
						<?php echo sanitize_text_field($log->ip_address) ?>
					</td>
					<td>
						<small>
							<?php echo sanitize_text_field(date('M d Y', strtotime($log->occurred_at))) ?>, <?php echo sanitize_text_field(date('H:i:s', strtotime($log->occurred_at))); ?>
						</small>
					</td>
					<td class="text-center">
						<button class="button js-logtivity-view-log">View</button>
						<div style="display: none;" class="js-modal-content">
							<?php echo logtivity_view('_log-show', [
								'log' => $log
							]) ?>
						</div>
					</td>
				</tr>

			<?php endforeach ?>
		<?php else: ?>
			<?php if (isset($message)): ?>
				<tr>
					<td colspan="6"><?php echo sanitize_text_field($message) ?></td>
				</tr>
			<?php else: ?>
				<tr>
					<td colspan="6">No results found.</td>
				</tr>
			<?php endif ?>
		<?php endif ?>

	</tbody>
</table>

<?php if (isset($meta) && $meta->current_page): ?>
	<div class="nav-pages-buttons" data-current-page="<?php echo $meta->current_page ?>">
		<button <?php echo ( $meta->current_page == 1 ? 'disabled' : ''); ?> class="js-logtivity-pagination button-primary nav-pages-prev" data-page="<?php echo sanitize_text_field($meta->current_page - 1) ?>">&laquo; Previous</button><button 
			<?php echo ( ! $hasNextPage ? 'disabled' : ''); ?> class="js-logtivity-pagination button-primary nav-pages-next" data-page="<?php echo sanitize_text_field($meta->current_page + 1) ?>">Next &raquo;</button>
	</div>
<?php endif ?>

<div class="logtivity-modal">
	<div class="logtivity-modal-dialog">
		<div class="logtivity-modal-content">
			<!-- Populated with JS -->
		</div>
	</div>
</div>