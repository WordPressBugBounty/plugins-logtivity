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

// @phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
// @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

class Logtivity_Log_Index_Controller
{
    public function __construct()
    {
        add_action('wp_ajax_nopriv_logtivity_log_index_filter', [$this, 'search']);
        add_action('wp_ajax_logtivity_log_index_filter', [$this, 'search']);
    }

    /**
     * @return void
     */
    public function search(): void
    {
        if (current_user_can(Logtivity::ACCESS_LOGS)) {
            $response = (new Logtivity_Api())
                ->get(
                    '/logs',
                    [
                        'page'        => $this->getInput('page'),
                        'action'      => $this->getInput('search_action'),
                        'context'     => $this->getInput('search_context'),
                        'action_user' => $this->getInput('action_user'),
                    ]
                );

            if ($response) {
                $this->successResponse(json_decode(json_encode($response)));

            } else {
                $this->errorResponse((new Logtivity_Api())->getConnectionMessage());
            }
        } else {
            $this->errorResponse(__('You do not have sufficient permissions to access this page.'));
        }
    }

    /**
     * @param object $response
     *
     * @return void
     */
    private function successResponse(object $response): void
    {
        wp_send_json([
            'view' => logtivity_view('_logs-loop', [
                'logs'        => $response->data,
                'meta'        => $response->meta,
                'hasNextPage' => $response->links->next,
            ]),
        ]);
    }

    /**
     * @param string $message
     *
     * @return void
     */
    private function errorResponse(string $message): void
    {
        wp_send_json([
            'view' => logtivity_view('_logs-loop', [
                'message' => $message,
                'logs'    => [],
            ]),
        ]);
    }

    /**
     * @param string $field
     *
     * @return ?string
     */
    private function getInput(string $field): ?string
    {
        return (isset($_GET[$field]) && is_string($_GET[$field]) ? $_GET[$field] : null);
    }
}

new Logtivity_Log_Index_Controller();
