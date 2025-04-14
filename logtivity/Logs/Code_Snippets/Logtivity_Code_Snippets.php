<?php

use Code_Snippets\Snippet;

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
class Logtivity_Code_Snippets
{
    public function __construct()
    {
        add_action('code_snippets/delete_snippet', [$this, 'snippetDeleted'], 10, 2);
        add_action('code_snippets/create_snippet', [$this, 'snippetCreated'], 10, 2);
        add_action('code_snippets/update_snippet', [$this, 'snippetUpdated'], 10, 2);
        add_action('code_snippets/activate_snippet', [$this, 'snippetActivated'], 10, 2);
        add_action('code_snippets/deactivate_snippet', [$this, 'snippetDeactivated'], 10, 2);
    }

    /**
     * @param int   $snippetId
     * @param ?bool $network
     *
     * @return void
     */
    public function snippetDeleted(int $snippetId, ?bool $network): void
    {
        Logtivity::log()
            ->setAction('Code Snippet Deleted')
            ->setContext($snippetId)
            ->addMeta('Network', $network ? 'Yes' : 'No')
            ->send();
    }

    /**
     * @param Snippet $snippet
     *
     * @return void
     */
    public function snippetCreated(Snippet $snippet): void
    {
        $cloneId = ($_GET['action'] ?? null) == 'clone' ? ($_GET['id'] ?? null) : null;

        $logger = Logtivity::log()
            ->setAction('Code Snippet Created')
            ->setContext($snippet->id)
            ->addMetaIf($cloneId !== null, 'Cloned From ID', $cloneId);

        $this->logWithMeta($logger, $snippet);
    }

    /**
     * @param Snippet $snippet
     *
     * @return void
     */
    public function snippetUpdated(Snippet $snippet): void
    {
        $logger = Logtivity::log()
            ->setAction('Code Snippet Updated')
            ->setContext($snippet->id);

        $this->logWithMeta($logger, $snippet);
    }

    /**
     * @param Snippet $snippet
     *
     * @return void
     */
    public function snippetActivated(Snippet $snippet): void
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
     * @param Logtivity_Logger $logger
     *
     * @return void
     */
    public function logWithMeta(Logtivity_Logger $logger, ?Snippet $snippet = null): void
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
