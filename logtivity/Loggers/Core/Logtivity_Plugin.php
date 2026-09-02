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
     * @var array
     */
    protected array $savedPluginData = [];

    /**
     * @inheritDoc
     */
    protected function registerHooks(): void
    {
        add_action('activated_plugin', [$this, 'pluginActivated'], 10, 2);
        add_action('deactivated_plugin', [$this, 'pluginDeactivated'], 10, 2);
        add_action('delete_plugin', [$this, 'savePluginData'], 10, 1);
        add_action('deleted_plugin', [$this, 'pluginDeleted'], 10, 2);

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
            $pluginData = $this->savedPluginData[$plugin]
                ?? get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin, false, false);

        } elseif ($plugin instanceof Plugin_Upgrader) {
            if ($slug = $plugin->plugin_info()) {
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
        if ($plugin = $this->getPluginData($slug)) {
            Logtivity::log()
                ->setAction('Plugin ' . $state)
                ->setContext($plugin['Name'])
                ->addMeta('Version', $plugin['Version'])
                ->addMeta('Slug', $plugin['Slug'])
                ->addMeta('Network Wide', $networkWide ? 'Yes' : 'No')
                ->send();
        }
    }

    /**
     * @param string $slug
     * @param bool   $networkWide
     *
     * @return void
     */
    public function pluginActivated(string $slug, bool $networkWide): void
    {
        $this->pluginStateChanged('Activated', $slug, $networkWide);
    }

    /**
     * @param string $slug
     * @param bool   $networkDeactivating
     *
     * @return void
     */
    public function pluginDeactivated(string $slug, bool $networkDeactivating): void
    {
        $this->pluginStateChanged('Deactivated', $slug, $networkDeactivating);
    }

    /**
     * @param string $plugin
     *
     * @return void
     */
    public function savePluginData(string $plugin): void
    {
        if (empty($this->savedPluginData)) {
            if ($pluginData = $this->getPluginData($plugin)) {
                $this->savedPluginData[$plugin] = $pluginData;
            }
        }
    }

    /**
     * @param string $pluginFile
     * @param bool   $deleted
     *
     * @return void
     */
    public function pluginDeleted(string $pluginFile, bool $deleted): void
    {
        if ($plugin = $this->getPluginData($pluginFile)) {
            Logtivity::log()
                ->setAction('Plugin Deleted')
                ->setContext($plugin['Name'])
                ->addMeta('Slug', $plugin['Slug'])
                ->addMeta('Version', $plugin['Version'])
                ->addMeta('Deletion Successful', $deleted ? 'Yes' : 'No')
                ->send();
        }
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
            if ($pluginData = $this->getPluginData($plugin)) {
                Logtivity::log()
                    ->setAction('Plugin ' . $action)
                    ->setContext($pluginData['Name'])
                    ->addMeta('Slug', $pluginData['Slug'])
                    ->addMeta('Version', $pluginData['Version'])
                    ->addMeta('Bulk', $bulk ? 'Yes' : 'No')
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
        $action = sanitize_text_field($_POST['action'] ?? null);
        $plugin = sanitize_text_field($_POST['plugin'] ?? null);
        $file   = sanitize_text_field($_REQUEST['file'] ?? null);

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
