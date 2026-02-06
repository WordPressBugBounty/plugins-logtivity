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

class Logtivity_Rest_Endpoints
{
    /**
     * @var Logtivity_JsonWebToken
     */
    protected Logtivity_JsonWebToken $token;

    public function __construct()
    {
        require_once __DIR__ . '/Logtivity_JsonWebToken.php';

        $this->token = new Logtivity_JsonWebToken();

        add_action('rest_api_init', function () {
            register_rest_route(
                'logtivity/v1',
                '/options',
                [
                    'methods'             => 'GET',
                    'permission_callback' => function (WP_REST_Request $request) {
                        return $this->verifyAuthorization($request->get_header('Authorization'));
                    },
                    'callback'            => function () {
                        $options = new Logtivity_Options();

                        $response = $options->getOptions();

                        $lastCheckinOption = 'logtivity_last_settings_check_in_at';

                        $response[$lastCheckinOption]        = get_option($lastCheckinOption);
                        $response[$lastCheckinOption]        = $response[$lastCheckinOption]['date'] ?? null;
                        $response['logtivity_checkin_delay'] = $options->checkinDelay;
                        $response['version']                 = $this->getLogtivityVersion();

                        return $response;
                    },
                ]);
        });
    }

    /**
     * @return self
     */
    public static function init(): self
    {
        return new static();
    }

    /**
     * @param string $authHeader
     *
     * @return true|WP_Error
     */
    protected function verifyAuthorization(string $authHeader)
    {
        if ($apikey = (new Logtivity_Options())->getApiKey()) {
            try {
                $keys = explode(' ', $authHeader);
                if (count($keys) == 2 && $keys[0] == 'Bearer') {
                    $payload = $this->parseToken($keys[1], $apikey);

                    $issuer = $payload->iss ?? '';
                    if ($this->compareDomains($issuer, logtivity_get_app_url()) == false) {
                        throw new Exception(
                            sprintf(
                                'Request Source: %s',
                                $issuer ? 'invalid' : 'unidentified'
                            )
                        );
                    }

                    $audience = $payload->aud ?? '';
                    if ($this->compareDomains($audience, site_url()) == false) {
                        throw new Exception(
                            sprintf(
                                'Target Site: %s',
                                $audience ? 'incorrect' : 'unidentified'
                            )
                        );
                    }
                }

                return true;

            } catch (Throwable $e) {
                return new WP_Error('invalid_token', $e->getMessage(), ['status' => 401]);
            }
        }

        return new WP_Error('missing_api_key', 'The API Key has not been set on this site', ['status' => 401]);
    }

    /**
     * @param string $token
     * @param string $secret
     *
     * @return ?object
     * @throws Exception
     */
    protected function parseToken(string $token, string $secret): object
    {
        if (empty($this->token)) {
            require_once __DIR__ . '/Logtivity_JsonWebToken.php';
            $this->token = new Logtivity_JsonWebToken();
        }

        return $this->token->parse($token, $secret);
    }

    /**
     * @param string $urlLeft
     * @param string $urlRight
     *
     * @return bool
     */
    protected function compareDomains(string $urlLeft, string $urlRight): bool
    {
        if (
            preg_match('#^(?:https?://)?(\S*?)/?$#', $urlLeft, $left)
            && preg_match('#^(?:https?://)?(\S*?)/?$#', $urlRight, $right)
        ) {
            return $left[1] == $right[1];
        }

        return false;
    }

    /**
     * @return string
     */
    protected function getLogtivityVersion(): string
    {
        $path = WP_PLUGIN_DIR . '/logtivity/logtivity.php';
        if (is_file($path)) {
            $pluginData = get_plugin_data($path);
            $version    = $pluginData['Version'] ?? 'Unknown';
        }

        return $version ?? 'Logtivity not available';
    }
}
