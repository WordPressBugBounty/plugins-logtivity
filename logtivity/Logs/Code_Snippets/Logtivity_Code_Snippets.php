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

class Logtivity_Code_Snippets
{
	public function __construct()
	{
		add_action( 'code_snippets/delete_snippet', [$this, 'snippetDeleted'], 10, 2);
		add_action( 'code_snippets/create_snippet', [$this, 'snippetCreated'], 10, 2);
		add_action( 'code_snippets/update_snippet', [$this, 'snippetUpdated'], 10, 2);
	}

	public function snippetDeleted($id, $multisite)
	{
		Logtivity_Logger::log()
			->setAction('Code Snippet Deleted')
			->setContext($id)
			->addMeta('Multisite', $multisite)
			->send();
	}

	public function snippetCreated($id, $table)
	{
		return $this->logWithMeta(
			Logtivity_Logger::log()
				->setAction('Code Snippet Created')
				->setContext($id)
				->addMetaIf(
					isset($_GET['action']) && $_GET['action'] == 'clone',
					'Cloned from ID',
					$_GET['id'] ?? null
				)
		);
	}

	public function snippetUpdated($id, $table)
	{
		$this->logWithMeta(
			Logtivity_Logger::log()
				->setAction('Code Snippet Updated')
				->setContext($id)
		);
		
		if (isset($_POST['save_snippet_activate'])) {
			$this->snippetActivated($id);
		}

		if (isset($_POST['save_snippet_deactivate'])) {
			$this->snippetDeactivated($id);
		}
	}

	public function snippetActivated($id)
	{
		Logtivity_Logger::log()
			->setAction('Code Snippet Activated')
			->setContext($id)
			->send();
	}

	public function snippetDeactivated($id)
	{
		Logtivity_Logger::log()
			->setAction('Code Snippet Deactivated')
			->setContext($id)
			->send();
	}

	public function logWithMeta($logger)
	{
		return $logger
			->addMetaIf(
				isset($_POST['snippet_name']) && $_POST['snippet_name'] != '', 
				'Snippet Title', 
				$_POST['snippet_name']
			)
			->addMetaIf(
				isset($_POST['snippet_code']) && $_POST['snippet_code'] != '', 
				'Snippet Code', 
				$_POST['snippet_code']
			)
			->addMetaIf(
				isset($_POST['snippet_scope']) && $_POST['snippet_scope'] != '', 
				'Snippet Scope', 
				$_POST['snippet_scope']
			)
			->addMetaIf(
				isset($_POST['snippet_priority']) && $_POST['snippet_priority'] != '', 
				'Snippet Priority', 
				$_POST['snippet_priority']
			)
			->addMetaIf(
				isset($_POST['snippet_tags']) && $_POST['snippet_tags'] != '', 
				'Snippet Tags', 
				$_POST['snippet_tags']
			)
			->send();
	}
}

$Logtivity_Code_Snippets = new Logtivity_Code_Snippets;