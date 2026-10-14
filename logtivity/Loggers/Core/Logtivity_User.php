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

class Logtivity_User extends Logtivity_Abstract_Logger
{
    /**
     * Meta keys matching this pattern are never sent to the API in full. A
     * change to one is still reported — only the value is hidden — so e.g. a
     * 2FA reset, a new application password, or a fresh session stays visible
     * in the audit trail without exposing the credential itself.
     *
     * @var string
     */
    // phpcs:ignore Generic.Files.LineLength.TooLong
    protected const SENSITIVE_META_PATTERN = '/password|secret|totp|backup_code|session_token|private_key|api_key|access_token|refresh_token|webauthn/i';

    /**
     * @var array
     */
    protected array $meta = [];

    /**
     * @var WP_User[]
     */
    protected array $user = [];

    /**
     * @inheritDoc
     */
    protected function registerHooks(): void
    {
        add_action('wp_login', [$this, 'wp_login'], 10, 2);
        add_action('wp_logout', [$this, 'wp_logout'], 10, 1);
        add_action('user_register', [$this, 'user_register'], 10, 2);
        add_action('deleted_user', [$this, 'deleted_user'], 10, 3);

        add_action('personal_options_update', [$this, 'saveOriginalMetadata'], 10, 1);  // Profile page
        add_action('edit_user_profile_update', [$this, 'saveOriginalMetadata'], 10, 1); // Editing other user
        add_action('update_user_meta', [$this, 'saveOriginalUser'], 10, 4);
        add_action('profile_update', [$this, 'profile_update'], 10, 3);
        add_action('retrieve_password', [$this, 'retrieve_password'], 10, 1);
        add_action('wp_set_password', [$this, 'wp_set_password'], 10, 3);

        // Triggered from bulk updates
        add_action('set_user_role', [$this, 'set_user_role'], 10, 3);
    }

    /**
     * @param string  $username
     * @param WP_User $user
     *
     * @return void
     */
    public function wp_login($username, $user): void
    {
        $log = Logtivity::log()->setUser($user);

        $log->setAction('User Logged In')->setContext($log->user->userLogin())->send();
    }

    /**
     * @param int $userId
     *
     * @return void
     */
    public function wp_logout($userId): void
    {
        if ($userId) {
            $logger = Logtivity::log()->setUser($userId);

            $logger
                ->setAction('User Logged Out')
                ->setContext($logger->user->userLogin())
                ->send();
        }
    }

    /**
     * @param int $userId
     *
     * @return void
     */
    public function user_register($userId): void
    {
        $user = new Logtivity_WP_User($userId);
        $meta = $this->getMetadata('user', $userId);

        // Self-registration has no logged-in actor, so the registering user is the actor.
        $actorId = is_user_logged_in() ? null : $userId;

        Logtivity::log('User Created', null, $actorId)
            ->setContext($user->userLogin())
            ->addMetaArray($this->sanitizeDataFields($user->to_array()))
            ->addMetaArray($this->sanitizeDataFields($this->redactSensitiveMeta($meta)))
            ->send();
    }

    /**
     * @param int     $userId
     * @param ?int    $reassign
     * @param WP_User $user
     *
     * @return void
     */
    public function deleted_user($userId, $reassign, $user): void
    {
        $user         = new Logtivity_WP_User($user);
        $assignToUser = $reassign ? get_user($reassign) : null;
        $meta         = $this->getMetadata('user', $userId);

        Logtivity::log('User Deleted')
            ->setContext($user->userLogin())
            ->addMetaIf($assignToUser, 'Reassign Content To', $assignToUser ? $assignToUser->user_login : null)
            ->addMetaArray($this->sanitizeDataFields($user->to_array()))
            ->addMetaArray($this->sanitizeDataFields($this->redactSensitiveMeta($meta)))
            ->send();
    }

    /**
     * @param int     $userId
     * @param WP_User $oldUser
     *
     * @return void
     */
    public function profile_update($userId, $oldUser): void
    {
        $user = new Logtivity_Wp_User($userId);

        $oldUserdata = $oldUser->to_array();
        $userdata    = $user->to_array();

        $oldMeta = $this->meta[$userId] ?? [];
        $meta    = $this->getMetadata('user', $userId);

        $activationKey  = $userdata['user_activation_key'];
        $passwordChange = $oldUserdata['user_pass'] != $userdata['user_pass'];

        $oldUserdata = $this->sanitizeDataFields($oldUserdata);
        $userdata    = $this->sanitizeDataFields($userdata);
        if ($passwordChange || $oldUserdata != $userdata || $oldMeta != $meta) {
            [$safeOldMeta, $safeMeta] = $this->redactSensitiveMetaChanges($oldMeta, $meta);

            Logtivity::log('User Updated')
                ->setContext($user->userLogin())
                ->addMeta('User ID', $user->id())
                ->addMeta('Username', $user->userLogin())
                ->addMeta('Role', $user->getRole())
                ->addMetaIf($passwordChange, 'Password Changed', 'Yes')
                ->addMetaIf($activationKey, 'Activation Pending', 'Yes')
                ->addMetaChanges($oldUserdata, $userdata)
                ->addMetaChanges($safeOldMeta, $safeMeta)
                ->send();
        }
    }

    /**
     * @param string $username
     *
     * @return void
     */
    public function retrieve_password($username): void
    {
        $user = get_user_by('login', $username);
        if ($user) {
            $this->saveOriginalUser(null, $user->ID);
        }

        Logtivity::log('Password Reset Sent')
            ->setContext($username)
            ->send();
    }

    /**
     * @param string  $password
     * @param int     $userId
     * @param WP_User $oldUser
     *
     * @return void
     */
    public function wp_set_password($password, $userId, $oldUser): void
    {
        $this->saveOriginalMetadata($userId);

        $this->profile_update($userId, $oldUser);
    }

    /**
     * Cover bulk update of role changes
     *
     * @param int $userId
     *
     * @return void
     */
    public function set_user_role($userId): void
    {
        if ($this->user[$userId] ?? null) {
            $this->profile_update($userId, $this->user[$userId]);
        }
    }

    /**
     * @param ?int $metaId
     * @param int  $objectId
     *
     * @return void
     */
    public function saveOriginalUser($metaId, $objectId): void
    {
        if (empty($this->meta[$objectId])) {
            $this->saveOriginalMetadata($objectId);
            $this->user[$objectId] = get_user($objectId);
        }
    }

    /**
     * @param int $userId
     *
     * @return void
     */
    public function saveOriginalMetadata(int $userId): void
    {
        if (empty($this->meta[$userId])) {
            $this->meta[$userId] = $this->getMetadata('user', $userId);
        }
    }

    /**
     * @inheritDoc
     */
    protected function sanitizeDataFields(array $fields, array $ignoredKeys = []): array
    {
        if (array_key_exists('user_pass', $fields)) {
            unset($fields['user_pass']);
        }
        if (array_key_exists('user_activation_key', $fields)) {
            unset($fields['user_activation_key']);
        }

        return parent::sanitizeDataFields($fields);
    }

    /**
     * Mask sensitive values in a meta snapshot that is about to be logged in
     * full (user creation or deletion), where there is no "before" state to
     * diff against.
     *
     * @param array $meta
     *
     * @return array
     */
    protected function redactSensitiveMeta(array $meta): array
    {
        foreach ($meta as $key => $value) {
            if (preg_match(static::SENSITIVE_META_PATTERN, $key)) {
                $meta[$key] = '[Hidden]';
            }
        }

        return $meta;
    }

    /**
     * Mask sensitive values in a before/after meta pair that is about to be
     * diffed and logged. A sensitive key that changed is still reported as
     * changed, with both sides replaced by distinct markers so the diff still
     * renders; a sensitive key that did not change is dropped entirely so it
     * never reaches the API.
     *
     * @param array $oldMeta
     * @param array $meta
     *
     * @return array
     */
    protected function redactSensitiveMetaChanges(array $oldMeta, array $meta): array
    {
        foreach (array_unique(array_merge(array_keys($oldMeta), array_keys($meta))) as $key) {
            if (preg_match(static::SENSITIVE_META_PATTERN, $key) == false) {
                continue;
            }

            if (($oldMeta[$key] ?? null) === ($meta[$key] ?? null)) {
                unset($oldMeta[$key], $meta[$key]);
                continue;
            }

            if (array_key_exists($key, $oldMeta)) {
                $oldMeta[$key] = '[Hidden]';
            }
            if (array_key_exists($key, $meta)) {
                $meta[$key] = '[Hidden - Changed]';
            }
        }

        return [$oldMeta, $meta];
    }
}

new Logtivity_User();
