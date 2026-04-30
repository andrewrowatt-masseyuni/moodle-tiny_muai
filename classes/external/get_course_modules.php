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

namespace tiny_muai\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * External API: return sections + modules with their context ids for a course.
 *
 * Used by the testcase form's cascading "section / activity" dropdown.
 *
 * @package    tiny_muai
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_course_modules extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id', VALUE_REQUIRED),
        ]);
    }

    /**
     * Build the section + module tree for a course.
     *
     * @param int $courseid
     * @return array
     */
    public static function execute(int $courseid): array {
        ['courseid' => $courseid] = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
        ]);

        $systemcontext = \core\context\system::instance();
        self::validate_context($systemcontext);
        require_capability('tiny/muai:managetestcases', $systemcontext);

        $course = get_course($courseid);
        $coursecontext = \core\context\course::instance($course->id);

        $modinfo = get_fast_modinfo($course);

        $sections = [];
        foreach ($modinfo->get_section_info_all() as $section) {
            $modules = [];
            foreach ($section->get_sequence_cm_infos() as $cm) {
                if ($cm->deletioninprogress) {
                    continue;
                }
                $modules[] = [
                    'cmid' => (int) $cm->id,
                    'name' => format_string($cm->name, true, ['context' => $cm->context]),
                    'modname' => $cm->modname,
                    'modcontextid' => (int) $cm->context->id,
                ];
            }

            $sections[] = [
                'sectionid' => (int) $section->id,
                'sectionnumber' => (int) $section->section,
                'name' => get_section_name($course, $section),
                'modules' => $modules,
            ];
        }

        return [
            'coursecontextid' => (int) $coursecontext->id,
            'sections' => $sections,
        ];
    }

    /**
     * Returns description.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'coursecontextid' => new external_value(PARAM_INT, 'Course context id'),
            'sections' => new external_multiple_structure(new external_single_structure([
                'sectionid' => new external_value(PARAM_INT, 'course_sections.id'),
                'sectionnumber' => new external_value(PARAM_INT, 'Section number within the course'),
                'name' => new external_value(PARAM_TEXT, 'Display name for the section'),
                'modules' => new external_multiple_structure(new external_single_structure([
                    'cmid' => new external_value(PARAM_INT, 'Course module id'),
                    'name' => new external_value(PARAM_TEXT, 'Display name of the activity'),
                    'modname' => new external_value(PARAM_PLUGIN, 'Module type, e.g. forum'),
                    'modcontextid' => new external_value(PARAM_INT, 'Module context id'),
                ])),
            ])),
        ]);
    }
}
