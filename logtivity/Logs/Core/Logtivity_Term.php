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

class Logtivity_Term
{
	protected $ignoreTaxonomies = [
		'nav_menu',
		'edd_log_type',
	];

	public function __construct()
	{
		add_action( 'edited_terms', [$this, 'termUpdated'], 10, 2 );
		add_action( 'created_term', [$this, 'termCreated'], 10, 3 );
		add_action( 'delete_term', [$this, 'termDeleted'], 10, 5);
	}

	public function termCreated($term_id, $tt_id, $taxonomy)
	{
		if (in_array($taxonomy, $this->ignoreTaxonomies)) {
			return;
		}

		$term = get_term_by('id', $term_id, $taxonomy);

		return Logtivity_Logger::log()
			->setAction('Term Created')
			->setContext($term->name) 
			->addMeta('Term ID', $term->term_id)
			->addMeta('Slug', $term->slug)
			->addMeta('Taxonomy', $term->taxonomy)
			->addMeta('Edit Link', get_edit_term_link($term))
			->send();
	}

	public function termUpdated($term_id, $taxonomy)
	{
		if (in_array($taxonomy, $this->ignoreTaxonomies)) {
			return;
		}

		$term = get_term_by('id', $term_id, $taxonomy);

		return Logtivity_Logger::log()
			->setAction('Term Updated')
			->setContext($term->name) 
			->addMeta('Term ID', $term->term_id)
			->addMeta('Slug', $term->slug)
			->addMeta('Taxonomy', $term->taxonomy)
			->addMeta('Edit Link', get_edit_term_link($term))
			->send();
	}

	public function termDeleted($term, $tt_id, $taxonomy, $deleted_term, $object_ids)
	{
		if (in_array($taxonomy, $this->ignoreTaxonomies)) {
			return;
		}

		return Logtivity_Logger::log()
			->setAction('Term Deleted')
			->setContext($deleted_term->name) 
			->addMeta('Term ID', $deleted_term->term_id)
			->addMeta('Slug', $deleted_term->slug)
			->addMeta('Taxonomy', $deleted_term->taxonomy)
			->send();
	}
}

$Logtivity_Term = new Logtivity_Term;