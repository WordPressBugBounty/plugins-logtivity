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

class Logtivity_Admin
{
	protected $options;

	protected static $shouldHidePluginFromUI = false;
	
	public function __construct()
	{
		add_action( 'admin_menu', [$this, 'registerOptionsPage'] );

		add_action( 'wp_ajax_logtivity_update_settings', [$this, 'update']);
		add_action( 'wp_ajax_nopriv_logtivity_update_settings', [$this, 'update']);

		add_filter('logtivity_hide_from_menu', [$this, 'shouldHidePluginFromUI']);
		add_filter('all_plugins', [$this, 'maybeHideFromMenu']);

		$this->options = new Logtivity_Options;
	}

	public function maybeHideFromMenu($plugins)
	{
		if ($name = (new Logtivity_Options)->customPluginName()) {
			if (isset($plugins['logtivity/logtivity.php'])) {
				$plugins['logtivity/logtivity.php']['Name'] = $name;
			}
		}

		if (!$this->shouldHidePluginFromUI(false)) {
			return $plugins;
		}

		$shouldHide = ! array_key_exists( 'show_all', $_GET );

		if ( $shouldHide ) {
			$hiddenPlugins = [
				'logtivity/logtivity.php',
			];

			foreach ( $hiddenPlugins as $hiddenPlugin ) {
				unset( $plugins[ $hiddenPlugin ] );
			}
		}
		return $plugins;
	}

	public function shouldHidePluginFromUI($value)
	{
		if (self::$shouldHidePluginFromUI = (new Logtivity_Options)->isPluginHiddenFromUI()) {
			return self::$shouldHidePluginFromUI;
		}
		return $value;
	}

	/**
	 * Register the settings page
	 */
	public function registerOptionsPage() 
	{
		if (!apply_filters('logtivity_hide_from_menu', false)) {
			add_menu_page(
				($this->options->isWhiteLabelMode() ? 'Logs' : 'Logtivity'), 
				($this->options->isWhiteLabelMode() ? 'Logs' : 'Logtivity'), 
				'manage_options', 
				($this->options->isWhiteLabelMode() ? 'lgtvy-logs' : 'logtivity'), 
				[$this, 'showLogIndexPage'], 
				'dashicons-chart-area', 
				26 
			);
		}
		
		if (!apply_filters('logtivity_hide_settings_page', false)) {
			add_submenu_page(
				($this->options->isWhiteLabelMode() ? 'lgtvy-logs' : 'logtivity'),
				'Logtivity Settings', 
				'Settings', 
				'manage_options', 
				'logtivity'.'-settings', 
				[$this, 'showLogtivitySettingsPage']
			);
		}
	}

	/**	
	 * Show the admin log index
	 * 
	 * @return void
	 */
	public function showLogIndexPage()
	{
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		$options = $this->options->getOptions();

		echo logtivity_view('log-index', compact('options'));
	}

	/**	
	 * Show the admin settings template
	 * 
	 * @return void
	 */
	public function showLogtivitySettingsPage() 
	{
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		$options = $this->options->getOptions();

		echo logtivity_view('settings', compact('options'));
	}

	/**
	 * Update the settings
	 * 
	 * @return WP_Redirect
	 */
	public function update()
	{
		if (!wp_verify_nonce( $_POST['logtivity_update_settings'], 'logtivity_update_settings' )) 
		{
		    wp_safe_redirect( $this->settingsPageUrl() );
			exit;
			return;
		}

		$user = new Logtivity_Wp_User;

		if (!$user->hasRole('administrator')) {
		    wp_safe_redirect( $this->settingsPageUrl() );
			exit;
			return;
		}

		$this->options->update([
				'logtivity_url_hash' => md5(home_url()),
			],
			false
		);

		delete_transient( 'dismissed-logtivity-site-url-has-changed-notice' );

		$this->options->update();

		(new Logtivity_Check_For_New_Settings)->checkForNewSettings();
		
	    wp_safe_redirect( $this->settingsPageUrl() );
	    exit;
	}

	/**
	 * Get the url to the settings page
	 * 
	 * @return string
	 */
	public function settingsPageUrl()
	{
		return admin_url('admin.php?page=logtivity-settings');
	}

}

$Logtivity_Admin = new Logtivity_Admin;

