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

namespace tiny_muai\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Form for creating and editing tiny_muai test cases.
 *
 * @package    tiny_muai
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testcase_form extends \moodleform {
    /**
     * Build the form fields for a tiny_muai test case.
     */
    protected function definition(): void {
        global $PAGE;

        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement(
            'text',
            'name',
            get_string('namelabel', 'tiny_muai'),
            ['size' => 60, 'maxlength' => 255]
        );
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', null, 'maxlength', 255, 'client');

        $mform->addElement('course', 'courseid', get_string('courselabel', 'tiny_muai'), [
            'multiple' => false,
            'requiredcapabilities' => ['moodle/course:view'],
        ]);
        $mform->setType('courseid', PARAM_INT);
        $mform->addHelpButton('courseid', 'courselabel', 'tiny_muai');
        $mform->addRule('courseid', null, 'required', null, 'client');

        // cmcontextid is a hidden field — JS populates it from a separate UI <select>.
        // We can't use a 'select' form element here because HTML_QuickForm's select
        // exportValue() filters submitted values against the options registered at
        // form-definition time, and the real option list is built dynamically on the client.
        $mform->addElement('hidden', 'cmcontextid');
        $mform->setType('cmcontextid', PARAM_INT);

        $pickerhtml = \html_writer::select(
            ['' => get_string('selectcoursefirst', 'tiny_muai')],
            'tiny_muai_cmpicker',
            '',
            false,
            ['id' => 'tiny_muai_cmpicker', 'class' => 'form-control']
        );
        $mform->addElement('static', 'cmpicker', get_string('cmcontextidlabel', 'tiny_muai'), $pickerhtml);
        $mform->addHelpButton('cmpicker', 'cmcontextidlabel', 'tiny_muai');

        $promptoptions = $this->build_prompt_options();
        $mform->addElement('select', 'promptkey', get_string('promptkeylabel', 'tiny_muai'), $promptoptions);
        $mform->setType('promptkey', PARAM_RAW);
        $mform->addHelpButton('promptkey', 'promptkeylabel', 'tiny_muai');
        $mform->addRule('promptkey', null, 'required', null, 'client');

        $mform->addElement(
            'textarea',
            'editorcontextprompt',
            get_string('editorcontextpromptlabel', 'tiny_muai'),
            ['rows' => 5, 'cols' => 80]
        );
        $mform->setType('editorcontextprompt', PARAM_RAW);
        $mform->addHelpButton('editorcontextprompt', 'editorcontextpromptlabel', 'tiny_muai');

        $mform->addElement('hidden', 'page');
        $mform->setType('page', PARAM_TEXT);
        $mform->addElement('hidden', 'editorcontext');
        $mform->setType('editorcontext', PARAM_TEXT);

        $mform->addElement(
            'textarea',
            'editorcontent',
            get_string('editorcontentlabel', 'tiny_muai'),
            ['rows' => 10, 'cols' => 80]
        );
        $mform->setType('editorcontent', PARAM_RAW);
        $mform->addRule('editorcontent', null, 'required', null, 'client');

        $mform->addElement(
            'text',
            'nameparam',
            get_string('nameparamlabel', 'tiny_muai'),
            ['size' => 60, 'maxlength' => 255]
        );
        $mform->setType('nameparam', PARAM_TEXT);
        $mform->addHelpButton('nameparam', 'nameparamlabel', 'tiny_muai');

        $mform->addElement(
            'textarea',
            'previousresponse',
            get_string('previousresponselabel', 'tiny_muai'),
            ['rows' => 6, 'cols' => 80]
        );
        $mform->setType('previousresponse', PARAM_RAW);
        $mform->addHelpButton('previousresponse', 'previousresponselabel', 'tiny_muai');

        $mform->addElement(
            'header',
            'overrides',
            get_string('overridesheading', 'tiny_muai')
        );
        $mform->setExpanded('overrides', false);

        $mform->addElement(
            'static',
            'overridesdesc',
            '',
            get_string('overridesheading_desc', 'tiny_muai')
        );

        $mform->addElement(
            'textarea',
            'defaultpromptcontext',
            get_string('defaultpromptcontext', 'tiny_muai'),
            ['rows' => 10, 'cols' => 80]
        );
        $mform->setType('defaultpromptcontext', PARAM_RAW);

        $mform->addElement(
            'textarea',
            'defaultprompt',
            get_string('defaultprompt', 'tiny_muai'),
            ['rows' => 10, 'cols' => 80]
        );
        $mform->setType('defaultprompt', PARAM_RAW);

        $mform->addElement(
            'textarea',
            'prompts',
            get_string('promptslabel', 'tiny_muai'),
            ['rows' => 10, 'cols' => 80]
        );
        $mform->setType('prompts', PARAM_RAW);

        $mform->closeHeaderBefore('buttonar');

        $buttongroup = [
            $mform->createElement('submit', 'submitbutton', get_string('savechanges')),
            $mform->createElement('submit', 'submitrun', get_string('saveandrun', 'tiny_muai')),
            $mform->createElement('cancel'),
        ];
        $mform->addGroup($buttongroup, 'buttonar', '', [' '], false);

        $PAGE->requires->js_call_amd('tiny_muai/testcase_form', 'init', [[
            'formId' => $mform->getAttribute('id'),
            'prompts' => array_values($this->get_available_prompts()),
        ]]);
    }

    /**
     * Build the option list for the page+editor_context dropdown.
     *
     * Keys are "page|editor_context"; the value is a human-readable label.
     *
     * @return array<string, string>
     */
    protected function build_prompt_options(): array {
        $options = ['' => get_string('nopromptsconfigured', 'tiny_muai')];
        foreach ($this->get_available_prompts() as $row) {
            $key = $row['page'] . '|' . $row['editorcontext'];
            $options[$key] = $row['page'] . ' — ' . $row['editorcontext'];
        }
        return $options;
    }

    /**
     * Return parsed prompts from the live admin setting, merged with any
     * per-testcase prompts override passed in via customdata.
     *
     * @return array<int, array{page: string, editorcontext: string, prompt: string}>
     */
    protected function get_available_prompts(): array {
        $rows = \tiny_muai\utils::get_configured_prompts();

        $override = (string) ($this->_customdata['prompts'] ?? '');
        if (trim($override) !== '') {
            foreach (\tiny_muai\utils::parse_prompts_string($override) as $extra) {
                $rows[] = $extra;
            }
        }

        $unique = [];
        foreach ($rows as $row) {
            $key = $row['page'] . '|' . $row['editorcontext'];
            if (!isset($unique[$key])) {
                $unique[$key] = $row;
            }
        }
        return $unique;
    }

    #[\Override]
    public function validation($data, $files): array {
        global $DB;

        $errors = parent::validation($data, $files);

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $errors['name'] = get_string('required');
        } else {
            $params = ['name' => $name];
            $select = 'name = :name';
            if (!empty($data['id'])) {
                $select .= ' AND id <> :id';
                $params['id'] = (int) $data['id'];
            }
            if ($DB->record_exists_select('tiny_muai_testcase', $select, $params)) {
                $errors['name'] = get_string('nametaken', 'tiny_muai');
            }
        }

        $courseid = (int) ($data['courseid'] ?? 0);
        if ($courseid <= 0) {
            $errors['courseid'] = get_string('required');
        } else {
            try {
                get_course($courseid);
            } catch (\dml_missing_record_exception $e) {
                $errors['courseid'] = get_string('coursenotfound', 'tiny_muai');
                $courseid = 0;
            }
        }

        $cmcontextid = (int) ($data['cmcontextid'] ?? 0);
        if ($cmcontextid <= 0) {
            $errors['cmcontextid'] = get_string('required');
        } else if ($courseid > 0) {
            $context = \core\context::instance_by_id($cmcontextid, IGNORE_MISSING);
            $valid = false;
            if ($context instanceof \core\context\course && (int) $context->instanceid === $courseid) {
                $valid = true;
            } else if ($context instanceof \core\context\module) {
                try {
                    [$cmcourse] = get_course_and_cm_from_cmid((int) $context->instanceid);
                    $valid = ((int) $cmcourse->id === $courseid);
                } catch (\moodle_exception $e) {
                    $valid = false;
                }
            }
            if (!$valid) {
                $errors['cmcontextid'] = get_string('cmcontextnotincourse', 'tiny_muai');
            }
        }

        $promptkey = (string) ($data['promptkey'] ?? '');
        if ($promptkey === '') {
            $errors['promptkey'] = get_string('required');
        } else {
            $available = $this->get_available_prompts();
            $found = false;
            foreach ($available as $row) {
                if (($row['page'] . '|' . $row['editorcontext']) === $promptkey) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $errors['promptkey'] = get_string('promptkeyunknown', 'tiny_muai');
            }
        }

        return $errors;
    }
}
