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
 * tiny_muai single run detail page.
 *
 * @package    tiny_muai
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$id = required_param('id', PARAM_INT);

admin_externalpage_setup('tinymuaitestcases');
require_capability('tiny/muai:managetestcases', context_system::instance());

$run = $DB->get_record('tiny_muai_testcase_run', ['id' => $id], '*', MUST_EXIST);
$testcase = $DB->get_record('tiny_muai_testcase', ['id' => $run->testcaseid]);

$pageurl = new moodle_url('/lib/editor/tiny/plugins/muai/testcases/run_view.php', ['id' => $id]);
$PAGE->set_url($pageurl);

$listurl = new moodle_url('/lib/editor/tiny/plugins/muai/testcases/index.php');
$PAGE->navbar->add(get_string('testcases', 'tiny_muai'), $listurl);
if ($testcase) {
    $editurl = new moodle_url('/lib/editor/tiny/plugins/muai/testcases/edit.php', ['id' => $testcase->id]);
    $runsurl = new moodle_url('/lib/editor/tiny/plugins/muai/testcases/runs.php', ['testcaseid' => $testcase->id]);
    $PAGE->navbar->add(format_string($testcase->name), $editurl);
    $PAGE->navbar->add(get_string('runhistory', 'tiny_muai'), $runsurl);
}
$PAGE->navbar->add(get_string('runviewheading', 'tiny_muai'));

$heading = get_string('runviewheading', 'tiny_muai');
$PAGE->set_title($heading);
$PAGE->set_heading($heading);

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

$summary = [
    get_string('runtimecreated', 'tiny_muai') => userdate($run->timecreated),
    get_string('runresult', 'tiny_muai') => $run->success
        ? get_string('success', 'tiny_muai')
        : get_string('lastrunfailed', 'tiny_muai'),
    get_string('runduration', 'tiny_muai') => ((int) $run->durationms) . ' ms',
];

$rows = [];
foreach ($summary as $key => $value) {
    $rows[] = html_writer::tag('dt', s($key))
        . html_writer::tag('dd', s($value));
}
echo html_writer::tag('dl', implode("\n", $rows));

if (!$run->success && (string) $run->errormessage !== '') {
    echo $OUTPUT->notification(
        get_string('runerrormessage', 'tiny_muai') . ': ' . s((string) $run->errormessage),
        \core\output\notification::NOTIFY_ERROR,
        false
    );
}

echo $OUTPUT->heading(get_string('runinputs', 'tiny_muai'), 3);
$inputs = json_decode((string) $run->inputs, true) ?: [];
$inputrows = [];
foreach ($inputs as $key => $value) {
    if (is_scalar($value)) {
        $value = (string) $value;
    } else {
        $value = json_encode($value);
    }
    $inputrows[] = html_writer::tag('dt', s((string) $key))
        . html_writer::tag('dd', html_writer::tag('pre', s($value)));
}
echo html_writer::tag('dl', implode("\n", $inputrows));

if ((string) $run->systeminstruction !== '') {
    echo $OUTPUT->heading(get_string('runsysteminstruction', 'tiny_muai'), 3);
    echo html_writer::tag(
        'pre',
        s((string) $run->systeminstruction),
        ['class' => 'card card-body bg-light']
    );
}

if ((string) $run->prompttext !== '') {
    echo $OUTPUT->heading(get_string('runprompttext', 'tiny_muai'), 3);
    echo html_writer::tag(
        'pre',
        s((string) $run->prompttext),
        ['class' => 'card card-body bg-light']
    );
}

if ($run->success) {
    echo $OUTPUT->heading(get_string('runoutput', 'tiny_muai'), 3);
    $outputtext = file_rewrite_pluginfile_urls(
        (string) $run->output,
        'pluginfile.php',
        context_system::instance()->id,
        'tiny_muai',
        'testcase_run_output',
        $run->id
    );
    echo html_writer::div(
        format_text($outputtext, FORMAT_MARKDOWN, ['context' => context_system::instance()]),
        'card card-body bg-light'
    );
}

if ($testcase) {
    echo html_writer::link(
        new moodle_url('/lib/editor/tiny/plugins/muai/testcases/runs.php', ['testcaseid' => $testcase->id]),
        get_string('runhistory', 'tiny_muai')
    );
}

echo $OUTPUT->footer();
