<?php

/**
 * @package   Logtivity
 * @contact   logtivity.io, hello@logtivity.io
 * @copyright 2024-2026 Logtivity. All rights reserved
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

use Code_Snippets\Model;
use Code_Snippets\Snippet;

class Logtivity_Code_Snippets
{
    public function __construct()
    {
        add_action('code_snippets/trash_snippet', [$this, 'snippetTrashed'], 10, 2);
        add_action('code_snippets/delete_snippet', [$this, 'snippetDeleted'], 10, 2);
        add_action('code_snippets/create_snippet', [$this, 'snippetCreated'], 10, 2);
        add_action('code_snippets/update_snippet', [$this, 'snippetUpdated'], 10, 2);
        add_action('code_snippets/activate_snippet', [$this, 'snippetActivated'], 10, 2);
        add_action('code_snippets/deactivate_snippet', [$this, 'snippetDeactivated'], 10, 2);
    }

    /**
     * @param Model\Snippet $snippet
     *
     * @return void
     */
    public function snippetTrashed($snippet): void
    {
        $logger = Logtivity::log()
            ->setAction('Code Snippet Trashed')
            ->setContext($snippet->id);

        $this->logWithMeta($logger, $snippet);
    }

    /**
     * @param int|Model\Snippet $snippet
     * @param ?bool             $network
     *
     * @return void
     */
    public function snippetDeleted($snippet, $network): void
    {
        $snippetId = $snippet->id ?? $snippet;

        $logger = Logtivity::log()
            ->setAction('Code Snippet Deleted')
            ->setContext($snippetId);

        if (is_object($snippet)) {
            $this->logWithMeta($logger, $snippet);
        } else {
            $logger
                ->addMeta('Network', $network ? 'Yes' : 'No')
                ->send();
        }
    }

    /**
     * @param Snippet|Model\Snippet $snippet
     * @param string                $table
     *
     * @return void
     */
    public function snippetCreated($snippet, $table): void
    {
        // Will not work in newer versions of Code Snippets
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Passive logger reads Code Snippets request state after that plugin handles auth.
        $action = isset($_GET['action'])
            ? sanitize_text_field(wp_unslash($_GET['action']))
            : null;
        $id = isset($_GET['id'])
            ? sanitize_text_field(wp_unslash($_GET['id']))
            : null;
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        $cloneId = $action == 'clone'
            ? $id
            : null;

        $logger = Logtivity::log()
            ->setAction('Code Snippet Created')
            ->setContext($snippet->id)
            ->addMetaIf($cloneId !== null, 'Cloned From ID', $cloneId)
            ->addMetaIf((bool)$table, 'Table', $table);

        $this->logWithMeta($logger, $snippet);
    }

    /**
     * @param Snippet|Model\Snippet $snippet
     * @param string                $table
     *
     * @return void
     */
    public function snippetUpdated($snippet, $table): void
    {
        $logger = Logtivity::log()
            ->setAction('Code Snippet Updated')
            ->setContext($snippet->id)
            ->addMetaIf((bool)$table, 'Table', $table);

        $this->logWithMeta($logger, $snippet);
    }

    /**
     * @param Snippet|Model\Snippet $snippet
     *
     * @return void
     */
    public function snippetActivated($snippet): void
    {
        Logtivity::log()
            ->setAction('Code Snippet Activated')
            ->setContext($snippet->id)
            ->addMeta('Snippet Name', $snippet->name)
            ->addMeta('Network', $snippet->network == 0 ? 'No' : $snippet->network)
            ->send();
    }

    /**
     * @param int $snippetId
     *
     * @return void
     */
    public function snippetDeactivated(int $snippetId): void
    {
        Logtivity::log()
            ->setAction('Code Snippet Deactivated')
            ->setContext($snippetId)
            ->send();
    }

    /**
     * @param Logtivity_Logger      $logger
     * @param Snippet|Model\Snippet $snippet
     *
     * @return void
     */
    public function logWithMeta(Logtivity_Logger $logger, object $snippet): void
    {
        $logger
            ->addMeta('Snippet Name', $snippet->name)
            ->addMeta('Active', $snippet->active ? 'Yes' : 'No')
            ->addMeta('Network', $snippet->network == 0 ? 'No' : $snippet->network)
            ->addMeta('Snippet Code', $snippet->code)
            ->addMeta('Snippet Scope', $snippet->scope)
            ->addMeta('Snippet Priority', $snippet->priority)
            ->addMeta('Snippet Tags', $snippet->tags)
            ->send();
    }
}

new Logtivity_Code_Snippets();
