<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_raisecli;

use local_raisecli\external\policy;
use externallib_advanced_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * RAISE CLI Web Service tests
 *
 * @package     local_raisecli
 * @copyright   2023 OpenStax
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class policy_test extends externallib_advanced_testcase {
    /**
     * Test policy::get_policy_acceptance_data method
     *
     * @covers ::get_policy_acceptance_data
     */
    public function test_get_policy_acceptance_data() {
        global $DB;

        $this->resetAfterTest(true);

        $context = \context_system::instance();
        $this->assignUserCapability('tool/policy:viewacceptances', $context->id);
        $policyversionid = 1;

        $DB->insert_record('tool_policy_acceptances',
            ['userid' => '1', 'policyversionid' => $policyversionid,
            'status' => '1', 'usermodified' => '1',
            'timecreated' => '1', 'timemodified' => '1', ]);
        $DB->insert_record('tool_policy_acceptances',
            ['userid' => '2', 'policyversionid' => $policyversionid,
            'status' => '0', 'usermodified' => '1',
            'timecreated' => '1', 'timemodified' => '1', ]);
        $DB->insert_record('tool_policy_acceptances',
            ['userid' => '3', 'policyversionid' => $policyversionid,
            'status' => '1', 'usermodified' => '1',
            'timecreated' => '1', 'timemodified' => '1', ]);
        $params = [
            'policyversionid' => $policyversionid,
            'user_ids' => [
                ['id' => '1'],
                ['id' => '2'],
            ],
        ];

        $result = policy::get_policy_acceptance_data($params['policyversionid'], $params['user_ids']);
        $this->assertCount(2, $result);
        $this->assertEquals('1', $result[0]['user_id']);
        $this->assertEquals('1', $result[0]['status']);
        $this->assertEquals('2', $result[1]['user_id']);
        $this->assertEquals('0', $result[1]['status']);

        $params = [
            'policyversionid' => $policyversionid,
            'user_ids' => [],
        ];

        $resultwithoutuserids = policy::get_policy_acceptance_data($params['policyversionid'], $params['user_ids']);
        $this->assertCount(3, $resultwithoutuserids);
        $this->assertEquals('1', $resultwithoutuserids[0]['user_id']);
        $this->assertEquals('1', $resultwithoutuserids[0]['status']);
        $this->assertEquals('2', $resultwithoutuserids[1]['user_id']);
        $this->assertEquals('0', $resultwithoutuserids[1]['status']);
        $this->assertEquals('3', $resultwithoutuserids[2]['user_id']);
        $this->assertEquals('1', $resultwithoutuserids[2]['status']);

    }
}
