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

/**
 * @var string $fileName
 * @var array $vars
 * @var array $options
 */

$whiteLabel = ($options['logtivity_enable_white_label_mode'] ?? '0') == '1';
if ($whiteLabel) :
    return;
endif;

$isWelcome = !($options['logtivity_site_api_key'] ?? false);
?>
<!-- sidebar -->
<div id="postbox-container-1" class="postbox-container">
    <div class="postbox">
        <?php
        if ($isWelcome) : ?>
            <h2><span><?php esc_attr_e('Welcome to Logtivity', 'logtivity'); ?></span></h2>
        <?php else : ?>
            <h2><span><?php esc_attr_e('Logtivity', 'logtivity'); ?></span></h2>
        <?php endif; ?>

        <div class="inside">
            <?php
            if ($isWelcome) : ?>
                <p>
                    <?php
                    esc_html_e(
                        "Logtivity is a hosted service that provides activity logs for your WordPress site. 
                        This is better than using an activity log plugin because you don’t need to store huge amounts of data on your own server.",
                        'logtivity'
                    );
                    ?>
                </p>
                <p>
                    <?php
                    esc_html_e(
                        "Logtivity's dashboard gives you one place to monitor changes and activity across all your WordPress sites.",
                        'logtivity'
                    );
                    ?>
                </p>
                <p>
                    <?php
                    esc_html_e(
                        'You can send alert notifications for any action on your site. 
                        For example, you can get a Slack notification for all Administrator logins.',
                        'logtivity'
                    );
                    ?>
                </p>
                <p>
                    <?php
                    esc_html_e(
                        'You can also create beautiful charts, allowing you to visualise the actions made on your site with ease.',
                        'logtivity'
                    );
                    ?>
                </p>
                <p>
                    <a class="button-primary logtivity-button logtivity-button-primary"
                       target="_blank"
                       href="<?php echo logtivity_get_app_url() . '/register'; ?>">
                        <?php esc_attr_e('Start your free 10 day trial', 'logtivity'); ?>
                    </a>
                </p>
            <?php endif ?>
            <ul>
                <li>
                    <a target="_blank"
                       href="<?php echo logtivity_get_app_url(); ?>">
                        <?php esc_html_e('Logtivity Dashboard', 'logtivity'); ?>
                    </a>
                </li>
                <li>
                    <a target="_blank"
                       href="https://logtivity.io/docs">
                        <?php esc_html_e('View our documentation here', 'logtivity'); ?>
                    </a>
                </li>
            </ul>
        </div>
        <!-- .inside -->
    </div>
    <!-- .postbox -->
</div>
<!-- #postbox-container-1 .postbox-container -->
