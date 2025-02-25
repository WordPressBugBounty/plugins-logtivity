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

class Logtivity_Check_For_Disabled_Individual_Logs
{
	public function __construct()
	{
		add_action('wp_logtivity_instance', [$this, 'handle'], 10, 999);
	}

	public function handle($Logtivity_Logger)
	{
		foreach ($this->getLogsToExclude() as $log) {
			if ($this->check($Logtivity_Logger, $log)) {
				$Logtivity_Logger->stop();
			}
		}

		foreach ($this->globalLogsToExclude() as $log) {
			if ($this->check($Logtivity_Logger, $log)) {
				$Logtivity_Logger->stop();
			}
		}
	}

	public function check($Logtivity_Logger, $log)
	{
		$array = explode('&&', $log);

		if (isset($array[0]) && isset($array[1])) {
			if (trim($array[0]) === '*' && trim($array[1]) === '*') {
				return;
			}
		    if ($this->matches($Logtivity_Logger->action, $array[0]) && $this->matches($Logtivity_Logger->context, $array[1])) {
		        return true;
		    }
		} elseif(isset($array[0])) {
			if (trim($array[0]) === '*') {
				return;
			}
			if ($this->matches($Logtivity_Logger->action, $array[0])) {
		        return true;
		    }
		}
		return false;
	}

	private function matches($keyword, $disabledKeyword)
	{
        // @TODO: this may not be the best way to check the arguments
		if (is_string($keyword) && is_string($disabledKeyword)) {
			$keyword = trim(strtolower($keyword));
			$disabledKeyword = trim(strtolower($disabledKeyword));

			if ($disabledKeyword === '*') {
				return true;
			}

			if (strpos($disabledKeyword, '*') !== false) {
				return strpos($keyword, str_replace('*', '', $disabledKeyword)) !== false;
			}

			return $keyword == $disabledKeyword;
		}

		return false;
	}

	private function getLogsToExclude()
	{
		$value = (new Logtivity_Options)->getOption('logtivity_disable_individual_logs');

		if ($value == '') {
			return [];
		}

		return preg_split("/\\r\\n|\\r|\\n/", $value);
	}

	public function globalLogsToExclude()
	{
		$value = (new Logtivity_Options)->getOption('logtivity_global_disabled_logs');

		if ($value == '') {
			return [];
		}

		return preg_split("/\\r\\n|\\r|\\n/", $value);
	}
}

$CheckForDisabledIndividualLogs = new Logtivity_Check_For_Disabled_Individual_Logs;
