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

class Logtivity_Download_Monitor extends Logtivity_Abstract_Logger
{
    /**
     * @inheritDoc
     */
    protected function registerHooks(): void
    {
        add_action('dlm_downloading', [$this, 'itemDownloaded']);
    }

    public function itemDownloaded($download)
    {
        Logtivity::log()
            ->setAction('File Downloaded')
            ->setContext($download->get_title())
            ->setPostType(get_post_type($download->get_id()))
            ->setPostId($download->get_id())
            ->addMeta('Download Slug', $download->get_slug())
            ->addMeta('Download ID', $download->get_id())
            ->addMeta('Download Count', $download->get_download_count())
            ->addMeta('Is Featured', $download->is_featured())
            ->addMeta('Is Members Only', $download->is_members_only())
            ->send();
    }
}

$Logtivity_Download_Monitor = new Logtivity_Download_Monitor;
