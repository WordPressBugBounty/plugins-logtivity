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
    public const LAST_LOGGED_KEY     = 'logtivity_last_logged';
    public const LAST_LOGGED_SECONDS = 5;

    public function __construct()
    {
        $this->registerHooks();
    }

    /**
     * @return void
     */
    abstract protected function registerHooks(): void;

    /**
     * @param string|int $id
     * @param ?string    $metaType
     *
     * @return bool
     */
    protected function recentlyLogged($id, ?string $metaType = null): bool
    {
        if ($metaType) {
            /* Use a metadata table */
            $lastLogged     = (int)get_metadata($metaType, $id, static::LAST_LOGGED_KEY, true);
            $loggedRecently = (time() - $lastLogged) < static::LAST_LOGGED_SECONDS;

        } else {
            /* Metadata not available, use transients */
            $key = $this->getLastLoggedKey($id);

            $loggedRecently = (bool)get_transient($key);
        }

        return $loggedRecently;
    }

    /**
     * @param string|int $id
     * @param ?string    $metaType
     *
     * @return void
     */
    protected function setRecentlyLogged($id, ?string $metaType = null): void
    {
        if ($metaType) {
            /* Use a metadata table */
            update_metadata($metaType, $id, static::LAST_LOGGED_KEY, time());

        } else {
            /* Metadata not available, using transients */
            $key = $this->getLastLoggedKey($id);

            $className = explode('\\', static::class);

            set_transient($key, $id . ': ' . array_pop($className), static::LAST_LOGGED_SECONDS);
        }
    }

    /**
     * @param string $id
     *
     * @return string
     */
    protected function getLastLoggedKey(string $id): string
    {
        $key = join(
            ':',
            [
                static::class,
                $id,
            ]
        );

        return static::LAST_LOGGED_KEY . '_' . md5($key);
    }

    /**
     * @param string   $metaType
     * @param int      $parentId
     *
     * @return array
     */
    protected function getMetadata(string $metaType, int $parentId): array
    {
        return array_map(
            function ($row) {
                return logtivity_logger_value($row);
            },
            get_metadata($metaType, $parentId) ?: []
        );
    }

    /**
     * @param array $fields
     * @param array $ignoredKeys
     *
     * @return array
     */
    protected function sanitizeDataFields(array $fields, array $ignoredKeys = []): array
    {
        $values = [];
        foreach ($fields as $key => $value) {
            if (in_array($key, $ignoredKeys) == false) {
                $key = logtivity_logger_label($key);
                $values[$key] = logtivity_logger_value($value);
            }
        }

        return $values;
    }
}
