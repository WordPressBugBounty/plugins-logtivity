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
        add_action('delete_site_transient_update_themes', [$this, 'themeMaybeDeleted'], 10, 1);
    }

    public function themeSwitched($new_name, $new_theme, $old_theme)
    {
        Logtivity::log()
            ->setAction('Theme Activated')
            ->setContext((is_object($new_theme) ? $new_theme->name : $new_theme))
            ->addMeta('Version', (is_object($new_theme) ? $new_theme->version : $new_theme))
            ->addMeta('Old Theme', (is_object($old_theme) ? $old_theme->name : $old_theme))
            ->addInventory()
            ->send();
    }

    public function themeMaybeDeleted()
    {
        $slug = $this->getDeletedThemeSlug();

        if (empty($slug)) {
            return;
        }

        $theme = wp_get_theme($slug);

        Logtivity::log()
            ->setAction('Theme Deleted')
            ->setContext($theme->get('Name'))
            ->addMeta('Version', $theme->get('Version'))
            ->addMeta('URI', $theme->get('ThemeURI'))
            ->addInventory()
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
                ->addInventory()
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
            ->addInventory()
            ->send();
    }

    public function themeFileModified($default_types, $theme)
    {
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Passive logger reads core theme-editor request after WordPress validates it.
        $action = isset($_POST['action'])
            ? sanitize_text_field(wp_unslash($_POST['action']))
            : null;
        $themeSlug = isset($_POST['theme'])
            ? sanitize_text_field(wp_unslash($_POST['theme']))
            : null;
        $file = isset($_POST['file'])
            ? sanitize_text_field(wp_unslash($_POST['file']))
            : null;
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        if ($action != 'edit-theme-plugin-file') {
            return $default_types;
        }

        if (!$themeSlug) {
            return $default_types;
        }

        $log = Logtivity::log('Theme File Edited');

        if ($file) {
            $log->addMeta('File', $file);
        }

        if ($themeSlug) {
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

    private function getDeletedThemeSlug(): ?string
    {
        foreach ((new Exception())->getTrace() as $call) {
            if (isset($call['function']) && 'delete_theme' === $call['function']) {
                return $call['args'][0] ?? null;
            }
        }

        return null;
    }
}

new Logtivity_Theme();
