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
namespace local_raisecli\external;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/externallib.php');
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_value;
use external_single_structure;

/**
 * RAISE CLI Web Service Function - User Attribute Access Functions
 *
 * @package    local_raisecli
 * @copyright  2022 OpenStax
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user extends external_api {

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
