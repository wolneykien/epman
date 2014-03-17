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
 * for the education program assistant users.
 *
 * @package    tool
 * @subpackage epman
 * @copyright  2014 Paul Wolneykien <manowar@altlinux.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("base.php");
require_once("helpers.php");

class epman_program_assistant_external extends crud_external_api {

  /* Define the `list_assistants` implementation functions. */
  
  /**
   * Returns the description of the `list_program_assistants` method's
   * parameters.
   *
   * @return external_function_parameters
   */
  public static function list_program_assistants_parameters() {
    return new external_function_parameters(array(
      'programid' => new external_value(
        PARAM_INT,
        'Education program ID'),
    ));
  }

  /**
   * Returns the list of education program assistant users.
   *
   * @return array of education program modules
   */
    public static function list_program_assistants($programid) {
      global $DB;

      $params = self::validate_parameters(
        self::list_program_assistants_parameters(),
        array('programid' => $programid)
      );
      $programid = $params['programid'];

      program_exists($programid);

      $assistants = $DB->get_records_sql(
        'select u.id, u.username, '.
        'u.firstname, u.lastname, u.email, '.
        'pa.programid, pa.userid '.
        'from {tool_epman_program_assistant} pa '.
        'left join {user} u on u.id = pa.userid '
        'where programid = ? '.
        'order by lastname, firstname, username',
        array('programid' => $programid)
      );

      return array_map(
        function($assistant) {
          return (array) $assistant;
        },
        $assistants
      );
    }

    /**
     * Returns the description of the `list_program_assistants` method's
     * return value.
     *
     * @return external_description
     */
    public static function list_program_assistants_returns() {
      return new external_multiple_structure(
        new external_single_structure(array(
          'id' => new external_value(
            PARAM_INT,
            'ID of the assistant user'),
          'programid' => new external_value(
            PARAM_INT,
            'Education program ID'),
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
     * Returns the description of the `get_program_assistant` method's
     * parameters.
     *
     * @return external_function_parameters
     */
    public static function get_program_assistant_parameters() {
      return new external_function_parameters(array(
        'programid' => new external_value(
          PARAM_INT,
          'Education program ID'),
        'id' => new external_value(
          PARAM_INT,
          'The ID of the education program assistant user to get'),
    ));
  }

  /**
   * Returns the complete education program program_assistant's data.
   *
   * @return array (education program assistant user)
   */
    public static function get_program_assistant($programid, $id) {
      global $DB;

      $params = self::validate_parameters(
        self::get_program_assistant_parameters(),
        array('programid' => $programid, 'id' => $id)
      );
      $programid = $params['programid'];
      $id = $params['id'];

      program_exists($programid);

      if (program_assistant($programid, $id)) {
        $assistant = $DB->get_record('user', array('id' => $id));
        if ($assistant) {
          return array(
            'id' => $assistant->id,
            'programid' = $programid,
            'username' => $assistant->username,
            'firstname' => $assistant->firstname,
            'lastname' => $assistant->lastname,
            'email' => $assistant->email,
          );
        } else {
          return array(
            'id' => $id,
            'programid' = $programid,
          );
        }
      }
    }

    /**
     * Returns the description of the `get_program_assistant` method's
     * return value.
     *
     * @return external_description
     */
    public static function get_program_assistant_returns() {
      return new external_single_structure(array(
        'id' => new external_value(
          PARAM_INT,
           'ID of the assistant user'),
        'programid' => new external_value(
          PARAM_INT,
          'Education program ID'),
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


    /* Define the `add_program_assistant` implementation functions. */

    /**
     * Returns the description of the `add_program_assistant` method's
     * parameters.
     *
     * @return external_function_parameters
     */
    public static function add_program_assistant_parameters() {
      return new external_function_parameters(array(
        'programid' => new external_value(
          PARAM_INT,
          'Education program ID'),
        'model' => new external_single_structure(array(
          'userid' => new external_value(
            PARAM_INT,
            'User ID'),
        )),
      ));
    }

    /**
     * Adds the given user to the given education program
     * as an assistant.
     *
     * @return int new record ID
     */
    public static function add_program_assistant($programid, array $model) {
      global $DB, $USER;

      $params = self::validate_parameters(
        self::add_program_assistant_parameters(),
        array('programid' => $programid, 'model' => $model)
      );
      $programid = $params['programid'];
      $assistant = $params['model'];

      program_exists($programid);
      user_exists($assistant['userid']);

      if (!has_sys_capability('tool/epman:editprogram', $USER->id)) {
        if (!program_responsible($programid, $USER->id)) {
          throw new moodle_exception("You don't have right to modify the asistant user set of this education program");
        }
      }

      $assistant['programid'] = $programid;
      $DB->insert_record('tool_epman_program_assistant', $assistant);

      return self::get_program_assistant($programid, $assistant['userid']);
    }

    /**
     * Returns the description of the `add_program_assistant` method's
     * return value.
     *
     * @return external_description
     */
    public static function add_program_assistant_returns() {
      return self::get_program_assistant_returns();
    }

    
    /* Define the `delete_program_assistant` implementation functions. */

    /**
     * Returns the description of the `delete_program_assistant` method's
     * parameters.
     *
     * @return external_function_parameters
     */
    public static function delete_program_assistant_parameters() {
      return new external_function_parameters(array(
        'programid' => new external_value(
          PARAM_INT,
          'Education program ID'),
        'id' => new external_value(
          PARAM_INT,
          'Education program assistant user ID'),
      ));
    }

    /**
     * Removes the given user from the set of assistants of
     * the given education program.
     *
     * @return bool success flag
     */
    public static function delete_program_assistant($programid, $id) {
      global $USER, $DB;

      $params = self::validate_parameters(
        self::delete_program_assistant_parameters(),
        array('programid' => $programid, 'id' => $id)
      );
      $programid = $params['programid'];
      $id = $params['id'];

      program_exists($programid);

      if (program_assistant($programid, $id)) {
        if (!has_sys_capability('tool/epman:editprogram', $USER->id)) {
          if (!program_responsible($programid, $USER->id)) {
            throw new moodle_exception("You don't have right to modify the asistant user set of this education program");
          }
        }
        $DB->delete_record('tool_epman_program_assistant', array('programid' => $programid, 'userid' => $id));
        return true;
      } else {
        return false;
      }
    }

    /**
     * Returns the description of the `delete_program_assistant` method's
     * return value.
     *
     * @return external_description
     */
    public static function delete_program_assistant_returns() {
      return new external_value(
        PARAM_BOOL,
        'Successfull return flag'
      );
    }

}

?>
