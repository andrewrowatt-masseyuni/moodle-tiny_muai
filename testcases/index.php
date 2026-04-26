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
 * tiny_muai test cases list page.
 *
 * @package    tiny_muai
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use core_reportbuilder\system_report_factory;
use tiny_muai\reportbuilder\local\systemreports\testcases;

admin_externalpage_setup('tinymuaitestcases');
require_capability('tiny/muai:managetestcases', context_system::instance());

$strheading = get_string('testcasesheading', 'tiny_muai');
$PAGE->set_title($strheading);
$PAGE->set_heading($strheading);

echo $OUTPUT->header();

echo $OUTPUT->single_button(
    new moodle_url('/lib/editor/tiny/plugins/muai/testcases/edit.php'),
    get_string('newtestcase', 'tiny_muai'),
    'get'
);

$report = system_report_factory::create(testcases::class, context_system::instance());
echo $report->output();

echo $OUTPUT->footer();
