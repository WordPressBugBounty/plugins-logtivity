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
?>
<div style="<?php echo esc_attr(($display ?? false) ? 'display:block;' : '') ?>"
     class="updated notice logtivity-notice is-dismissible">
    <div>
        <div style="<?php echo esc_attr(($logo ?? false) ? '' : 'display:none;'); ?>">
            <img style="margin: 0 0 20px; display: block; width: 200px; height: auto;"
                 src="<?php echo esc_url(plugin_dir_url('logtivity.php') . 'logtivity/assets/logtivity-logo.svg') ?>"
                 alt="Logtivity">
        </div>
        <div>
            <h3>
                <?php esc_html_e('Welcome to Logtivity! We make activity logs simple!', 'logtivity') ?>
            </h3>
            <p>
                <?php esc_html_e("Here's how to start your activity log for this site:", 'logtivity') ?>
            </p>
            <ol>
                <li>
                    <?php
                    printf(
                        wp_kses(
                            __('%1$sCreate a Logtivity account%2$s or %3$slog in to your Logtivity account%4$s.', 'logtivity'),
                            [
                                'a' => [
                                    'href'   => true,
                                    'rel'    => true,
                                    'target' => true,
                                ],
                            ]
                        ),
                        '<a target="_blank" rel="noopener noreferrer" href="' . esc_url(logtivity_get_app_url() . '/register') . '">',
                        '</a>',
                        '<a target="_blank" rel="noopener noreferrer" href="' . esc_url(logtivity_get_app_url() . '/login') . '">',
                        '</a>'
                    );
                    ?>
                </li>
                <li>
                    <?php
                    printf(
                        wp_kses(
                            __('%sRead the "Add Site" instructions%s and get your API Key.', 'logtivity'),
                            [
                                'a' => [
                                    'href'   => true,
                                    'rel'    => true,
                                    'target' => true,
                                ],
                            ]
                        ),
                        '<a target="_blank" rel="noopener noreferrer" href="' . esc_url('https://logtivity.io/docs/connect-your-site-to-logtivity/') . '">',
                        '</a>'
                    );
                    ?>
                </li>
                <li>
                    <?php
                    printf(
                        wp_kses(
                            __('Add your API Key into %sthe Logtivity Settings area%s on this site.', 'logtivity'),
                            [
                                'a' => [
                                    'href'   => true,
                                    'rel'    => true,
                                    'target' => true,
                                ],
                            ]
                        ),
                        '<a target="_blank" rel="noopener noreferrer" href="' . esc_url(admin_url('admin.php?page=logtivity-settings')) . '">',
                        '</a>'
                    );
                    ?>
                </li>
            </ol>
            <p>
                <a class="button-primary logtivity-button logtivity-button-secondary"
                   target="_blank"
                   rel="noopener noreferrer"
                   href="<?php echo esc_url(logtivity_get_app_url() . '/login'); ?>">
                    <?php esc_html_e('Login to Logtivity', 'logtivity'); ?>
                </a> <a class="button-primary logtivity-button logtivity-button-primary"
                        target="_blank"
                        rel="noopener noreferrer"
                        href="<?php echo esc_url(logtivity_get_app_url() . '/register'); ?>">
                    <?php esc_html_e('Start your free 10 day trial', 'logtivity'); ?>
                </a>
            </p>
        </div>
    </div>
</div>
