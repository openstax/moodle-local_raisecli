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

defined('MOODLE_INTERNAL') || die();

global $CFG;

use local_raise_external;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/local/raisecli/externallib.php');

/**
 * RAISE CLI Web Service tests
 *
 * @package     local_raisecli
 * @copyright   2022 OpenStax
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_raisecli_externallib_testcase extends externallib_advanced_testcase {

    /**
     * Test enable_self_enrolment_method
     */
    public function test_enable_self_enrolment_method() {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $context = context_course::instance($course->id, MUST_EXIST);
        $roleid = $this->assignUserCapability('moodle/course:view', $context->id);
        $this->assignUserCapability('enrol/self:config', $context->id, $roleid);

        $conditions = array('courseid' => $course->id, 'enrol' => 'self');

        $enrolinstance = $DB->get_record('enrol', $conditions, 'id, status', MUST_EXIST);

        // Initialize the ennrolment to be disabled.
        $enrolinstance->status = ENROL_INSTANCE_DISABLED;
        $DB->update_record('enrol', $enrolinstance);

        $result = local_raisecli_external::enable_self_enrolment_method($enrolinstance->id);
        $result = external_api::clean_returnvalue(local_raisecli_external::enable_self_enrolment_method_returns(), $result);

        $this->assertEquals($result['courseid'], $course->id);
        $this->assertEquals($result['id'], $enrolinstance->id);
        $this->assertEquals($result['enabled'], true);

        // Confirm database reflects enabled status.
        $enrolinstance = $DB->get_record('enrol', ['id' => $enrolinstance->id], 'status', MUST_EXIST);
        $this->assertEquals($enrolinstance->status, ENROL_INSTANCE_ENABLED);
    }

    /**
     * Test enable_self_enrolment_method without capabilities
     */
    public function test_enable_self_enrolment_method_without_capabilities() {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $context = context_course::instance($course->id, MUST_EXIST);
        $this->assignUserCapability('moodle/course:view', $context->id);

        $conditions = array('courseid' => $course->id, 'enrol' => 'self');

        $enrolinstance = $DB->get_record('enrol', $conditions, 'id', MUST_EXIST);

        $this->expectException(required_capability_exception::class);
        $result = local_raisecli_external::enable_self_enrolment_method($enrolinstance->id);
    }

    /**
     * Test get_role_by_shortname
     */
    public function test_get_role_by_shortname() {
        global $DB;

        $this->resetAfterTest(true);

        $roleid = $this->getDataGenerator()->create_role(array('shortname' => 'roleshortname'));
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $context = context_system::instance();
        $this->assignUserCapability('moodle/role:manage', $context->id);

        $role = $role = $DB->get_record('role', ['id' => $roleid], 'id, shortname, archetype', MUST_EXIST);

        $result = local_raisecli_external::get_role_by_shortname($role->shortname);
        $result = external_api::clean_returnvalue(local_raisecli_external::get_role_by_shortname_returns(), $result);

        $this->assertEquals($result['id'], $role->id);
        $this->assertEquals($result['shortname'], $role->shortname);
        $this->assertEquals($result['archetype'], $role->archetype);
    }

    /**
     * Test get_role_by_shortname without capabilities
     */
    public function test_get_role_by_shortname_without_capabilities() {
        global $DB;

        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->expectException(required_capability_exception::class);
        $result = local_raisecli_external::get_role_by_shortname('student');
    }

    /**
     * Test get_self_enrolment_methods
     */
    public function test_get_self_enrolment_methods() {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $conditions = array('courseid' => $course->id, 'enrol' => 'self');

        $enrolinstance = $DB->get_record('enrol', $conditions, 'id, courseid, roleid, status', MUST_EXIST);

        $result = local_raisecli_external::get_self_enrolment_methods($enrolinstance->courseid, $enrolinstance->roleid);
        $result = external_api::clean_returnvalue(local_raisecli_external::get_self_enrolment_methods_returns(), $result);

        $this->assertEquals(count($result), 1);
        $this->assertEquals($result[0]['id'], $enrolinstance->id);
        $this->assertEquals($result[0]['roleid'], $enrolinstance->roleid);
        $this->assertEquals($result[0]['courseid'], $enrolinstance->courseid);
        $this->assertEquals($result[0]['enabled'], $enrolinstance->status == ENROL_INSTANCE_ENABLED);
    }

    /**
     * Test set_self_enrolment_method_key
     */
    public function test_set_self_enrolment_method_key() {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $context = context_course::instance($course->id, MUST_EXIST);
        $roleid = $this->assignUserCapability('moodle/course:view', $context->id);
        $this->assignUserCapability('enrol/self:config', $context->id, $roleid);

        $conditions = array('courseid' => $course->id, 'enrol' => 'self');

        $enrolinstance = $DB->get_record('enrol', $conditions, 'id, status', MUST_EXIST);

        $enrolkey = 'enrolkey123.,;:!?_-+/*@#&$';
        $result = local_raisecli_external::set_self_enrolment_method_key($enrolinstance->id, $enrolkey);
        $result = external_api::clean_returnvalue(local_raisecli_external::set_self_enrolment_method_key_returns(), $result);

        $this->assertEquals($result['courseid'], $course->id);
        $this->assertEquals($result['id'], $enrolinstance->id);
        $this->assertEquals($result['enabled'], $enrolinstance->status == ENROL_INSTANCE_ENABLED);

        // Confirm database reflects key.
        $enrolinstance = $DB->get_record('enrol', ['id' => $enrolinstance->id], 'password', MUST_EXIST);
        $this->assertEquals($enrolinstance->password, $enrolkey);
    }

    /**
     * Test set_self_enrolment_method_key without capabilities
     */
    public function test_set_self_enrolment_method_key_without_capabilities() {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $context = context_course::instance($course->id, MUST_EXIST);
        $this->assignUserCapability('moodle/course:view', $context->id);

        $conditions = array('courseid' => $course->id, 'enrol' => 'self');

        $enrolinstance = $DB->get_record('enrol', $conditions, 'id', MUST_EXIST);

        $this->expectException(required_capability_exception::class);
        local_raisecli_external::set_self_enrolment_method_key($enrolinstance->id, 'enrolkey123');
    }

    /**
     * Test local_raisecli_get_user_uuids
     */
    public function test_local_raisecli_get_user_uuids() {
        global $DB;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $this->setUser($user1);
        $user1data = local_raise_external::get_raise_user();
        $this->setUser($user2);
        $user2data = local_raise_external::get_raise_user();
        $this->setUser($user3);
        $user3data = local_raise_external::get_raise_user();

        $params = array(
            'user_ids' => array(
                'id' => $user1->id
            )
        );

        $result = local_raisecli_external::get_user_uuids($params);
        $result = external_api::clean_returnvalue(local_raisecli_external::get_user_uuids_returns(), $result);

        $this->assertEquals($result[0]['user_uuid'], $user1data['uuid']);
        $this->assertEquals(count($result), 1);

        $params = array(
        );

        $result = local_raisecli_external::get_user_uuids($params);
        $result = external_api::clean_returnvalue(local_raisecli_external::get_user_uuids_returns(), $result);

        $this->assertEquals(count($result), 3);
        foreach ($result as $item) {
            $userid = $item['user_id'];
            $uuid = $item['user_uuid'];
            if ($userid == $user1->id) {
                $this->assertEquals($uuid, $user1data['uuid']);
            } else if ($userid == $user2->id) {
                $this->assertEquals($uuid, $user2data['uuid']);
            } else if ($userid == $user3->id) {
                $this->assertEquals($uuid, $user3data['uuid']);
            } else {
                $this->assertEquals(true, false);
            };
        };
    }
}
