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
 * tiny_muai test case create / edit / run page.
 *
 * @package    tiny_muai
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use tiny_muai\form\testcase_form;
use tiny_muai\utils;

admin_externalpage_setup('tinymuaitestcases');
require_capability('tiny/muai:managetestcases', context_system::instance());

$id = optional_param('id', 0, PARAM_INT);
$runid = optional_param('runid', 0, PARAM_INT);

$listurl = new moodle_url('/lib/editor/tiny/plugins/muai/testcases/index.php');
$pageurl = new moodle_url('/lib/editor/tiny/plugins/muai/testcases/edit.php', $id ? ['id' => $id] : []);
$PAGE->set_url($pageurl);
$PAGE->navbar->add(get_string('testcases', 'tiny_muai'), $listurl);

$existing = null;
if ($id) {
    $existing = $DB->get_record('tiny_muai_testcase', ['id' => $id], '*', MUST_EXIST);
    $heading = get_string('editingtestcase', 'tiny_muai', format_string($existing->name));
    $PAGE->navbar->add($heading);
} else {
    $heading = get_string('newtestcase', 'tiny_muai');
    $PAGE->navbar->add($heading);
}
$PAGE->set_title($heading);
$PAGE->set_heading($heading);

$form = new testcase_form($pageurl->out(false));

if ($existing) {
    $form->set_data((array) $existing);
} else {
    $form->set_data([
        'id' => 0,
        'defaultpromptcontext' => (string) get_config('tiny_muai', 'defaultpromptcontext'),
        'defaultprompt' => (string) get_config('tiny_muai', 'defaultprompt'),
        'prompts' => (string) get_config('tiny_muai', 'prompts'),
    ]);
}

if ($form->is_cancelled()) {
    redirect($listurl);
}

if ($data = $form->get_data()) {
    $now = time();
    $record = (object) [
        'name' => trim($data->name),
        'contextid' => (int) $data->contextid,
        'page' => trim((string) $data->page),
        'editorcontext' => trim((string) $data->editorcontext),
        'editorcontent' => (string) $data->editorcontent,
        'nameparam' => trim((string) ($data->nameparam ?? '')),
        'previousresponse' => (string) ($data->previousresponse ?? ''),
        'defaultpromptcontext' => (string) ($data->defaultpromptcontext ?? ''),
        'defaultprompt' => (string) ($data->defaultprompt ?? ''),
        'prompts' => (string) ($data->prompts ?? ''),
        'usermodified' => $USER->id,
        'timemodified' => $now,
    ];

    if (!empty($data->id)) {
        $record->id = (int) $data->id;
        $DB->update_record('tiny_muai_testcase', $record);
        $savedid = $record->id;
    } else {
        $record->timecreated = $now;
        $savedid = $DB->insert_record('tiny_muai_testcase', $record);
    }

    \core\notification::success(get_string('testcaseupdated', 'tiny_muai'));

    if (!empty($data->submitrun)) {
        $newrunid = utils::run_test_case($savedid);
        redirect(new moodle_url(
            '/lib/editor/tiny/plugins/muai/testcases/edit.php',
            ['id' => $savedid, 'runid' => $newrunid]
        ));
    }

    redirect($listurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

$form->display();

if ($id && $runid) {
    $run = $DB->get_record('tiny_muai_testcase_run', ['id' => $runid, 'testcaseid' => $id]);
    if ($run) {
        echo $OUTPUT->heading(get_string('lastrunoutput', 'tiny_muai'), 3);
        if ($run->success) {
            echo $OUTPUT->notification(
                get_string('lastrunsucceeded', 'tiny_muai') . ' (' . (int) $run->durationms . ' ms)',
                \core\output\notification::NOTIFY_SUCCESS,
                false
            );
            if ((string) $run->systeminstruction !== '') {
                echo $OUTPUT->heading(get_string('runsysteminstruction', 'tiny_muai'), 4);
                echo html_writer::tag(
                    'pre',
                    s((string) $run->systeminstruction),
                    ['class' => 'card card-body bg-light']
                );
            }
            if ((string) $run->prompttext !== '') {
                echo $OUTPUT->heading(get_string('runprompttext', 'tiny_muai'), 4);
                echo html_writer::tag(
                    'pre',
                    s((string) $run->prompttext),
                    ['class' => 'card card-body bg-light']
                );
            }
            echo $OUTPUT->heading(get_string('runoutput', 'tiny_muai'), 4);
            echo html_writer::tag(
                    'pre',
                    s((string) $run->output),
                    ['class' => 'card card-body bg-light']
                );
        } else {
            echo $OUTPUT->notification(
                get_string('lastrunfailed', 'tiny_muai') . ': ' . s((string) $run->errormessage),
                \core\output\notification::NOTIFY_ERROR,
                false
            );
        }
    }
}

if ($id) {
    $recent = $DB->get_records(
        'tiny_muai_testcase_run',
        ['testcaseid' => $id],
        'timecreated DESC',
        'id, success, durationms, timecreated',
        0,
        5
    );
    if ($recent) {
        echo $OUTPUT->heading(get_string('recentruns', 'tiny_muai'), 3);
        $items = [];
        foreach ($recent as $row) {
            $url = new moodle_url('/lib/editor/tiny/plugins/muai/testcases/run_view.php', ['id' => $row->id]);
            $label = userdate($row->timecreated)
                . ' — '
                . ($row->success
                    ? get_string('success', 'tiny_muai')
                    : get_string('lastrunfailed', 'tiny_muai'))
                . ' (' . (int) $row->durationms . ' ms)';
            $items[] = html_writer::link($url, $label);
        }
        echo html_writer::alist($items);
        echo html_writer::link(
            new moodle_url('/lib/editor/tiny/plugins/muai/testcases/runs.php', ['testcaseid' => $id]),
            get_string('runhistory', 'tiny_muai')
        );
    }
}

echo $OUTPUT->footer();
