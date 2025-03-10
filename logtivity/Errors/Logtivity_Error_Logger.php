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

class Logtivity_Error_Logger extends Logtivity_Api
{
	use Logtivity_User_Logger_Trait;

	protected $active = true;

	protected $error;

	protected static $recordedErrors = [];

	public function __construct($error)
	{
		$this->error = $error;

		$this->setUser();

		parent::__construct();
	}

	public function stop()
	{
		$this->active = false;

		return $this;
	}

	public function send()
	{
		if (in_array($this->error['message'], self::$recordedErrors)) {
			return;
		}

		self::$recordedErrors[] = $this->error['message'];

		do_action('wp_logtivity_error_logger_instance', $this);

		if (!$this->active) {
			return;
		}

		return $this->async()
					->makeRequest(
						'/errors/store', 
						$this->getData()
					);
	}

	public function getData()
	{
		$error = explode('Stack trace:', $this->error['message']);

		return [
			'type' => $this->getErrorLevel($this->error['type']) ?? null,
			'message' => $error[0],
			'stack_trace' => $this->generateStackTrace(
				[
					'file' => $this->error['file'] ?? null,
					'line' => $this->error['line'] ?? null,
				], 
				$error[1] ?? null
			),
			'file' => $this->error['file'] ?? null,
			'line' => $this->error['line'] ?? null,
			'user_id' => $this->getUserID(),
			'username' => $this->maybeGetUsersUsername(),
			'ip_address' => $this->maybeGetUsersIp(),
			'user_authenticated' => $this->user->isLoggedIn(),
			'url' => $this->getCurrentUrl(),
			'method' => $this->getRequestMethod(),
			'php_version' => phpversion(),
			'level' => $this->error['level'] ?? null,
		];
	}

	private function generateStackTrace($line, $stackTrace)
	{
		$stackTraceObject = new Logtivity_Stack_Trace();

		if (isset($this->error['stack_trace'])) {
			return $stackTraceObject->createFromArray($this->error['stack_trace']);
		}

		return array_merge(
			[$stackTraceObject->createFileObject($line['file'], $line['line'])],
			$stackTraceObject->createFromString($stackTrace)
		);
	}

	private function getRequestMethod()
	{
		return $_SERVER['REQUEST_METHOD'] ?? null;
	}

	private function getCurrentUrl()
	{
		if (!isset($_SERVER['HTTP_HOST'])) {
			return;
		}

		$protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

		$url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		
		if ($url == $protocol) {
			return;
		}

		return $url;
	}

	private function getErrorLevel($level)
	{
	    $errorlevels = logtivity_get_error_levels();

	    return isset($errorlevels[$level]) ? $errorlevels[$level] : $level;
	}
}