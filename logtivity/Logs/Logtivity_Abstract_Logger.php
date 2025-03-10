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

abstract class Logtivity_Abstract_Logger
{
	protected $logger;

	protected $ignoredPostTypes = [
		'revision', 
		'customize_changeset', 
		'nav_menu_item', 
		'edd_log', 
		'edd_payment',
		'edd_license_log',
	];
	
	protected $ignoredPostTitles = [
		'Auto Draft',
	];
	
	protected $ignoredPostStatuses = ['trash'];

	public function __construct()
	{
		$this->registerHooks();
	}

	/**
	 * Check against certain rules on whether we should ignore the logging of a certain post
	 * 
	 * @param  WP_Post $post
	 * @return bool
	 */
	protected function shouldIgnore($post, $action = null)
	{
		if ($this->ignoringPostType($post->post_type)) {
			return true;
		}

		if ($this->ignoringPostTitle($post->post_title)) {
			return true;
		}

		if ($this->ignoringPostStatus($post->post_status)) {
			return true;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return true;
		}

		return false;
	}

	/**
	 * Check to see if the request is Gutenbergs second request
	 * 
	 * @return bool
	 */
	protected function loggedRecently($postId)
	{
		$date = get_post_meta($postId, 'logtivity_last_logged', true);

		if (!$date) {
			return false;
		}

		$now = new DateTime();
		$lastLogged = $this->sanitizeDate($date);

		$diffInSeconds = $now->getTimestamp() - $lastLogged->getTimestamp();

		return $diffInSeconds < 5;
	}

	protected function sanitizeDate( $date ) 
	{
		try {
			return new \DateTime( $date );
		} catch (\Throwable $e) {
			return new \DateTime( '1970-01-01' );
		} catch (\Exception $e) {
			return new \DateTime( '1970-01-01' );
		}
	}

	/**
	 * Ignoring certain post statuses. Example: trash. 
	 * We already have a postWasTrashed hook so 
	 * don't need to log twice.
	 * 
	 * @param  string $post_status
	 * @return bool
	 */
	protected function ignoringPostStatus($post_status)
	{
		return in_array($post_status, $this->ignoredPostStatuses);
	}

	/**	
	 * Ignoring certain post types. Particularly system generated
	 * that are not directly triggered by the user.
	 * 
	 * @param  string $post_type
	 * @return bool
	 */
	protected function ignoringPostType($post_type)
	{
		return in_array($post_type, $this->ignoredPostTypes);
	}

	/**	
	 * Ignore certain system generated post titles
	 * 
	 * @param  string $title
	 * @return bool
	 */
	protected function ignoringPostTitle($title)
	{
		return in_array($title, $this->ignoredPostTitles);
	}

	/**	
	 * Generate a label version of the given post ids post type
	 * 
	 * @param  integer $post_id
	 * @return string
	 */
	protected function getPostTypeLabel($post_id)
	{
		return $this->formatLabel(get_post_type($post_id));
	}

	protected function formatLabel($label)
	{
		return ucwords( str_replace(['_', '-'], ' ', $label) );
	}
}