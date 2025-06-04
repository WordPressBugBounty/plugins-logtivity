<?php

/**
 * @package   Logtivity
 * @contact   logtivity.io, hello@logtivity.io
 * @copyright 2025 Logtivity. All rights reserved
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
 * @var array  $vars
 * @var array  $value
 * @var string $key
 * @var array  $options
 */

$api            = new Logtivity_Api();
$apiKey         = $api->getApiKey();
$latestResponse = $api->getLatestResponse();
$status         = $api->getConnectionStatus();

echo logtivity_view('_admin-header', compact('options'));
?>

<div class="postbox logtivity-register">
    <div class="inside">
        <h1 style="padding-top: 20px; margin-bottom: 0;">Register This Site</h1>

        <?php if ($apiKey) : ?>
            <div class="logtivity-notice logtivity-notice-info">
                <h2>The API Key has already been set</h2>
            </div>

        <?php else : ?>

            <div class="logtivity-notice logtivity-notice-info" style="width: fit-content; margin-bottom: 20px;">
                <div>
                    If you already have an account with Logtivity, you can register this site using your team API
                    key.
                    <br>
                    You can find this value by logging into your account and navigating to the
                    <?php echo sprintf(
                        '<a href="%s" target="_blank">%s</a>.',
                        logtivity_get_app_url() . '/team-settings/developers',
                        'developers page in Team Settings'
                    );
                    ?>
                </div>
            </div>

            <form action="<?php echo admin_url('admin-ajax.php'); ?>"
                  id="logtivity-register-site"
                  method="post">
                <?php wp_nonce_field('logtivity_register_site', 'logtivity_register_site') ?>
                <input type="hidden" name="action" value="logtivity_register_site">

                <table>
                    <tbody>
                    <tr class="user-user-login-wrap">
                        <th>
                            <label for="logtivity_team_api_key">Team API Key</label>
                        </th>

                        <td>
                            <input id="logtivity_team_api_key"
                                   name="logtivity_team_api_key"
                                   type="text"
                                   class="regular-text">
                        </td>
                    </tr>
                    </tbody>
                </table>

                <p class="submit">
                    <input id="submit"
                           name="submit"
                           type="submit"
                           class="button button-primary"
                           value="Register Site">
                </p>
            </form>
        <?php endif; ?>

        <div id="logtivity-register-response"></div>
    </div>
</div>

<?php echo logtivity_view('_admin-footer', compact('options')); ?>
