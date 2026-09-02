<?php

/**
 * @package   Logtivity
 * @contact   logtivity.io, hello@logtivity.io
 * @copyright 2024-2026 Logtivity. All rights reserved
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
 * @var string   $fileName
 * @var ?string  $message
 * @var object[] $logs
 * @var object   $meta
 * @var string   $hasNextPage
 */

?>
<table class="form-table logtivity-table">
    <caption class="screen-reader-text"><?php esc_html_e('Activity logs', 'logtivity'); ?></caption>
    <thead>
    <tr>
        <th scope="col">Action</th>
        <th scope="col">Context</th>
        <th scope="col">User</th>
        <th scope="col">Date</th>
        <th scope="col">Actions</th>
    </tr>
    </thead>
    <tbody>

    <?php if (count($logs)) : ?>
        <?php foreach ($logs as $key => $log) : ?>

            <tr>
                <td><?php echo sanitize_text_field($log->action) ?></td>
                <td><?php echo sanitize_text_field($log->context) ?></td>
                <td class="break-long">
                    <div style="margin-bottom: 5px;"><?php echo sanitize_text_field($log->action_username ?? $log->action_user_id) ?></div>
                    <?php echo sanitize_text_field($log->ip_address) ?>
                </td>
                <td>
                    <small>
                        <?php echo logtivity_datetime($log->occurred_at); ?>
                    </small>
                </td>
                <td class="text-center">
                    <button type="button" class="button js-logtivity-view-log" aria-haspopup="dialog" aria-controls="logtivity-log-modal" aria-label="<?php esc_attr_e('View log details', 'logtivity'); ?>">View</button>
                    <div style="display: none;" class="js-modal-content">
                        <?php echo logtivity_view('_log-show', ['log' => $log,]) ?>
                    </div>
                </td>
            </tr>

        <?php endforeach ?>
    <?php elseif (isset($message)) : ?>
        <tr>
            <td colspan="5"><?php echo sanitize_text_field($message) ?></td>
        </tr>
    <?php else : ?>
        <tr>
            <td colspan="5">No results found.</td>
        </tr>
    <?php endif ?>
    </tbody>
</table>

<?php if ($meta->current_page ?? null) : ?>
    <nav class="nav-pages-buttons" data-current-page="<?php echo esc_attr($meta->current_page) ?>" aria-label="<?php esc_attr_e('Log pages', 'logtivity'); ?>">
        <button type="button" <?php echo $meta->current_page == 1 ? 'disabled' : ''; ?>
                class="js-logtivity-pagination button-primary nav-pages-prev"
                aria-label="<?php esc_attr_e('Previous log page', 'logtivity'); ?>"
                data-page="<?php echo esc_attr($meta->current_page - 1) ?>">
            &laquo; Previous
        </button>
        <button type="button" <?php echo $hasNextPage ? '' : 'disabled'; ?>
                class="js-logtivity-pagination button-primary nav-pages-next"
                aria-label="<?php esc_attr_e('Next log page', 'logtivity'); ?>"
                data-page="<?php echo esc_attr($meta->current_page + 1) ?>">Next &raquo;
        </button>
    </nav>
<?php endif ?>

<div id="logtivity-log-modal" class="logtivity-modal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="logtivity-log-modal-title">
    <div class="logtivity-modal-dialog" tabindex="-1">
        <div class="logtivity-modal-content">
            <!-- Populated with JS -->
        </div>
    </div>
</div>
