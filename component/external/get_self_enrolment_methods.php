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
 * RAISE CLI Web Service Function - get_self_enrolment_methods
 *
 * @package    local_raisecli
 * @copyright  2022 OpenStax
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/raisecli/component/external/get_self_enrolment_methods.php');

/**
 * RAISE CLI Web Service - get_self_enrolment_methods
 *
 * @package    local_raisecli
 * @copyright  2022 OpenStax
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_get_self_enrolment_methods_external extends external_api {

    /**
     * Returns description of get_self_enrolment_methods() parameters
     *
     * @return external_function_parameters
     */
    public static function get_self_enrolment_methods_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'Course id'),
                'roleid' => new external_value(PARAM_INT, 'Role id')
            )
        );
    }

    /**
     * Get list of self enrolment methods for a course and role.
     *
     * @param int $courseid
     * @param int $roleid
     * @return array of course self enrolment methods
     * @throws moodle_exception
     */
    public static function get_self_enrolment_methods($courseid, $roleid) {
        global $DB;

        $params = self::validate_parameters(
            self::get_self_enrolment_methods_parameters(),
            array('courseid' => $courseid, 'roleid' => $roleid)
        );
        self::validate_context(context_system::instance());

        $course = $DB->get_record('course', array('id' => $params['courseid']), '*', MUST_EXIST);
        if (!core_course_category::can_view_course_info($course) && !can_access_course($course)) {
            throw new moodle_exception('coursehidden');
        }

        $result = array();
        $conditions = array(
            'courseid' => $params['courseid'],
            'roleid' => $params['roleid'],
            'enrol' => 'self'
        );
        $rs = $DB->get_recordset('enrol', $conditions, 'sortorder,id', 'id, courseid, roleid, status');
        foreach ($rs as $enrolinstance) {
            $result[] = array(
                'id' => $enrolinstance->id,
                'courseid' => $enrolinstance->courseid,
                'roleid' => $enrolinstance->roleid,
                'enabled' => $enrolinstance->status == ENROL_INSTANCE_ENABLED
            );
        }
        $rs->close();
        return $result;
    }

    /**
     * Returns description of get_self_enrolment_methods() result value
     *
     * @return external_description
     */
    public static function get_self_enrolment_methods_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'id of course enrolment instance'),
                    'courseid' => new external_value(PARAM_INT, 'id of course'),
                    'roleid' => new external_value(PARAM_INT, 'id of role'),
                    'enabled' => new external_value(PARAM_BOOL, 'Enabled status of enrolment plugin'),
                )
            )
        );
    }
}