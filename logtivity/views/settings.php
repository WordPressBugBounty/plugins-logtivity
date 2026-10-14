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
 * @var string $fileName
 * @var array  $vars
 * @var array  $value
 * @var string $key
 * @var array  $options
 */

$api              = new Logtivity_Api();
$apiKey           = $api->getApiKey();
$connectionStatus = $api->getConnectionStatus();
$debugLogEntries  = (new Logtivity_Debug_Log())->recent();

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- logtivity_view() renders trusted plugin templates.
echo logtivity_view('_admin-header', compact('options'));
?>

<div class="postbox logtivity-settings">
    <div class="inside">
        <h1 style="padding-top: 20px;">Settings</h1>

        <?php if (logtivity_has_site_url_changed()) : ?>
            <div class="logtivity-notice logtivity-notice-danger">
                <h2>We've detected a change in your site URL.</h2>
                <div>
                    As a precaution, we've stopped logging. To start recording
                    logs, again click the 'Update Settings' button below.
                </div>
            </div>
        <?php endif;

        if (logtivity_url_environment_mismatch()) : ?>
            <div class="logtivity-notice logtivity-notice-danger">
                <h2>Logtivity is misconfigured and logging has stopped.</h2>
                <div>
                    Your App URL (<?php echo esc_html(logtivity_get_app_url()); ?>)
                    and API URL (<?php echo esc_html(logtivity_get_api_url()); ?>)
                    point to different environments. Neither
                    <code>LOGTIVITY_APP_URL</code> or <code>LOGTIVITY_API_URL</code>
                    constant should be set, or both must be set to the same
                    non-production environment. No logs will be sent until this is
                    corrected.
                </div>
            </div>
        <?php elseif (logtivity_is_production() == false) : ?>
            <div class="logtivity-notice logtivity-notice-warning">
                <h2>This site is not sending logs to the production application.</h2>
                <div>
                    See your dashboard at
                    <a target="_blank"
                       rel="noopener noreferrer"
                       href="<?php echo esc_url(logtivity_get_app_url()); ?>">
                        <?php esc_html_e('Logtivity dashboard', 'logtivity'); ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <form action="<?php echo esc_url(admin_url('admin-ajax.php?action=logtivity_update_settings')); ?>"
              method="post">

            <?php wp_nonce_field('logtivity_update_settings', 'logtivity_update_settings') ?>

            <table class="form-table">
                <tbody>
                <tr class="user-user-login-wrap">
                    <th scope="row">
                        <label for="logtivity_site_api_key">Site API Key</label>
                        <?php if (has_filter('logtivity_site_api_key')) : ?>
                            <div class="logtivity-constant">This option has been set in code.</div>
                        <?php endif ?>
                    </th>

                    <td>
                        <input id="logtivity_site_api_key"
                               name="logtivity_site_api_key"
                               type="text"
                                <?php echo has_filter('logtivity_site_api_key') ? 'readonly' : ''; ?>
                               value="<?php echo esc_attr($apiKey); ?>"
                               class="regular-text">
                        <p>
                            Status:
                            <?php
                            switch ($connectionStatus) {
                                case 'success':
                                    echo '<span style="color: #4caf50; font-weight: bold;">Connected</span>';
                                    break;

                                case 'paused':
                                    echo '<span style="color: #dbbf24; font-weight: bold;">Logging is paused</span>';
                                    echo '<br>' . esc_html($api->getConnectionMessage());
                                    break;

                                case 'staging':
                                    echo sprintf(
                                        '<span style="color: #dbbf24; font-weight: bold;">%s</span>',
                                        esc_html($api->getConnectionMessage())
                                    );
                                    break;

                                case 'fail':
                                    echo sprintf(
                                        '<span style="color: #ff3232; font-weight: bold;">%s</span>',
                                        esc_html($api->getConnectionMessage())
                                    );
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
                            and navigating to this site's settings page.
                        </span>
                    </td>
                </tr>

                <tr class="user-user-login-wrap">
                    <th scope="row">
                        <label for="logtivity_should_store_user_id">Store User ID</label>
                        <?php if (has_filter('logtivity_should_store_user_id')) : ?>
                            <div class="logtivity-constant">This option has been set in code.</div>
                        <?php endif ?>
                    </th>

                    <td>
                        <input name="logtivity_should_store_user_id"
                               type="hidden"
                               value="0">

                        <input id="logtivity_should_store_user_id"
                               name="logtivity_should_store_user_id"
                               type="checkbox"
                                <?php echo $options['logtivity_should_store_user_id'] ? 'checked' : ''; ?>
                                <?php echo has_filter('logtivity_should_store_user_id') ? 'readonly' : ''; ?>
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
                    <th scope="row">
                        <label for="logtivity_should_log_profile_link">Store Users Profile Link</label>
                        <?php if (has_filter('logtivity_should_log_profile_link')) : ?>
                            <div class="logtivity-constant">This option has been set in code.</div>
                        <?php endif ?>
                    </th>

                    <td>
                        <input name="logtivity_should_log_profile_link"
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
                    <th scope="row">
                        <label for="logtivity_should_log_username">Store Users Username</label>
                        <?php if (has_filter('logtivity_should_log_username')) : ?>
                            <div class="logtivity-constant">This option has been set in code.</div>
                        <?php endif ?>
                    </th>

                    <td>
                        <input name="logtivity_should_log_username"
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
                    <th scope="row">
                        <label for="logtivity_should_store_ip">Store Users IP Address</label>
                        <?php if (has_filter('logtivity_should_store_ip')) : ?>
                            <div class="logtivity-constant">This option has been set in code.</div>
                        <?php endif ?>
                    </th>

                    <td>
                        <input name="logtivity_should_store_ip"
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
                    <th scope="row">
                        <label for="logtivity_app_verify_url">
                            Verify Site URL
                        </label>
                        <?php if (has_filter('logtivity_app_verify_url')) : ?>
                            <div class="logtivity-constant">This option has been set in code.</div>
                        <?php endif ?>
                    </th>

                    <td>
                        <input name="logtivity_app_verify_url"
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
                            against the URL Logtivity has on file for this API Key. If they do
                            not match, logging will be paused.
                        </span>
                    </td>
                </tr>

                <tr class="user-user-login-wrap">
                    <th scope="row">
                        <label for="logtivity_enable_debug_mode">
                            Enable debug mode (recommended off by default)
                        </label>
                        <?php if (has_filter('logtivity_enable_debug_mode')) : ?>
                            <div class="logtivity-constant">This option has been set in code.</div>
                        <?php endif ?>
                    </th>

                    <td>
                        <input name="logtivity_enable_debug_mode"
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
                            This will record a history of request and response data for API calls
                            to a debug log file (<code>wp-content/uploads/logtivity-logs/debug.log</code>),
                            viewable below. Response details are only captured for calls that already
                            wait for a response (periodic settings check-ins, roughly every 10 minutes);
                            other calls are still sent without waiting and only the outgoing request is
                            logged. Enabling this does not affect performance.
                        </span>
                    </td>
                </tr>

                <tr class="user-user-login-wrap">
                    <th scope="row">
                        <label for="logtivity_disable_individual_logs">Disable Individual Logs</label>
                        <?php if (has_filter('logtivity_disable_individual_logs')) : ?>
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
                                You can disable individual logged actions here by listing the action names,
                                one per line.
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
                                ) :
                                    ?>
                                    If you have multiple sites on Logtivity and would rather control disabled
                                    logs globally you can go to the
                                    <?php
                                    printf(
                                        '<a href="%s" target="_blank" rel="nofollow">%s</a>',
                                        esc_url(logtivity_get_app_url() . '/team-settings/activity-log-settings'),
                                        esc_html__('Activity Log Settings page', 'logtivity')
                                    );
                                    ?>
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

<?php if ($options['logtivity_enable_debug_mode']) : ?>
    <div class="postbox">
        <div class="inside">

            <h3>Debug Log</h3>

            <form method="post"
                  action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                  style="display: inline-block; margin-bottom: 15px;">
                <input type="hidden" name="action" value="logtivity_clear_debug_log">
                <?php wp_nonce_field('logtivity_clear_debug_log', 'logtivity_clear_debug_log'); ?>
                <input id="logtivity-clear-debug-log"
                       type="submit"
                       class="button"
                       value="Clear Debug Log"
                       onclick="return confirm('Are you sure you want to clear the debug log?');">
            </form>

            <?php if ($debugLogEntries) : ?>
                <a id="logtivity-download-debug-log"
                   class="button"
                   style="margin-bottom: 15px;"
                   href="<?php echo esc_url(wp_nonce_url(
                       admin_url('admin-post.php?action=logtivity_download_debug_log'),
                       'logtivity_download_debug_log',
                       'logtivity_download_debug_log'
                   )); ?>">
                    Download Debug Log
                </a>
            <?php endif; ?>

            <?php if ($debugLogEntries) : ?>
                <?php foreach ($debugLogEntries as $entry) : ?>
                    <div style="border-bottom: 1px solid #ddd; padding: 10px 0;">
                        <h4>
                            <?php echo esc_html($entry['timestamp'] ?? ''); ?>
                            &mdash;
                            <?php echo esc_html($entry['method'] ?? ''); ?>
                            <?php echo esc_html($entry['endpoint'] ?? ''); ?>
                        </h4>
                        <?php $responseCode = $entry['response_code'] ?? null; ?>
                        <h4>
                            Response:
                            <?php
                            echo $responseCode === null
                                    ? 'not captured (sent without waiting for a response)'
                                    : esc_html(sprintf(
                                        '%s - %s (%sms)',
                                        $responseCode,
                                        $entry['response_message'] ?? 'No Message',
                                        $entry['response_time_ms'] ?? 'NA'
                                    ));
                            ?>
                        </h4>
                        <code style="display: block; padding: 20px; overflow-x: auto;">
                            Request: <?php echo esc_html(wp_json_encode($entry['request_body'] ?? null)); ?>
                        </code>
                        <?php if ($responseCode !== null) : ?>
                            <code style="display: block; padding: 20px; overflow-x: auto;">
                                Response: <?php echo esc_html(wp_json_encode($entry['response_body'] ?? null)); ?>
                            </code>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p>The debug log will appear here after an API call has been made.</p>
            <?php endif; ?>
        </div>
    </div>

<?php endif ?>

<?php
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- logtivity_view() renders trusted plugin templates.
echo logtivity_view('_admin-footer', compact('options'));
?>
