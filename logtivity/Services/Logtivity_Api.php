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
    public const CURL_TIMEOUT = 28;

    /**
     * @var array
     */
    protected static $responseData = [
        'code'    => null,
        'message' => null,
        'error'   => null,
        'body'    => null,
    ];

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
     * The API Key for either the site or team
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
    public function ignoreStatus(): self
    {
        $this->ignoreStatus = true;

        return $this;
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
     * Make a request to the Logtivity API
     *
     * @param string $url
     * @param ?array $body
     * @param string $method
     *
     * @return ?array
     */
    public function makeRequest(string $url, ?array $body = [], string $method = 'POST'): ?array
    {
        $body = $body ?: [];

        $body['occurred_at'] = time();

        if ($this->options->urlHash() == false) {
            $this->options->update(['logtivity_url_hash' => md5(home_url())], false);
        }

        if ($this->ready()) {
            if ($this->getOption('logtivity_app_verify_url')) {
                $body['site_hash'] = $this->options->urlHash();
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

            // @TODO: Switch to Logtivity_Response class to get standardized responses
            $response = wp_remote_request($this->getEndpoint($url), $request);
            if ($waitForResponse && $this->notUpdatingWidgetInCustomizer()) {
                // We waited and received a response
                if ($response instanceof WP_Error) {
                    $responseData = array_merge(static::$responseData, [
                        'code'    => 500,
                        'message' => $response->get_error_code(),
                        'error'   => $response->get_error_message() ?: $response->get_error_code(),
                        'body'    => get_object_vars($response),
                    ]);

                } else {
                    $responseCode    = wp_remote_retrieve_response_code($response);
                    $responseMessage = wp_remote_retrieve_response_message($response);
                    $responseBody    = json_decode(wp_remote_retrieve_body($response), true);

                    if ($responseCode < 400) {
                        $responseError = $responseBody['error'] ?? null;
                    } else {
                        $responseError   = $responseMessage;
                        $responseMessage = (($responseBody['message'] ?? $responseMessage) ?: 'Unknown error');
                    }
                    $responseData = [
                        'code'    => $responseCode,
                        'message' => $responseMessage,
                        'error'   => $responseError,
                        'body'    => $responseBody,
                    ];
                }

                $newStatus = 'success';
                if ($responseData['code']) {
                    if ($responseData['code'] < 400) {
                        // Successful request, check if api is telling us to pause
                        if ($responseData['error']) {
                            $newStatus = 'paused';
                        }

                        $body = json_decode($response['body'] ?? 'null', true);
                        $this->updateSettings($body['settings'] ?? []);

                    } else {
                        if ($this->getCurlError($responseData['error']) != static::CURL_TIMEOUT) {
                            // Something other than a timeout went wrong with the request
                            $newStatus = 'fail';
                        }

                        $this->updateLastCheckin();
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

                    return $responseData;
                }
            }
        }

        return null;
    }

    /**
     * @return bool
     */
    protected function ready(): bool
    {
        return ($this->getApiKey())
            && logtivity_has_site_url_changed() == false
            && (
                $this->ignoreStatus
                || $this->getOption('logtivity_api_key_check') == 'success'
                || $this->options->shouldCheckInWithApi()
            );
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
     * @param ?string $key
     *
     * @return Logtivity_Options|mixed
     */
    public function getOption(?string $key = null)
    {
        if ($key) {
            return $this->options->getOption($key);
        }

        return $this->options;
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
     * You cannot call an extra update_option during a widget update so we make
     * sure not to log the most recent log response in this case.
     *
     * @return bool
     */
    protected function notUpdatingWidgetInCustomizer(): bool
    {
        $customize = sanitize_text_field($_POST['wp_customize'] ?? null);
        $action    = sanitize_text_field($_POST['action'] ?? null);

        return ($action == 'update-widget' && $customize == 'on') == false;
    }

    /**
     * @param array $settings
     *
     * @return void
     */
    public function updateSettings(array $settings): void
    {
        if ($settings) {
            $verifiedSettings = [];
            foreach ($settings as $key => $value) {
                if ($key == 'disabled_logs') {
                    $key = 'global_' . $key;
                }
                $verifiedSettings['logtivity_' . $key] = is_null($value) ? '' : $value;
            }

            $this->options->update($verifiedSettings, false);
        }

        $this->updateLastCheckin();
    }

    /**
     * @return void
     */
    protected function updateLastCheckin(): void
    {
        update_option('logtivity_last_settings_check_in_at', ['date' => date('Y-m-d H:i:s')]);
    }

    /**
     * @param ?string $message
     *
     * @return int
     */
    protected function getCurlError(?string $message): ?int
    {
        preg_match('/curl\s+error\s+(\d+)/i', $message, $matches);

        return $matches[1] ?? null;
    }

    /**
     * @return ?array
     */
    public function getLatestResponse(): ?array
    {
        $response = $this->getOption('logtivity_latest_response');

        return $response ?: null;
    }

    /**
     * @return ?Exception
     */
    public function getLastError(): ?Exception
    {
        $latestResponse = $this->getLatestResponse();

        $code = $latestResponse['code'] ?? null;

        if ($code >= 400 && isset($latestResponse['body']['message'])) {
            $message = $latestResponse['body']['message'];

        } elseif (isset($latestResponse['body']['errors'])) {
            $message = array_shift($latestResponse['body']['errors']);
            while (is_array($message)) {
                $message = array_shift($message);
            }

        } elseif (isset($latestResponse['body']['error'])) {
            $message = $latestResponse['body']['error'];
        } else {
            $message = $latestResponse['error'] ?? $latestResponse['message'] ?? 'Unknown error';
        }

        if (($code == false || $message == false) && $this->getApiKey()) {
            $message = 'Not connected. Please check API Key';
        }

        return new Exception($message, $code);
    }

    /**
     * @param string $url
     * @param ?array $body
     *
     * @return ?array
     */
    public function get(string $url, ?array $body = []): ?array
    {
        return $this->waitForResponse()->makeRequest($url, $body, 'GET');
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
     * @TODO: This method is duplicated in the App.
     *
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
            case 'fail':
                $error   = $this->getLastError();
                $message = $error
                    ? sprintf('Disconnected (%s - %s)', $error->getCode(), $error->getMessage())
                    : null;
                break;

            default:
                $message = $this->getApiKey() ? null : 'API Key has not been set';
                break;
        }

        return $message ?: 'Unknown Status';
    }

    /**
     * @return ?string
     */
    public function getConnectionStatus(): ?string
    {
        $status = null;
        $apiKey = $this->getApiKey();

        if ($apiKey) {
            $status = $this->getOption('logtivity_api_key_check');
        }

        return $status;
    }
}
