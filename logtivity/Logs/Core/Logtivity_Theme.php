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

class Logtivity_Theme extends Logtivity_Abstract_Logger
{
    /**
     * @inheritDoc
     */
    protected function registerHooks(): void
    {
        add_action('switch_theme', [$this, 'themeSwitched'], 10, 3);
        add_action('upgrader_process_complete', [$this, 'upgradeProcessComplete'], 10, 2);
        add_filter('wp_theme_editor_filetypes', [$this, 'themeFileModified'], 10, 2);
        add_action('customize_save', [$this, 'themeCustomizerModified'], 10, 2);
        add_action('delete_site_transient_update_themes', [$this, 'themeMaybeDeleted']);
    }

    public function themeSwitched($new_name, $new_theme, $old_theme)
    {
        Logtivity::log()
            ->setAction('Theme Activated')
            ->setContext((is_object($new_theme) ? $new_theme->name : $new_theme))
            ->addMeta('Version', (is_object($new_theme) ? $new_theme->version : $new_theme))
            ->addMeta('Old Theme', (is_object($old_theme) ? $old_theme->name : $old_theme))
            ->send();
    }

    public function themeMaybeDeleted()
    {
        $delete_theme_call = $this->getDeleteThemeCall();

        if (empty($delete_theme_call)) {
            return;
        }

        $slug  = $delete_theme_call['args'][0];
        $theme = wp_get_theme($slug);

        Logtivity::log()
            ->setAction('Theme Deleted')
            ->setContext($theme->get('Name'))
            ->addMeta('Version', $theme->get('Version'))
            ->addMeta('URI', $theme->get('ThemeURI'))
            ->send();
    }

    public function upgradeProcessComplete($upgrader_object, $options)
    {
        if ($options['type'] != 'theme') {
            return;
        }

        if ($options['action'] == 'update') {
            $this->themeUpdated($upgrader_object, $options);
        }

        if ($options['action'] == 'install') {
            $this->themeInstalled($upgrader_object, $options);
        }
    }

    public function themeUpdated($upgrader, $options)
    {
        $bulk = $options['bulk'] ?? false;
        if ($bulk) {
            $slugs = $options['themes'];
        } else {
            $slugs = [$upgrader->skin->theme];
        }

        foreach ($slugs as $slug) {
            $theme = wp_get_theme($slug);

            Logtivity::log()
                ->setAction('Theme Updated')
                ->setContext($theme->name)
                ->addMeta('Version', $theme->version)
                ->addMeta('Bulk Update', $bulk ? 'Yes' : 'No')
                ->send();
        }
    }

    public function themeInstalled($upgrader_object, $options)
    {
        $slug = $upgrader_object->theme_info();

        if (!$slug) {
            return;
        }

        wp_clean_themes_cache();

        $theme = wp_get_theme($slug);

        Logtivity::log()
            ->setAction('Theme Installed')
            ->setContext($theme->name)
            ->addMeta('Version', $theme->version)
            ->send();
    }

    public function themeFileModified($default_types, $theme)
    {
        if (!isset($_POST['action']) || $_POST['action'] != 'edit-theme-plugin-file') {
            return $default_types;
        }

        if (!isset($_POST['theme'])) {
            return $default_types;
        }

        $log = Logtivity::log('Theme File Edited');

        if (!empty($_POST['file']) && is_string($_POST['file'])) {
            $log->addMeta('File', sanitize_text_field($_POST['file']));
        }

        if (!empty($_POST['theme'])) {
            $log->setContext($theme->display('Name'));
        }

        $log->send();

        return $default_types;
    }

    public function themeCustomizerModified(WP_Customize_Manager $obj)
    {
        Logtivity::log()
            ->setAction('Theme Customizer Updated')
            ->setContext($obj->theme()->display('Name'))
            ->send();
    }

    private function getDeleteThemeCall()
    {
        $backtrace = debug_backtrace();

        $delete_theme_call = null;

        foreach ($backtrace as $call) {
            if (isset($call['function']) && 'delete_theme' === $call['function']) {
                $delete_theme_call = $call;
                break;
            }
        }

        return $delete_theme_call;
    }
}

new Logtivity_Theme();
