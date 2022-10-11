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
 * RAISE CLI Web Service Function - get_role_by_shortname
 *
 * @package    local_raisecli
 * @copyright  2022 OpenStax
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/raisecli/component/external/get_role_by_shortname.php');

/**
 * RAISE CLI Web Service - get_role_by_shortname
 *
 * @package    local_raisecli
 * @copyright  2022 OpenStax
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_get_role_by_shortname_external extends external_api {

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
}