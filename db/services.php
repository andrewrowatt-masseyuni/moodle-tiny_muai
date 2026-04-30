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
 * Web service definitions for tiny_muai.
 *
 * @package    tiny_muai
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'tiny_muai_get_ai_response' => [
        'classname'   => \tiny_muai\external\get_ai_response::class,
        'description' => 'Build a prompt from the tiny_muai prompts setting and call core_ai generate_text.',
        'type'        => 'write',
        'ajax'        => true,
    ],
    'tiny_muai_get_course_modules' => [
        'classname'   => \tiny_muai\external\get_course_modules::class,
        'description' => 'Return sections and activities (with context ids) for a course, for the testcase form picker.',
        'type'        => 'read',
        'ajax'        => true,
    ],
];
