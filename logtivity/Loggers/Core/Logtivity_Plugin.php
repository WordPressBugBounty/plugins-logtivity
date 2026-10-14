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

class Logtivity_Plugin extends Logtivity_Abstract_Logger
{
    /**
     * Plugin data captured before a destructive file operation — a deletion, or
     * an update that overwrites the files — keyed by plugin slug. Read back once
     * the operation completes, when the on-disk copy is gone or already replaced.
     *
     * @var array
     */
    protected array $capturedPluginData = [];

    /**
     * @inheritDoc
     */
    protected function registerHooks(): void
    {
        /*
         * Track the active plugin lists directly rather than the activated_plugin/
         * deactivated_plugin actions. Those actions are skipped when a plugin is
         * (de)activated silently (the $silent argument to activate_plugin() /
         * deactivate_plugins()), whereas the underlying option is always written.
         * Diffing the option therefore catches every status change, silent or not.
         */
        add_action('update_option_active_plugins', [$this, 'activePluginsUpdated'], 10, 2);
        add_action('add_option_active_plugins', [$this, 'activePluginsAdded'], 10, 2);
        add_action('update_site_option_active_sitewide_plugins', [$this, 'networkPluginsUpdated'], 10, 3);
        add_action('add_site_option_active_sitewide_plugins', [$this, 'networkPluginsAdded'], 10, 2);

        add_action('delete_plugin', [$this, 'savePluginData'], 10, 1);
        add_action('deleted_plugin', [$this, 'pluginDeleted'], 10, 2);

        add_filter('upgrader_pre_install', [$this, 'savePreUpdateVersion'], 10, 2);
        add_action('upgrader_process_complete', [$this, 'upgradeProcessComplete'], 10, 2);

        add_filter('editable_extensions', [$this, 'pluginFileModified'], 10, 2);
    }

    /**
     * @param string|Plugin_Upgrader $plugin
     *
     * @return ?array
     */
    protected function getPluginData($plugin): ?array
    {
        if (is_string($plugin)) {
            $slug       = $plugin;
            $pluginData = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin, false, false);
        } elseif ($plugin instanceof Plugin_Upgrader) {
            $slug = $plugin->plugin_info();
            if ($slug) {
                $pluginData = get_plugin_data($plugin->result['local_destination'] . '/' . $slug, false, false);
            }
        }

        if (isset($pluginData)) {
            $pluginData = array_merge(
                [
                    'Name'    => 'Not Set',
                    'Slug'    => $slug ?? 'Not Set',
                    'Version' => 'Not Set',
                ],
                $pluginData
            );
        }

        return $pluginData ?? null;
    }

    /**
     * @param string $state
     * @param string $slug
     * @param bool   $networkWide
     *
     * @return void
     */
    public function pluginStateChanged(string $state, string $slug, bool $networkWide): void
    {
        $plugin = $this->getPluginData($slug);
        if ($plugin) {
            Logtivity::log()
                ->setAction('Plugin ' . $state)
                ->setContext($plugin['Name'])
                ->addMeta('Version', $plugin['Version'])
                ->addMeta('Slug', $plugin['Slug'])
                ->addMeta('Network Wide', $networkWide ? 'Yes' : 'No')
                ->addInventory()
                ->send();
        }
    }

    /**
     * The active_plugins option was updated (single site).
     *
     * @param mixed $oldValue
     * @param mixed $value
     *
     * @return void
     */
    public function activePluginsUpdated($oldValue, $value): void
    {
        $this->logPluginListChanges((array)$oldValue, (array)$value, false);
    }

    /**
     * The active_plugins option was created (single site, first activation).
     *
     * @param string $option
     * @param mixed  $value
     *
     * @return void
     */
    public function activePluginsAdded(string $option, $value): void
    {
        $this->logPluginListChanges([], (array)$value, false);
    }

    /**
     * The active_sitewide_plugins network option was updated (multisite).
     *
     * The value is keyed by plugin slug, so compare against the keys.
     *
     * @param string $option
     * @param mixed  $value
     * @param mixed  $oldValue
     *
     * @return void
     */
    public function networkPluginsUpdated(string $option, $value, $oldValue): void
    {
        $this->logPluginListChanges(
            array_keys((array)$oldValue),
            array_keys((array)$value),
            true
        );
    }

    /**
     * The active_sitewide_plugins network option was created (multisite).
     *
     * @param string $option
     * @param mixed  $value
     *
     * @return void
     */
    public function networkPluginsAdded(string $option, $value): void
    {
        $this->logPluginListChanges([], array_keys((array)$value), true);
    }

    /**
     * Diff a previous and current list of active plugin slugs and log each
     * activation/deactivation. A set-based diff means a pure reordering of the
     * list (e.g. Logtivity moving itself to load first) produces no events.
     *
     * @param string[] $previous
     * @param string[] $current
     * @param bool     $networkWide
     *
     * @return void
     */
    protected function logPluginListChanges(array $previous, array $current, bool $networkWide): void
    {
        foreach (array_diff($current, $previous) as $slug) {
            $this->pluginStateChanged('Activated', $slug, $networkWide);
        }

        foreach (array_diff($previous, $current) as $slug) {
            /*
             * A plugin whose files no longer exist was deleted, not merely
             * deactivated. Deleting an active plugin drops it from the active
             * list too, but that deletion is already reported via the
             * deleted_plugin hook, so skip the redundant deactivation event.
             */
            if ($this->pluginFileExists($slug) == false) {
                continue;
            }

            $this->pluginStateChanged('Deactivated', $slug, $networkWide);
        }
    }

    /**
     * Whether the plugin's main file still exists on disk.
     *
     * @param string $slug
     *
     * @return bool
     */
    protected function pluginFileExists(string $slug): bool
    {
        return is_readable(WP_PLUGIN_DIR . '/' . $slug);
    }

    /**
     * Capture a plugin's data before its files are removed or overwritten, so it
     * can still be reported once the on-disk copy is gone or replaced. Keyed by
     * slug and captured at most once per plugin per request.
     *
     * @param string $slug
     *
     * @return void
     */
    protected function capturePluginData(string $slug): void
    {
        if (
            isset($this->capturedPluginData[$slug]) == false
            && $this->pluginFileExists($slug)
        ) {
            $pluginData = $this->getPluginData($slug);
            if ($pluginData) {
                $this->capturedPluginData[$slug] = $pluginData;
            }
        }
    }

    /**
     * Snapshot the version of every installed plugin. Used when an install does
     * not name the plugin being written (an upload, including one overwriting an
     * existing plugin), so the prior version can still be resolved by slug once
     * the install completes. Runs while the old files are still on disk, and
     * never overwrites data already captured for a specific plugin.
     *
     * @return void
     */
    protected function captureAllPluginData(): void
    {
        if (function_exists('get_plugins') == false) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        foreach (get_plugins() as $slug => $pluginData) {
            if (isset($this->capturedPluginData[$slug]) == false) {
                $this->capturedPluginData[$slug] = array_merge(
                    [
                        'Name'    => 'Not Set',
                        'Slug'    => $slug,
                        'Version' => 'Not Set',
                    ],
                    $pluginData
                );
            }
        }
    }

    /**
     * @param string $plugin
     *
     * @return void
     */
    public function savePluginData(string $plugin): void
    {
        $this->capturePluginData($plugin);
    }

    /**
     * @param string $pluginFile
     * @param bool   $deleted
     *
     * @return void
     */
    public function pluginDeleted(string $pluginFile, bool $deleted): void
    {
        $plugin = $this->capturedPluginData[$pluginFile] ?? $this->getPluginData($pluginFile);

        if ($plugin) {
            Logtivity::log()
                ->setAction('Plugin Deleted')
                ->setContext($plugin['Name'])
                ->addMeta('Slug', $plugin['Slug'])
                ->addMeta('Version', $plugin['Version'])
                ->addMeta('Deletion Successful', $deleted ? 'Yes' : 'No')
                ->addInventory()
                ->send();
        }
    }

    /**
     * Capture plugin data before an install/update overwrites the files, so the
     * old version can be logged once it completes. Fires while the old files are
     * still on disk. Updates name the plugin in $hookExtra, so only that one is
     * captured; uploads (including one overwriting an existing plugin) do not,
     * so every installed plugin is snapshotted and the replaced one resolved by
     * slug at completion. Registered as a filter, so the value is returned as-is.
     *
     * @param bool|WP_Error $response
     * @param array         $hookExtra
     *
     * @return bool|WP_Error
     */
    public function savePreUpdateVersion($response, array $hookExtra)
    {
        $slug = $hookExtra['plugin'] ?? null;
        if ($slug) {
            $this->capturePluginData($slug);
        } else {
            $this->captureAllPluginData();
        }

        return $response;
    }

    /**
     * @param WP_Upgrader $upgrader
     * @param array       $options
     *
     * @return void
     */
    public function upgradeProcessComplete(WP_Upgrader $upgrader, array $options): void
    {
        $action = $options['action'] ?? null;

        if ($upgrader instanceof Plugin_Upgrader) {
            switch ($action) {
                case 'install':
                    $this->pluginInstalled('Installed', $upgrader, $options);
                    break;

                case 'update':
                    $this->pluginInstalled('Updated', $upgrader, $options);
                    break;
            }
        }
    }

    /**
     * @param Plugin_Upgrader $upgrader
     * @param bool            $bulk
     *
     * @return string[]|Plugin_Upgrader[]
     */
    protected function getPluginList(Plugin_Upgrader $upgrader, array $options): array
    {
        if ($options['bulk'] ?? false) {
            return (array)$options['plugins'] ?? [];
        }

        return [$upgrader];
    }

    /**
     * @param Plugin_Upgrader $upgrader
     * @param array           $options
     *
     * @return void
     */
    public function pluginInstalled(string $action, Plugin_Upgrader $upgrader, array $options): void
    {
        $bulk    = $options['bulk'] ?? false;
        $plugins = $this->getPluginList($upgrader, $options);

        foreach ($plugins as $plugin) {
            $pluginData = $this->getPluginData($plugin);
            if ($pluginData) {
                $log = Logtivity::log()
                    ->setAction('Plugin ' . $action)
                    ->setContext($pluginData['Name'])
                    ->addMeta('Slug', $pluginData['Slug'])
                    ->addMeta('Version', $pluginData['Version']);

                $oldVersion = $this->capturedPluginData[$pluginData['Slug']]['Version'] ?? null;
                if ($oldVersion) {
                    $log->addMeta('Old Version', $oldVersion);
                }

                $log->addMeta('Bulk', $bulk ? 'Yes' : 'No')
                    ->addInventory()
                    ->send();
            }
        }
    }

    /**
     * @param string[] $defaultTypes
     *
     * @return array
     */
    public function pluginFileModified(array $defaultTypes): array
    {
        // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Passive logger reads core plugin-editor request after WordPress validates it.
        $action = isset($_POST['action'])
            ? sanitize_text_field(wp_unslash($_POST['action']))
            : null;
        $plugin = isset($_POST['plugin'])
            ? sanitize_text_field(wp_unslash($_POST['plugin']))
            : null;
        $file   = isset($_REQUEST['file'])
            ? sanitize_text_field(wp_unslash($_REQUEST['file']))
            : null;
        // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

        if ($file && $plugin && $action == 'edit-theme-plugin-file') {
            $pluginData = $this->getPluginData($plugin);

            Logtivity::log('Plugin File Edited')
                ->setContext($pluginData['Name'])
                ->addMeta('File', $file)
                ->addMeta('Slug', $pluginData['Slug'])
                ->addMeta('Version', $pluginData['Version'])
                ->send();
        }

        return $defaultTypes;
    }
}

new Logtivity_Plugin();
