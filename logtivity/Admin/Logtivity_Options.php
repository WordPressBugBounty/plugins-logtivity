<?php

/**
 * @package   Logtivity
 * @contact   logtivity.io, hello@logtivity.io
 * @copyright 2024 Logtivity. All rights reserved
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
        'logtivity_site_api_key'                 => null,
        'logtivity_disable_default_logging'      => null,
        'logtivity_enable_options_table_logging' => null,
        'logtivity_enable_post_meta_logging'     => null,
        'logtivity_should_store_user_id'         => 1,
        'logtivity_should_store_ip'              => 1,
        'logtivity_should_log_profile_link'      => 1,
        'logtivity_should_log_username'          => 1,
        'logtivity_disable_individual_logs'      => null,
        'logtivity_enable_debug_mode'            => null,
        'logtivity_latest_response'              => null,
        'logtivity_api_key_check'                => null,
        'logtivity_url_hash'                     => null,
        'logtivity_global_disabled_logs'         => null,
        'logtivity_enable_white_label_mode'      => null,
        'logtivity_disable_error_logging'        => null,
        'logtivity_disabled_error_levels'        => null,
        'logtivity_hide_plugin_from_ui'          => null,
        'logtivity_custom_plugin_name'           => null,
    ];

    /**
     * The option keys that we can save to the options table
     *
     * @var array
     */
    protected array $rules = [
        'logtivity_site_api_key'            => 'is_string',
        'logtivity_disable_default_logging' => 'is_bool',
        'logtivity_should_store_user_id'    => 'is_bool',
        'logtivity_should_store_ip'         => 'is_bool',
        'logtivity_should_log_profile_link' => 'is_bool',
        'logtivity_should_log_username'     => 'is_bool',
        'logtivity_enable_debug_mode'       => 'is_bool',
        'logtivity_latest_response'         => 'is_array',
        'logtivity_disable_individual_logs' => 'is_string',
    ];

    /**
     * Get the admin settings or the plugin
     *
     * @return array
     */
    public function getOptions(): array
    {
        $options = [];

        foreach ($this->settings as $setting => $default) {
            $options[$setting] = $this->getOption($setting);
        }

        return $options;
    }

    /**
     * Get an option from the database
     *
     * @param string $key
     * @return mixed
     */
    public function getOption(string $key)
    {
        if (has_filter($key)) {
            return apply_filters($key, false);
        }

        if (array_key_exists($key, $this->settings)) {
            return get_option($key, $this->settings[$key]);
        }

        return false;
    }

    /**
     * Get the API key for the site
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->getOption('logtivity_site_api_key');
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

        if (is_array($latestResponse) && isset($latestResponse['date'])) {
            return time() - strtotime($latestResponse['date']) > 10 * MINUTE_IN_SECONDS; // 10 minutes
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
        delete_option('logtivity_api_key_check');

        if ($apiKey) {
            $response = Logtivity::log()
                ->setAction('Settings Updated')
                ->setContext('Logtivity')
                ->waitForResponse()
                ->send();

            if (strpos($response, 'Log Received') !== false) {
                update_option('logtivity_api_key_check', 'success');

                return;
            }
        }

        update_option('logtivity_api_key_check', 'fail');
    }

    /**
     * Validate that the passed parameters are in the correct format
     *
     * @param string $setting
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validateSetting(string $setting, $value): bool
    {
        if (isset($this->rules[$setting])) {
            $method = $this->rules[$setting];

            if ($method == 'is_bool') {
                return $method((bool)$value);
            }

            return $method($value);
        }

        return true;
    }
}
