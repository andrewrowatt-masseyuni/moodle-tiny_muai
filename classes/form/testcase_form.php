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

        $mform->addElement(
            'text',
            'contextid',
            get_string('contextidlabel', 'tiny_muai')
        );
        $mform->setType('contextid', PARAM_INT);
        $mform->addHelpButton('contextid', 'contextidlabel', 'tiny_muai');
        $mform->addRule('contextid', null, 'required', null, 'client');

        $mform->addElement(
            'text',
            'page',
            get_string('pagelabel', 'tiny_muai'),
            ['size' => 60, 'maxlength' => 255]
        );
        $mform->setType('page', PARAM_TEXT);
        $mform->addHelpButton('page', 'pagelabel', 'tiny_muai');
        $mform->addRule('page', null, 'required', null, 'client');

        $mform->addElement(
            'text',
            'editorcontext',
            get_string('editorcontextlabel', 'tiny_muai'),
            ['size' => 60, 'maxlength' => 255]
        );
        $mform->setType('editorcontext', PARAM_TEXT);
        $mform->addHelpButton('editorcontext', 'editorcontextlabel', 'tiny_muai');
        $mform->addRule('editorcontext', null, 'required', null, 'client');

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
        $mform->setExpanded('overrides', false); // Collapse by default.

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

        $contextid = (int) ($data['contextid'] ?? 0);
        if ($contextid <= 0) {
            $errors['contextid'] = get_string('required');
        } else {
            try {
                \core\context::instance_by_id($contextid);
            } catch (\moodle_exception $e) {
                $errors['contextid'] = get_string('invalidcontextid', 'tiny_muai');
            }
        }

        return $errors;
    }
}
