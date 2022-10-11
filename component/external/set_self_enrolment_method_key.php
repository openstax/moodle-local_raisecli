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
 * RAISE CLI Web Service Function - set_self_enrolment_method_key
 *
 * @package    local_raisecli
 * @copyright  2022 OpenStax
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/raisecli/component/external/set_self_enrolment_method_key.php');

/**
 * RAISE CLI Web Service - set_self_enrolment_method_key
 *
 * @package    local_raisecli
 * @copyright  2022 OpenStax
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_set_self_enrolment_method_key_external extends external_api {

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
}
