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
 * External functions and service definitions.
 *
 * @package    local_raisecli
 * @copyright  2021 OpenStax
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$services = array(
    'RAISE CLI web service' => array(
        'enabled' => 0,
        'shortname' => 'local_raisecli_service',
        'functions' => array(
            'core_course_import_course',
            'core_course_duplicate_course',
            'core_course_get_courses',
            'core_enrol_get_enrolled_users',
            'core_user_create_users',
            'core_user_get_users',
            'enrol_manual_enrol_users',
            'gradereport_user_get_grade_items',
            'local_raisecli_enable_self_enrolment_method',
            'local_raisecli_get_role_by_shortname',
            'local_raisecli_get_self_enrolment_methods',
            'local_raisecli_set_self_enrolment_method_key',
            'local_raisecli_get_user_uuids'
        )
    )
);

$functions = array(
    'local_raisecli_enable_self_enrolment_method' => array(
        'classname'    => 'enrolment',
        'methodname'   => 'enable_self_enrolment_method',
        'description'  => 'Enable self enrolment method',
        'type'         => 'write',
        'capabilities' => 'enrol/self:config'
    ),
    'local_raisecli_get_role_by_shortname' => array(
        'classname'    => 'role',
        'methodname'   => 'get_role_by_shortname',
        'description'  => 'Get role information by shortname',
        'type'         => 'read',
        'capabilities' => 'moodle/role:manage'
    ),
    'local_raisecli_get_self_enrolment_methods' => array(
        'classname'    => 'enrolment',
        'methodname'   => 'get_self_enrolment_methods',
        'description'  => 'Return self-enrolment methods for a course and role',
        'type'         => 'read',
        'capabilities' => ''
    ),
    'local_raisecli_set_self_enrolment_method_key' => array(
        'classname'    => 'enrolment',
        'methodname'   => 'set_self_enrolment_method_key',
        'description'  => 'Set key for self enrolment method',
        'type'         => 'write',
        'capabilities' => 'enrol/self:config'
    ),
    'local_raisecli_get_user_uuids' => array(
        'classname'    => 'uuid',
        'methodname'   => 'get_user_uuids',
        'description'  => 'Get the uuids associated with a series of user ids',
        'type'         => 'read',
    )
);
