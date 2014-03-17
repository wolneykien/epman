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
 * for the academic (student) groups.
 *
 * @package    tool
 * @subpackage epman
 * @copyright  2014 Paul Wolneykien <manowar@altlinux.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("base.php");
require_once("helpers.php");

class epman_group_external extends crud_external_api {

  /* Define the `list_groups` implementation functions. */
  
  /**
   * Returns the description of the `list_groups` method's
   * parameters.
   *
   * @return external_function_parameters
   */
  public static function list_groups_parameters() {
    return new external_function_parameters(array(
      'userid' => new external_value(
        PARAM_INT,
        'Output only the groups editable by the given user (id)',
        VALUE_DEFAULT,
        0),
      'programid' => new external_value(
        PARAM_INT,
        'Output only the groups studying the given education program (id)',
        VALUE_DEFAULT,
        0),
    ));
  }

  /**
   * Returns the list of education groups.
   *
   * @return array of education groups
   */
  public static function list_groups($userid, $programid) {
      global $DB;

      $params = self::validate_parameters(
        self::list_groups_parameters(),
        array('userid' => $userid)
      );
      $userid = $params['userid'];

      if ($userid) {
        $groups = $DB->get_records_sql(
            'select g.id, '.
            'max(g.name) as name, '.
            'max(g.programid) as programid, '.
            'max(p.name) as programname, '.
            'max(p.year) as year, '.
            'max(p.responsibleid) as responsibleid, '.
            'max(u.username) as username, '.
            'max(u.firstname) as firstname, '.
            'max(u.lastname) as lastname, '.
            'max(u.email) as email, '.
            'from {tool_epman_group} g '.
            'left join {tool_epman_group_assistant} ga '.
            'on ga.programid = g.id '.
            'left join {tool_epman_program} p '.
            'on p.id = g.programid '.
            'left join {user} u '.
            'on u.id = g.responsibleid '.
            'where g.responsibleid = ? or ga.userid = ? '.
            ($programid ? ' and g.programid = ? ' : '').
            'group by p.id '.
            'order by year, name',
            array($userid, $userid, $programid));
      } else {
        $groups = $DB->get_records(
            'select p.*, u.username, '.
            'u.firstname, u.lastname, u.email, '.
            'from {tool_epman_group} g '.
            'left join {tool_epman_program} p '.
            'on p.id = g.programid '.
            'left join {user} u '.
            'on u.id = g.responsibleid '.
            ($programid ? 'where g.programid = ? ' : '').
            'order by year, name',
            array($programid));
      }

      return array_map(
        function($group) {
          $group = (array) $group;
          if ($group['programid']) {
            $group['program'] = array(
              'id' => $group['programid'],
              'name' => $group['programname'],
            );
          } else {
            $group['program'] = null;
          }
          unset($group['programid']);
          $group['responsible'] = array(
            'id' => $group['responsibleid'],
            'username' => $group['username'],
            'firstname' => $group['firstname'],
            'lastname' => $group['lastname'],
            'email' => $group['email'],
          );
          unset($group['responsibleid']);
          return $group;
        },
        $groups
      );
    }

    /**
     * Returns the description of the `list_groups` method's
     * return value.
     *
     * @return external_description
     */
    public static function list_groups_returns() {
      return new external_multiple_structure(
        new external_single_structure(array(
          'id' => new external_value(
            PARAM_INT,
            'Academic group ID'),
          'name' => new external_value(
            PARAM_TEXT,
            'Academic group name'),
          'program' => new external_single_structure(array(
            'id' => new external_value(
              PARAM_INT,
              'Education program ID'),
            'name' => new external_value(
              PARAM_TEXT,
              'Education program name'),
          ), VALUE_OPTIONAL),
          'year' => new external_value(
            PARAM_INT,
            'Actual learning year'),
          'responsible' => new external_single_structure(array(
            'id' => new external_value(
              PARAM_INT,
              'ID of the responsible user'),
            'username' => new external_value(
              PARAM_TEXT,
              'System name of the responsible user',
              VALUE_OPTIONAL),
            'firstname' => new external_value(
              PARAM_TEXT,
              'First name of the responsible user',
              VALUE_OPTIONAL),
            'lastname' => new external_value(
              PARAM_TEXT,
              'Last name of the responsible user',
              VALUE_OPTIONAL),
            'email' => new external_value(
              PARAM_TEXT,
              'E-mail of the responsible user',
              VALUE_OPTIONAL),
          )),
        )));
    }


    /**
     * Returns the description of the `get_group` method's
     * parameters.
     *
     * @return external_function_parameters
     */
    public static function get_group_parameters() {
      return new external_function_parameters(array(
        'id' => new external_value(
          PARAM_INT,
          'The ID of the academic group to get'),
    ));
  }

  /**
   * Returns the complete academic group's data.
   *
   * @return array (academic group)
   */
    public static function get_group($id) {
      global $DB;

      $params = self::validate_parameters(
        self::get_group_parameters(),
        array('id' => $id)
      );
      $id = $params['id'];

      group_exists($id);

      $group = $DB->get_record('tool_epman_group', array('id' => $id));
      if ($group) {
        $group = (array) $group;
      }

      $responsible = $DB->get_record('user', array('id' => $group['responsible']['id']));
      if ($responsible) {
        $group['responsible'] = array(
          'id' => $responsible->id,
          'username' => $responsible->username,
          'firstname' => $responsible->firstname,
          'lastname' => $responsible->lastname,
          'email' => $responsible->email);
      }

      $students = $DB->get_records_sql(
        'select g.id, gs.userid, u.username, '.
        'u.firstname, u.lastname, u.email '.
        'from {tool_epman_group} g left join '.
        '{tool_epman_group_student} gs '.
        'on gs.groupid = g.id '.
        'left join {user} u on u.id = gs.userid '.
        'where g.id = ? and ga.userid is not null '.
        'order by u.username',
        array('id' => $id));

      $group['students'] = array();
      foreach ($students as $rec) {
        $group['students'][] = array(
          'id' => $rec->userid,
          'username' => $rec->username,
          'firstname' => $rec->firstname,
          'lastname' => $rec->lastname,
          'email' => $rec->email);
      }

      $assistants = $DB->get_records_sql(
        'select g.id, ga.userid, u.username, '.
        'u.firstname, u.lastname, u.email '.
        'from {tool_epman_group} g left join '.
        '{tool_epman_group_assistant} ga '.
        'on ga.groupid = g.id '.
        'left join {user} u on u.id = ga.userid '.
        'where g.id = ? and ga.userid is not null '.
        'order by u.username',
        array('id' => $id));

      $group['assistants'] = array();
      foreach ($assistants as $rec) {
        $group['assistants'][] = array(
          'id' => $rec->userid,
          'username' => $rec->username,
          'firstname' => $rec->firstname,
          'lastname' => $rec->lastname,
          'email' => $rec->email);
      }

      return $group;
    }

    /**
     * Returns the description of the `get_group` method's
     * return value.
     *
     * @return external_description
     */
    public static function get_group_returns() {
      return new external_single_structure(array(
        'id' => new external_value(
          PARAM_INT,
          'Academic group ID'),
        'name' => new external_value(
          PARAM_TEXT,
          'Academic group name'),
        'program' => new external_single_structure(array(
          'id' => new external_value(
            PARAM_INT,
            'Education program ID'),
          'name' => new external_value(
            PARAM_TEXT,
            'Education program name'),
        ), VALUE_OPTIONAL),
        'year' => new external_value(
          PARAM_INT,
          'Actual learning year'),
        'responsible' => new external_single_structure(array(
          'id' => new external_value(
            PARAM_INT,
            'ID of the responsible user'),
          'username' => new external_value(
            PARAM_TEXT,
            'System name of the responsible user',
            VALUE_OPTIONAL),
          'firstname' => new external_value(
            PARAM_TEXT,
            'First name of the responsible user',
            VALUE_OPTIONAL),
          'lastname' => new external_value(
            PARAM_TEXT,
            'Last name of the responsible user',
            VALUE_OPTIONAL),
          'email' => new external_value(
            PARAM_TEXT,
            'E-mail of the responsible user',
            VALUE_OPTIONAL),
        )),
        'students' => new external_multiple_structure(
          new external_single_structure(array(
            'id' => new external_value(
              PARAM_INT,
              'ID of the student user'),
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
          ))
        ),
        'assistants' => new external_multiple_structure(
          new external_single_structure(array(
            'id' => new external_value(
              PARAM_INT,
              'ID of the assistant user'),
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
          ))
        ),
      ));
    }


    /* Define the `create_group` implementation functions. */

    /**
     * Returns the description of the `create_group` method's
     * parameters.
     *
     * @return external_function_parameters
     */
    public static function create_group_parameters() {
      global $USER;

      return new external_function_parameters(array(
        'model' => new external_single_structure(array(
          'name' => new external_value(
            PARAM_TEXT,
            'Academic group name'),
          'programid' => new external_value(
            PARAM_INT,
            'Education program ID',
            VALUE_OPTIONAL),
          'year' => new external_value(
            PARAM_INT,
            'Actual learning year',
            VALUE_DEFAULT,
            0),
          'responsibleid' => new external_value(
            PARAM_INT,
            'ID of the responsible user',
            VALUE_DEFAULT,
            $USER->id),
        )),
      ));
    }

    /**
     * Creates a new education program.
     *
     * @return int new program ID
     */
    public static function create_group(array $model) {
      global $USER, $DB;

      $params = self::validate_parameters(
        self::create_group_parameters(),
        array('model' => $model)
      );
      $group = $params['model'];

      program_exists($group['programid']);
      user_exists($group['responsibleid']);

      $group['id'] = $DB->insert_record('tool_epman_group', $group);

      return $group;
    }

    /**
     * Returns the description of the `create_group` method's
     * return value.
     *
     * @return external_description
     */
    public static function create_group_returns() {
      return new external_single_structure(array(
        'id' => new external_value(
          PARAM_INT,
          'Academic group ID'),
        'name' => new external_value(
          PARAM_TEXT,
          'Academic group name'),
        'programid' => new external_value(
          PARAM_INT,
          'Education program ID',
          VALUE_OPTIONAL),
        'responsibleid' => new external_value(
          PARAM_INT,
          'ID of the responsible user'),
      ));
    }


    /* Define the `update_group` implementation functions. */

    /**
     * Returns the description of the `update_group` method's
     * parameters.
     *
     * @return external_function_parameters
     */
    public static function update_group_parameters() {
      return new external_function_parameters(array(
        'id' => new external_value(
          PARAM_INT,
          'Education program ID'),
        'model' => new external_single_structure(array(
          'name' => new external_value(
            PARAM_TEXT,
            'Academic group name',
            VALUE_OPTIONAL),
          'programid' => new external_value(
            PARAM_INT,
            'Education program ID',
            VALUE_OPTIONAL),
          'year' => new external_value(
            PARAM_INT,
            'Actual learning year',
            VALUE_OPTIONAL),
          'responsibleid' => new external_value(
            PARAM_INT,
            'ID of the responsible user',
            VALUE_OPTIONAL),
        )),
      ));
    }

    /**
     * Updates the education program with the given ID.
     *
     * @return boolean success flag
     */
    public static function update_group($id, array $model) {
      global $USER, $DB;

      $params = self::validate_parameters(
        self::update_group_parameters(),
        array('id' => $id, 'model' => $model)
      );
      $id = $params['id'];
      $group = $params['model'];
      $group['id'] = $id;

      group_exists($id);
      $group0 = $DB->get_record('tool_epman_group', array('id' => $id));

      if (!has_sys_capability('tool/epman:editgroup', $USER->id)) {
        value_unchanged($group0, $group, 'responsibleid', 'responsible user of this academic group');
        if (!group_responsible($id, $USER->id)) {
          value_unchanged($group0, $group, 'name', 'name of this academic group');
          value_unchanged($group0, $group, 'programid', 'education program of this academic group');
          value_unchanged($group0, $group, 'year', 'year of this academic group');
          if (!group_assistant($id, $USER->id)) {
            throw new moodle_exception("You don't have right to modify this academic group");
          }
        }
      } else {
        if (isset($group['responsibleid'])) {
          user_exists($group['responsibleid']);
        }
      }

      $DB->update_record('tool_epman_group', $group);

      return $group;
    }

    /**
     * Returns the description of the `update_group` method's
     * return value.
     *
     * @return external_description
     */
    public static function update_group_returns() {
      return new external_single_structure(array(
        'name' => new external_value(
          PARAM_TEXT,
          'Academic group name',
          VALUE_OPTIONAL),
        'programid' => new external_value(
          PARAM_INT,
          'Education program ID',
          VALUE_OPTIONAL),
        'year' => new external_value(
          PARAM_INT,
          'Acutual learning year',
          VALUE_OPTIONAL),
        'responsibleid' => new external_value(
          PARAM_INT,
          'ID of the responsible user',
          VALUE_OPTIONAL),
      ));
    }

    
    /* Define the `delete_group` implementation functions. */

    /**
     * Returns the description of the `delete_group` method's
     * parameters.
     *
     * @return external_function_parameters
     */
    public static function delete_group_parameters() {
      global $USER;

      return new external_function_parameters(array(
        'id' => new external_value(
          PARAM_INT,
          'Academic group ID'),
      ));
    }

    /**
     * Deletes a new education program.
     *
     * @return bool successful result flag
     */
    public static function delete_group($id) {
      global $USER, $DB;

      $params = self::validate_parameters(
        self::delete_group_parameters(),
        array('id' => $id)
      );
      $id = $params['id'];

      if (!has_sys_capability('tool/epman:editgroup', $USER->id) &&
          !group_responsible($id, $USER->id)) {
        throw new moodle_exception("You don't have right to delete this academic group");
      }

      group_exists($id);
      clear_group_students($id);
      clear_group_assistants($id);
      $DB->delete_record('tool_epman_group', array('id' => $id));
      
      return true;
    }

    /**
     * Returns the description of the `delete_group` method's
     * return value.
     *
     * @return external_description
     */
    public static function delete_group_returns() {
      return new external_value(
        PARAM_BOOL,
        'Successfull return flag'
      );
    }

}

?>
