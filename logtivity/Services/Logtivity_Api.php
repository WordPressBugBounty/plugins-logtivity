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
     * @var object
     */
    protected Logtivity_Options $options;

    /**
     * Should we wait to return the response from the API?
     *
     * @var bool
     */
    public bool $waitForResponse = true;

    /**
     * Definitely don't wait for a response.
     *
     * @var bool
     */
    public bool $asyncOverride = false;

    /**
     * The API key for either the site or team
     *
     * @var string
     */
    public string $api_key = '';

    public function __construct()
    {
        $this->options = new Logtivity_Options();
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

    public function post(string $url, array $body)
    {
        return $this->makeRequest($url, $body);
    }

    public function get($url, $body)
    {
        return $this->makeRequest($url, $body, 'GET');
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
     * @return $this
     */
    public function async(): self
    {
        $this->asyncOverride = true;

        return $this;
    }

    /**
     * Make a request to the Logtivity API
     *
     * @param string $url
     * @param array  $body
     * @param string $method
     *
     * @return mixed $response
     */
    public function makeRequest(string $url, array $body, string $method = 'POST'): ?string
    {
        $this->api_key = $this->api_key ?: logtivity_get_api_key();
        if ($this->options->urlHash() == false) {
            $this->options->update(['logtivity_url_hash' => md5(home_url())], false);
        }

        if ($this->api_key && logtivity_has_site_url_changed() == false) {
            $shouldLogLatestResponse = $this->asyncOverride == false
                && ($this->waitForResponse || $this->options->shouldLogLatestResponse());

            $response = wp_remote_post($this->getEndpoint($url), [
                'method'      => $method,
                'timeout'     => ($shouldLogLatestResponse ? 6 : 0.01),
                'blocking'    => $shouldLogLatestResponse,
                'redirection' => 5,
                'httpversion' => '1.0',
                'headers'     => [
                    'Authorization' => 'Bearer ' . $this->api_key,
                ],
                'body'        => $body,
                'cookies'     => [],
            ]);

            $response = wp_remote_retrieve_body($response);

            if ($response) {
                if ($shouldLogLatestResponse && $this->notUpdatingWidgetInCustomizer() && $method === 'POST') {
                    $this->options->update([
                        'logtivity_latest_response' => [
                            'date'     => date('Y-m-d H:i:s'),
                            'response' => print_r($response, true),
                        ],
                    ],
                        false
                    );

                    update_option('logtivity_last_settings_check_in_at', ['date' => date('Y-m-d H:i:s')]);

                    $body = json_decode($response, true);

                    $this->updateSettings($body);
                }
            }
        }

        return $response ?: null;
    }

    public function updateSettings($body)
    {
        if (isset($body['settings'])) {
            $this->options->update([
                'logtivity_global_disabled_logs'         => $body['settings']['disabled_logs'] ?? null,
                'logtivity_enable_white_label_mode'      => $body['settings']['enable_white_label_mode'] ?? null,
                'logtivity_disabled_error_levels'        => $body['settings']['disabled_error_levels'] ?? null,
                'logtivity_disable_error_logging'        => $body['settings']['disable_error_logging'] ?? null,
                'logtivity_hide_plugin_from_ui'          => $body['settings']['hide_plugin_from_ui'] ?? null,
                'logtivity_disable_default_logging'      => $body['settings']['disable_default_logging'] ?? null,
                'logtivity_enable_options_table_logging' => $body['settings']['enable_options_table_logging'] ?? null,
                'logtivity_enable_post_meta_logging'     => $body['settings']['enable_post_meta_logging'] ?? null,
                'logtivity_custom_plugin_name'           => $body['settings']['custom_plugin_name'] ?? null,
            ],
                false
            );
        }
    }

    /**
     * You cannot call an extra update_option during a widget update so we make
     * sure not to log the most recent log response in this case.
     *
     * @return bool
     */
    private function notUpdatingWidgetInCustomizer(): bool
    {
        if (!isset($_POST['wp_customize'])) {
            return true;
        }

        if (!isset($_POST['action'])) {
            return true;
        }

        return !($_POST['action'] === 'update-widget' && $_POST['wp_customize'] === 'on');
    }
}
