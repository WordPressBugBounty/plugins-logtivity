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

class Logtivity_Formidable
{
	protected $hashFieldTypes = [
		'password',
		'credit_card',
	];

	protected $ignoreFieldTypes = [
		'divider',
		'end_divider',
	];

	public function __construct()
	{
		add_action('frm_after_create_entry', [$this, 'entryCreated'], 10, 3);
	}

	public function entryCreated($entry_id, $form_id, $is_child)
	{
		if (apply_filters('logtivity_disable_formidable_logging', false)) {
			return;
		}

		try {
			$entry = FrmEntry::getOne( $entry_id, true );

			$FrmEntryFormatter = new Logtivity_FrmEntryFormatter([
				'entry' => $entry,
				'format' => 'array',
			]);

			$log = Logtivity_Logger::log()
				->setAction('Form Submitted')
				->setContext($entry->form_name)
				->addMeta('Form ID', $form_id)
				->addMeta('Entry ID', $entry_id);

				try {

					if (class_exists('FrmProEntriesController')) {
						if (isset($is_child['is_child']) && $is_child['is_child'] === true) {
							return;
						}
						$entry = FrmProEntriesController::show_entry_shortcode( array( 'id' => $entry_id, 'format' => 'array' ) );
						
						$seenSubFields = [];

						foreach ($entry as $key => $value) {
							if (isset($value['form'])) {
								foreach ($value as $key => $sub_fields) {
									if ($key != 'form') {
										foreach ($sub_fields as $key => $value) {
											$seenSubFields[$key] = $key;
											$this->maybeStoreField($log, $key, $value);
										}
									}
								}
							} else {
								if (isset($seenSubFields[$key])) {
									unset($seenSubFields[$key]);
									continue;
								}
								$this->maybeStoreField($log, $key, $value);
							}
						}
					} else {
						foreach ($FrmEntryFormatter->logtivityGetEntryValues() as $key => $field_value) {
							$field_value->prepare_displayed_value();

							if (
								$this->shouldStoreField(
									$field_value->get_field_type(), 
									$field_value->get_field_key(),
									$field_value->get_displayed_value()
								)
							) {
								$log->addMeta(
									$field_value->get_field_label(), 
									$this->maybeLogFormValue(
										$field_value->get_field_type(), 
										$field_value->get_displayed_value()
									)
								);
							}
						}
					}

				} catch (\Exception $e) {
				
			}

			$log->send();
		} catch (\Exception $e) {
			error_log($e);
		}
	}

	private function maybeStoreField($log, $key, $value)
	{
		$field = FrmField::getOne( str_replace('-value', '', $key) );

		if (strpos($key, '-value') !== false) {
			if ($field->type != 'address') {
				return;
			}
		}

		if ($this->shouldStoreField($field->type, $field->field_key, $value)) {
			if ($value = $this->maybeLogFormValue($field->type, $value)) {
				$log->addMeta($field->name, $value);
			}
		}
	}

	private function shouldStoreField($field_type, $field_key, $value)
	{
		if (in_array($field_type, $this->ignoreFieldTypes)) {
			return false;
		}

		if (!$value) {
			return false;
		}

		return apply_filters('logtivity_should_store_formidable_field_value', true, $field_key, $field_type);
	}

	private function maybeLogFormValue($field_type, $value)
	{
		if (in_array($field_type, $this->hashFieldTypes)) {
			return '********';
		}

		if ($field_type == 'address') {
			if (is_array($value)) {
				return implode(', ', $value);
			}
			return;
		}

		return $value;
	}
}

$Logtivity_Formidable = new Logtivity_Formidable;
