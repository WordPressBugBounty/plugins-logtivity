<?php

/**
 * @package   Logtivity
 * @copyright 2025 Logtivity.io. All rights reserved
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
     * @var array
     */
    public array $request = [
        'method'      => 'POST',
        'timeout'     => 0.01,
        'blocking'    => false,
        'redirection' => 5,
        'httpversion' => '1.0',
        'body'        => null,
        'cookies'     => [],
    ];

    /**
     * @var null|array|WP_Error
     */
    public $response = null;

    public function __construct(string $apikey, string $url, array $request)
    {
        $this->request = array_merge(
            $this->request,
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apikey,
                ],
            ],
            $request
        );

        $api = new Logtivity_Api();

        $this->response = wp_remote_request($api->getEndpoint($url), $this->request);

        $this->processResponse();
    }

    protected function processResponse(): void
    {
        if ($this->response instanceof WP_Error) {
            $this->code    = 500;
            $this->message = $this->response->get_error_code();
            $this->error   = $this->response->get_error_message() ?: $this->response->get_error_code();
            $this->body    = get_object_vars($this->response);

        } else {
            $this->code    = wp_remote_retrieve_response_code($this->response);
            $this->message = wp_remote_retrieve_response_message($this->response);
            $this->body    = json_decode(wp_remote_retrieve_body($this->response), true);

            if ($this->code < 400) {
                $this->error = $responseBody['error'] ?? null;

            } else {
                $this->error   = $this->message;
                $this->message = (($this->body['message'] ?? $this->message) ?: 'Unknown error');
            }
        }
    }
}
