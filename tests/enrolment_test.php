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
use \local_raisecli\external\enrolment;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->libdir . '/externallib.php');

/**
 * RAISE CLI Web Service tests
 *
 * @package     local_raisecli
 * @copyright   2022 OpenStax
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrolment_test extends externallib_advanced_testcase {

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

        $result = enrolment::enable_self_enrolment_method($enrolinstance->id);
        $result = \external_api::clean_returnvalue(enrolment::enable_self_enrolment_method_returns(), $result);

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
        $result = enrolment::enable_self_enrolment_method($enrolinstance->id);
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

        $result = enrolment::get_self_enrolment_methods($enrolinstance->courseid, $enrolinstance->roleid);
        $result = \external_api::clean_returnvalue(enrolment::get_self_enrolment_methods_returns(), $result);

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
        $result = enrolment::set_self_enrolment_method_key($enrolinstance->id, $enrolkey);
        $result = \external_api::clean_returnvalue(enrolment::set_self_enrolment_method_key_returns(), $result);

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
        $context = \context_course::instance($course->id, MUST_EXIST);
        $this->assignUserCapability('moodle/course:view', $context->id);

        $conditions = array('courseid' => $course->id, 'enrol' => 'self');

        $enrolinstance = $DB->get_record('enrol', $conditions, 'id', MUST_EXIST);

        $this->expectException(required_capability_exception::class);
        enrolment::set_self_enrolment_method_key($enrolinstance->id, 'enrolkey123');
    }
}
