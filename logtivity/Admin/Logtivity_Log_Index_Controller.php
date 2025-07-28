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
            $api = Logtivity::log();

            if ($api->getOption()->getApiKey()) {
                $response = $api
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
                    $response = json_decode(json_encode($response));

                    $this->renderView([
                        'message'     => $response->error,
                        'logs'        => $response->body->data ?? [],
                        'meta'        => $response->body->meta ?? null,
                        'hasNextPage' => $response->body->links->next ?? null,
                    ]);
                } else {
                    $this->renderView($api->getConnectionMessage());
                }

            } else {
                $this->welcomeResponse();
            }
        } else {
            $this->renderView(__('You do not have sufficient permissions to access this page.'));
        }
    }

    /**
     * @param string $field
     *
     * @return ?string
     */
    protected function getInput(string $field): ?string
    {
        return sanitize_text_field($_GET[$field] ?? null);
    }

    /**
     * @param string|array $response
     *
     * @return void
     */
    protected function renderView($viewData): void
    {
        if (is_string($viewData)) {
            $viewData = ['message' => $viewData];
        }

        $viewData = array_merge(
            [
                'message'     => null,
                'logs'        => [],
                'meta'        => null,
                'hasNextPage' => null,
            ],
            $viewData
        );

        wp_send_json([
            'view' => logtivity_view('_logs-loop', $viewData),
        ]);
    }

    /**
     * @return void
     */
    protected function welcomeResponse()
    {
        wp_send_json([
            'view' => logtivity_view('activation', [
                'display' => true,
                'logo'    => false,
            ]),
        ]);
    }
}

new Logtivity_Log_Index_Controller();
