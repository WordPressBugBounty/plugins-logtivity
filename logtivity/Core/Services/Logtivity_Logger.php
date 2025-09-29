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

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

class Logtivity_Logger extends Logtivity_Api
{
    use Logtivity_User_Logger_Trait;

    /**
     * Can this instance log something
     *
     * @var bool
     */
    public bool $active = true;

    /**
     * @var ?string
     */
    public ?string $action = null;

    /**
     * @var ?string
     */
    public ?string $context = null;

    /**
     * @var ?string
     */
    public ?string $post_type = null;

    /**
     * @var ?int
     */
    public ?int $post_id = null;

    /**
     * @var array
     */
    public array $meta = [];

    /**
     * @var array
     */
    public array $userMeta = [];

    /**
     * @param ?int $userId
     */
    public function __construct(?int $userId = null)
    {
        $this->setUser($userId);

        parent::__construct();
    }

    /**
     * Way into class.
     *
     * @param ?string        $action
     * @param ?array|array[] $meta
     * @param ?int           $userId
     *
     * @return Logtivity_Logger
     */
    public static function log(?string $action = null, ?array $meta = null, ?int $userId = null): Logtivity_Logger
    {
        $logtivityLogger = new Logtivity_Logger($userId);

        if ($action) {
            $logtivityLogger->setAction($action);
        }

        if ($meta) {
            // Convert legacy form to current
            // @deprecated v3.1.8
            $key   = $meta['key'] ?? null;
            $value = $meta['value'] ?? null;
            if ($key && $value) {
                $meta = [$key => $value];

            }

            if (array_is_list($meta) == false) {
                // Associative array of keys and values
                foreach ($meta as $key => $value) {
                    $logtivityLogger->addMeta($key, $value);
                }
            }
        }

        return $logtivityLogger;
    }

    /**
     * @param string $action
     *
     * @return $this
     */
    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @param ?string $context
     *
     * @return $this
     */
    public function setContext(?string $context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * @param ?string $postType
     *
     * @return $this
     */
    public function setPostType(?string $postType): self
    {
        $this->post_type = $postType;

        return $this;
    }

    /**
     * @param int $postId
     *
     * @return $this
     */
    public function setPostId(int $postId): self
    {
        $this->post_id = $postId;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function addMeta(string $key, $value): self
    {
        $this->meta[] = [
            'key'   => $key,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * @param array  $values
     * @param string $key (optional)
     *
     * @return $this
     */
    public function addMetaArray(array $values, string $key = ''): self
    {
        if ($key) {
            $title   = str_pad(' ' . $key . ' ', strlen($key) + 8, '*', STR_PAD_BOTH);
            $comment = str_pad(count($values), strlen($key) + 8, '*', STR_PAD_BOTH);
            $this->addMeta($title, $comment);
        }

        foreach ($values as $key => $value) {
            $this->addMeta($key, $value);
        }

        return $this;
    }

    /**
     * @param array            $oldValues
     * @param array            $newValues
     *
     * @return $this
     */
    public function addMetaChanged(array $oldValues, array $newValues): self
    {
        $keys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));
        foreach ($keys as $key) {
            $value    = $newValues[$key] ?? 'NULL';
            $oldValue = $oldValues[$key] ?? 'NULL';
            if ($value != $oldValue) {
                $this->addMeta($key, sprintf('%s => %s', $oldValue, $value));
            }
        }

        return $this;
    }

    /**
     * Add the meta if the first condition is true
     *
     * @param mixed  $condition
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function addMetaIf($condition, string $key, $value): self
    {
        if ($condition) {
            $this->addMeta($key, $value);
        }

        return $this;
    }

    /**
     * Add to an array of user meta you would like to pass to this log.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function addUserMeta(string $key, $value): self
    {
        $this->userMeta[$key] = $value;

        return $this;
    }

    /**
     * @return $this
     */
    public function addTrace(): self
    {
        $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        array_shift($stack);

        $this->addMeta('Stack Trace', '**********');

        foreach ($stack as $index => $stackItem) {
            $file = str_replace(ABSPATH, '', $stackItem['file'] ?? 'NULL');
            $line = $stackItem['line'] ?? 'NULL';
            $this->addMeta($index, sprintf('%s: %s', $line, $file));
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function stop(): self
    {
        $this->active = false;

        return $this;
    }

    /**
     * Send the logged data to Logtivity
     *
     * @return void
     */
    public function send(): ?array
    {
        $this->maybeAddProfileLink();

        do_action('wp_logtivity_instance', $this);

        return $this->active
            ? $this->makeRequest('/logs/store', $this->getData())
            : null;
    }

    /**
     * @return array
     */
    protected function getData(): array
    {
        return [
            'action'     => $this->action,
            'context'    => $this->context,
            'post_type'  => $this->post_type,
            'post_id'    => $this->post_id,
            'meta'       => $this->getMeta(),
            'user_id'    => $this->getUserID(),
            'username'   => $this->maybeGetUsersUsername(),
            'user_meta'  => $this->getUserMeta(),
            'ip_address' => $this->maybeGetUsersIp(),
        ];
    }

    /**
     * Build the user meta array
     *
     * @return array
     */
    public function getUserMeta(): array
    {
        return (array)apply_filters('wp_logtivity_get_user_meta', $this->userMeta);
    }

    /**
     * Build the meta array
     *
     * @return array
     */
    public function getMeta(): array
    {
        return (array)apply_filters('wp_logtivity_get_meta', $this->meta);
    }

    /**
     * @return $this
     */
    protected function maybeAddProfileLink(): self
    {
        if (
            $this->options->shouldStoreProfileLink()
            && $this->user->isLoggedIn()
            && ($profileLink = $this->user->profileLink())
        ) {
            $this->addUserMeta('Profile Link', $profileLink);
        }

        return $this;
    }
}
