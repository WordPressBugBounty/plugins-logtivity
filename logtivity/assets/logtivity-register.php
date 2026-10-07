<?php

/**
 * Plugin Name: Logtivity Site Registration
 */

abstract class Logtivity_Register
{
    /**
     * Enter your Team API Key here
     *
     * @var string
     */
    protected static string $teamApi = 'YOUR-TEAM-APIKEY-HERE';

    /**
     * @var WP_Error|Logtivity_Response|null
     */
    protected static $response = null;

    public static function register()
    {
        $apiKey = get_option('logtivity_site_api_key');
        if (empty($apiKey) && is_callable([Logtivity::class, 'registerSite'])) {
            static::$response = Logtivity::registerSite(static::$teamApi);
        }

        add_action('admin_notices', [static::class, 'adminMessage']);
    }

    /**
     * @return void
     */
    public static function adminMessage()
    {
        if (static::$response instanceof WP_Error) {
            $message = static::$response->get_error_message();
            $status  = 'error';

        } elseif (static::$response instanceof Logtivity_Response) {
            $message = static::$response->message;
            $status  = 'success';
        }

        if (empty($message) === false) {
            printf(
                '<div class="notice notice-%s"><p>%s</p></div>',
                esc_attr($status),
                wp_kses_post($message)
            );
        }
    }
}

add_action('init', [Logtivity_Register::class, 'register']);
