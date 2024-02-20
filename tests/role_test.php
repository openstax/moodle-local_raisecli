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

use local_raisecli\external\role;
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
class role_test extends externallib_advanced_testcase {
    /**
     * Test get_role_by_shortname
     *
     * @covers ::get_role_by_shortname
     */
    public function test_get_role_by_shortname() {
        global $DB;

        $this->resetAfterTest(true);

        $roleid = $this->getDataGenerator()->create_role(['shortname' => 'roleshortname']);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $context = \context_system::instance();
        $this->assignUserCapability('moodle/role:manage', $context->id);

        $role = $role = $DB->get_record('role', ['id' => $roleid], 'id, shortname, archetype', MUST_EXIST);

        $result = role::get_role_by_shortname($role->shortname);
        $result = \external_api::clean_returnvalue(role::get_role_by_shortname_returns(), $result);

        $this->assertEquals($result['id'], $role->id);
        $this->assertEquals($result['shortname'], $role->shortname);
        $this->assertEquals($result['archetype'], $role->archetype);
    }

    /**
     * Test get_role_by_shortname without capabilities
     *
     * @covers ::get_role_by_shortname
     */
    public function test_get_role_by_shortname_without_capabilities() {
        global $DB;

        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->expectException(\required_capability_exception::class);
        $result = role::get_role_by_shortname('student');
    }
}
