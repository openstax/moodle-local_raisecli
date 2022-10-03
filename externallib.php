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
 * RAISE CLI Web Service
 *
 * @package    local_raisecli
 * @copyright  2021 OpenStax
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * RAISE CLI Web Service
 *
 * @package    local_raisecli
 * @copyright  2021 OpenStax
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_raisecli_external extends external_api {
    /**
     * Returns description of enable_self_enrolment_method() parameters
     *
     * @return external_function_parameters
     */
    public static function enable_self_enrolment_method_parameters() {
        return new external_function_parameters(
            array(
                'enrolid' => new external_value(PARAM_INT, 'Enrolment id')
            )
        );
    }

    /**
     * Enable self enrolment method.
     *
     * @param int $enrolid
     * @return array enrolment method information
     * @throws moodle_exception
     */
    public static function enable_self_enrolment_method($enrolid) {
        global $DB;
        $params = self::validate_parameters(
            self::enable_self_enrolment_method_parameters(),
            array('enrolid' => $enrolid)
        );

        $conditions = array(
            'id' => $params['enrolid'],
            'enrol' => 'self'
        );
        $enrolinstance = $DB->get_record('enrol', $conditions, 'id, courseid, roleid', MUST_EXIST);

        $context = context_course::instance($enrolinstance->courseid, MUST_EXIST);
        self::validate_context($context);
        require_capability('enrol/self:config', $context);

        $enrolinstance->status = ENROL_INSTANCE_ENABLED;
        $DB->update_record('enrol', $enrolinstance);
        return array(
            'id' => $enrolinstance->id,
            'courseid' => $enrolinstance->courseid,
            'roleid' => $enrolinstance->roleid,
            'enabled' => $enrolinstance->status == ENROL_INSTANCE_ENABLED
        );
    }

    /**
     * Returns description of enable_self_enrolment_method() result value
     *
     * @return external_description
     */
    public static function enable_self_enrolment_method_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'id of course enrolment instance'),
                'courseid' => new external_value(PARAM_INT, 'id of course'),
                'roleid' => new external_value(PARAM_INT, 'id of role'),
                'enabled' => new external_value(PARAM_BOOL, 'Enabled status of enrolment plugin'),
            )
        );
    }

    /**
     * Returns description of get_role_by_shortname() parameters
     *
     * @return external_function_parameters
     */
    public static function get_role_by_shortname_parameters() {
        return new external_function_parameters(
            array(
                'shortname' => new external_value(PARAM_ALPHANUM, 'Role shortname')
            )
        );
    }

    /**
     * Get role information based upon shortname
     *
     * @param string $shortname
     * @return array role information
     * @throws moodle_exception
     */
    public static function get_role_by_shortname($shortname) {
        global $DB;

        $params = self::validate_parameters(
            self::get_role_by_shortname_parameters(),
            array('shortname' => $shortname)
        );
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/role:manage', $context);

        $conditions = array('shortname' => $params['shortname']);
        $role = $DB->get_record('role', $conditions, 'id, shortname, archetype', MUST_EXIST);

        return $role;
    }

    /**
     * Returns description of get_role_by_shortname() result value
     *
     * @return external_description
     */
    public static function get_role_by_shortname_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'id of role'),
                'shortname' => new external_value(PARAM_ALPHANUM, 'shortname of role'),
                'archetype' => new external_value(PARAM_ALPHANUM, 'archetype of role'),
            )
        );
    }

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

    /**
     * Returns description of set_self_enrolment_method_key() parameters
     *
     * @return external_function_parameters
     */
    public static function set_self_enrolment_method_key_parameters() {
        return new external_function_parameters(
            array(
                'enrolid' => new external_value(PARAM_INT, 'Enrolment id'),
                'enrolkey' => new external_value(PARAM_TEXT, 'Enrolment key')
            )
        );
    }

    /**
     * Set key for self enrolment method.
     *
     * @param int $enrolid
     * @param string $enrolkey
     * @return array enrolment method information
     * @throws moodle_exception
     */
    public static function set_self_enrolment_method_key($enrolid, $enrolkey) {
        global $DB;
        $params = self::validate_parameters(
            self::set_self_enrolment_method_key_parameters(),
            array('enrolid' => $enrolid, 'enrolkey' => $enrolkey)
        );

        $conditions = array(
            'id' => $params['enrolid'],
            'enrol' => 'self'
        );
        $enrolinstance = $DB->get_record('enrol', $conditions, 'id, courseid, roleid, status', MUST_EXIST);

        $context = context_course::instance($enrolinstance->courseid, MUST_EXIST);
        self::validate_context($context);
        require_capability('enrol/self:config', $context);

        $enrolinstance->password = $params['enrolkey'];
        $DB->update_record('enrol', $enrolinstance);
        return array(
            'id' => $enrolinstance->id,
            'courseid' => $enrolinstance->courseid,
            'roleid' => $enrolinstance->roleid,
            'enabled' => $enrolinstance->status == ENROL_INSTANCE_ENABLED
        );
    }

    /**
     * Returns description of set_self_enrolment_method_key() result value
     *
     * @return external_description
     */
    public static function set_self_enrolment_method_key_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'id of course enrolment instance'),
                'courseid' => new external_value(PARAM_INT, 'id of course'),
                'roleid' => new external_value(PARAM_INT, 'id of role'),
                'enabled' => new external_value(PARAM_BOOL, 'Enabled status of enrolment plugin'),
            )
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_user_uuids_parameters() {
        return new external_function_parameters(
            array(
                'user_ids' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'user id'),
                        )
                    ),
                    'User IDs requested',
                    VALUE_DEFAULT,
                    array()
                )
            )
        );
    }

    /**
     * Get the uuids associated with the given user ids.
     * @param array $userids
     * @return array list of objects with userids and uuids
     * @throws moodle_exception
     */
    public static function get_user_uuids($userids) {
        global $DB;

        $params = self::validate_parameters(
            self::get_user_uuids_parameters(),
            array('user_ids' => $userids)
        );

        if (count($userids) == 0) {
            $rs = $DB->get_recordset('local_raise_user', array(), '', 'user_id, user_uuid');
        } else {
            $selector = implode(", ", array_column($userids, 'id'));
            $rs = $DB->get_recordset_select(
                'local_raise_user',
                "user_id IN ({$selector})"
            );
        };

        $data = array();
        foreach ($rs as $item) {
            $data[] = array(
                'user_id' => $item->user_id,
                'user_uuid' => $item->user_uuid
            );
        };
        $rs->close();
        return $data;
    }

    /**
     * Returns description of get_user_uuids() result value
     *
     * @return external_description
     */
    public static function get_user_uuids_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'user_id' => new external_value(PARAM_INT, 'user_id value'),
                    'user_uuid' => new external_value(PARAM_TEXT, 'user uuid value'),
                )
            )
        );
    }
}
