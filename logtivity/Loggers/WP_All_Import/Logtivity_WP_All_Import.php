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

class Logtivity_WP_All_Import
{
	protected $allowedAllImportLogs = [
		'All Import Running',
		'All Import Completed',
	];

	public function __construct()
	{
		if ($this->isImporting()) {
			$this->logImportButDisableIndividualChanges();
		}

		add_action('pmxi_before_xml_import', [$this, 'importRunning'], 10, 1);
		add_action('pmxi_after_xml_import', [$this, 'importCompleted'], 10, 2);
	}

	public function importRunning($import_id)
	{
		Logtivity::log()
			->setAction('All Import Running')
			->setContext($import_id)
			->addMeta('Import ID', $import_id)
			->send();
	}

	public function importCompleted($import_id, $import)
	{
		Logtivity::log()
			->setAction('All Import Completed')
			->setContext($import_id)
			->addMeta('Import ID', $import_id)
			->send();
	}

	public function isImporting()
	{
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Passive import-state detection for WP All Import requests.
		$page      = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$action    = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
		$q         = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		$import_id = isset( $_GET['import_id'] ) ? absint( wp_unslash( $_GET['import_id'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// Manually running an import
		$manual_run = $page == 'pmxi-admin-import' || $page == 'pmxi-admin-manage';

		// Cron jobs running an import
		$cron_run = $import_id && $action == 'processing';

		// Automatic Scheduling service running an import
		$automatic_scheduling_run = $action == 'wpai_public_api' && $q == 'scheduling/process';

		// Manually uploading an import file
		$manual_upload = $page == 'pmxi-admin-settings' && $action == 'upload';

		// Extra check: see if import ID is numeric
		$import_id = is_numeric( $this->wp_all_import_get_import_id() );

		// Check if they're importing
		if ( $manual_run || $cron_run || $automatic_scheduling_run || $manual_upload || $import_id ) {
			return true;
		}
	}

	public function logImportButDisableIndividualChanges()
	{
		add_action('wp_logtivity_instance', function($Logtivity_Logger) {
			if (!in_array($Logtivity_Logger->action, $this->allowedAllImportLogs)) {
				$Logtivity_Logger->stop();
			}
		});
	}

    public function wp_all_import_get_import_id() {
        global $argv;
        $import_id = 'new';

        if ( ! empty( $argv ) ) {
            $import_id_arr = array_filter( $argv, function( $a ) {
                return ( is_numeric( $a ) ) ? true : false;
            });

            if ( ! empty( $import_id_arr ) ) {
                $import_id = reset( $import_id_arr );
            }
        }

	        if ( $import_id == 'new' ) {
	            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Passive import-state detection for WP All Import requests.
	            if ( isset( $_GET['import_id'] ) ) {
	                $import_id = absint( wp_unslash( $_GET['import_id'] ) );
            // } elseif ( isset( $_GET['id'] ) ) {
            //     $import_id = intval($_GET['id']);
            }
        }

        return $import_id;
    }
}

$Logtivity_WP_All_Import = new Logtivity_WP_All_Import;
