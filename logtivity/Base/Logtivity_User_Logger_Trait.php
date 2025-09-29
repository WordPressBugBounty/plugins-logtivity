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

trait Logtivity_User_Logger_Trait
{
    /**
     * @var Logtivity_Wp_User
     */
    public Logtivity_Wp_User $user;

    /**
     * @param null|int|WP_User|Logtivity_Wp_User $user
     *
     * @return $this
     */
    public function setUser($user = null): self
    {
        $this->user = new Logtivity_Wp_User($user);

        return $this;
    }

    /**
     * @return ?int
     */
    protected function getUserID(): ?int
    {
        if (
            $this->options->shouldStoreUserId()
            && $this->user->isLoggedIn()
        ) {
            return $this->user->id();
        }

        return null;
    }

    /**
     * @return ?string
     */
    protected function maybeGetUsersIp(): ?string
    {
        if ($this->options->shouldStoreIp()) {
            $ipAddress = filter_var(
                ($_SERVER['HTTP_CLIENT_IP'] ?? '')
                    ?: ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '')
                    ?: $_SERVER['REMOTE_ADDR'] ?? '',
                FILTER_VALIDATE_IP
            );

            return $ipAddress ?: null;
        }

        return null;
    }

    /**
     * @return ?string
     */
    protected function maybeGetUsersUsername(): ?string
    {
        if (
            $this->options->shouldStoreUsername()
            && $this->user->isLoggedIn()
        ) {
            return $this->user->userLogin();
        }

        return null;
    }
}
