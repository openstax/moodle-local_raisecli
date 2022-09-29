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
namespace local_raisecli;

/**
 * CLI utility functions
 *
 * @package    local_raisecli
 * @copyright  2022 OpenStax
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Utility function to query / set a user UUID for RAISE
 *
 * @return string A new or existing user UUID
 */
class user_table_helper {

    public static function get_user_table_entries($user_ids) {
        global $USER, $DB;

        if(count($user_ids) == 0){
            $records = $DB->get_records('local_raise_user', array(), '', 'user_id, user_uuid');
            return $records;
        } else {
            $selector = implode(", ", array_column($user_ids, 'id'));
            $records = $DB->get_records_select(
                'local_raise_user',
                "user_id IN ({$selector})"
            );
            return $records;
        }
    }
}
