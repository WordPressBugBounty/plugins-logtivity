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

class Logtivity_Term extends Logtivity_Abstract_Logger
{
    /**
     * @var string[]
     */
    protected array $ignoreTaxonomies = [
        'nav_menu',
        'edd_log_type',
    ];

    /**
     * @var WP_Term[]
     */
    public array $terms = [];

    /**
     * @var array[]
     */
    public array $metas = [];

    /**
     * @inheritDoc
     */
    protected function registerHooks(): void
    {
        add_action('edit_term', [$this, 'saveOriginalData'], 10, 3);
        add_action('saved_term', [$this, 'saved_term'], 10, 5);
        add_action('delete_term', [$this, 'delete_term'], 10, 5);
    }

    /**
     * @param int    $termId
     * @param int    $ttId
     * @param string $taxonomy
     *
     * @return void
     */
    public function saveOriginalData($termId, $ttId, $taxonomy): void
    {
        if (
            in_array($taxonomy, $this->ignoreTaxonomies) == false
            && ($term = get_term($termId, $taxonomy))
            && $term instanceof WP_Term
        ) {
            $this->terms[$termId] = get_object_vars($term);
            $this->metas[$termId] = $this->getMetadata('term', $termId);
        }
    }

    /**
     * @param int    $termId
     * @param int    $ttId
     * @param string $taxonomy
     * @param bool   $update
     *
     * @return void
     */
    public function saved_term($termId, $ttId, $taxonomy, $update): void
    {
        if (in_array($taxonomy, $this->ignoreTaxonomies) == false) {
            if (($term = get_term($termId, $taxonomy)) && $term instanceof WP_Term) {
                $term = get_object_vars($term);
            }
            $term    = $term ?: [];
            $oldTerm = $this->terms[$termId] ?? [];

            $meta    = $this->sanitizeDataFields($this->getMetadata('term', $termId));
            $oldMeta = $this->sanitizeDataFields($this->metas[$termId] ?? []);

            if ($term != $oldTerm || $meta != $oldMeta) {
                Logtivity::log('Term ' . ($update ? 'Updated' : 'Created'))
                    ->setContext($taxonomy)
                    ->addMeta('Term ID', $term['term_id'])
                    ->addMeta('Name', $term['name'])
                    ->addMeta('Slug', $term['slug'])
                    ->addMeta('Edit', get_edit_term_link($termId))
                    ->addMetaChanges($oldTerm, $term)
                    ->addMetaChanges($oldMeta, $meta)
                    ->send();
            }
        }
    }

    /**
     * @param int     $termId
     * @param int     $ttId
     * @param string  $taxonomy
     * @param WP_Term $term
     *
     * @return void
     */
    public function delete_term($termId, $ttId, $taxonomy, $term): void
    {
        if (in_array($taxonomy, $this->ignoreTaxonomies) == false) {
            Logtivity::log()
                ->setAction('Term Deleted')
                ->setContext($taxonomy)
                ->addMeta('Term ID', $term->term_id)
                ->addMeta('Name', $term->name)
                ->addMeta('Slug', $term->slug)
                ->send();
        }
    }
}

new Logtivity_Term();
