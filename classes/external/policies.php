<?php
namespace local_raisecli\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/questionlib.php');

use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_value;
use external_single_structure;

class policies extends external_api {

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
    public static function get_policy_acceptance_data_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    "userid" => new external_value(PARAM_INT, 'User ID'),
                    "status" => new external_value(PARAM_TEXT, 'Policy acceptance status')
                ]
            )
        );
    }
}
