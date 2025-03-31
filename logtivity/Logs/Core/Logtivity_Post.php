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

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

class Logtivity_Post extends Logtivity_Abstract_Logger
{
    /**
     * @var bool
     */
    protected bool $disableUpdateLog = false;

    /**
     * @var ?string
     */
    protected ?string $action = null;

    /**
     * @return void
     */
    public function registerHooks(): void
    {
        add_action('transition_post_status', [$this, 'postStatusChanged'], 10, 3);
        add_action('save_post', [$this, 'postWasUpdated'], 10, 3);
        add_action('wp_trash_post', [$this, 'postWasTrashed']);
        add_action('delete_post', [$this, 'postPermanentlyDeleted']);

        add_filter('wp_handle_upload', [$this, 'mediaUploaded'], 10, 2);
    }

    /**
     * @param string  $newStatus
     * @param string  $oldStatus
     * @param WP_Post $post
     *
     * @return void
     */
    public function postStatusChanged(string $newStatus, string $oldStatus, WP_Post $post): void
    {
        if ($this->shouldIgnore($post) == false) {
            if ($oldStatus == 'trash') {
                $this->disableUpdateLog = true;
                $this->postWasRestored($post);

            } elseif ($oldStatus != 'publish' && $newStatus == 'publish') {
                $this->action = $this->getPostTypeLabel($post->ID) . ' Published';

            } elseif ($oldStatus == 'publish' && $newStatus == 'draft') {
                $this->action = $this->getPostTypeLabel($post->ID) . ' Unpublished';

            } elseif ($oldStatus != $newStatus) {
                $action = sprintf(
                    '%s Status changed from %s to %s',
                    $this->getPostTypeLabel($post->ID),
                    $oldStatus,
                    $newStatus
                );

                Logtivity_Logger::log()
                    ->setAction($action)
                    ->setContext($post->post_title)
                    ->setPostType($post->post_type)
                    ->setPostId($post->ID)
                    ->send();
            }
        }
    }

    /**
     * Post was updated or created. ignoring certain auto save system actions
     *
     * @param int     $postId
     * @param WP_Post $post
     *
     * @return void
     */
    public function postWasUpdated(int $postId, WP_Post $post): void
    {
        if (
            $this->disableUpdateLog == false
            && $this->shouldIgnore($post) == false
            && $this->loggedRecently($post->ID) == false
        ) {
            $revision = $this->getRevision($postId);

            Logtivity_Logger::log()
                ->setAction($this->action ?: $this->getPostTypeLabel($post->ID) . ' Updated')
                ->setContext($post->post_title)
                ->setPostType($post->post_type)
                ->setPostId($post->ID)
                ->addMeta('Post Title', $post->post_title)
                ->addMeta('Post Status', $post->post_status)
                ->addMetaIf($revision, 'View Revision', $revision)
                ->send();

            update_post_meta($postId, 'logtivity_last_logged', (new DateTime())->format('Y-m-d H:i:s'));
        }
    }

    /**
     * @param int $postId
     *
     * @return ?string
     */
    protected function getRevision(int $postId): ?string
    {
        if ($revisions = wp_get_post_revisions($postId)) {
            $revision = array_shift($revisions);

            $revision = $this->getRevisionLink($revision->ID);
        }

        return $revision ?? null;
    }

    /**
     * @param null|int $revisionId
     * @return null|string
     */
    private function getRevisionLink(?int $revisionId): ?string
    {
        return $revisionId
            ? add_query_arg('revision', $revisionId, admin_url('revision.php'))
            : null;
    }

    /**
     * @param int $postId
     *
     * @return void
     */
    public function postWasTrashed(int $postId): void
    {
        if (get_post_type($postId) != 'customize_changeset') {
            Logtivity_Logger::log()
                ->setAction($this->getPostTypeLabel($postId) . ' Trashed')
                ->setContext(logtivity_get_the_title($postId))
                ->setPostType(get_post_type($postId))
                ->setPostId($postId)
                ->addMeta('Post Title', logtivity_get_the_title($postId))
                ->send();
        }
    }

    /**
     * @param WP_Post $post
     *
     * @return void
     */
    public function postWasRestored(WP_Post $post): void
    {
        $action = $this->getPostTypeLabel($post->ID) . ' Restored from Trash';

        Logtivity_Logger::log()
            ->setAction($action)
            ->setContext($post->post_title)
            ->setPostType($post->post_type)
            ->setPostId($post->ID)
            ->addMeta('Post Title', $post->post_title)
            ->send();
    }

    /**
     * @param int $postId
     *
     * @return void
     */
    public function postPermanentlyDeleted(int $postId): void
    {
        if (
            $this->ignoringPostType(get_post_type($postId)) == false
            && $this->ignoringPostTitle(logtivity_get_the_title($postId)) == false
        ) {
            Logtivity_Logger::log()
                ->setAction(
                    $this->getPostTypeLabel($postId) . ' Permanently Deleted'
                )
                ->setContext(logtivity_get_the_title($postId))
                ->setPostType(get_post_type($postId))
                ->setPostId($postId)
                ->addMeta('Post Title', logtivity_get_the_title($postId))
                ->send();
        }
    }

    /**
     * @param array  $upload
     * @param string $context
     *
     * @return array
     */
    public function mediaUploaded(array $upload, string $context): array
    {
        Logtivity_Logger::log()
            ->setAction('Attachment Uploaded')
            ->setContext(basename($upload['file']))
            ->addMeta('Url', $upload['url'])
            ->addMeta('Type', $upload['type'])
            ->addMeta('Context', $context)
            ->send();

        return $upload;
    }
}

new Logtivity_Post();
