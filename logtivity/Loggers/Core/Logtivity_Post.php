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
     * @var array[]
     */
    protected array $post = [];

    /**
     * @var array[]
     */
    protected array $meta = [];

    /**
     * @var string[]
     */
    protected array $statusTexts = [
        'publish' => 'Published',
        'future'  => 'Scheduled',
        'draft'   => 'Draft Saved',
        'pending' => 'Pending',
        'private' => 'Made Private',
        'trash'   => 'Trashed',
    ];

    /**
     * @var string[]
     */
    protected array $ignoreStatuses = [
        'auto-draft',
    ];

    /**
     * @var string[]
     */
    protected array $ignoreDataKeys = [
        'post_modified',
        'post_modified_gmt',
        'post_date',
        'post_date_gmt',
        self::LAST_LOGGED_KEY,
        '_edit_lock',
        '_edit_last',
        '_wp_attachment_metadata',
        '_encloseme',
        '_wp_trash_meta_status',
        '_wp_trash_meta_time',
        '_wp_desired_post_slug',
        '_wp_trash_meta_comments_status',
        '_pingme',
        '_{$prefix}old_slug',
        '_encloseme',
        '_{$prefix}trash_meta_status',
        '_{$prefix}trash_meta_time',
    ];

    /**
     * @var string[]
     */
    protected array $clearName = [
        '__trashed',
    ];

    /**
     * @var string[]
     */
    protected array $ignoreTypes = [
        'revision',
        'customize_changeset',
        'nav_menu_item',
        /* @TODO: These need to be moved to the EDD Logger */
        'edd_log',
        'edd_payment',
        'edd_license_log',
    ];

    /**
     * @inheritDoc
     */
    protected function registerHooks(): void
    {
        global $wpdb;

        $prefix = $wpdb->get_blog_prefix();

        $this->ignoreDataKeys = array_map(
            function (string $key) use ($prefix) {
                return str_replace('{$prefix}', $prefix, $key);
            },
            $this->ignoreDataKeys
        );

        // Post meta changes

        if (get_option('logtivity_enable_post_meta_logging')) {
            add_action('added_post_meta', [$this, 'added_post_meta'], 10, 4);
            add_action('updated_post_meta', [$this, 'updated_post_meta'], 10, 4);
            add_action('delete_post_meta', [$this, 'delete_post_meta'], 10, 4);
        }

        // Core post changes
        add_action('pre_post_update', [$this, 'saveOriginalData'], 10, 2);
        add_action('save_post', [$this, 'save_post'], 10, 3);
        add_action('before_delete_post', [$this, 'before_delete_post'], 10, 2);
        add_action('after_delete_post', [$this, 'after_delete_post'], 10, 2);

        // Catch category changes that are otherwise missed
        add_action('set_object_terms', [$this, 'set_object_terms'], 10, 6);

        // Media/Attachment changes
        // @TODO: skip media data changes for now. Maybe always?
        add_filter('wp_handle_upload', [$this, 'wp_handle_upload'], 10, 2);
        //add_action('attachment_updated', [$this, 'attachment_updated'], 10, 3);
        add_action('deleted_post', [$this, 'deleted_post'], 10, 2);

    }

    public function __call(string $name, array $arguments)
    {
        $log = Logtivity::log($name)
            ->setContext('TESTING');

        foreach ($arguments as $i => $argument) {
            $log->addMeta('Arg #' . ($i + 1), $argument);
        }

        $log->addTrace()->send();

        // For filters
        return $arguments[0] ?? null;
    }

    /**
     * @param int    $metaId
     * @param int    $objectId
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function added_post_meta($metaId, $objectId, $key, $value): void
    {
        $this->logMetaChange($objectId, $key, $value, 'Added');
    }

    /**
     * @param int    $metaId
     * @param int    $objectId
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function updated_post_meta($metaId, $objectId, $key, $value): void
    {
        $this->logMetaChange($objectId, $key, $value, 'Updated');
    }

    public function delete_post_meta($metaId, $objectId, $key, $value): void
    {
        $this->logMetaChange($objectId, $key, $value, 'Deleted');
    }

    /**
     * @param int    $objectId
     * @param string $key
     * @param mixed  $value
     * @param string $action
     *
     * @return void
     */
    protected function logMetaChange($objectId, $key, $value, $action): void
    {
        $oldValue = $this->meta[$objectId][$key] ?? null;
        $post     = get_post($objectId);

        if (
            $post
            && $this->shouldIgnorePost($post) == false
            && array_search($key, $this->ignoreDataKeys) == false
            && $oldValue != $value
            && $post->post_type != 'attachment' // @TODO: deal with these later
        ) {
            $action = sprintf(
                '%s Meta %s',
                logtivity_logger_label($post->post_type),
                $action
            );

            Logtivity::log($action)
                ->setContext($key)
                ->setPostType($post->post_type)
                ->setPostId($objectId)
                ->addMeta('Post Title', $post->post_title)
                ->addMeta('Old Value', $oldValue)
                ->addMeta('New Value', $value)
                ->send();

            if (isset($this->meta[$objectId][$key])) {
                $this->meta[$objectId][$key] = $value;
            }
        }
    }

    /**
     * @param int    $objectId
     * @param int[]  $terms
     * @param int[]  $ttIds
     * @param string $taxonomy
     *
     * @return void
     */
    public function set_object_terms($objectId, $terms, $ttIds, $taxonomy): void
    {
        if (
            isset($this->post[$objectId])
            && $taxonomy == 'category'
        ) {
            $oldPost    = $this->post[$objectId];
            $categories = $this->getCategoryNames($terms);

            if (
                $categories != $oldPost['post_category']
                && $this->recentlyLogged($objectId, 'post') == false
            ) {
                // Thw normal post updated entry probably missed category changes
                $oldCategories = join(':', $oldPost['post_category']);
                $newCategories = join(':', $categories);
                $post          = $this->getPostData(get_post($objectId));
                $action        = $this->getPostAction($this->sanitizeDataFields($post), 'Category Changed');

                Logtivity::log($action)
                    ->setContext($post['post_title'])
                    ->setPostType($post['post_type'])
                    ->setPostId($objectId)
                    ->addMeta('Old Categories', $oldCategories)
                    ->addMeta('New Categories', $newCategories)
                    ->send();
            }
        }
    }

    /**
     * Note that we are ignoring the second argument passed to this hook
     *
     * @param int $postId
     *
     * @return void
     */
    public function saveOriginalData($postId): void
    {
        if (empty($this->post[$postId])) {
            $this->post[$postId] = $this->getPostData(get_post($postId));
            $this->meta[$postId] = $this->getPostMeta($postId);
        }
    }

    /**
     * @param int     $postId
     * @param WP_Post $post
     *
     * @return void
     */
    public function save_post($postId, $post): void
    {
        if (
            $this->shouldIgnorePost($post) == false
            && $this->recentlyLogged($postId, 'post') == false
        ) {
            $oldData = $this->sanitizeDataFields($this->post[$postId] ?? []);
            $data    = $this->sanitizeDataFields($this->getPostData($post));

            $contentChanged = $this->checkContent($oldData, $data);

            $oldMeta = $this->sanitizeDataFields($this->meta[$postId] ?? []);
            $meta    = $this->sanitizeDataFields($this->getPostMeta($postId));

            if ($contentChanged || $oldData != $data || $oldMeta != $meta) {
                $revisions = wp_get_post_revisions($postId);

                Logtivity::log($this->getPostAction($data, $oldData))
                    ->setContext($post->post_title)
                    ->setPostType($post->post_type)
                    ->setPostId($postId)
                    ->addMeta('Status', $post->post_status)
                    ->addMeta('Revisions', count($revisions), false)
                    ->addMeta('View Revision', $this->getRevisionLink($revisions))
                    ->addMetaIf($contentChanged, 'Content Changed', 'Yes')
                    ->addMetaChanges($oldData, $data)
                    ->addMetaChanges($oldMeta, $meta)
                    ->send();

                $this->setRecentlyLogged($postId, 'post');
            }
        }
    }

    /**
     * @param int $postId
     *
     * @return void
     */
    public function before_delete_post($postId): void
    {
        $this->meta[$postId] = $this->getPostMeta($postId);
    }

    /**
     * @param int     $postId
     * @param WP_Post $post
     *
     * @return void
     */
    public function after_delete_post($postId, $post): void
    {
        if ($this->shouldIgnorePost($post) == false) {
            $postData = $this->sanitizeDataFields($this->getPostData($post));
            unset($postData['Post Content']);

            $metaData = $this->sanitizeDataFields($this->meta[$postId] ?? []);

            Logtivity::log(ucfirst($post->post_type) . ' Deleted')
                ->setContext($post->post_title)
                ->setPostType($post->post_type)
                ->setPostId($postId)
                ->addMetaArray($postData)
                ->addMetaArray($metaData)
                ->send();
        }
    }

    /**
     * Using this filter rather than add_attachment action
     * because we get all the info we want more easily
     *
     * @param array  $upload
     * @param string $context
     *
     * @return array
     */
    public function wp_handle_upload($upload, $context): array
    {
        Logtivity::log()
            ->setAction('Attachment Uploaded')
            ->setContext(basename($upload['file']))
            ->addMeta('Url', $upload['url'])
            ->addMeta('Type', $upload['type'])
            ->addMeta('Context', $context)
            ->addTrace()
            ->send();

        return $upload;
    }

    /**
     * @param int     $postId
     * @param WP_Post $post
     *
     * @return void
     */
    public function deleted_post($postId, $post): void
    {
        if ($post->post_type == 'attachment') {
            $data = $this->getPostData($post);
            unset($data['post_content']);

            Logtivity::log('Attachment Deleted')
                ->setContext($post->post_title)
                ->setPostType($post->post_type)
                ->setPostId($postId)
                ->addMetaArray($this->sanitizeDataFields($data))
                ->send();
        }
    }

    /**
     * @param WP_Post $post
     *
     * @return array
     */
    protected function getPostData(WP_Post $post): array
    {
        $data = $post->to_array();

        // Never show actual content
        $data['post_content'] = sha1($post->post_content);

        // Some statuses change name reflecting status
        $data['post_name'] = str_replace($this->clearName, '', $data['post_name']);

        // Convert author ID to author name
        if (empty($data['post_author'] == false)) {
            if ($author = get_user($data['post_author'])) {
                $data['post_author'] = $author->user_login;
            } else {
                $data['post_author'] = 'ID ' . $data['post_author'];
            }
        }

        $data['post_category'] = $this->getCategoryNames($data['post_category']);

        return $data;
    }

    /**
     * @param int $postId
     *
     * @return array
     */
    protected function getPostMeta(int $postId): array
    {
        return $this->getMetadata('post', $postId);
    }

    /**
     * @param ?array $categoryIds
     *
     * @return array
     */
    protected function getCategoryNames(?array $categoryIds): array
    {
        $categories = [];
        $terms      = get_categories($categoryIds);
        foreach ($terms as $term) {
            $categories[] = $term->name;
        }

        return $categories;
    }

    /**
     * @inheritDoc
     */
    protected function sanitizeDataFields(array $fields, array $ignoredKeys = []): array
    {
        return parent::sanitizeDataFields($fields, $this->ignoreDataKeys);
    }

    /**
     * @param array        $current
     * @param string|array $previous
     *
     * @return string
     */
    protected function getPostAction(array $current, $previous): string
    {
        $type   = ucfirst($current['Post Type'] ?? 'Post');
        $status = $current['Post Status'] ?? '';

        if (is_string($previous)) {
            $change = $previous;
        } else {
            $oldStatus = $previous['Post Status'] ?? '';
            $change    = ($status != $oldStatus) ? ($this->statusTexts[$status] ?? null) : 'Updated';
        }

        return sprintf('%s %s', $type, $change);
    }

    /**
     * @param array $old
     * @param array $current
     *
     * @return bool
     */
    protected function checkContent(array &$old, array &$current): bool
    {
        // Assume arrays have already been sanitized
        $key = 'Post Content';

        if (isset($old[$key])) {
            $contentChanged = $old[$key] != $current[$key];
            unset($old[$key], $current[$key]);
        }

        return $contentChanged ?? false;
    }

    /**
     * @param WP_Post[] $revisions
     *
     * @return ?string
     */
    protected function getRevisionLink(array $revisions): ?string
    {
        reset($revisions);

        $id = key($revisions);
        return $id
            ? add_query_arg('revision', $id, admin_url('revision.php'))
            : null;
    }

    /**
     * @param WP_Post $post
     *
     * @return bool
     */
    protected function shouldIgnorePost(WP_Post $post): bool
    {
        return in_array($post->post_status, $this->ignoreStatuses)
            || in_array($post->post_type, $this->ignoreTypes);
    }
}

new Logtivity_Post();
