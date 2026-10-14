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
 * @var object $log
 */
?>
    <div class="logtivity-modal-strip">
        <h2 id="logtivity-log-modal-title" class="title">Overview</h2>
        <button type="button" class="notice-dismiss js-logtivity-notice-dismiss">
            <span class="screen-reader-text">Dismiss this modal.</span>
        </button>
    </div>

    <table style="margin-top: 0;" class="form-table logtivity-table">
        <tbody>
        <tr>
            <th scope="row">Action</th>
            <td><?php echo esc_html($log->action) ?></td>
        </tr>
        <?php if ($log->context) : ?>
            <tr>
                <th scope="row">Context</th>
                <td><?php echo esc_html($log->context) ?></td>
            </tr>
        <?php endif ?>
        <?php if ($log->action_user_id) : ?>
            <tr>
                <th scope="row">User ID</th>
                <td><?php echo esc_html($log->action_user_id) ?></td>
            </tr>
        <?php endif ?>
        <?php if ($log->action_username) : ?>
            <tr>
                <th scope="row">Username</th>
                <td><?php echo esc_html($log->action_username) ?></td>
            </tr>
            <?php
        endif;

        if ($log->action_user_meta) :
            foreach (json_decode($log->action_user_meta) as $key => $value) :
                ?>
                <tr>
                    <th scope="row"><?php echo esc_html(ucfirst(str_replace('_', ' ', $key))) ?></th>
                    <td>
                        <?php
                        if (is_array($value) || is_object($value)) :
                            ?>
                            <pre class="mb-0"><?php
                                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- used to render arbitrary meta values in the admin log viewer, not debug output.
                                echo esc_html(print_r($value, true));
                            ?></pre>
                            <?php
                        elseif (is_string($value) && substr($value, 0, 4) === 'http') :
                            ?>
                            <a target="_blank"
                               rel="noopener noreferrer"
                               href="<?php echo esc_url($value) ?>">
                                <?php echo esc_html($value) ?>
                            </a>
                            <?php
                        else :
                            echo esc_html((string)$value);
                        endif;
                        ?>
                    </td>
                </tr>
                <?php
            endforeach;
        endif;

        if ($log->ip_address) : ?>
            <tr>
                <th scope="row">IP</th>
                <td><?php echo esc_html($log->ip_address) ?></td>
            </tr>
        <?php endif ?>
        <tr>
            <th scope="row">Date</th>
            <td>
                <?php echo esc_html(logtivity_datetime($log->occurred_at)); ?>
            </td>
        </tr>
        </tbody>
    </table>

<?php
$meta = json_decode($log->meta, true);
if ($meta) :
    ?>
    <div class="logtivity-modal-strip light small">
        <h3 class="hndle">Meta</h3>
    </div>

    <table style="margin-top: 0;" class="form-table logtivity-table">
        <tbody>
        <?php
        foreach ($meta as $key => $value) :
            ?>
            <tr>
                <th scope="row">
                    <?php if (isset($value['key'])) : ?>
                        <strong><?php echo esc_html($value['key']) ?></strong>
                    <?php else : ?>
                        <strong><?php echo esc_html($key) ?></strong>
                    <?php endif ?>
                </th>

                <td>
                    <?php
                    $value = (isset($value['key']) && !isset($value['value'])) ? '' : ($value['value'] ?? '');

                    if (is_array($value) || is_object($value)) :
                        ?>
                        <pre><?php
                            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- used to render arbitrary meta values in the admin log viewer, not debug output.
                            echo esc_html(print_r($value, true));
                        ?></pre>
                        <?php
                    elseif (is_string($value) && preg_match('#^https?://#', ltrim($value))) :
                        ?>
                        <a target="_blank"
                           rel="noopener noreferrer"
                           href="<?php echo esc_url($value) ?>">
                            Visit Link
                            <i class="ml-1 small fas fa-external-link-alt"></i>
                        </a>
                        <?php
                    else :
                        echo esc_html((string)$value);
                    endif;
                    ?>
                </td>
            </tr>

        <?php endforeach; ?>
        </tbody>
    </table>
    <?php
endif;
