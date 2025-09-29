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

class Logtivity_Wp_User
{
    /**
     * @var ?WP_User
     */
    protected ?WP_User $user = null;

    /**
     * @param null|int|WP_User|Logtivity_Wp_User $user
     * @param string                             $field
     */
    public function __construct($user = null, string $field = 'ID')
    {
        if (is_null($user)) {
            if (function_exists('wp_get_current_user')) {
                $this->user = wp_get_current_user();
            }

        } elseif ($user instanceof WP_User) {
            $this->user = $user;

        } elseif ($user instanceof static) {
            $this->user = $user->user;

        } elseif (is_scalar($user) && function_exists('get_user_by')) {
            $this->user = get_user_by($field, $user) ?: null;
        }
    }

    /**
     * @param string $name
     *
     * @return ?mixed
     */
    public function __get(string $name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }

        return null;
    }

    /**
     * @param string $field
     * @param mixed  $value
     *
     * @return $this
     */
    public function findByUserMeta(string $field, $value): self
    {
        $users = get_users([
            'meta_key'   => $field,
            'meta_value' => $value,
        ]);

        $this->user = $users ? array_shift($users) : null;

        return $this;
    }

    /**
     * @return ?int
     */
    public function id(): ?int
    {
        return $this->user->ID ?? null;
    }

    /**
     * @return string
     */
    public function userLogin(): string
    {
        return $this->user->user_login ?? '';
    }

    /**
     * @return string
     */
    public function email(): string
    {
        return $this->user->user_email ?? '';
    }

    /**
     * @return ?string
     */
    public function name(): string
    {
        return trim($this->firstName() . ' ' . $this->lastName());
    }

    /**
     * @return string
     */
    public function firstName(): string
    {
        return $this->meta('first_name');
    }

    /**
     * @return string
     */
    public function lastName(): string
    {
        return $this->meta('last_name');
    }

    /**
     * @return string
     */
    public function displayName(): string
    {
        return $this->user->display_name ?? '';
    }

    /**
     * @return string
     */
    public function niceName(): string
    {
        return $this->user->user_nicename ?? '';
    }

    /**
     * @return string
     */
    public function profileLink(): string
    {
        if ($this->user) {
            return add_query_arg('user_id', $this->id(), self_admin_url('user-edit.php'));
        }

        return '';
    }

    /**
     * @param string $metaKey
     * @param bool   $returnString (optional)
     *
     * @return mixed
     */
    public function meta(string $metaKey, bool $returnString = true)
    {
        return $this->user ? get_user_meta($this->user->ID, $metaKey, $returnString) : '';
    }

    /**
     * Does the fetched user exist
     *
     * @return bool
     */
    public function exists(): bool
    {
        return (bool)$this->user;
    }

    /**
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return ($this->user->ID ?? false);
    }

    /**
     * @param string $role
     *
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles());
    }

    /**
     * @return array
     */
    public function getRoles(): array
    {
        return $this->user->roles ?? [];
    }

    /**
     * Get the users first role
     *
     * @return string
     */
    public function getRole(): string
    {
        $roles = $this->getRoles();

        return array_shift($roles) ?: '';
    }

    /**
     * @return array
     */
    public function to_array(): array
    {
        return $this->user->to_array();
    }
}
