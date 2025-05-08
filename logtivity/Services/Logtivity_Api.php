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

class Logtivity_Api
{
    /**
     * Option class to access the plugin settings
     *
     * @var Logtivity_Options
     */
    protected Logtivity_Options $options;

    /**
     * We generally don't want to wait for a response from the API.
     *
     * @var bool
     */
    protected bool $waitForResponse = false;

    /**
     * If the currrent status is not 'fail' or 'success'
     * attempt to send anyway
     *
     * @var bool
     */
    protected bool $ignoreStatus = false;

    /**
     * The API key for either the site or team
     *
     * @var ?string
     */
    protected ?string $api_key = null;

    public function __construct()
    {
        $this->options = new Logtivity_Options();
    }

    /**
     * @return $this
     */
    public function waitForResponse(): self
    {
        $this->waitForResponse = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function ignoreStatus(): self
    {
        $this->ignoreStatus = true;

        return $this;
    }

    /**
     * @param string $endpoint
     *
     * @return string
     */
    public function getEndpoint(string $endpoint): string
    {
        return logtivity_get_api_url() . $endpoint;
    }

    /**
     * @param string $url
     * @param array  $body
     *
     * @return ?array
     */
    public function post(string $url, array $body): ?array
    {
        return $this->makeRequest($url, $body);
    }

    /**
     * @param string $url
     * @param ?array $body
     *
     * @return ?array
     */
    public function get(string $url, ?array $body = null): ?array
    {
        return $this->waitForResponse()->makeRequest($url, $body, 'GET');
    }

    /**
     * @return ?string
     */
    public function getApiKey(): ?string
    {
        if ($this->api_key == false) {
            $this->api_key = $this->options->getApiKey();
        }

        return $this->api_key;
    }

    /**
     * @param string $apikey
     *
     * @return $this
     */
    public function setApiKey(string $apikey): self
    {
        $this->api_key = $apikey;

        return $this;
    }

    /**
     * Make a request to the Logtivity API
     *
     * @param string $url
     * @param ?array $body
     * @param string $method
     *
     * @return ?array
     */
    public function makeRequest(string $url, ?array $body = null, string $method = 'POST'): ?array
    {
        if ($this->options->urlHash() == false) {
            $this->options->update(['logtivity_url_hash' => md5(home_url())], false);
        }

        if ($this->ready()) {
            if ($this->options->getOption('logtivity_app_verify_url')) {
                $body = array_merge(
                    $body ?: [],
                    [
                        'site_hash' => $this->options->urlHash(),
                    ]
                );
            }

            $waitForResponse = $this->waitForResponse || $this->options->shouldLogLatestResponse();

            $request = [
                'method'      => $method,
                'timeout'     => ($waitForResponse ? 6 : 0.01),
                'blocking'    => $waitForResponse,
                'redirection' => 5,
                'httpversion' => '1.0',
                'headers'     => [
                    'Authorization' => 'Bearer ' . $this->getApiKey(),
                ],
                'body'        => $body,
                'cookies'     => [],
            ];

            $response = wp_remote_request($this->getEndpoint($url), $request);
            if ($this->notUpdatingWidgetInCustomizer()) {
                // We waited and received a response
                if ($response instanceof WP_Error) {
                    $responseData = [
                        'code'    => 500,
                        'message' => $response->get_error_code(),
                        'error'   => $response->get_error_message() ?: $response->get_error_code(),
                        'body'    => get_object_vars($response),
                    ];

                } else {
                    $responseCode    = wp_remote_retrieve_response_code($response);
                    $responseMessage = wp_remote_retrieve_response_message($response);
                    $responseBody    = json_decode(wp_remote_retrieve_body($response), true);
                    $responseError   = $responseCode < 400
                        ? ($responseBody['error'] ?? null)
                        : ($responseMessage ?: 'Unknown error');

                    $responseData = [
                        'code'    => $responseCode,
                        'message' => $responseMessage,
                        'error'   => $responseError,
                        'body'    => $responseBody,
                    ];
                }

                if ($responseData['code']) {
                    if ($responseData['code'] < 400) {
                        // Successful request, check if api is telling us to pause
                        $newStatus = $responseData['error'] ? 'paused' : 'success';
                        $this->updateSettings($response['body']);

                    } else {
                        // Something went wrong submitting the api request
                        $newStatus = 'fail';
                        update_option('logtivity_last_settings_check_in_at', ['date' => date('Y-m-d H:i:s')]);
                    }

                    $this->options->update([
                        'logtivity_api_key_check'   => $newStatus,
                        'logtivity_latest_response' => array_merge(
                            ['date' => date('Y-m-d H:i:s')],
                            $responseData
                        ),
                    ],
                        false
                    );

                    return $responseData['body'];
                }
            }
        }

        return null;
    }

    /**
     * @param array|object $body
     *
     * @return void
     */
    public function updateSettings($body): void
    {
        $settings = (object)($body->settings ?? $body['settings'] ?? null);

        if ($settings) {
            $this->options->update(
                [
                    'logtivity_global_disabled_logs'         => $settings->disabled_logs ?? null,
                    'logtivity_enable_white_label_mode'      => $settings->enable_white_label_mode ?? null,
                    'logtivity_disabled_error_levels'        => $settings->disabled_error_levels ?? null,
                    'logtivity_disable_error_logging'        => $settings->disable_error_logging ?? null,
                    'logtivity_hide_plugin_from_ui'          => $settings->hide_plugin_from_ui ?? null,
                    'logtivity_disable_default_logging'      => $settings->disable_default_logging ?? null,
                    'logtivity_enable_options_table_logging' => $settings->enable_options_table_logging ?? null,
                    'logtivity_enable_post_meta_logging'     => $settings->enable_post_meta_logging ?? null,
                    'logtivity_custom_plugin_name'           => $settings->custom_plugin_name ?? null,
                ],
                false
            );

            update_option('logtivity_last_settings_check_in_at', ['date' => date('Y-m-d H:i:s')]);
        }
    }

    /**
     * @return ?array
     */
    public function getLatestResponse(): ?array
    {
        $response = $this->options->getOption('logtivity_latest_response');

        return $response ?: null;
    }

    /**
     * @return ?string
     */
    public function getConnectionStatus(): ?string
    {
        $status = null;
        $apiKey = $this->getApiKey();

        if ($apiKey) {
            $status = $this->options->getOption('logtivity_api_key_check');
        }

        return $status;
    }

    /**
     * @return ?string
     */
    public function getConnectionMessage(): ?string
    {
        $status = $this->getConnectionStatus();

        switch ($status) {
            case 'success':
                $message = 'Connected';
                break;

            case 'paused':
                $message = $this->getLatestResponse()['body']['error'] ?? null;
                break;

            case 'fail':
                $error   = $this->getLatestResponse();
                $code    = $error['code'] ?? null;
                $message = $error['message'] ?? null;

                if ($code && $message) {
                    $message = sprintf('Disconnected (%s - %s)', $code, $message);
                } else {
                    $message = 'Not connected. Please check API key';
                }

                break;

            default:
                $message = $this->getApiKey() ? 'Unknown error' : 'API Key has not been set';
                break;
        }

        return $message;
    }

    /**
     * You cannot call an extra update_option during a widget update so we make
     * sure not to log the most recent log response in this case.
     *
     * @return bool
     */
    private function notUpdatingWidgetInCustomizer(): bool
    {
        $customize = sanitize_text_field($_POST['wp_customize'] ?? null);
        $action    = sanitize_text_field($_POST['action'] ?? null);

        return ($action == 'update-widget' && $customize == 'on') == false;
    }

    /**
     * @return bool
     */
    private function ready(): bool
    {
        //var_dump($this->ignoreStatus);
        return ($this->getApiKey())
            && logtivity_has_site_url_changed() == false
            && (
                $this->ignoreStatus
                || $this->options->getOption('logtivity_api_key_check') == 'success'
                || $this->options->shouldCheckInWithApi()
            );
    }
}
