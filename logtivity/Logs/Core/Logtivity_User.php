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

class Logtivity_User extends Logtivity_Abstract_Logger
{
	protected static $loggedUserlogin = false;

	public function registerHooks()
	{
		add_action('wp_login', [$this, 'wpLogin'], 10, 2);
		add_action('wp_logout', [$this, 'userLoggedOut'], 10, 1);
		add_action( 'user_register', [$this, 'userCreated'], 10, 1 );
		add_action( 'delete_user', [$this, 'userDeleted'] );
		add_action( 'profile_update', [$this, 'profileUpdated'], 10, 2 );
	}

	public function wpLogin($user_login, $user)
	{
		if (self::$loggedUserlogin) {
			return;
		}

		return $this->userLoggedIn($user->ID);
	}

	public function userLoggedIn( $user_id ) 
	{
		self::$loggedUserlogin = true;

		$logtivityUser = new Logtivity_WP_User($user_id);

		return (new Logtivity_Logger($user_id))
			->setAction('User Logged In')
			->setContext($logtivityUser->getRole())
			->send();
	}

	public function userLoggedOut($user_id)
	{
		if ($user_id == 0) {
			return;
		}
		
		$user = new Logtivity_WP_User($user_id);

		return (new Logtivity_Logger($user_id))
			->setAction('User Logged Out')
			->setContext($user->getRole())
			->send();
	}

	public function userCreated($user_id)
	{
		$log =  Logtivity_Logger::log();

		if (!is_user_logged_in()) {
			$log->setUser($user_id);
		}

		$user = new Logtivity_WP_User($user_id);

		$log->setAction('User Created')
			->setContext($user->getRole())
			->addMeta('Username', $user->userLogin())
			->send();
	}

	public function userDeleted($user_id)
	{
		$user = new Logtivity_WP_User($user_id);

		return Logtivity_Logger::log()
			->setAction('User Deleted')
			->setContext($user->getRole())
			->addMeta('Username', $user->userLogin())
			->send();
	}

	public function profileUpdated($user_id, $old_user_data)
	{
		$user = new Logtivity_WP_User($user_id);

		return Logtivity_Logger::log()
			->setAction('User Updated')
			->setContext($user->getRole())
			->addMeta('Username', $user->userLogin())
			->send();
	}
}

$Logtivity_User = new Logtivity_User;