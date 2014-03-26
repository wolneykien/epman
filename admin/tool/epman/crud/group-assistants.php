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
 * for the academic group assistant users.
 *
 * @package    tool
 * @subpackage epman
 * @copyright  2014 Paul Wolneykien <manowar@altlinux.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("base.php");
require_once("helpers.php");

class epman_group_assistant_external extends crud_external_api {

  /* Define the `list_assistants` implementation functions. */
  
  /**
   * Returns the description of the `list_group_assistants` method's
   * parameters.
   *
   * @return external_function_parameters
   */
  public static function list_group_assistants_parameters() {
    return new external_function_parameters(array(
      'groupid' => new external_value(
        PARAM_INT,
        'Academic group ID'),
    ));
  }

  /**
   * Returns the list of academic group assistant users.
   *
   * @return array of academic group modules
   */
    public static function list_group_assistants($groupid) {
      global $DB;

      $params = self::validate_parameters(
        self::list_group_assistants_parameters(),
        array('groupid' => $groupid)
      );
      $groupid = $params['groupid'];

      group_exists($groupid);

      $assistants = $DB->get_records_sql(
        'select ga.userid as id, u.username, '.
        'u.firstname, u.lastname, u.email, '.
        'ga.groupid, ga.userid '.
        'from {tool_epman_group_assistant} ga '.
        'left join {user} u on u.id = ga.userid '
        'where ga.groupid = :groupid '.
        'order by lastname, firstname, username',
        array('groupid' => $groupid)
      );

      return array_map(
        function($assistant) {
          return (array) $assistant;
        },
        $assistants
      );
    }

    /**
     * Returns the description of the `list_group_assistants` method's
     * return value.
     *
     * @return external_description
     */
    public static function list_group_assistants_returns() {
      return new external_multiple_structure(
        new external_single_structure(array(
          'id' => new external_value(
            PARAM_INT,
            'ID of the assistant user'),
          'groupid' => new external_value(
            PARAM_INT,
            'Academic group ID'),
          'userid' => new external_value(
            PARAM_INT,
            'User ID'),
          'username' => new external_value(
            PARAM_TEXT,
            'System name of the assistant user',
            VALUE_OPTIONAL),
          'firstname' => new external_value(
            PARAM_TEXT,
            'First name of the assistant user',
            VALUE_OPTIONAL),
          'lastname' => new external_value(
            PARAM_TEXT,
            'Last name of the assistant user',
            VALUE_OPTIONAL),
          'email' => new external_value(
            PARAM_TEXT,
            'E-mail of the assistant user',
            VALUE_OPTIONAL),
        )));
    }


    /**
     * Returns the description of the `get_group_assistant` method's
     * parameters.
     *
     * @return external_function_parameters
     */
    public static function get_group_assistant_parameters() {
      return new external_function_parameters(array(
        'groupid' => new external_value(
          PARAM_INT,
          'Academic group ID'),
        'id' => new external_value(
          PARAM_INT,
          'The ID of the academic group assistant user to get'),
    ));
  }

  /**
   * Returns the complete academic group group_assistant's data.
   *
   * @return array (academic group assistant user)
   */
    public static function get_group_assistant($groupid, $id) {
      global $DB;

      $params = self::validate_parameters(
        self::get_group_assistant_parameters(),
        array('groupid' => $groupid, 'id' => $id)
      );
      $groupid = $params['groupid'];
      $id = $params['id'];

      group_exists($groupid);

      $assistant = $DB->get_record_sql(
          'select ga.userid as id, u.username, '.
          'u.firstname, u.lastname, u.email, '.
          'ga.groupid, ga.userid, ga.period '.
          'from {tool_epman_group_assistant} ga '.
          'left join {user} u on u.id = ga.userid '
          'where ga.userid = :userid',
          array('userid' => $id));

      return (array) $assistant;
    }

    /**
     * Returns the description of the `get_group_assistant` method's
     * return value.
     *
     * @return external_description
     */
    public static function get_group_assistant_returns() {
      return new external_single_structure(array(
        'id' => new external_value(
          PARAM_INT,
           'ID of the assistant user'),
        'groupid' => new external_value(
          PARAM_INT,
          'Academic group ID'),
        'userid' => new external_value(
          PARAM_INT,
          'User ID'),
        'username' => new external_value(
          PARAM_TEXT,
          'System name of the assistant user',
          VALUE_OPTIONAL),
        'firstname' => new external_value(
          PARAM_TEXT,
          'First name of the assistant user',
          VALUE_OPTIONAL),
        'lastname' => new external_value(
          PARAM_TEXT,
          'Last name of the assistant user',
          VALUE_OPTIONAL),
        'email' => new external_value(
          PARAM_TEXT,
          'E-mail of the assistant user',
          VALUE_OPTIONAL),
      ));
    }


    /* Define the `add_group_assistant` implementation functions. */

    /**
     * Returns the description of the `add_group_assistant` method's
     * parameters.
     *
     * @return external_function_parameters
     */
    public static function add_group_assistant_parameters() {
      return new external_function_parameters(array(
        'groupid' => new external_value(
          PARAM_INT,
          'Academic group ID'),
        'model' => new external_single_structure(array(
          'userid' => new external_value(
            PARAM_INT,
            'User ID'),
        )),
      ));
    }

    /**
     * Adds the given user to the given academic group
     * as an assistant.
     *
     * @return array assistant user
     */
    public static function add_group_assistant($groupid, array $model) {
      global $DB, $USER;

      $params = self::validate_parameters(
        self::add_group_assistant_parameters(),
        array('groupid' => $groupid, 'model' => $model)
      );
      $groupid = $params['groupid'];
      $assistant = $params['model'];

      group_exists($groupid);
      user_exists($assistant['userid']);

      if (!has_sys_capability('tool/epman:editgroup', $USER->id)) {
        if (!group_responsible($groupid, $USER->id)) {
          throw new moodle_exception("You don't have right to modify the assistant user set of this academic group");
        }
      }

      $assistant['groupid'] = $groupid;
      $DB->insert_record('tool_epman_group_assistant', $assistant);

      return self::get_group_assistant($groupid, $assistant['userid']);
    }

    /**
     * Returns the description of the `add_group_assistant` method's
     * return value.
     *
     * @return external_description
     */
    public static function add_group_assistant_returns() {
      return self::get_group_assistant_returns();
    }

    
    /* Define the `delete_group_assistant` implementation functions. */

    /**
     * Returns the description of the `delete_group_assistant` method's
     * parameters.
     *
     * @return external_function_parameters
     */
    public static function delete_group_assistant_parameters() {
      return new external_function_parameters(array(
        'groupid' => new external_value(
          PARAM_INT,
          'Academic group ID'),
        'id' => new external_value(
          PARAM_INT,
          'Academic group assistant user ID'),
      ));
    }

    /**
     * Removes the given user from the set of assistants of
     * the given academic group.
     *
     * @return bool success flag
     */
    public static function delete_group_assistant($groupid, $id) {
      global $USER, $DB;

      $params = self::validate_parameters(
        self::delete_group_assistant_parameters(),
        array('groupid' => $groupid, 'id' => $id)
      );
      $groupid = $params['groupid'];
      $id = $params['id'];

      group_exists($groupid);

      if (group_assistant($groupid, $id)) {
        if (!has_sys_capability('tool/epman:editgroup', $USER->id)) {
          if (!group_responsible($groupid, $USER->id)) {
            throw new moodle_exception("You don't have right to modify the asistant user set of this academic group");
          }
        }
        $DB->delete_records('tool_epman_group_assistant', array('groupid' => $groupid, 'userid' => $id));
        return true;
      } else {
        return false;
      }
    }

    /**
     * Returns the description of the `delete_group_assistant` method's
     * return value.
     *
     * @return external_description
     */
    public static function delete_group_assistant_returns() {
      return new external_value(
        PARAM_BOOL,
        'Successfull return flag'
      );
    }

}

?>
