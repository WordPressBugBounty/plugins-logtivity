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

class Logtivity_Error_Log
{
    /**
     * @var ?callable
     */
    private $errorHandler;

    /**
     * @var ?callable
     */
    private $exceptionHandler;

    public function __construct()
    {
        $this->errorHandler = set_error_handler([$this, 'errorHandler']);

        $this->exceptionHandler = set_exception_handler([$this, 'exceptionHandler']);
    }

    /**
     * @return self
     */
    public static function init(): self
    {
        return new static();
    }

    /**
     * @param int     $code
     * @param string  $message
     * @param ?string $file
     * @param ?int    $line
     *
     * @return mixed
     */
    public function errorHandler(
        int $code,
        string $message,
        ?string $file = null,
        ?int $line = null
    ) {
        try {
            if (isset($_SERVER['HTTP_HOST'])) {
                $stackTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS & ~DEBUG_BACKTRACE_PROVIDE_OBJECT);
            } else {
                $stackTrace = [
                    [
                        'line' => $file,
                        'file' => $line,
                    ],
                ];
            }

            $error = [
                'type'        => $code,
                'message'     => $message,
                'file'        => $file,
                'line'        => $line,
                'stack_trace' => $stackTrace,
                'level'       => 'warning',
            ];

            if ($this->shouldIgnore($error, 'warnings')) {
                return false;
            }

            Logtivity::logError($error)->send();

        } catch (Throwable $e) {
            // Ignore
        }

        if ($this->errorHandler) {
            return ($this->errorHandler)($code, $message, $file, $line);
        }

        return false;
    }

    /**
     * @param Throwable $throwable
     *
     * @return void
     */
    public function exceptionHandler(Throwable $throwable): void
    {
        try {
            if (isset($_SERVER['HTTP_HOST'])) {
                $stackTrace = array_merge(
                    [
                        [
                            'line' => $throwable->getLine(),
                            'file' => $throwable->getFile(),
                        ],
                    ],
                    $throwable->getTrace()
                );
            } else {
                $stackTrace = [
                    [
                        'line' => $throwable->getLine(),
                        'file' => $throwable->getFile(),
                    ],
                ];
            }

            $error = [
                'type'        => get_class($throwable),
                'message'     => $throwable->getMessage(),
                'file'        => $throwable->getFile(),
                'line'        => $throwable->getLine(),
                'stack_trace' => $stackTrace,
                'level'       => 'error',
            ];

            if ($this->shouldIgnore($error, 'errors')) {
                return;
            }

            Logtivity::logError($error)->send();

        } catch (Throwable $e) {
            // Ignore
        }

        if ($this->exceptionHandler) {
            ($this->exceptionHandler)($throwable);
        }
    }

    /**
     * @param array  $error
     * @param string $type
     *
     * @return bool
     */
    private function shouldIgnore(array $error, string $type): bool
    {
        $errorType    = $error['type'] ?? null;
        $errorMessage = $error['message'] ?? null;

        if (
            ($errorType == E_WARNING && strpos($errorMessage, 'unlink') !== false)
            || $this->loggingDisabled()
            || $this->isErrorTypeDisabled($type)
            || $this->maybeRateLimit($error)
        ) {
            return true;
        }

        return apply_filters('logtivity_should_ignore_error', false, $error);
    }

    /**
     * @param array $error
     *
     * @return bool
     */
    public function maybeRateLimit(array $error): bool
    {
        $errorType = $error['type'] ?? null;

        if (
            is_int($errorType)
            && in_array($errorType, [E_ERROR, E_PARSE]) == false
        ) {
            // Most error types are rate limited to once/24 hours
            $hash = md5($error['message']);

            if (get_transient('logtivity_' . $hash) === false) {
                set_transient('logtivity_' . $hash, true, 24 * HOUR_IN_SECONDS);

                return false;
            }

            return true;
        }

        // Exceptions are not rate limited
        return false;
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public function isErrorTypeDisabled(string $type): bool
    {
        return in_array(
            $type,
            (new Logtivity_Options())->disabledErrorLevels()
        );
    }

    /**
     * @return bool
     */
    public function loggingDisabled(): bool
    {
        return (bool)(new Logtivity_Options())->getOption('logtivity_disable_error_logging');
    }
}
