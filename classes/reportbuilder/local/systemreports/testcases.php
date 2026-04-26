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

namespace tiny_muai\reportbuilder\local\systemreports;

use context_system;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\local\report\action;
use core_reportbuilder\system_report;
use html_writer;
use lang_string;
use moodle_url;
use pix_icon;
use stdClass;
use tiny_muai\reportbuilder\local\entities\testcase;

/**
 * System report listing all saved tiny_muai test cases.
 *
 * @package    tiny_muai
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testcases extends system_report {
    /**
     * Set the main table, entities, columns, filters, and actions for the report.
     */
    protected function initialise(): void {
        $entity = new testcase();
        $entityalias = $entity->get_table_alias('tiny_muai_testcase');

        $this->set_main_table('tiny_muai_testcase', $entityalias);
        $this->add_entity($entity);

        $this->add_base_fields("{$entityalias}.id, {$entityalias}.name");

        $userentity = new user();
        $useralias = $userentity->get_table_alias('user');
        $this->add_entity($userentity->add_join(
            "LEFT JOIN {user} {$useralias} ON {$useralias}.id = {$entityalias}.usermodified"
        ));

        $this->add_columns_from_entities([
            'testcase:name',
            'testcase:contextid',
            'testcase:page',
            'testcase:editorcontext',
            'user:fullname',
            'testcase:timemodified',
        ]);

        $this->get_column('testcase:name')
            ->add_fields("{$entityalias}.id")
            ->add_callback(static function (string $output, stdClass $row): string {
                $url = new moodle_url('/lib/editor/tiny/plugins/muai/testcases/edit.php', ['id' => $row->id]);
                return html_writer::link($url, $output);
            });

        $this->get_column('user:fullname')
            ->set_title(new lang_string('usermodified', 'core_reportbuilder'));

        $this->add_filters_from_entities([
            'testcase:name',
            'testcase:timemodified',
        ]);

        $this->set_initial_sort_column('testcase:timemodified', SORT_DESC);

        $this->add_actions();

        $this->set_downloadable(false);
    }

    /**
     * Validate access to view this report.
     *
     * @return bool
     */
    protected function can_view(): bool {
        return has_capability('tiny/muai:managetestcases', context_system::instance());
    }

    /**
     * Get the visible name of the report.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('testcases', 'tiny_muai');
    }

    /**
     * Add row actions (edit, run history, delete) to the report.
     */
    protected function add_actions(): void {
        $this->add_action(new action(
            new moodle_url('/lib/editor/tiny/plugins/muai/testcases/edit.php', ['id' => ':id']),
            new pix_icon('t/edit', ''),
            [],
            false,
            new lang_string('edit'),
        ));

        $this->add_action(new action(
            new moodle_url('/lib/editor/tiny/plugins/muai/testcases/runs.php', ['testcaseid' => ':id']),
            new pix_icon('i/log', ''),
            [],
            false,
            new lang_string('runhistory', 'tiny_muai'),
        ));

        $this->add_action((new action(
            new moodle_url('/lib/editor/tiny/plugins/muai/testcases/delete.php'),
            new pix_icon('t/delete', ''),
            [
                'class' => 'text-danger',
                'data-modal' => 'confirmation',
                'data-modal-title-str' => json_encode(['deletetestcase', 'tiny_muai']),
                'data-modal-content-str' => ':confirmstr',
                'data-modal-yes-button-str' => json_encode(['delete', 'core']),
                'data-modal-destination' => ':deleteurl',
            ],
            false,
            new lang_string('delete'),
        ))->add_callback(static function (stdClass $row): bool {
            $row->confirmstr = json_encode([
                'confirmdeletetestcase',
                'tiny_muai',
                $row->name,
            ]);
            $row->deleteurl = (new moodle_url(
                '/lib/editor/tiny/plugins/muai/testcases/delete.php',
                ['id' => $row->id, 'sesskey' => sesskey()]
            ))->out(false);
            return true;
        }));
    }
}
