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

?>
<!-- sidebar -->

<?php if (isset($options['logtivity_enable_white_label_mode']) && $options['logtivity_enable_white_label_mode'] == '1'):
    return; ?>

<?php endif ?>
<div id="postbox-container-1" class="postbox-container">
    <div class="postbox">

        <?php if ($options['logtivity_api_key_check'] !== 'success'): ?>
            <h2><span><?php esc_attr_e('Welcome to Logtivity', 'logtivity'); ?></span></h2>
        <?php else: ?>
            <h2><span><?php esc_attr_e('Logtivity', 'logtivity'); ?></span></h2>
        <?php endif; ?>

        <div class="inside">
            <?php if ($options['logtivity_api_key_check'] !== 'success'): ?>
                <p>
                    Logtivity is a hosted SaaS service that provides dedicated activity monitoring for your WordPress
                    site. This offers a strong alternative to using a plugin, because you don’t need to store huge
                    amounts of data on your own server.</p>
                <p>
                    Simply connect this plugin to your Logtivity account and see the logs start coming in.
                </p>
                <p>
                    You can send alert notifications for any action on your site. For example, you can get a Slack
                    notification for all Administrator logins.
                </p>
                <p>
                    You can also create beautiful charts, allowing you to visualise the actions made on your site with
                    ease.
                </p>
                <p>
                    <a href="<?php echo logtivity_get_app_url() . '/register'; ?>"
                       class="button-primary"
                       target="_blank">
                        <?php esc_attr_e('Set up your Logtivity account', 'logtivity'); ?>
                    </a>
                </p>
            <?php endif ?>
            <p>
                <a href="<?php echo logtivity_get_app_url(); ?>"
                   target="_blank"
                >
                    <?php esc_attr_e('Logtivity Dashboard', 'logtivity'); ?>
                </a>
            </p>
            <p>
                <a target="_blank" href="https://logtivity.io/docs"><?php esc_attr_e(
                        'View our documentation here',
                        'logtivity'
                    ); ?></a>
            </p>

        </div>
        <!-- .inside -->

    </div>
    <!-- .postbox -->
</div>
<!-- #postbox-container-1 .postbox-container -->
