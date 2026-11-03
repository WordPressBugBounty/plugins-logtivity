<?php

/**
 * @package   Logtivity
 * @copyright 2025-2026 Logtivity. All rights reserved
 * @contact   logtivity.io, hello@logtivity.io
 *
 * This file is part of Logtivity
 */

class Logtivity_Response
{
    /**
     * @var ?int
     */
    public ?int $code = null;

    /**
     * @var ?string
     */
    public ?string $message = null;

    /**
     * @var ?string
     */
    public ?string $error = null;

    /**
     * @var ?array
     */
    public ?array $body = null;

    /**
     * Round trip time of the request in milliseconds
     *
     * @var ?int
     */
    public ?int $responseTimeMs = null;

    /**
     * The arguments passed to wp_remote_request(). A POST body is JSON encoded.
     *
     * @var array
     */
    public array $request = [];

    /**
     * @var null|array|WP_Error
     */
    public $response = null;

    /**
     * @param string  $url
     * @param ?array  $body
     * @param string  $method
     * @param bool    $wait   Block until the API responds
     * @param ?string $apikey Defaults to the site API key
     */
    public function __construct(
        string $url,
        ?array $body = null,
        string $method = 'POST',
        bool $wait = false,
        ?string $apikey = null
    ) {
        $apikey = $apikey ?: (new Logtivity_Options())->getApiKey();

        $headers = [
            'Authorization' => 'Bearer ' . $apikey,
        ];

        /*
         * A form-encoded body loses every field past the server's max_input_vars, and a site
         * with many plugins sends more than that. GET bodies go in the query string, and a
         * body that can't be encoded is still sent as a form rather than lost.
         */
        if ($method != 'GET' && $body !== null) {
            $json = wp_json_encode($body);
            if ($json !== false) {
                $body = $json;

                $headers['Content-Type'] = 'application/json';
            }
        }

        $this->request = [
            'method'      => $method,
            // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- only blocking when the caller explicitly needs the response.
            'timeout'     => $wait ? 6 : 0.01,
            'blocking'    => $wait,
            'redirection' => 5,
            'httpversion' => '1.0',
            'headers'     => $headers,
            'body'        => $body,
            'cookies'     => [],
        ];

        /**
         * This request must not be cached. It writes a log entry to the API rather
         * than reading a cacheable resource, so a cached response would silently
         * drop logs. The default path is also non-blocking with a 0.01s timeout,
         * so it does not hold up the page load.
         *
         * Settings check-ins are already rate limited by
         * Logtivity_Options::shouldCheckInWithApi(), which gates on the
         * logtivity_last_settings_check_in_at state value.
         */
        $requestStartedAt = microtime(true);
        $this->response   = wp_remote_request(logtivity_get_api_url() . $url, $this->request);

        if ($wait) {
            $this->responseTimeMs = (int)round((microtime(true) - $requestStartedAt) * 1000);

            $this->processResponse();
        }
    }

    /**
     * @return void
     */
    protected function processResponse(): void
    {
        if ($this->response instanceof WP_Error) {
            $this->code    = 500;
            $this->message = $this->response->get_error_code();
            $this->error   = $this->response->get_error_message() ?: $this->response->get_error_code();
            $this->body    = get_object_vars($this->response);
        } else {
            $this->code    = (int)wp_remote_retrieve_response_code($this->response);
            $this->message = wp_remote_retrieve_response_message($this->response);

            $body       = json_decode(wp_remote_retrieve_body($this->response), true);
            $this->body = is_array($body) ? $body : null;

            if ($this->code < 400) {
                $this->error = $this->body['error'] ?? null;
            } else {
                $this->error   = $this->message;
                $this->message = (($this->body['message'] ?? $this->message) ?: 'Unknown error');
            }
        }
    }

    /**
     * Standard array format used for return values
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'code'    => $this->code,
            'message' => $this->message,
            'error'   => $this->error,
            'body'    => $this->body,
        ];
    }
}
