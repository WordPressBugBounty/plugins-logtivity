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

/**
 * Writes an append-only, size-capped history of Logtivity API calls (request and
 * response data) to a protected file in the uploads directory when debug mode is
 * enabled. This is separate from, and does not affect, the `logtivity_latest_response`
 * option used to track connection status.
 */
class Logtivity_Debug_Log
{
    public const DIRECTORY_NAME = 'logtivity-logs';

    public const FILE_NAME = 'debug.log';

    public const MAX_BYTES = 5 * MB_IN_BYTES;

    public const MAX_ENTRIES_RETURNED = 50;

    /**
     * @var ?WP_Filesystem_Base
     */
    protected $filesystem = null;

    /**
     * Append a log entry to the debug log file.
     *
     * @param array $entry
     *
     * @return void
     */
    public function write(array $entry): void
    {
        if (!$this->initFilesystem() || !$this->ensureDirectory()) {
            return;
        }

        $path     = $this->filePath();
        $existing = $this->filesystem->exists($path) ? $this->filesystem->get_contents($path) : '';

        $this->filesystem->put_contents(
            $path,
            $existing . wp_json_encode($entry) . "\n",
            FS_CHMOD_FILE
        );

        $this->maybeTrim($path);
    }

    /**
     * Get the most recent log entries, newest first.
     *
     * @param int $limit
     *
     * @return array
     */
    public function recent(int $limit = self::MAX_ENTRIES_RETURNED): array
    {
        if (!$this->initFilesystem() || !$this->filesystem->exists($this->filePath())) {
            return [];
        }

        $lines   = $this->readLines($this->filePath());
        $entries = [];

        foreach (array_slice($lines, -$limit) as $line) {
            $entry = json_decode($line, true);
            if (is_array($entry)) {
                $entries[] = $entry;
            }
        }

        return array_reverse($entries);
    }

    /**
     * Get the raw contents of the debug log file, for downloading.
     *
     * @return string
     */
    public function contents(): string
    {
        if (!$this->initFilesystem() || !$this->filesystem->exists($this->filePath())) {
            return '';
        }

        return (string)$this->filesystem->get_contents($this->filePath());
    }

    /**
     * Empty the debug log file.
     *
     * @return bool
     */
    public function clear(): bool
    {
        if (!$this->initFilesystem() || !$this->ensureDirectory()) {
            return false;
        }

        return (bool)$this->filesystem->put_contents($this->filePath(), '', FS_CHMOD_FILE);
    }

    /**
     * Remove the debug log file and its containing directory (including the
     * protective .htaccess/index.php, and anything else that happens to be in
     * there) entirely.
     *
     * Lists whatever files are actually present with PHP's own scandir()
     * (guaranteed to include dotfiles like .htaccess, unlike some
     * WP_Filesystem::dirlist() implementations) and deletes each one before
     * removing the directory itself, so the final directory delete never fails
     * because something was left behind.
     *
     * @return bool
     */
    public function delete(): bool
    {
        if (!$this->initFilesystem()) {
            return false;
        }

        $dir = $this->directoryPath();

        if (!$this->filesystem->is_dir($dir)) {
            return true;
        }

        foreach (scandir($dir) ?: [] as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $this->filesystem->delete($dir . '/' . $file, true);
        }

        return (bool)$this->filesystem->delete($dir);
    }

    /**
     * Trim the oldest entries if the file has grown past the size cap.
     *
     * @param string $path
     *
     * @return void
     */
    protected function maybeTrim(string $path): void
    {
        if ((int)$this->filesystem->size($path) <= static::MAX_BYTES) {
            return;
        }

        $lines = $this->readLines($path);
        $lines = array_slice($lines, (int)(count($lines) * 0.25));

        $this->filesystem->put_contents(
            $path,
            implode("\n", $lines) . (empty($lines) ? '' : "\n"),
            FS_CHMOD_FILE
        );
    }

    /**
     * @param string $path
     *
     * @return array
     */
    protected function readLines(string $path): array
    {
        $contents = $this->filesystem->get_contents($path);

        return array_values(array_filter(explode("\n", trim((string)$contents))));
    }

    /**
     * @return string
     */
    protected function filePath(): string
    {
        return $this->directoryPath() . '/' . static::FILE_NAME;
    }

    /**
     * @return string
     */
    protected function directoryPath(): string
    {
        $uploads = wp_upload_dir();

        return rtrim($uploads['basedir'], '/') . '/' . static::DIRECTORY_NAME;
    }

    /**
     * Create the log directory, protected from direct web access, if it doesn't
     * already exist.
     *
     * @return bool
     */
    protected function ensureDirectory(): bool
    {
        $dir = $this->directoryPath();

        if ($this->filesystem->is_dir($dir)) {
            return true;
        }

        if (!wp_mkdir_p($dir)) {
            return false;
        }

        $this->filesystem->put_contents($dir . '/.htaccess', "deny from all\n", FS_CHMOD_FILE);
        $this->filesystem->put_contents($dir . '/index.php', "<?php\n// Silence is golden.\n", FS_CHMOD_FILE);

        return true;
    }

    /**
     * @return bool
     */
    protected function initFilesystem(): bool
    {
        if ($this->filesystem) {
            return true;
        }

        global $wp_filesystem;

        if (!$wp_filesystem) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        $this->filesystem = $wp_filesystem ?: null;

        return (bool)$this->filesystem;
    }
}
