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

class Logtivity_Options
{
	/**
	 * The option keys that we can save to the options table
	 * 
	 * @var array
	 */
	protected $settings = [
		'logtivity_site_api_key',
		'logtivity_disable_default_logging',
		'logtivity_enable_options_table_logging',
		'logtivity_enable_post_meta_logging',
		'logtivity_should_store_user_id',
		'logtivity_should_store_ip',
		'logtivity_should_log_profile_link',
		'logtivity_should_log_username',
		'logtivity_disable_individual_logs',
		'logtivity_enable_debug_mode',
		'logtivity_latest_response',
		'logtivity_api_key_check',
		'logtivity_url_hash',
		'logtivity_global_disabled_logs',
		'logtivity_enable_white_label_mode',
		'logtivity_disable_error_logging',
		'logtivity_disabled_error_levels',
		'logtivity_hide_plugin_from_ui',
		'logtivity_custom_plugin_name',
	];

	/**
	 * The option keys that we can save to the options table
	 * 
	 * @var array
	 */
	protected $rules = [
		'logtivity_site_api_key' => 'is_string',
		'logtivity_disable_default_logging' => 'is_bool',
		'logtivity_should_store_user_id' => 'is_bool',
		'logtivity_should_store_ip' => 'is_bool',
		'logtivity_should_log_profile_link' => 'is_bool',
		'logtivity_should_log_username' => 'is_bool',
		'logtivity_enable_debug_mode' => 'is_bool',
		'logtivity_latest_response' => 'is_array',
		'logtivity_disable_individual_logs' => 'is_string',
	];

	/**
	 * Get the admin settings or the plugin
	 * 
	 * @return array
	 */
	public function getOptions()
	{
		$options = [];

		foreach ($this->settings as $setting) {
			$options[$setting] = $this->getOption($setting);
		}

		return $options;
	}

	/**
	 * Get an option from the database
	 * 
	 * @param  string $key
	 * @return mixed
	 */
	public function getOption($key)
	{
		if (has_filter($key)) {
			return apply_filters($key, false);
		}

		if (!in_array($key, $this->settings)) {
			return false;
		}

		return get_option($key);
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
	public function shouldStoreUserId()
	{
		return $this->getOption('logtivity_should_store_user_id');
	}

	/**
	 * Should we store the users IP?
	 * 
	 * @return bool
	 */
	public function shouldStoreIp()
	{
		return $this->getOption('logtivity_should_store_ip');
	}

	/**	
	 * Should we store the users profile link?
	 * 
	 * @return bool
	 */
	public function shouldStoreProfileLink()
	{
		return $this->getOption('logtivity_should_log_profile_link');
	}

	/**	
	 * Should we store the users username?
	 * 
	 * @return bool
	 */
	public function shouldStoreUsername()
	{
		return $this->getOption('logtivity_should_log_username');
	}

	/**	
	 * Get the error levels that are disabled
	 * 
	 * @return array
	 */
	public function disabledErrorLevels()
	{
		$result = $this->getOption('logtivity_disabled_error_levels');

		if (is_array($result)) {
			return array_keys(array_filter($result));
		}

		return [];
	}

	/**	
	 * Should we be logging the response from the API
	 * 
	 * @return bool
	 */
	public function shouldLogLatestResponse()
	{
		return $this->getOption('logtivity_enable_debug_mode') || $this->shouldCheckInWithApi();
	}

	public function shouldCheckInWithApi()
	{
		$latestReponse = get_option('logtivity_last_settings_check_in_at');

		if (is_array($latestReponse) && isset($latestReponse['date'])) {
			return time() - strtotime($latestReponse['date']) > 10 * MINUTE_IN_SECONDS; // 10 minutes
		}

		return true;
	}

	public function urlHash()
	{
		return $this->getOption('logtivity_url_hash');
	}

	public function disabledLogs()
	{
		return $this->getOption('logtivity_global_disabled_logs');
	}

	public function isWhiteLabelMode()
	{
		return $this->getOption('logtivity_enable_white_label_mode');
	}

	public function isPluginHiddenFromUI()
	{
		return $this->getOption('logtivity_hide_plugin_from_ui');
	}

	public function customPluginName()
	{
		return $this->getOption('logtivity_custom_plugin_name');
	}

	/**
	 * Update the options for this plugin
	 *
	 * @param  array $data
	 * @return void
	 */
	public function update($data = [], $checkApiKey = true)
	{
		if (count($data)) {
			foreach ($this->settings as $setting) {
				if (array_key_exists($setting, $data) && $this->validateSetting($setting, $data[$setting])) {
					update_option($setting, $data[$setting]);
				}
			}
		} else {
			foreach ($this->settings as $setting) {
				if (isset($_POST[$setting]) && $this->validateSetting($setting, $_POST[$setting])) {
					update_option($setting, $_POST[$setting]);
				}
			}
		}

		if ($checkApiKey) {
			$this->checkApiKey($data['logtivity_site_api_key'] ?? $_POST['logtivity_site_api_key'] ?? false);
		}
	}

	public function checkApiKey($apiKey)
	{
		delete_option('logtivity_api_key_check');
		
		if (!$apiKey) {
			update_option('logtivity_api_key_check', 'fail');
			return;
		}

		$response = Logtivity::log()
			->setAction('Settings Updated')
			->setContext('Logtivity')
			->waitForResponse()
			->send();

		if (strpos($response, 'Log Received') !== false) {
			update_option('logtivity_api_key_check', 'success');
		} else {
			update_option('logtivity_api_key_check', 'fail');
		}
	}

	/**	
	 * Validate that the passed parameters are in the correct format
	 * 
	 * @param  string $setting
	 * @param  string $value
	 * @return bool  
	 */
	protected function validateSetting($setting, $value)
	{
		if (!isset($this->rules[$setting])) {
			return true;
		}

		$method = $this->rules[$setting];

		if ($method == 'is_bool') {
			return $method((bool) $value);
		}

		return $method($value);
	}
}