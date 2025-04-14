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
 * @var array  $vars
 * @var array  $value
 * @var string $key
 * @var array  $options
 */

echo logtivity_view('_admin-header', compact('options'));
?>

<div class="postbox logtivity-settings">
    <?php if (logtivity_has_site_url_changed()): ?>
        <div style="background: #DC3232;color: #fff;padding: 1em">
            <h2 style="color: white; padding-left: 0" class="title">We've detected a change in your site URL.</h2>
            <p>Is this a dev or staging environment? As a precaution, we've stopped logging. To start recording
                logs, again click the 'Update Settings' button below.</p>
        </div>
    <?php endif ?>

    <div class="inside">
        <h1 style="padding-top: 20px;">Settings</h1>

        <form action="<?php echo admin_url('admin-ajax.php'); ?>?action=logtivity_update_settings" method="post">

            <?php wp_nonce_field('logtivity_update_settings', 'logtivity_update_settings') ?>

            <table class="form-table">
                <tbody>
                <tr class="user-user-login-wrap">
                    <th>
                        <label for="logtivity_site_api_key">Site API Key</label>
                        <?php if (has_filter('logtivity_site_api_key')): ?>
                            <div class="logtivity-constant">This option has been set in code.</div>
                        <?php endif ?>
                    </th>

                    <td>
                        <?php
                        $api            = new Logtivity_Api();
                        $apiKey         = $api->getApiKey();
                        $latestResponse = $api->getLatestResponse();
                        $status         = $api->getConnectionStatus();
                        ?>
                        <input id="logtivity_site_api_key"
                               name="logtivity_site_api_key"
                               type="text"
                            <?php echo has_filter('logtivity_site_api_key') ? 'readonly' : ''; ?>
                               value="<?php echo sanitize_text_field($apiKey); ?>"
                               class="regular-text">
                        <p>
                            Status:
                            <?php
                            switch ($status) {
                                case 'success':
                                    echo '<span style="color: #4caf50; font-weight: bold;">Connected</span>';
                                    break;

                                case 'paused':
                                    echo '<span style="color: #dbbf24; font-weight: bold;">Logging is paused</span>';
                                    echo '<br>' . $api->getConnectionMessage();
                                    break;

                                case 'fail':
                                    echo '<span style="color: #ff3232; font-weight: bold;">Not connected. Please check API key.</span>';
                                    break;

                                default:
                                    if ($apiKey) {
                                        echo '<span style="color: #ffdd32; font-weight: bold;">Unknown error</span>';
                                    } else {
                                        echo '<span style="font-weight: bold;">Enter this site\'s API Key</span>';
                                    }
                                    break;
                            }
                            ?>
                        </p>
                    </td>

                    <td style="vertical-align: top;">
                        <span class="description">
                            You can find this value by logging into your account
                            and navigating to/creating this site settings page.
                        </span>
                    </td>
                </tr>

                <tr class="user-user-login-wrap">
                    <th>
                        <label for="logtivity_should_store_user_id">Store User ID</label>
                        <?php if (has_filter('logtivity_should_store_user_id')): ?>
                            <div class="logtivity-constant">This option has been set in code.</div>
                        <?php endif ?>
                    </th>

                    <td>
                        <input id="logtivity_should_store_user_id"
                               name="logtivity_should_store_user_id"
                               type="hidden"
                               value="0">

                        <input id="logtivity_should_store_user_id"
                               name="logtivity_should_store_user_id"
                               type="checkbox"
                            <?php echo($options['logtivity_should_store_user_id'] ? 'checked' : ''); ?>
                            <?php echo(has_filter('logtivity_should_store_user_id') ? 'readonly' : ''); ?>
                               value="1"
                               class="regular-checkbox">
                    </td>

                    <td>
                        <span class="description">
                            If you check this box, when logging an action,
                            we will include the users User ID in the logged action.
                        </span>
                    </td>
                </tr>

                <tr class="user-user-login-wrap">
                    <th>
                        <label for="logtivity_should_log_profile_link">Store Users Profile Link</label>
                        <?php if (has_filter('logtivity_should_log_profile_link')): ?>
                            <div class="logtivity-constant">This option has been set in code.</div>
                        <?php endif ?>
                    </th>

                    <td>
                        <input id="logtivity_should_log_profile_link"
                               name="logtivity_should_log_profile_link"
                               type="hidden"
                               value="0">

                        <input id="logtivity_should_log_profile_link"
                               name="logtivity_should_log_profile_link"
                               type="checkbox"
                            <?php echo $options['logtivity_should_log_profile_link'] ? 'checked' : ''; ?>
                            <?php echo has_filter('logtivity_should_log_profile_link') ? 'readonly' : ''; ?>
                               value="1"
                               class="regular-checkbox">
                    </td>

                    <td>
                        <span class="description">
                            If you check this box, when logging an action,
                            we will include the users profile link in the logged action.
                        </span>
                    </td>
                </tr>

                <tr class="user-user-login-wrap">
                    <th>
                        <label for="logtivity_should_log_username">Store Users Username</label>
                        <?php if (has_filter('logtivity_should_log_username')): ?>
                            <div class="logtivity-constant">This option has been set in code.</div>
                        <?php endif ?>
                    </th>

                    <td>
                        <input id="logtivity_should_log_username"
                               name="logtivity_should_log_username"
                               type="hidden"
                               value="0">

                        <input id="logtivity_should_log_username"
                               name="logtivity_should_log_username"
                               type="checkbox"
                            <?php echo has_filter('logtivity_should_log_username') ? 'readonly' : ''; ?>
                            <?php echo $options['logtivity_should_log_username'] ? 'checked' : ''; ?>
                               value="1"
                               class="regular-checkbox">
                    </td>

                    <td>
                        <span class="description">
                            If you check this box, when logging an action,
                            we will include the users username in the logged action.
                        </span>
                    </td>
                </tr>

                <tr class="user-user-login-wrap">
                    <th>
                        <label for="logtivity_should_store_ip">Store Users IP Address</label>
                        <?php if (has_filter('logtivity_should_store_ip')): ?>
                            <div class="logtivity-constant">This option has been set in code.</div>
                        <?php endif ?>
                    </th>

                    <td>
                        <input id="logtivity_should_store_ip"
                               name="logtivity_should_store_ip"
                               type="hidden"
                               value="0">

                        <input id="logtivity_should_store_ip"
                               name="logtivity_should_store_ip"
                               type="checkbox"
                            <?php echo $options['logtivity_should_store_ip'] ? 'checked' : ''; ?>
                            <?php echo has_filter('logtivity_should_store_ip') ? 'readonly' : ''; ?>
                               value="1"
                               class="regular-checkbox">
                    </td>

                    <td>
                        <span class="description">
                            If you check this box, when logging an action,
                            we will include the users IP address in the logged action.
                        </span>
                    </td>
                </tr>

                <tr class="user-user-login-wrap">
                    <th>
                        <label for="logtivity_app_verify_url">
                            Verify Site URL
                        </label>
                        <?php if (has_filter('logtivity_app_verify_url')): ?>
                            <div class="logtivity-constant">This option has been set in code.</div>
                        <?php endif ?>
                    </th>

                    <td>
                        <input id="logtivity_app_verify_url"
                               name="logtivity_app_verify_url"
                               type="hidden"
                               value="0">

                        <input id="logtivity_app_verify_url"
                               name="logtivity_app_verify_url"
                               type="checkbox"
                            <?php echo $options['logtivity_app_verify_url'] ? 'checked' : ''; ?>
                            <?php echo has_filter('logtivity_app_verify_url') ? 'readonly' : ''; ?>
                               value="1"
                               class="regular-checkbox">
                    </td>

                    <td>
                        <span class="description">
                            When messages are sent to Logtivity, the site URL will be checked
                            against the URL Logtivity has on file for this API key. If they do
                            not match, logging will be paused.
                        </span>
                    </td>
                </tr>

                <tr class="user-user-login-wrap">
                    <th>
                        <label for="logtivity_enable_debug_mode">
                            Enable debug mode (recommended off by default)
                        </label>
                        <?php if (has_filter('logtivity_enable_debug_mode')): ?>
                            <div class="logtivity-constant">This option has been set in code.</div>
                        <?php endif ?>
                    </th>

                    <td>
                        <input id="logtivity_enable_debug_mode"
                               name="logtivity_enable_debug_mode"
                               type="hidden"
                               value="0">

                        <input id="logtivity_enable_debug_mode"
                               name="logtivity_enable_debug_mode"
                               type="checkbox"
                            <?php echo $options['logtivity_enable_debug_mode'] ? 'checked' : ''; ?>
                            <?php echo has_filter('logtivity_enable_debug_mode') ? 'readonly' : ''; ?>
                               value="1"
                               class="regular-checkbox">
                    </td>

                    <td>
                        <span class="description">
                            This will log the latest response from the API.
                            This can be useful for debugging the result from an API call when storing a log.
                            We <strong>recommend setting this to off by default</strong> as this will allow
                            us to send logs asynchronously and not wait for a response from the API.
                            This will be more performant.
                        </span>
                    </td>
                </tr>

                <tr class="user-user-login-wrap">
                    <th>
                        <label for="logtivity_disable_individual_logs">Disable Individual Logs</label>
                        <?php if (has_filter('logtivity_disable_individual_logs')): ?>
                            <div class="logtivity-constant">This option has been set in code.</div>
                        <?php endif ?>
                    </th>
                    <td>
                        <textarea id="logtivity_disable_individual_logs"
                                  name="logtivity_disable_individual_logs"
                                  class="regular-checkbox"
                                  style="width: 100%;"
                                  <?php echo has_filter('logtivity_disable_individual_logs') ? 'readonly' : ''; ?>
                                  rows="10"
                                  placeholder="User Logged In&#10;User Created && subscriber"
                        ><?php echo esc_html($options['logtivity_disable_individual_logs']); ?></textarea>
                    </td>

                    <td>
							<span class="description">
								You can disable individual logged actions here by listing the action names, one per line.
								<br>
                                <br>
								To specify the context field as well,
                                separate the action and context keywords with an && symbol.
								<br>
                                <br>
								<?php
                                if (
                                    isset($options['logtivity_enable_white_label_mode']) == false
                                    || $options['logtivity_enable_white_label_mode'] != 1
                                ):
                                    ?>
                                    If you have multiple sites on Logtivity and would rather control disabled
                                    logs globally you can go to the
                                    <a href="<?php echo logtivity_get_app_url() . '/team-settings/activity-log-settings'; ?>"
                                       target="_blank"
                                       rel="nofollow"
                                    >Activity Log Settings page</a>
                                    in your Logtivity dashboard.
                                <?php endif; ?>
							</span>
                    </td>
                </tr>
                </tbody>
            </table>

            <p class="submit">
                <input id="submit"
                       name="submit"
                       type="submit"
                       class="button button-primary"
                       value="Update Settings">
            </p>
        </form>

    </div>
</div>

<?php if ($options['logtivity_enable_debug_mode']): ?>

    <div class="postbox">
        <div class="inside">

            <h3>Latest Response</h3>

            <?php
            if ($latestResponse) :
                $date = $latestResponse['date'] ?? null;
                $code = $latestResponse['code'] ?? null;
                $message = $latestResponse['message'] ?? null;
                $body = $latestResponse['body'] ?? null;

                if ($date) : ?>
                    <h4>Date: <?php echo $date; ?></h4>
                <?php
                endif;

                if ($code || $message) : ?>
                    <h4>
                        Response: <?php echo sprintf('%s - %s', $code ?: 'NA', $message ?: 'No Message'); ?>
                    </h4>
                <?php
                endif;

                if ($body) : ?>
                    <code style="display: block; padding: 20px; overflow-x: auto;">
                        <?php echo json_encode($body); ?>
                    </code>
                <?php
                endif;

            else : ?>
                <p>The latest logging response will appear here after an event has been logged.</p>

            <?php endif ?>
        </div>
    </div>

<?php endif ?>

<?php echo logtivity_view('_admin-footer', compact('options')); ?>
