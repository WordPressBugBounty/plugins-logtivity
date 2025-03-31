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

class Logtivity_Plugin extends Logtivity_Abstract_Logger
{
    /**
     * @return void
     */
    public function registerHooks(): void
    {
        add_action('activated_plugin', [$this, 'pluginActivated'], 10, 2);
        add_action('deactivated_plugin', [$this, 'pluginDeactivated'], 10, 2);
        add_action('upgrader_process_complete', [$this, 'upgradeProcessComplete'], 10, 2);
        add_action('deleted_plugin', [$this, 'pluginDeleted'], 10, 2);

        add_filter('editable_extensions', [$this, 'pluginFileModified'], 10, 2);
    }

    /**
     * @param string $slug
     * @param bool   $networkWide
     *
     * @return void
     */
    public function pluginActivated(string $slug, bool $networkWide): void
    {
        $data = get_plugin_data(WP_PLUGIN_DIR . '/' . $slug, true, false);

        Logtivity_Logger::log()
            ->setAction('Plugin Activated')
            ->setContext($slug)
            ->addMeta('Slug', $slug)
            ->addMeta('Version', $data['Version'] ?? 'Not set')
            ->addMeta('Network Wide', $networkWide ? 'Yes' : 'No')
            ->send();
    }

    /**
     * @param string $slug
     * @param bool   $networkDeactivating
     *
     * @return void
     */
    public function pluginDeactivated(string $slug, bool $networkDeactivating): void
    {
        Logtivity_Logger::log()
            ->setAction('Plugin Deactivated')
            ->setContext($slug)
            ->addMeta('Slug', $slug)
            ->addMeta('Network Deactivating', $networkDeactivating ? 'Yes' : 'No')
            ->send();
    }

    /**
     * @param string $pluginFile
     * @param bool   $deleted
     *
     * @return void
     */
    public function pluginDeleted(string $pluginFile, bool $deleted): void
    {
        Logtivity_Logger::log()
            ->setAction('Plugin Deleted')
            ->setContext($pluginFile)
            ->addMeta('Slug', $pluginFile)
            ->addMeta('Deletion Successful', $deleted ? 'Yes' : 'No')
            ->send();
    }

    /**
     * @param WP_Upgrader $upgrader
     * @param array       $options
     *
     * @return void
     */
    public function upgradeProcessComplete(WP_Upgrader $upgrader, array $options): void
    {
        $type = $options['type'] ?? null;

        if ($type == 'plugin') {
            $action = $options['action'] ?? null;

            switch ($action) {
                case 'update':
                    $this->pluginUpdated($upgrader, $options);
                    break;

                case 'install':
                    $this->pluginInstalled($upgrader);
                    break;
            }
        }
    }

    /**
     * @param WP_Upgrader $upgrader
     * @param array       $options
     *
     * @return void
     */
    public function pluginUpdated(WP_Upgrader $upgrader, array $options): void
    {
        $bulk = $options['bulk'] ?? false;
        if ($bulk) {
            $slugs = $options['plugins'] ?? [];

        } elseif ($upgrader->skin->plugin ?? false) {
            $slugs = [$upgrader->skin->plugin];

        } else {
            return;
        }

        foreach ($slugs as $slug) {
            $data = get_plugin_data(WP_PLUGIN_DIR . '/' . $slug, true, false);

            Logtivity_Logger::log()
                ->setAction('Plugin Updated')
                ->setContext($data['Name'])
                ->addMeta('Slug', $slug)
                ->addMeta('Version', $data['Version'] ?? 'Not set')
                ->addMeta('Bulk', $bulk ? 'Yes' : 'No')
                ->send();
        }
    }

    /**
     * @param WP_Upgrader $upgrader
     *
     * @return void
     */
    public function pluginInstalled(WP_Upgrader $upgrader): void
    {
        if ($path = $upgrader->plugin_info()) {
            $data = get_plugin_data($upgrader->skin->result['local_destination'] . '/' . $path, true, false);

            Logtivity_Logger::log()
                ->setAction('Plugin Installed')
                ->setContext($data['Name'])
                ->addMeta('Slug', $path)
                ->addMeta('Version', $data['Version'])
                ->send();
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
        if (
            $action == 'edit-theme-plugin-file'
            && isset($_POST['plugin'])
        ) {
            $log = Logtivity_Logger::log()->setAction('Plugin File Edited');

            $file = sanitize_text_field($_REQUEST['file'] ?? null);
            if ($file) {
                $pluginData = array_values(get_plugins('/' . ltrim(dirname($file), '/')));
                $pluginData = array_shift($pluginData);

                $log->addMeta('File', $file);

                if ($pluginName = ($pluginData['Name'] ?? null)) {
                    $log->setContext($pluginName);
                }
            }

            $log->send();
        }

        return $defaultTypes;
    }
}

new Logtivity_Plugin();
