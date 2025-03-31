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

abstract class Logtivity_Abstract_Logger
{
    /**
     * @var string[]
     */
    protected array $ignoredPostTypes = [
        'revision',
        'customize_changeset',
        'nav_menu_item',
        'edd_log',
        'edd_payment',
        'edd_license_log',
    ];

    /**
     * @var string[]
     */
    protected array $ignoredPostTitles = [
        'Auto Draft',
    ];

    /**
     * @var string[]
     */
    protected array $ignoredPostStatuses = ['trash'];

    public function __construct()
    {
        $this->registerHooks();
    }

    /**
     * @param WP_Post $post
     *
     * @return bool
     */
    protected function shouldIgnore(WP_Post $post): bool
    {
        return $this->ignoringPostType($post->post_type)
            || $this->ignoringPostTitle($post->post_title)
            || $this->ignoringPostStatus($post->post_status)
            || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE);
    }

    /**
     * @param int $postId
     *
     * @return bool
     */
    protected function loggedRecently(int $postId): bool
    {
        $now = new DateTime();

        if ($date = get_post_meta($postId, 'logtivity_last_logged', true)) {
            $lastLogged = $this->sanitizeDate($date);

            $diffInSeconds = $now->getTimestamp() - $lastLogged->getTimestamp();

            return $diffInSeconds < 5;
        }

        return false;
    }

    /**
     * @param ?string $date
     *
     * @return DateTime
     */
    protected function sanitizeDate(?string $date): DateTime
    {
        try {
            return new DateTime($date);

        } catch (Throwable $e) {
            return new DateTime('1970-01-01');
        }
    }

    /**
     * Ignoring certain post statuses. Example: trash.
     * We already have a postWasTrashed hook so
     * don't need to log twice.
     *
     * @param ?string $postStatus
     *
     * @return bool
     */
    protected function ignoringPostStatus(?string $postStatus): bool
    {
        return in_array($postStatus, $this->ignoredPostStatuses);
    }

    /**
     * Ignoring certain post types. Particularly system generated
     * that are not directly triggered by the user.
     *
     * @param ?string $postType
     *
     * @return bool
     */
    protected function ignoringPostType(?string $postType): bool
    {
        return in_array($postType, $this->ignoredPostTypes);
    }

    /**
     * Ignore certain system generated post titles
     *
     * @param ?string $title
     *
     * @return bool
     */
    protected function ignoringPostTitle(?string $title): bool
    {
        return in_array($title, $this->ignoredPostTitles);
    }

    /**
     * Generate a label version of the given post ids post type
     *
     * @param int $postId
     *
     * @return string
     */
    protected function getPostTypeLabel(int $postId): string
    {
        return $this->formatLabel(get_post_type($postId));
    }

    /**
     * @param string $label
     *
     * @return string
     */
    protected function formatLabel(string $label): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $label));
    }
}
