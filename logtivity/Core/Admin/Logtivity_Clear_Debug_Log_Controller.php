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

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

class Logtivity_Clear_Debug_Log_Controller
{
    public function __construct()
    {
        add_action('admin_post_logtivity_clear_debug_log', [$this, 'clear']);
        add_action('admin_post_logtivity_download_debug_log', [$this, 'download']);
        add_action('update_option_logtivity_enable_debug_mode', [$this, 'maybeClearOnDisable'], 10, 2);
    }

    /**
     * @return self
     */
    public static function init(): self
    {
        return new static();
    }

    /**
     * @return void
     */
    public function clear(): void
    {
        $nonce = isset($_POST['logtivity_clear_debug_log'])
            ? sanitize_text_field(wp_unslash($_POST['logtivity_clear_debug_log']))
            : '';

        if (
            !wp_verify_nonce(
                $nonce,
                'logtivity_clear_debug_log'
            )
        ) {
            wp_safe_redirect($this->settingsPageUrl());
            exit();
        }

        if (!current_user_can(Logtivity::ACCESS_SETTINGS)) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'logtivity'));
        }

        (new Logtivity_Debug_Log())->clear();

        wp_safe_redirect($this->settingsPageUrl());
        exit();
    }

    /**
     * Download the raw debug log file.
     *
     * @return void
     */
    public function download(): void
    {
        $nonce = isset($_GET['logtivity_download_debug_log'])
            ? sanitize_text_field(wp_unslash($_GET['logtivity_download_debug_log']))
            : '';

        if (
            !wp_verify_nonce(
                $nonce,
                'logtivity_download_debug_log'
            )
        ) {
            wp_safe_redirect($this->settingsPageUrl());
            exit();
        }

        if (!current_user_can(Logtivity::ACCESS_SETTINGS)) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'logtivity'));
        }

        $contents = (new Logtivity_Debug_Log())->contents();

        nocache_headers();
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . Logtivity_Debug_Log::FILE_NAME . '"');
        header('Content-Length: ' . strlen($contents));

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- raw file download, not HTML output.
        echo $contents;
        exit();
    }

    /**
     * Remove the debug log file and its directory entirely whenever debug mode
     * is turned off, so nothing sits around with old request/response data once
     * the site owner has stopped needing it.
     *
     * @param mixed $oldValue
     * @param mixed $newValue
     *
     * @return void
     */
    public function maybeClearOnDisable($oldValue, $newValue): void
    {
        if ($newValue) {
            return;
        }

        (new Logtivity_Debug_Log())->delete();
    }

    /**
     * @return string
     */
    protected function settingsPageUrl(): string
    {
        return admin_url('admin.php?page=logtivity-settings');
    }
}
