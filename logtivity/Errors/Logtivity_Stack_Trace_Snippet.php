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

class Logtivity_Stack_Trace_Snippet
{
	private $file;
	
	private $line;

	private $lines = [];

	public function __construct($file)
	{
		$this->file = new SplFileObject($file);
	}

	public function line($line)
	{
		$this->line = $line;

		return $this;
	}

	public function getMaxLine()
	{
		$this->file->seek(PHP_INT_MAX);

		return $this->file->key() + 1;
	}

	public function get()
	{
		$maxLine = $this->getMaxLine();

		$line = (($this->line - 5) >= 1 ? ($this->line - 5) : 1);

		$end = (($this->line + 5) <= $maxLine ? ($this->line + 5) : $maxLine);

		while ($line <= $end) {
			$this->file->seek($line - 1);

			$this->lines[] = [
				'code' => $this->file->current(),
				'line' => $line,
			];

			$line++;
		}

		return $this->lines;
	}
}