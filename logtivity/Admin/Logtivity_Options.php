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

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

class Logtivity_Options
{
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
        'logtivity_disable_error_logging'        => ['rule' => 'is_bool', 'default' => null],
        'logtivity_disable_individual_logs'      => ['rule' => 'is_string', 'default' => null],
        'logtivity_disabled_error_levels'        => ['rule' => 'is_array', 'default' => null],
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
     * Get the API key for the site
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
     * Get the error levels that are disabled
     *
     * @return array
     */
    public function disabledErrorLevels(): array
    {
        $result = (array)$this->getOption('logtivity_disabled_error_levels');

        return array_keys(array_filter($result));
    }

    /**
     * Should we be logging the response from the API
     *
     * @return bool
     */
    public function shouldLogLatestResponse(): bool
    {
        return $this->getOption('logtivity_enable_debug_mode') || $this->shouldCheckInWithApi();
    }

    /**
     * @return bool
     */
    public function shouldCheckInWithApi(): bool
    {
        $latestResponse = get_option('logtivity_last_settings_check_in_at');
        $lastCheckin    = $latestResponse['date'] ?? null;

        if ($lastCheckin) {
            return time() - strtotime($lastCheckin) > 10 * MINUTE_IN_SECONDS;
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
        if (count($data)) {
            foreach ($this->settings as $setting => $default) {
                if (array_key_exists($setting, $data) && $this->validateSetting($setting, $data[$setting])) {
                    update_option($setting, $data[$setting]);
                }
            }
        } else {
            foreach ($this->settings as $setting => $default) {
                if (isset($_POST[$setting]) && $this->validateSetting($setting, $_POST[$setting])) {
                    update_option($setting, $_POST[$setting]);
                }
            }
        }

        if ($checkApiKey) {
            $this->checkApiKey($data['logtivity_site_api_key'] ?? $_POST['logtivity_site_api_key'] ?? false);
        }
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
                ->ignoreStatus()
                ->waitForResponse()
                ->send();
        }
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
            $method = $setting['rule'] ?? null;

            if ($method == 'is_bool') {
                return $method((bool)$value);
            }

            return $method($value);
        }

        return true;
    }
}
