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
 * Functions implementing the core web services of the education
 * process management module. This module defines CRUD functions
 * for the academic group student users.
 *
 * @package    tool
 * @subpackage epman
 * @copyright  2014 Paul Wolneykien <manowar@altlinux.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("base.php");
require_once("helpers.php");

class epman_group_student_external extends crud_external_api {

  /* Define the `list_students` implementation functions. */
  
  /**
   * Returns the description of the `list_group_students` method's
   * parameters.
   *
   * @return external_function_parameters
   */
  public static function list_group_students_parameters() {
    return new external_function_parameters(array(
      'groupid' => new external_value(
        PARAM_INT,
        'Academic group ID'),
    ));
  }

  /**
   * Returns the list of academic group student users.
   *
   * @return array of academic group modules
   */
    public static function list_group_students($groupid) {
      global $DB;

      $params = self::validate_parameters(
        self::list_group_students_parameters(),
        array('groupid' => $groupid)
      );
      $groupid = $params['groupid'];

      group_exists($groupid);

      $students = $DB->get_records_sql(
        'select gs.userid as id, u.username, '.
        'u.firstname, u.lastname, u.email, '.
        'gs.groupid, gs.userid, gs.period '.
        'from {tool_epman_group_student} gs '.
        'left join {user} u on u.id = ga.userid '
        'where gs.groupid = :groupid '.
        'order by lastname, firstname, username',
        array('groupid' => $groupid)
      );

      return array_map(
        function($student) {
          return (array) $student;
        },
        $students
      );
    }

    /**
     * Returns the description of the `list_group_students` method's
     * return value.
     *
     * @return external_description
     */
    public static function list_group_students_returns() {
      return new external_multiple_structure(
        new external_single_structure(array(
          'id' => new external_value(
            PARAM_INT,
            'ID of the student user'),
          'groupid' => new external_value(
            PARAM_INT,
            'Academic group ID'),
          'userid' => new external_value(
            PARAM_INT,
            'User ID'),
          'username' => new external_value(
            PARAM_TEXT,
            'System name of the student user',
            VALUE_OPTIONAL),
          'firstname' => new external_value(
            PARAM_TEXT,
            'First name of the student user',
            VALUE_OPTIONAL),
          'lastname' => new external_value(
            PARAM_TEXT,
            'Last name of the student user',
            VALUE_OPTIONAL),
          'email' => new external_value(
            PARAM_TEXT,
            'E-mail of the student user',
            VALUE_OPTIONAL),
          'period' => new external_value(
            PARAM_INT,
            'Education period number',
            VALUE_OPTIONAL),
        )));
    }


    /**
     * Returns the description of the `get_group_student` method's
     * parameters.
     *
     * @return external_function_parameters
     */
    public static function get_group_student_parameters() {
      return new external_function_parameters(array(
        'groupid' => new external_value(
          PARAM_INT,
          'Academic group ID'),
        'id' => new external_value(
          PARAM_INT,
          'The ID of the academic group student user to get'),
    ));
  }

  /**
   * Returns the complete academic group group_student's data.
   *
   * @return array (academic group student user)
   */
    public static function get_group_student($groupid, $id) {
      global $DB;

      $params = self::validate_parameters(
        self::get_group_student_parameters(),
        array('groupid' => $groupid, 'id' => $id)
      );
      $groupid = $params['groupid'];
      $id = $params['id'];

      group_exists($groupid);

      $student = $DB->get_record_sql(
          'select gs.userid as id, u.username, '.
          'u.firstname, u.lastname, u.email, '.
          'gs.groupid, gs.userid, gs.period '.
          'from {tool_epman_group_student} gs '.
          'left join {user} u on u.id = gs.userid '
          'where gs.userid = :userid',
          array('userid' => $id));

      return (array) $student;
    }

    /**
     * Returns the description of the `get_group_student` method's
     * return value.
     *
     * @return external_description
     */
    public static function get_group_student_returns() {
      return new external_single_structure(array(
        'id' => new external_value(
          PARAM_INT,
           'ID of the student user'),
        'groupid' => new external_value(
          PARAM_INT,
          'Academic group ID'),
        'userid' => new external_value(
          PARAM_INT,
          'User ID'),
        'username' => new external_value(
          PARAM_TEXT,
          'System name of the student user',
          VALUE_OPTIONAL),
        'firstname' => new external_value(
          PARAM_TEXT,
          'First name of the student user',
          VALUE_OPTIONAL),
        'lastname' => new external_value(
          PARAM_TEXT,
          'Last name of the student user',
          VALUE_OPTIONAL),
        'email' => new external_value(
          PARAM_TEXT,
          'E-mail of the student user',
          VALUE_OPTIONAL),
        'period' => new external_value(
          PARAM_INT,
          'Education period number',
          VALUE_OPTIONAL),
      ));
    }


    /* Define the `add_group_student` implementation functions. */

    /**
     * Returns the description of the `add_group_student` method's
     * parameters.
     *
     * @return external_function_parameters
     */
    public static function add_group_student_parameters() {
      return new external_function_parameters(array(
        'groupid' => new external_value(
          PARAM_INT,
          'Academic group ID'),
        'model' => new external_single_structure(array(
          'userid' => new external_value(
            PARAM_INT,
            'User ID',
            VALUE_OPTIONAL),
          'username' => new external_value(
            PARAM_TEXT,
            'System name of the student user',
            VALUE_OPTIONAL),
          'password' => new external_value(
            PARAM_TEXT,
            'User account password (for a new account only)',
            VALUE_OPTIONAL),
          'firstname' => new external_value(
            PARAM_TEXT,
            'First name of the student user',
            VALUE_OPTIONAL),
          'lastname' => new external_value(
            PARAM_TEXT,
            'Last name of the student user',
            VALUE_OPTIONAL),
          'email' => new external_value(
            PARAM_TEXT,
            'E-mail of the student user',
            VALUE_OPTIONAL),
          'period' => new external_value(
            PARAM_INT,
            'Education period number',
            VALUE_OPTIONAL),
        )),
      ));
    }

    /**
     * Adds the given user to the given academic group
     * as an student. Optionally creates a Moodle user account.
     *
     * @return array student
     */
    public static function add_group_student($groupid, array $model) {
      global $DB, $USER;

      $params = self::validate_parameters(
        self::add_group_student_parameters(),
        array('groupid' => $groupid, 'model' => $model)
      );
      $groupid = $params['groupid'];
      $student = $params['model'];

      group_exists($groupid);

      if (!isset($student['userid'])) {
        if (isset($student['username']) &&
            isset($student['password'])) {
          $student['userid'] = create_moodle_user($student);
        } else {
          throw new invalid_parameter_exception("In order to register a new user account you need to specify its username and password values");
        }
      }

      user_exists($student['userid']);

      if (!has_sys_capability('tool/epman:editgroup', $USER->id)) {
        if (!group_responsible($groupid, $USER->id)) {
          if (!group_assistant($groupid, $USER->id)) {
            throw new moodle_exception("You don't have right to modify the student user set of this academic group");
          }
        }
      }

      $student['groupid'] = $groupid;
      $DB->insert_record('tool_epman_group_student', $student);

      return self::get_group_student($groupid, $student['userid']);
    }

    /**
     * Returns the description of the `add_group_student` method's
     * return value.
     *
     * @return external_description
     */
    public static function add_group_student_returns() {
      return self::get_group_student_returns();
    }


    /* Define the `update_group_student` implementation functions. */

    /**
     * Returns the description of the `update_group_student` method's
     * parameters.
     *
     * @return external_function_parameters
     */
    public static function update_group_student_parameters() {
      return new external_function_parameters(array(
        'groupid' => new external_value(
          PARAM_INT,
          'Academic group ID'),
        'model' => new external_single_structure(array(
          'period' => new external_value(
            PARAM_INT,
            'Education period number',
            VALUE_OPTIONAL),
        )),
      ));
    }

    /**
     * Updates the membership info for the given student user.
     *
     * @return array student (updated fields)
     */
    public static function update_group_student($groupid, array $model) {
      global $DB, $USER;

      $params = self::validate_parameters(
        self::update_group_student_parameters(),
        array('groupid' => $groupid, 'model' => $model)
      );
      $groupid = $params['groupid'];
      $student = $params['model'];

      group_exists($groupid);
      user_exists($student['userid']);

      $student['groupid'] = $groupid;
      $student0 = $DB->get_record('tool_epman_group_student', array('groupid' => $student['groupid'], 'userid' => $student['userid']));
      if ($student0) {
        $student0 = (array) $student0;
      }

      if (!has_sys_capability('tool/epman:editgroup', $USER->id)) {
        if (!group_responsible($groupid, $USER->id)) {
          if (!group_assistant($groupid, $USER->id)) {
            throw new moodle_exception("You don't have right to modify the student user set of this academic group");
          }
        }
      }

      $DB->update_record('tool_epman_group_student', $student);

      return $student;
    }

    /**
     * Returns the description of the `update_group_student` method's
     * return value.
     *
     * @return external_description
     */
    public static function update_group_student_returns() {
      return new external_single_structure(array(
        'period' => new external_value(
          PARAM_INT,
          'Education period number',
          VALUE_OPTIONAL),
      ));
    }

    
    /* Define the `delete_group_student` implementation functions. */

    /**
     * Returns the description of the `delete_group_student` method's
     * parameters.
     *
     * @return external_function_parameters
     */
    public static function delete_group_student_parameters() {
      return new external_function_parameters(array(
        'groupid' => new external_value(
          PARAM_INT,
          'Academic group ID'),
        'id' => new external_value(
          PARAM_INT,
          'Academic group student user ID'),
      ));
    }

    /**
     * Removes the given user from the set of students of
     * the given academic group.
     *
     * @return bool success flag
     */
    public static function delete_group_student($groupid, $id) {
      global $USER, $DB;

      $params = self::validate_parameters(
        self::delete_group_student_parameters(),
        array('groupid' => $groupid, 'id' => $id)
      );
      $groupid = $params['groupid'];
      $id = $params['id'];

      group_exists($groupid);

      if (group_student($groupid, $id)) {
        if (!has_sys_capability('tool/epman:editgroup', $USER->id)) {
          if (!group_responsible($groupid, $USER->id)) {
            if (!group_assistant($groupid, $USER->id)) {
              throw new moodle_exception("You don't have right to modify the asistant user set of this academic group");
            }
          }
        }
        $DB->delete_records('tool_epman_group_student', array('groupid' => $groupid, 'userid' => $id));
        return true;
      } else {
        return false;
      }
    }

    /**
     * Returns the description of the `delete_group_student` method's
     * return value.
     *
     * @return external_description
     */
    public static function delete_group_student_returns() {
      return new external_value(
        PARAM_BOOL,
        'Successfull return flag'
      );
    }

}

?>
