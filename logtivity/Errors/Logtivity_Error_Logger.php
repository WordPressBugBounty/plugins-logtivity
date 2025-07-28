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

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

class Logtivity_Error_Logger extends Logtivity_Api
{
    use Logtivity_User_Logger_Trait;

    /**
     * @var bool
     */
    protected bool $active = true;

    /**
     * @var array
     */
    protected array $error = [];

    /**
     * @var string[]
     */
    protected static array $recordedErrors = [];

    /**
     * @param array $error
     */
    public function __construct(array $error)
    {
        $this->error = $error;

        $this->setUser();

        parent::__construct();
    }

    /**
     * @return $this
     */
    public function stop(): self
    {
        $this->active = false;

        return $this;
    }

    /**
     * @return ?array
     */
    public function send(): void
    {
        $message = $this->error['message'] ?? '';
        if (in_array($message, self::$recordedErrors) === false) {
            self::$recordedErrors[] = $this->error['message'];

            do_action('wp_logtivity_error_logger_instance', $this);

            if ($this->active) {
                $this->makeRequest('/errors/store', $this->getData());
            }
        }
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        $error = explode('Stack trace:', $this->error['message']);

        return [
            'type'               => $this->getErrorLevel($this->error['type']) ?? null,
            'message'            => $error[0],
            'stack_trace'        => $this->generateStackTrace(
                [
                    'file' => $this->error['file'] ?? null,
                    'line' => $this->error['line'] ?? null,
                ],
                $error[1] ?? null
            ),
            'file'               => $this->error['file'] ?? null,
            'line'               => $this->error['line'] ?? null,
            'user_id'            => $this->getUserID(),
            'username'           => $this->maybeGetUsersUsername(),
            'ip_address'         => $this->maybeGetUsersIp(),
            'user_authenticated' => $this->user->isLoggedIn(),
            'url'                => $this->getCurrentUrl(),
            'method'             => $this->getRequestMethod(),
            'php_version'        => phpversion(),
            'level'              => $this->error['level'] ?? null,
        ];
    }

    /**
     * @param array   $line
     * @param ?string $stackTrace
     *
     * @return array
     */
    private function generateStackTrace(array $line, ?string $stackTrace): array
    {
        $stackTraceObject = new Logtivity_Stack_Trace();

        if (isset($this->error['stack_trace'])) {
            return $stackTraceObject->createFromArray($this->error['stack_trace']);
        }

        return array_merge(
            [$stackTraceObject->createFileObject($line['file'], $line['line'])],
            $stackTraceObject->createFromString($stackTrace)
        );
    }

    /**
     * @return string
     */
    private function getRequestMethod(): string
    {
        return sanitize_text_field($_SERVER['REQUEST_METHOD'] ?? '');
    }

    /**
     * @return ?string
     */
    private function getCurrentUrl(): ?string
    {
        $host = sanitize_text_field($_SERVER['HTTP_HOST'] ?? null);
        if ($host) {
            $ssl = sanitize_text_field($_SERVER['HTTPS'] ?? 'off') != 'off'
                || sanitize_text_field($_SERVER['SERVER_PORT'] ?? '80') == 443;

            $protocol = $ssl ? 'https://' : 'http://';

            $path = sanitize_text_field($_SERVER['REQUEST_URI'] ?? '');

            $url = sanitize_url($protocol . $host . $path);

            if ($url != $protocol) {
                return $url;
            }
        }

        return null;
    }

    /**
     * @param $level
     *
     * @return string
     */
    private function getErrorLevel($level): string
    {
        $errorLevels = logtivity_get_error_levels();

        return $errorLevels[$level] ?? $level;
    }
}
