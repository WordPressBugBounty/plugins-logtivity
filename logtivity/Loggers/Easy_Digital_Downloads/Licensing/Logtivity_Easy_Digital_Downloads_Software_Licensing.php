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
class Logtivity_Easy_Digital_Downloads_Software_Licensing extends Logtivity_Abstract_Easy_Digital_Downloads
{
    protected $deactivatingLicenseArgs;

    public function __construct()
    {
        add_action('edd_sl_store_license', [$this, 'licenseCreated'], 10, 4);
        add_filter('edd_sl_post_activate_license_result', [$this, 'licenseActivated'], 10, 2);
        add_filter('edd_sl_pre_deactivate_license_args', [$this, 'deactivatingLicense']);
        add_action('edd_sl_deactivate_license', [$this, 'licenseDeactivated'], 10, 2);
        add_action('edd_sl_license_upgraded', [$this, 'licenseUpgraded'], 10, 2);
        add_action('edd_sl_post_set_status', [$this, 'licenseStatusUpdated'], 10, 2);
        add_action('edd_sl_post_license_renewal', [$this, 'licenseRenewed'], 10, 2);
        add_action('edd_deactivate_site', [$this, 'siteDeactivated'], 9);
        add_action('edd_insert_site', [$this, 'siteAdded'], 9);
    }

    public function licenseCreated($license_id, $purchased_download_id, $payment_id, $type)
    {
        $license = edd_software_licensing()->get_license($license_id);

        Logtivity::log()
            ->setAction('License Created')
            ->setContext($this->getDownloadTitle($license))
            ->setUser($license->user_id ?? null)
            ->addMeta('License Key', $license->key)
            ->addMeta('Customer ID', $license->customer_id)
            ->addMeta('Payment ID', $payment_id)
            ->addMeta('Type', $type)
            ->addMeta('Download ID', $purchased_download_id)
            ->send();
    }

    public function licenseActivated($result, $args)
    {
        $license = edd_software_licensing()->get_license($args['key'], true);

        $log = Logtivity::log()
            ->addMeta('License Key', $args['key']);

        if (false !== $license) {
            $log->setContext($this->getDownloadTitle($license));

            if (!is_user_logged_in()) {
                $log->setUser($license->user_id ?? null);
            }
            try {
                if ($license->customer_id) {
                    $log->addMeta('Customer ID', $license->customer_id);
                }
            } catch (Throwable $e) {
                // Ignore
            }
        } else {
            $log->setContext($args['key']);
        }

        if (isset($args['url'])) {
            $log->addMeta('Site', $args['url']);
        }

        if ($result['success']) {
            $log->setAction('License Activated');

            if (isset($result['site_count'])) {
                $log->addMeta('Activation Count', $result['site_count']);
            }

            if (isset($result['license_limit'])) {
                $log->addMeta('Activation Limit', $result['license_limit']);
            }

            if (isset($result['expires']) && $result['expires']) {
                try {
                    $log->addMeta('Expiration Date', date('M d Y', $result['expires']));
                } catch (Throwable $e) {
                    // Ignore
                }
            }

            $log->send();

            return $result;
        }

        $log->setAction('License Activation Failed')
            ->addMeta('Reason', $result['error'] ?? '');

        $log->send();

        return $result;
    }

    public function deactivatingLicense($args)
    {
        $this->deactivatingLicenseArgs = $args;

        return $args;
    }

    public function licenseDeactivated($license_id, $download_id)
    {
        $license = edd_software_licensing()->get_license($this->deactivatingLicenseArgs['key'], true);

        if (empty($this->deactivatingLicenseArgs['url']) && !edd_software_licensing()->force_increase()) {

            // Attempt to grab the URL from the user agent if no URL is specified
            $domain = array_map('trim', explode(';', $_SERVER['HTTP_USER_AGENT']));

            if (!empty($domain[1])) {
                $this->deactivatingLicenseArgs['url'] = trim($domain[1]);
            }
        }

        $log = Logtivity::log()
            ->setAction('License Deactivated')
            ->setContext($this->getDownloadTitle($license))
            ->addMeta('License Key', $this->deactivatingLicenseArgs['key']);

        if (!is_user_logged_in()) {
            $log->setUser($license->user_id ?? null);
        }

        try {
            if ($license->customer_id) {
                $log->addMeta('Customer ID', $license->customer_id);
            }
        } catch (Throwable $e) {
            // Ignore
        }

        $log->addMeta('Site', $this->deactivatingLicenseArgs['url'] ?? $domain ?? null)
            ->send();
    }

    public function licenseUpgraded($license_id, $args)
    {
        $license = edd_software_licensing()->get_license($license_id);

        $log = Logtivity::log()
            ->setAction('License Upgraded')
            ->setContext($this->getDownloadTitle($license))
            ->addMeta('License Key', $license->key);

        if (!is_user_logged_in()) {
            $log->setUser($license->user_id ?? null);
        }

        if (isset($args['payment_id'])) {
            $log->addMeta('Payment ID', $args['payment_id']);
        }

        if (isset($args['old_payment_id'])) {
            $log->addMeta('Old Payment ID', $args['old_payment_id']);
        }

        if (isset($args['download_id'])) {
            $log->addMeta('Download ID', $args['download_id']);
        }

        if (isset($args['old_download_id'])) {
            $log->addMeta('Old Download ID', $args['old_download_id']);
        }

        if (isset($args['old_price_id']) && $args['old_price_id']) {
            $log->addMeta('Old Price ID', $args['old_price_id']);
        }

        if (isset($args['upgrade_id']) && $args['upgrade_id']) {
            $log->addMeta('Upgrade ID', $args['upgrade_id']);
        }

        if (isset($args['upgrade_price_id']) && $args['upgrade_price_id']) {
            $log->addMeta('Upgrade Price ID', $args['upgrade_price_id']);
        }

        $log->addMeta('Customer ID', $license->customer_id)
            ->send();
    }

    public function licenseStatusUpdated($license_id, $status)
    {
        $license = edd_software_licensing()->get_license($license_id);

        $log = Logtivity::log()
            ->setAction('License Status Changed to ' . ucfirst($status))
            ->setContext($this->getDownloadTitle($license))
            ->addMeta('License Key', $license->key);

        if (!is_user_logged_in()) {
            $log->setUser($license->user_id ?? null);
        }

        $log->addMeta('Customer ID', $license->customer_id)
            ->send();
    }

    public function licenseRenewed($license_id, $new_expiration)
    {
        $license = edd_software_licensing()->get_license($license_id);

        $log = Logtivity::log()
            ->setAction('License Renewed')
            ->setContext($this->getDownloadTitle($license))
            ->addMeta('License Key', $license->key)
            ->addMeta('Customer ID', $license->customer_id);

        if (!is_user_logged_in()) {
            $log->setUser($license->user_id ?? null);
        }

        try {
            $log->addMeta('New Expiration Date', date('M d Y', $new_expiration));
        } catch (Throwable $e) {
            // Ignore
        }

        $log->send();
    }

    /**
     *
     * Taken from edd_sl_process_deactivate_site as there are no hooks for when a
     * user deactivates a site from within their dashboard.
     *
     * Use same validation code as in the original method, then log it.
     *
     */
    public function siteDeactivated()
    {
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? null, 'edd_deactivate_site_nonce')) {
            return;
        }

        $license_id = absint($_GET['license']);
        $license    = edd_software_licensing()->get_license($license_id);

        if ($license_id !== $license->ID) {
            return;
        }

        if ((is_admin() && !current_user_can('manage_licenses')) || (!is_admin() && $license->user_id != get_current_user_id())) {
            return;
        }

        $site_url = !empty($_GET['site_url']) ? urldecode($_GET['site_url']) : false;
        $site_id  = !empty($_GET['site_id']) ? absint($_GET['site_id']) : false;

        if (empty($site_url) && empty($site_id)) {
            return;
        }

        $site = !empty($site_id) ? $site_id : $site_url;

        /**
         * If we've made it this far let's log it.
         */

        $log = Logtivity::log()
            ->setAction('Site Deactivated')
            ->setContext($this->getDownloadTitle($license))
            ->addMeta('License Key', $license->key)
            ->addMeta('Site ID', $site);

        if (!is_user_logged_in()) {
            $log->setUser($license->user_id ?? null);
        }

        try {
            if ($license->customer_id) {
                $log->addMeta('Customer ID', $license->customer_id);
            }
        } catch (Throwable $e) {
            // Ignore
        }

        $log->send();
    }

    /**
     *
     * Taken from edd_sl_process_add_site as there are no hooks for when a
     * user adds a site from within their dashboard.
     *
     * Use same validation code as in the original method, then log it.
     *
     */
    public function siteAdded()
    {
        if (!wp_verify_nonce($_POST['edd_add_site_nonce'], 'edd_add_site_nonce')) {
            return;
        }

        if (!empty($_POST['license_id']) && empty($_POST['license'])) {
            // In 3.5, we switched from checking for license_id to just license. Fallback check for backwards compatibility
            $_POST['license'] = $_POST['license_id'];
        }

        $license_id = absint($_POST['license']);
        $license    = edd_software_licensing()->get_license($license_id);
        if ($license_id !== $license->ID) {
            return;
        }

        if ((is_admin() && !current_user_can('manage_licenses')) || (!is_admin() && $license->user_id != get_current_user_id())) {
            return;
        }

        $site_url = sanitize_text_field($_POST['site_url']);

        if ($license->is_at_limit() && !current_user_can('manage_licenses')) {
            // The license is at its activation limit so stop and show an error
            return;
        }

        /**
         * If we've made it this far let's log it.
         */

        $log = Logtivity::log()
            ->setAction('Site Added')
            ->setContext($this->getDownloadTitle($license))
            ->addMeta('License Key', $license->key)
            ->addMeta('Site', $site_url);

        if (!is_user_logged_in()) {
            $log->setUser($license->user_id ?? null);
        }

        try {
            if ($license->customer_id) {
                $log->addMeta('Customer ID', $license->customer_id);
            }
        } catch (Throwable $e) {
            // Ignore
        }

        $log->send();
    }
}

new Logtivity_Easy_Digital_Downloads_Software_Licensing();
