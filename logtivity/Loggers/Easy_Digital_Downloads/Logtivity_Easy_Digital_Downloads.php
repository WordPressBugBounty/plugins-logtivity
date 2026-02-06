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

class Logtivity_Easy_Digital_Downloads extends Logtivity_Abstract_Easy_Digital_Downloads
{
    public function __construct()
    {
        add_action('edd_post_add_to_cart', [$this, 'itemAddedToCart'], 10, 3);
        add_action('edd_post_remove_from_cart', [$this, 'itemRemovedFromCart'], 10, 2);
        add_action('edd_customer_post_create', [$this, 'customerCreated'], 10, 2);
        add_action('edd_update_payment_status', [$this, 'paymentStatusUpdated'], 10, 3);
        add_action('edd_process_verified_download', [$this, 'fileDownloaded'], 10, 4);
        add_action('edd_cart_discount_set', [$this, 'discountApplied'], 10, 2);
        add_action('edd_cart_discount_removed', [$this, 'discountRemoved'], 10, 2);
    }

    /**
     * @param int   $downloadId
     * @param array $options
     * @param array $items
     *
     * @return void
     */
    public function itemAddedToCart($downloadId, $options, $items): void
    {
        $log = Logtivity::log()
            ->setAction('Download Added to Cart')
            ->setContext(logtivity_get_the_title($downloadId))
            ->setPostId($downloadId);

        $prices = edd_get_variable_prices($downloadId);

        foreach ($items as $item) {
            if (isset($item['options']['price_id']) && isset($prices[$item['options']['price_id']])) {
                $log->addMeta('Variable Item', $prices[$item['options']['price_id']]['name']);
            }

            if ($item['quantity']) {
                $log->addMeta('Quantity', $item['quantity']);
            }
        }

        $log->send();
    }

    /**
     * @param int $key
     * @param int $itemId
     *
     * @return void
     */
    public function itemRemovedFromCart($key, $itemId): void
    {
        Logtivity::log()
            ->setAction('Download Removed from Cart')
            ->setContext(logtivity_get_the_title($itemId))
            ->send();
    }

    /**
     * @param int    $paymentId
     * @param string $status
     * @param string $oldStatus
     *
     * @return void
     */
    public function paymentStatusUpdated($paymentId, $status, $oldStatus): void
    {
        $payment = new EDD_Payment($paymentId);
        switch ($status) {
            case 'refunded':
                $this->paymentRefunded($payment);
                break;

            case 'publish':
                $this->paymentCompleted($payment);
                break;
            case 'edd_subscription':
                // Don't log these
                break;

            default:
                Logtivity::log()
                    ->setAction('Payment Status Changed to ' . ucfirst($status))
                    ->setContext($this->getPaymentKey($payment))
                    ->addMeta('Payment Key', $this->getPaymentKey($payment))
                    ->addMeta('Total', $payment->total)
                    ->addMeta('Currency', $payment->currency)
                    ->addMeta('Gateway', $payment->gateway)
                    ->addMeta('Customer ID', $payment->customer_id)
                    ->addMeta('Prevous Status', ucfirst($oldStatus))
                    ->send();
                break;
        }
    }

    /**
     * @param EDD_Payment $payment
     *
     * @return void
     */
    protected function paymentCompleted(EDD_Payment $payment): void
    {
        $key = $this->getPaymentKey($payment);

        $log = Logtivity::log()
            ->setAction('Payment Completed')
            ->setContext($key)
            ->addMeta('Payment Key', $key);

        foreach ($payment->cart_details as $item) {
            $log->addMeta('Cart Item', $item['name']);
        }

        if ($payment->discounts != 'none') {
            $log->addMeta('Discount Code', $payment->discounts);
        }

        $log->addMeta('Total', $payment->total)
            ->addMeta('Currency', $payment->currency)
            ->addMeta('Gateway', $payment->gateway)
            ->addMeta('Customer ID', $payment->customer_id)
            ->send();
    }

    /**
     * @param EDD_Payment $payment
     *
     * @return void
     */
    protected function paymentRefunded(EDD_Payment $payment): void
    {
        Logtivity::log()
            ->setAction('Payment Refunded')
            ->setContext($this->getPaymentKey($payment))
            ->addMeta('Amount', $payment->get_meta('_edd_payment_total'))
            ->addMeta('Payment Key', $this->getPaymentKey($payment))
            ->addMeta('Customer ID', $payment->customer_id)
            ->send();
    }

    /**
     * @param EDD_Payment $payment
     *
     * @return ?string
     */
    protected function getPaymentKey(EDD_Payment $payment): ?string
    {
        $meta = $payment->get_meta();

        return $meta['key'] ?? null;
    }

    /**
     * @param int   $created
     * @param array $args
     *
     * @return void
     */
    public function customerCreated($created, $args): void
    {
        if ($created) {
            Logtivity::log()
                ->setAction('Customer Created')
                ->setContext($args['name'] ?? null)
                ->addMeta('Customer ID', $created)
                ->send();
        }
    }

    /**
     * @param int       $downloadId
     * @param string    $email
     * @param int|false $paymentId
     * @param array     $args
     *
     * @return void
     */
    public function fileDownloaded($downloadId, $email, $paymentId, $args): void
    {
        $download = new EDD_Download($downloadId);

        $payment = new EDD_Payment($paymentId);

        $fileKey = $args['file_key'] ?? null;

        Logtivity::log()
            ->setAction('File Downloaded')
            ->setContext($this->getDownloadTitle($download->get_ID(), $args['price_id'] ?? null))
            ->setPostId($download->get_ID())
            ->addMeta('Payment Key', $this->getPaymentKey($payment))
            ->addMetaIf($fileKey, 'File ID', $fileKey)
            ->addMeta('Customer ID', $payment->customer_id)
            ->send();
    }

    /**
     * @param string $code
     * @param array  $discounts
     *
     * @return void
     */
    public function discountApplied($code, $discounts): void
    {
        Logtivity::log()
            ->setAction('Discount Applied')
            ->setContext($code)
            ->send();
    }

    /**
     * @param string $code
     * @param array $discounts
     *
     * @return void
     */
    public function discountRemoved($code, $discounts): void
    {
        Logtivity::log()
            ->setAction('Discount Removed')
            ->setContext($code)
            ->send();
    }
}

new Logtivity_Easy_Digital_Downloads();
