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

class Logtivity_Memberpress extends Logtivity_Abstract_Logger
{
	public function __construct()
	{
		add_action('mepr-event-member-signup-completed', [$this, 'freeSubscriptionCreated']);
		add_action('mepr-event-subscription-created', [$this, 'subscriptionCreated']);
		add_action('mepr-event-subscription-paused', [$this, 'subscriptionPaused']);
		add_action('mepr-event-subscription-resumed', [$this, 'subscriptionResumed']);
		add_action('mepr-event-subscription-stopped', [$this, 'subscriptionStopped']);
		add_action('init', [$this, 'profileUpdated']);
		add_filter('mepr_create_transaction', [$this, 'transactionCreated'], 10, 3);
		add_filter('mepr_update_transaction', [$this, 'transactionUpdated'], 10, 3);
		add_action('mepr_email_sent', [$this, 'emailSent'], 10, 3);
		add_action('mepr-process-options', [$this, 'settingsUpdated'], 10, 1);
	}

	public function freeSubscriptionCreated($event)
	{
		$user = $event->get_data();
		$subscription = $this->getSubscription($user);

		if (!$subscription) {
			return;
		}

		if ($subscription->gateway != 'free') {
			return;			
		}
		
		$product = $subscription->product();

		return (new Logtivity_Logger($user->ID))
			->setAction('Free Subscription Created')
			->setContext($product->post_title)
			->addMeta('Subscription ID', $subscription->id)
			->send();
	}

	protected function getSubscription($user)
	{
		foreach ($user->subscriptions() as $subscription) {
			return $subscription;
		}

		return null;
	}	

	public function subscriptionCreated($event) 
	{
		$subscription = $event->get_data();
		$user = $subscription->user();
		$product = $subscription->product();
		$paymentMethod = $subscription->payment_method();

		return (new Logtivity_Logger($user->ID))
			->setAction('Subscription Created')
			->setContext($product->post_title)
			->addMeta('Transaction Total', $subscription->total)
			->addMeta('Payment Method', $paymentMethod->name)
			->addMeta('Subscription ID', $subscription->id)
			->send();
	}

	public function subscriptionPaused($event) 
	{
		$subscription = $event->get_data();
		$product = $subscription->product();

		return Logtivity_Logger::log()
			->setAction('Subscription Paused')
			->setContext($product->post_title)
			->addMeta('Subscription ID', $subscription->id)
			->send();
	}

	public function subscriptionResumed($event) 
	{
		$subscription = $event->get_data();
		$product = $subscription->product();
	
		return Logtivity_Logger::log()
			->setAction('Subscription Resumed')
			->setContext($product->post_title)
			->addMeta('Subscription ID', $subscription->id)
			->send();
	}

	public function subscriptionStopped($event) 
	{
		$subscription = $event->get_data();
		$product = $subscription->product();

		return Logtivity_Logger::log()
			->setAction('Subscription Stopped')
			->setContext($product->post_title)
			->addMeta('Subscription ID', $subscription->id)
			->send();
	}

	public function profileUpdated()
	{
		if( MeprUtils::is_post_request() &&
        isset($_POST['mepr-process-account']) && $_POST['mepr-process-account'] == 'Y' &&
        isset($_POST['mepr_account_nonce']) && wp_verify_nonce($_POST['mepr_account_nonce'], 'update_account') ) {
        	$user = new Logtivity_Wp_User;

			return Logtivity_Logger::log()
				->setAction('Profile Updated')
				->setContext($user->getRole())
				->send();
		}
	}

	public function transactionCreated($rowId, $args, $user_id)
	{
		(new Logtivity_Logger($user_id))
			->setAction('Transaction Created')
			->setContext($args['status'])
			->addMeta('Total', $args['total'])
			->addMeta('Transaction ID', $rowId)
			->addMeta('Transaction Type', $args['txn_type'])
			->addMeta('Subscription ID', $args['subscription_id'])
			->addMeta('Product ID', $args['product_id'])
			->send();

		return $rowId;
	}

	public function transactionUpdated($rowId, $args, $user_id)
	{
		$log = (new Logtivity_Logger($user_id))
			->setAction('Transaction Updated')
			->setContext($args['status']);

		if ($args['total'] != '0') {
			$log->addMeta('Total', $args['total']);
		}

		$log->addMeta('Transaction ID', $args['id'])
			->addMeta('Transaction Type', $args['txn_type'])
			->addMeta('Subscription ID', $args['subscription_id'])
			->addMeta('Product ID', $args['product_id'])
			->send();

		return $rowId;
	}

	public function emailSent($MeprBaseEmail, $values, $attachments)
	{
		(new Logtivity_Logger())
			->setAction(strip_tags($MeprBaseEmail->title) . ' Sent')
			->setContext($MeprBaseEmail->to)
			->send();
	}

	public function settingsUpdated($postData)
	{
		Logtivity::log()
			->setAction('Settings Updated')
			->setContext('Memberpress')
			->send();
	}
}

$Logtivity_Memberpress = new Logtivity_Memberpress;