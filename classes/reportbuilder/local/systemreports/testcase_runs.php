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
use lang_string;
use moodle_url;
use pix_icon;
use tiny_muai\reportbuilder\local\entities\testcase_run;

/**
 * System report listing run history rows for a single test case.
 *
 * Expects a 'testcaseid' parameter — when missing or zero the report shows
 * nothing.
 *
 * @package    tiny_muai
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testcase_runs extends system_report {
    /**
     * Set the main table, entities, columns, filters, and the testcaseid scoping condition.
     */
    protected function initialise(): void {
        $entity = new testcase_run();
        $entityalias = $entity->get_table_alias('tiny_muai_testcase_run');

        $this->set_main_table('tiny_muai_testcase_run', $entityalias);
        $this->add_entity($entity);

        $this->add_base_fields("{$entityalias}.id, {$entityalias}.testcaseid");

        $userentity = new user();
        $useralias = $userentity->get_table_alias('user');
        $this->add_entity($userentity->add_join(
            "LEFT JOIN {user} {$useralias} ON {$useralias}.id = {$entityalias}.userid"
        ));

        $testcaseid = $this->get_parameter('testcaseid', 0, PARAM_INT);
        $this->add_base_condition_simple("{$entityalias}.testcaseid", $testcaseid);

        $this->add_columns_from_entities([
            'testcase_run:timecreated',
            'user:fullname',
            'testcase_run:success',
            'testcase_run:durationms',
            'testcase_run:errormessage',
        ]);

        $this->get_column('user:fullname')
            ->set_title(new lang_string('runuser', 'tiny_muai'));

        $this->add_filters_from_entities([
            'testcase_run:timecreated',
            'testcase_run:success',
        ]);

        $this->set_initial_sort_column('testcase_run:timecreated', SORT_DESC);

        $this->add_action(new action(
            new moodle_url('/lib/editor/tiny/plugins/muai/testcases/run_view.php', ['id' => ':id']),
            new pix_icon('e/search', ''),
            [],
            false,
            new lang_string('viewrun', 'tiny_muai'),
        ));

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
        return get_string('runhistory', 'tiny_muai');
    }
}
