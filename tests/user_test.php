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

use \local_raisecli\external\user;
use externallib_advanced_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * RAISE CLI Web Service tests
 *
 * @package     local_raisecli
 * @copyright   2022 OpenStax
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_test extends externallib_advanced_testcase {
    /**
     * Test local_raisecli_get_user_uuids
     */
    public function test_local_raisecli_get_user_uuids() {
        global $DB;

        $this->resetAfterTest(true);

        $user1 = array(
            'user_id' => 1,
            'user_uuid' => "aaabbbccc"
        );
        $user2 = array(
            'user_id' => 2,
            'user_uuid' => "dddeeefff"
        );
        $user3 = array(
            'user_id' => 3,
            'user_uuid' => "ggghhhiii"
        );
        $DB->insert_record('local_raise_user', $user1);
        $DB->insert_record('local_raise_user', $user2);
        $DB->insert_record('local_raise_user', $user3);

        $params = array(
            'user_ids' => array(
                'id' => $user1['user_id']
            )
        );

        $result = user::get_user_uuids($params);
        $result = \external_api::clean_returnvalue(user::get_user_uuids_returns(), $result);

        $this->assertEquals($result[0]['user_uuid'], $user1['user_uuid']);
        $this->assertEquals(count($result), 1);

        $params = array(
        );

        $result = user::get_user_uuids($params);
        $result = \external_api::clean_returnvalue(user::get_user_uuids_returns(), $result);

        $this->assertEquals(count($result), 3);
        foreach ($result as $item) {
            $userid = $item['user_id'];
            $uuid = $item['user_uuid'];
            if ($userid == $user1['user_id']) {
                $this->assertEquals($uuid, $user1['user_uuid']);
            } else if ($userid == $user2['user_id']) {
                $this->assertEquals($uuid, $user2['user_uuid']);
            } else if ($userid == $user3['user_id']) {
                $this->assertEquals($uuid, $user3['user_uuid']);
            } else {
                $this->assertEquals(true, false);
            };
        };
    }
}
