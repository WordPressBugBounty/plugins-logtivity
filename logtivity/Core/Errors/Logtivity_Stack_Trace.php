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

class Logtivity_Stack_Trace
{
    private array $files = [];

    /**
     * @param array $stackTrace
     *
     * @return array
     */
    public function createFromArray(array $stackTrace): array
    {
        foreach ($stackTrace as $file) {
            if (isset($file['file']) && isset($file['line'])) {
                $this->files[] = $this->createFileObject(
                    $file['file'],
                    $file['line']
                );
            }
        }

        return $this->files;
    }

    /**
     * @param string $stackTrace
     *
     * @return array
     */
    public function createFromString(string $stackTrace): array
    {
        $stackTrace = array_filter(
            preg_split('/\r\n|\r|\n/', $stackTrace)
        );

        foreach ($stackTrace as $file) {
            $array = explode(' ', $file);

            preg_match('#\((.*?)\)#', $array[1], $line);

            if (isset($line[1])) {
                $this->files[] = $this->createFileObject(
                    str_replace('(' . $line[1] . '):', '', $array[1]),
                    $line[1] ?? null
                );
            }
        }

        return $this->files;
    }

    /**
     * @param string $filePath
     * @param string $line
     *
     * @return object
     */
    public function createFileObject(string $filePath, string $line): object
    {
        return (object)[
            'file'         => $filePath,
            'line'         => $line,
            'code_snippet' => (new Logtivity_Stack_Trace_Snippet($filePath))->line($line)->get(),
        ];
    }
}
