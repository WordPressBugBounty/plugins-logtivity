<?php

/**
 * @package   Logtivity
 * @contact   logtivity.io, hello@logtivity.io
 * @copyright 2024 Logtivity. All rights reserved
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

class Logtivity_Dismiss_Notice_Controller
{
    /**
     * @var string[]
     */
    protected array $notices = [
        'logtivity-site-url-has-changed-notice',
    ];

    public function __construct()
    {
        add_action('wp_ajax_nopriv_logtivity_dismiss_notice', [$this, 'dismiss']);
        add_action('wp_ajax_logtivity_dismiss_notice', [$this, 'dismiss']);
    }

    /**
     * @return void
     */
    public function dismiss(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $postType = sanitize_text_field($_POST['postType'] ?? null);
        if (in_array($postType, $this->notices)) {
            $dismissUntil = sanitize_text_field($_POST['dismissUntil'] ?? null);

            if ($dismissUntil) {
                set_transient(
                    'dismissed-' . $postType,
                    true,
                    (int)$dismissUntil
                );
            } else {
                update_option('dismissed-' . $postType, true);
            }

            wp_send_json(['message' => 'success']);
        }
    }
}

new Logtivity_Dismiss_Notice_Controller();
