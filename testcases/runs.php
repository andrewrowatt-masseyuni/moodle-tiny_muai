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
 * tiny_muai test case run history page.
 *
 * @package    tiny_muai
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use core_reportbuilder\system_report_factory;
use tiny_muai\reportbuilder\local\systemreports\testcase_runs;

$testcaseid = required_param('testcaseid', PARAM_INT);

admin_externalpage_setup('tinymuaitestcases');
require_capability('tiny/muai:managetestcases', context_system::instance());

$testcase = $DB->get_record('tiny_muai_testcase', ['id' => $testcaseid], '*', MUST_EXIST);

$pageurl = new moodle_url('/lib/editor/tiny/plugins/muai/testcases/runs.php', ['testcaseid' => $testcaseid]);
$PAGE->set_url($pageurl);

$listurl = new moodle_url('/lib/editor/tiny/plugins/muai/testcases/index.php');
$editurl = new moodle_url('/lib/editor/tiny/plugins/muai/testcases/edit.php', ['id' => $testcaseid]);
$PAGE->navbar->add(get_string('testcases', 'tiny_muai'), $listurl);
$PAGE->navbar->add(format_string($testcase->name), $editurl);
$PAGE->navbar->add(get_string('runhistory', 'tiny_muai'));

$heading = get_string('runhistory', 'tiny_muai') . ': ' . format_string($testcase->name);
$PAGE->set_title($heading);
$PAGE->set_heading($heading);

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

$report = system_report_factory::create(
    testcase_runs::class,
    context_system::instance(),
    parameters: ['testcaseid' => $testcaseid]
);
echo $report->output();

echo $OUTPUT->footer();
