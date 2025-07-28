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

class Logtivity_Check_For_Disabled_Individual_Logs
{
    /**
     * @var Logtivity_Options
     */
    protected Logtivity_Options $options;

    public function __construct()
    {
        add_action('wp_logtivity_instance', [$this, 'handle'], 10, 2);
    }

    /**
     * @param Logtivity_Logger $logger
     *
     * @return void
     */
    public function handle(Logtivity_Logger $logger): void
    {
        // Refresh options whenever invoked
        $this->options = new Logtivity_Options();

        $exclusions = array_unique(
            array_merge(
                $this->parseExcludeEntries('logtivity_disable_individual_logs'),
                $this->parseExcludeEntries('logtivity_global_disabled_logs')
            )
        );

        foreach ($exclusions as $exclusion) {
            if ($this->isDisabled($logger, $exclusion)) {
                $logger->stop();

                return;
            }
        }
    }

    /**
     * @param string $option
     *
     * @return array
     */
    protected function parseExcludeEntries(string $option): array
    {
        $value = (string)$this->options->getOption($option);

        $entries = preg_split("/\\r\\n|\\r|\\n/", $value);

        return array_unique(array_filter($entries));
    }

    /**
     * @param Logtivity_Logger $logger
     * @param string           $exclusion
     *
     * @return bool
     */
    protected function isDisabled(Logtivity_Logger $logger, string $exclusion): bool
    {
        $array   = explode('&&', $exclusion);
        $action  = strtolower(trim((string)array_shift($array)));
        $context = strtolower(trim((string)array_shift($array)));

        if ($action == '*' && $context == '*') {
            return false;
        } elseif ($this->matches($logger->action, $action) && $this->matches($logger->context, $context)) {
            return true;
        } elseif ($action == '*') {
            return false;
        } elseif ($this->matches($logger->action, $action)) {
            return true;
        }

        return false;
    }

    /**
     * @param ?string $keywordTarget
     * @param ?string $keywordCheck
     *
     * @return bool
     */
    protected function matches(?string $keywordTarget, ?string $keywordCheck): bool
    {
        $keywordTarget = strtolower(trim((string)$keywordTarget));
        $keywordCheck  = strtolower(trim((string)$keywordCheck));

        if ($keywordTarget && $keywordCheck) {
            if ($keywordCheck == '*') {
                return true;
            }

            if (strpos($keywordCheck, '*') !== false) {
                $regex = str_replace(['*', '/'], ['.*?', '\/'], $keywordCheck);
                return preg_match('/' . $regex . '/', $keywordTarget);
            }

            return $keywordCheck == $keywordTarget;
        }

        return false;
    }
}

new Logtivity_Check_For_Disabled_Individual_Logs();
