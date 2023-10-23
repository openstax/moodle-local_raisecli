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
require_once($CFG->libdir . '/questionlib.php');

use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_value;
use external_single_structure;

/**
 * RAISE CLI Web Service Function - Role Attribute Access Functions
 *
 * @package    local_raisecli
 * @copyright  2022 OpenStax
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class policies extends external_api {
    /**
     * Describes the parameters for get_policy_acceptance_data.
     *
     * @return external_function_parameters
     */
    public static function get_policy_acceptance_data_parameters() {
        return new external_function_parameters(
            [
                'policyversionid' => new external_value(PARAM_INT, 'Policy version ID'),
                'user_ids' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'id' => new external_value(PARAM_INT, 'User ID'),
                        ]
                    ),
                    'Optional list of user IDs',
                    VALUE_DEFAULT,
                    []
                ),
            ]
        );
    }

    /**
     * Retrieve policy acceptance data for specified users and policy version
     *
     * @param int $policyversionid Policy version ID
     * @param array $userids Optional list of user IDs
     * @return array Policy acceptance data (userid, status) for specified users and policy version
     */
    public static function get_policy_acceptance_data($policyversionid, $userids = []) {
        global $DB;

        $params = self::validate_parameters(
            self::get_policy_acceptance_data_parameters(),
            ['policyversionid' => $policyversionid, 'user_ids' => $userids]
        );

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('moodle/user:viewhiddendetails', $context);

        if (empty($userids)) {
            $rs = $DB->get_recordset('tool_policy_acceptances', ['policyversionid' => $policyversionid], '', 'userid, status');
        } else {
            $selector = implode(", ", array_column($params['user_ids'], 'id'));
            $rs = $DB->get_recordset_select(
                'tool_policy_acceptances',
                "policyversionid = :policyversionid AND userid IN ({$selector})",
                ['policyversionid' => $policyversionid]
            );
        }

        $data = [];
        foreach ($rs as $record) {
            $data[] = [
                'userid' => $record->userid,
                'status' => $record->status,
            ];
        }

        $rs->close();
        return $data;
    }

    /**
     * Returns description of get_policy_acceptance_data return values
     *
     * @return external_single_structure
     */
    public static function get_policy_acceptance_data_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    "userid" => new external_value(PARAM_INT, 'User ID'),
                    "status" => new external_value(PARAM_TEXT, 'Policy acceptance status'),
                ]
            )
        );
    }
}
