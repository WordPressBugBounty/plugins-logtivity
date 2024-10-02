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

class Logtivity_Comment
{
	public function __construct()
	{
		add_action('comment_post', [$this, 'commentCreated'], 10, 3);
		add_action( 'wp_set_comment_status', [$this, 'statusChanged'], 10, 2);
		add_action( 'unspam_comment', [$this, 'commentMarkedAsNotspam'], 10, 2);
	}

	public function commentCreated($comment_ID, $comment_approved, $commentdata)
	{
		if ($comment_approved === 'spam') {
			return;
		}
		
		return Logtivity_Logger::log()
			->setAction('Comment Created')
			->setContext(substr(strip_tags($commentdata['comment_content']), 0, 30).'...') 
			->addMeta('Author', $commentdata['comment_author'] ?? null)
			->addMeta('Approved', $comment_approved)
			->addMeta('Post URL', get_permalink($commentdata['comment_post_ID']))
			->send();
	}

	public function statusChanged($comment_id, $comment_status)
	{
		if (in_array($comment_status, ['0', '1'])) {
			return;
		}

		return Logtivity_Logger::log()
			->setAction('Comment Status Changed')
			->setContext($comment_status) 
			->addMeta('Comment ID', $comment_id)
			->send();
	}

	public function commentMarkedAsNotspam($comment_ID, $comment)
	{
		return $this->statusChanged($comment->comment_ID, 'Not Spam');
	}
}

$Logtivity_Comment = new Logtivity_Comment;