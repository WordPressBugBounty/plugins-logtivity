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
    protected static bool $loggedUserlogin = false;

    /**
     * @param string  $username
     * @param WP_User $user
     *
     * @return void
     */
    public function userLoggedIn(string $username, WP_User $user)
    {
        if (static::$loggedUserlogin == false) {
            static::$loggedUserlogin = true;

            $logger = Logtivity::log()->setUser($user->ID);

            $logger->setAction('User Logged In')
                ->setContext($logger->user->getRole())
                ->send();
        }
    }

    /**
     * @param int $userId
     *
     * @return void
     */
    public function userLoggedOut(int $userId): void
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
    public function userCreated(int $userId): void
    {
        $log = Logtivity::log();

        if (is_user_logged_in() == false) {
            $log->setUser($userId);
            $user = $log->user;
        } else {
            $user = new Logtivity_WP_User($userId);
        }

        $log->setAction('User Created')
            ->setContext($user->getRole())
            ->addMeta('Username', $user->userLogin())
            ->send();
    }

    /**
     * @param int $userId
     *
     * @return void
     */
    public function userDeleted(int $userId): void
    {
        $user = new Logtivity_WP_User($userId);

        Logtivity::log()
            ->setAction('User Deleted')
            ->setContext($user->getRole())
            ->addMeta('Username', $user->userLogin())
            ->send();
    }

    public function profileUpdated($userId)
    {
        $user = new Logtivity_WP_User($userId);

        Logtivity::log()
            ->setAction('User Updated')
            ->setContext($user->getRole())
            ->addMeta('Username', $user->userLogin())
            ->send();
    }

    /**
     * @inheritDoc
     */
    protected function registerHooks(): void
    {
        add_action('wp_login', [$this, 'userLoggedIn'], 10, 2);
        add_action('wp_logout', [$this, 'userLoggedOut'], 10, 1);
        add_action('user_register', [$this, 'userCreated'], 10, 1);
        add_action('delete_user', [$this, 'userDeleted']);
        add_action('profile_update', [$this, 'profileUpdated'], 10, 1);
    }
}

new Logtivity_User();
