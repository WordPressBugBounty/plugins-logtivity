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

class Logtivity_User extends Logtivity_Abstract_Logger
{
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

        $log->setAction('User Logged In')->setContext($log->user->getRole())->send();
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
                ->setContext($logger->user->getRole())
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
        $user = get_user($userId);
        $meta = $this->getMetadata('user', $userId);

        Logtivity::log('User Created')
            ->setContext($user->user_login)
            ->addMetaArray($this->sanitizeDataFields($user->to_array()))
            ->addMetaArray($this->sanitizeDataFields($meta))
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
        $assignToUser = $reassign ? get_user($reassign) : null;
        $meta         = $this->getMetadata('user', $userId);

        Logtivity::log('User Deleted')
            ->setContext($user->user_login)
            ->addMetaIf($assignToUser, 'Reassign Content To', $assignToUser->user_login)
            ->addMetaArray($this->sanitizeDataFields($user->to_array()))
            ->addMetaArray($this->sanitizeDataFields($meta))
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
            Logtivity::log('User Updated')
                ->setContext($user->userLogin())
                ->addMeta('User ID', $user->id())
                ->addMeta('Username', $user->userLogin())
                ->addMeta('Role', $user->getRole())
                ->addMetaIf($passwordChange, 'Password Changed', 'Yes')
                ->addMetaIf($activationKey, 'Activation Pending', 'Yes')
                ->addMetaChanged($oldUserdata, $userdata)
                ->addMetaChanged($oldMeta, $meta)
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
        if ($user = get_user_by('login', $username)) {
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
        if ($this->user[$userId]) {
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
    protected function sanitizeDataFields(array $fields): array
    {
        if (array_key_exists('user_pass', $fields)) {
            unset($fields['user_pass']);
        }
        if (array_key_exists('user_activation_key', $fields)) {
            unset($fields['user_activation_key']);
        }

        return parent::sanitizeDataFields($fields);
    }
}

new Logtivity_User();
