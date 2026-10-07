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

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

class Logtivity_Options
{
    /**
     * Checkin for settings updates in designated seconds
     *
     * @var int
     */
    public int $checkinDelay = 10 * MINUTE_IN_SECONDS;

    /**
     * The option keys that we can save to the options table
     *
     * @var array
     */
    protected array $settings = [
        'logtivity_api_key_check'                => ['rule' => 'is_string', 'default' => null],
        'logtivity_app_verify_url'               => ['rule' => 'is_bool', 'default' => 1],
        'logtivity_custom_plugin_name'           => ['rule' => 'is_string', 'default' => null],
        'logtivity_disable_default_logging'      => ['rule' => 'is_bool', 'default' => null],
        'logtivity_disable_individual_logs'      => ['rule' => 'is_string', 'default' => null],
        'logtivity_enable_debug_mode'            => ['rule' => 'is_bool', 'default' => null],
        'logtivity_enable_options_table_logging' => ['rule' => 'is_bool', 'default' => null],
        'logtivity_enable_post_meta_logging'     => ['rule' => 'is_bool', 'default' => null],
        'logtivity_enable_white_label_mode'      => ['rule' => 'is_bool', 'default' => null],
        'logtivity_global_disabled_logs'         => ['rule' => 'is_string', 'default' => null],
        'logtivity_hide_plugin_from_ui'          => ['rule' => 'is_bool', 'default' => null],
        'logtivity_latest_response'              => ['rule' => 'is_array', 'default' => null],
        'logtivity_should_log_profile_link'      => ['rule' => 'is_bool', 'default' => 1],
        'logtivity_should_log_username'          => ['rule' => 'is_bool', 'default' => 1],
        'logtivity_should_store_ip'              => ['rule' => 'is_bool', 'default' => 1],
        'logtivity_should_store_user_id'         => ['rule' => 'is_bool', 'default' => 1],
        'logtivity_site_api_key'                 => ['rule' => 'is_string', 'default' => null],
        'logtivity_url_hash'                     => ['rule' => 'is_string', 'default' => null],
    ];

    /**
     * Get the admin settings or the plugin
     *
     * @return array
     */
    public function getOptions(): array
    {
        $options = [];

        foreach ($this->settings as $setting => $rules) {
            $options[$setting] = $this->getOption($setting);
        }

        return $options;
    }

    /**
     * Get an option from the database
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getOption(string $key)
    {
        if (has_filter($key)) {
            return apply_filters($key, false);
        }

        $setting = $this->settings[$key] ?? null;
        if ($setting) {
            $value = get_option($key, $setting['default'] ?? null);
        }

        return $value ?? null;
    }

    /**
     * Get the API Key for the site
     *
     * @return string
     */
    public function getApiKey(): string
    {
        return (string)$this->getOption('logtivity_site_api_key');
    }

    /**
     * Should we store the user id?
     *
     * @return bool
     */
    public function shouldStoreUserId(): bool
    {
        return (bool)$this->getOption('logtivity_should_store_user_id');
    }

    /**
     * Should we store the users IP?
     *
     * @return bool
     */
    public function shouldStoreIp(): bool
    {
        return (bool)$this->getOption('logtivity_should_store_ip');
    }

    /**
     * Should we store the users profile link?
     *
     * @return bool
     */
    public function shouldStoreProfileLink(): bool
    {
        return (bool)$this->getOption('logtivity_should_log_profile_link');
    }

    /**
     * Should we store the user's username?
     *
     * @return bool
     */
    public function shouldStoreUsername(): bool
    {
        return (bool)$this->getOption('logtivity_should_log_username');
    }


    /**
     * Should we be logging the response from the API
     *
     * @return bool
     */
    public function shouldLogLatestResponse(): bool
    {
        return $this->shouldCheckInWithApi();
    }

    /**
     * @return bool
     */
    public function shouldCheckInWithApi(): bool
    {
        $lastCheckin = get_option('logtivity_last_settings_check_in_at');
        $lastCheckin = $lastCheckin['date'] ?? null;

        if ($lastCheckin) {
            return time() - strtotime($lastCheckin) > $this->checkinDelay;
        }

        return true;
    }

    /**
     * @return string
     */
    public function urlHash(): string
    {
        return (string)$this->getOption('logtivity_url_hash');
    }

    /**
     * @return string
     */
    public function disabledLogs(): string
    {
        return (string)$this->getOption('logtivity_global_disabled_logs');
    }

    /**
     * @return bool
     */
    public function isWhiteLabelMode(): bool
    {
        return (bool)$this->getOption('logtivity_enable_white_label_mode');
    }

    /**
     * @return bool
     */
    public function isPluginHiddenFromUI(): bool
    {
        return (bool)$this->getOption('logtivity_hide_plugin_from_ui');
    }

    /**
     * @return string
     */
    public function customPluginName(): string
    {
        return (string)$this->getOption('logtivity_custom_plugin_name');
    }

    /**
     * Update the options for this plugin
     *
     * @param array $data
     * @param bool  $checkApiKey
     *
     * @return void
     */
    public function update(array $data = [], bool $checkApiKey = true): void
    {
        if (!$data) {
            if (!current_user_can(Logtivity::ACCESS_SETTINGS)) {
                return;
            }

            check_ajax_referer('logtivity_update_settings', 'logtivity_update_settings');

            $data = wp_unslash($_POST);
        }

        $settings = array_intersect_key($data, $this->settings);
        foreach ($settings as $key => $value) {
            $value = $this->sanitizeSetting($key, $value);

            if ($this->validateSetting($key, $value)) {
                update_option($key, $value);
            }
        }

        if ($checkApiKey) {
            $this->checkApiKey($settings['logtivity_site_api_key'] ?? null);
        }
    }

    /**
     * Sanitize setting values before saving.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return mixed
     */
    protected function sanitizeSetting(string $key, $value)
    {
        $setting = $this->settings[$key] ?? null;

        if (($setting['rule'] ?? null) === 'is_string' && is_scalar($value)) {
            return sanitize_textarea_field((string)$value);
        }

        return $value;
    }

    /**
     * Validate that the passed parameters are in the correct format
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validateSetting(string $key, $value): bool
    {
        $setting = $this->settings[$key] ?? null;

        if ($setting) {
            switch ($setting['rule'] ?? null) {
                case 'is_bool':
                    return is_bool($value) || in_array($value, [0, 1, '0', '1', ''], true);

                case 'is_array':
                    return is_array($value);

                case 'is_string':
                    return is_string($value);
            }

            return false;
        }

        return true;
    }

    /**
     * @param ?string $apiKey
     *
     * @return void
     */
    public function checkApiKey(?string $apiKey): void
    {
        if ($apiKey) {
            Logtivity::log()
                ->setAction('Settings Updated')
                ->setContext('Logtivity')
                ->waitForResponse()
                ->send();
        }
    }
}
