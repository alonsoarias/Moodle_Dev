<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Lightweight template interpolation helper.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\local;

/**
 * Replaces {{placeholders}} in strings with provided values.
 */
class interpolator {
    /**
     * Performs placeholder replacement.
     *
     * @param string $template
     * @param array $context
     * @return string
     */
    public static function render(string $template, array $context): string {
        if ($template === '') {
            return $template;
        }
        return preg_replace_callback('/{{\s*([a-z0-9_.]+)\s*}}/iu', static function(array $matches) use ($context) {
            $key = $matches[1];
            if (!array_key_exists($key, $context)) {
                return '';
            }
            return (string)$context[$key];
        }, $template) ?? $template;
    }
}
