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
        VALUE_OPTIONAL),
      'year' => new external_value(
        PARAM_INT,
        'Output only the groups studying within the given academic year',
        VALUE_OPTIONAL),
      'yeargroupid' => new external_value(
        PARAM_INT,
        'Output only the same-year groups, corresponding to the year value of the given group (id)',
        VALUE_OPTIONAL),
      'like' => new external_value(
        PARAM_TEXT,
        'Matching pattern',
        VALUE_OPTIONAL),
      'skip' => new external_value(
        PARAM_INT,
        'Skip that number of records',
        VALUE_DEFAULT,
        0),
      'limit' => new external_value(
        PARAM_INT,
        'Limit the number of selected records',
        VALUE_OPTIONAL),
    ));
  }

  /**
   * Returns the list of education groups.
   *
   * @return array of education groups
   */
  public static function list_groups($userid = null, $programid = null, $year = 0, $yeargroupid = null, $like = null, $skip = 0, $limit = null) {
      global $DB;

      $params = self::validate_parameters(
        self::list_groups_parameters(),
        array('userid' => $userid, 'programid' => $programid, 'year' => $year, 'yeargroupid' => $yeargroupid, 'like' => $like, 'skip' => $skip, 'limit' => $limit)
      );
      $userid = $params['userid'];
      $programid = $params['programid'];
      $year = $params['year'];
      $yeargroupid = $params['yeargroupid'];
      $like = $params['like'];
      $skip = $params['skip'];
      $limit = $params['limit'];

      if ($like) {
        $like = "%".preg_replace('/\s+/', '%', $like)."%";
      }

      if ($yeargroupid) {
        $yeargroup = $DB->get_record('tool_epman_group', array('id' => $yeargroupid));
        if ($yeargroup && $yeargroup->year) {
          $year = $yeargroup->year;
        } else {
          return array();
        }
      }

      if ($userid) {
        $groups = $DB->get_records_sql(
            'select g.id, '.
            'max(g.name) as name, '.
            'max(g.year) as year, '.
            'max(g.programid) as programid, '.
            'max(g.responsibleid) as responsibleid, '.
            'max(p.name) as programname, '.
            'max(p.year) as programyear, '.
            'max(u.username) as username, '.
            'max(u.firstname) as firstname, '.
            'max(u.lastname) as lastname, '.
            'max(u.email) as email '.
            'from {tool_epman_group} g '.
            'left join {tool_epman_group_assistant} ga '.
            'on ga.groupid = g.id '.
            'left join {tool_epman_program} p '.
            'on p.id = g.programid '.
            'left join {user} u '.
            'on u.id = g.responsibleid '.
            'where g.responsibleid = :userid1 or ga.userid = :userid2'.
            ($programid ? ' and g.programid = :programid' : '').
            ($year ? ' and g.year = :year' : '').
            ($like ? ' and '.$DB->sql_like('g.name', ':like', false) : '').
            ' group by p.id '.
            'order by year, name',
            array_merge(array('userid1' => $userid, 'userid2' => $userid),
                        ($programid ? array('programid' => $programid) : array()),
                        ($year ? array('year' => $year) : array()),
                        ($like ? array('like' => $like) : array())),
            $skip,
            $limit);
      } else {
        $where = array_merge(($programid ? array('g.programid = :programid') : array()),
                             ($year ? array('g.year = :year') : array()),
                             ($like ? array($DB->sql_like('g.name', ':like', false)) : array()));
        $groups = $DB->get_records_sql(
            'select g.id, g.name, g.year, '.
            'g.programid as programid, '.
            'p.name as programname, '.
            'p.year as programyear, '.
            'g.responsibleid as responsibleid, '.
            'u.username, u.firstname, u.lastname, u.email '.
            'from {tool_epman_group} g '.
            'left join {tool_epman_program} p '.
            'on p.id = g.programid '.
            'left join {user} u '.
            'on u.id = g.responsibleid'.
            (!empty($where) ? ' where '.implode(' and ', $where) : '').
            ' order by year, name',
            array_merge(($programid ? array('programid' => $programid) : array()),
                        ($year ? array('year' => $year) : array()),
                        ($like ? array('like' => $like) : array())),
            $skip,
            $limit);
      }

      return array_map(
        function($group) {
          $group = (array) $group;
          if ($group['programid']) {
            $group['program'] = array(
              'id' => $group['programid'],
              'name' => $group['programname'],
              //              'year' => $group['programyear'],
            );
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

      if ($group['programid']) {
        $program = $DB->get_record('tool_epman_program', array('id' => $group['programid']));
        if ($program) {
          $group['program'] = array(
            'id' => $program->id,
            'name' => $program->name,
            'year' => $program->year,
            'periods' => $DB->get_fieldset_sql('select distinct period from {tool_epman_module} where programid = ? and period is not null order by period', array($program->id)),
          );
        }
      }

      if ($group['responsibleid']) {
        $responsible = $DB->get_record('user', array('id' => $group['responsibleid']));
        if ($responsible) {
          $group['responsible'] = array(
            'id' => $responsible->id,
            'username' => $responsible->username,
            'firstname' => $responsible->firstname,
            'lastname' => $responsible->lastname,
            'email' => $responsible->email);
        }
      }

      $students = $DB->get_records_sql(
        'select gs.id, g.id as groupid, gs.userid, u.username, '.
        'u.firstname, u.lastname, u.email '.
        'from {tool_epman_group} g left join '.
        '{tool_epman_group_student} gs '.
        'on gs.groupid = g.id '.
        'left join {user} u on u.id = gs.userid '.
        'where g.id = :id and gs.userid is not null '.
        'order by gs.period, u.lastname',
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
        'select ga.id, g.id as groupid, ga.userid, u.username, '.
        'u.firstname, u.lastname, u.email '.
        'from {tool_epman_group} g left join '.
        '{tool_epman_group_assistant} ga '.
        'on ga.groupid = g.id '.
        'left join {user} u on u.id = ga.userid '.
        'where g.id = :id and ga.userid is not null '.
        'order by u.lastname',
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
          'year' => new external_value(
            PARAM_TEXT,
            'Education program year'),
          'periods' => new external_multiple_structure(
            new external_value(
              PARAM_INT,
              'Education program period number'),
            'Array of the education program period numbers'),
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
          'program' => new external_value(
            PARAM_INT,
            'Education program ID'),
          'year' => new external_value(
            PARAM_INT,
            'Actual learning year',
            VALUE_DEFAULT,
            0),
          'responsible' => new external_value(
            PARAM_INT,
            'ID of the responsible user',
            VALUE_DEFAULT,
            $USER->id),
          'assistants' => new external_multiple_structure(
            new external_value(
              PARAM_INT,
              'ID of an assistant user'
            ),
            'Array of the assistant user IDs',
            VALUE_OPTIONAL
          ),
          'students' => new external_multiple_structure(
            new external_value(
              PARAM_INT,
              'ID of a student user'
            ),
            'Array of the student user IDs',
            VALUE_OPTIONAL
          ),
        )),
      ));
    }

    /**
     * Creates a new academic group.
     *
     * @return array new group
     */
    public static function create_group(array $model) {
      global $USER, $DB;

      $params = self::validate_parameters(
        self::create_group_parameters(),
        array('model' => $model)
      );
      $group = $params['model'];
      $group['programid'] = $group['program'];
      $group['responsibleid'] = $group['responsible'];

      program_exists($group['programid']);
      user_exists($group['responsibleid']);

      $group['id'] = $DB->insert_record('tool_epman_group', $group);

      if (array_key_exists('assistants', $group)) {
        clear_group_assistants($group['id']);
        foreach ($group['assistants'] as $userid) {
          $DB->insert_record('tool_epman_group_assistant', array('userid' => $userid, 'groupid' => $group['id']), false);
        }
      }

      if (array_key_exists('students', $group)) {
        foreach ($group['students'] as $userid) {
          $DB->insert_record('tool_epman_group_student', array('userid' => $userid, 'groupid' => $group['id']), false);
        }
      }

      return self::get_group($group['id']);
    }

    /**
     * Returns the description of the `create_group` method's
     * return value.
     *
     * @return external_description
     */
    public static function create_group_returns() {
      return self::get_group_returns();
    }


    /* Define the `update_group` implementation functions. */

    /**
     * Returns the description of the `update_group` method's
     * parameters.
     *
     * @return external_function_parameters
     */
    public static function update_group_parameters() {
      global $USER;

      return new external_function_parameters(array(
        'id' => new external_value(
          PARAM_INT,
          'Education program ID'),
        'model' => new external_single_structure(array(
          'name' => new external_value(
            PARAM_TEXT,
            'Academic group name',
            VALUE_OPTIONAL),
          'program' => new external_value(
            PARAM_INT,
            'Education program ID',
            VALUE_OPTIONAL),
          'year' => new external_value(
            PARAM_INT,
            'Actual learning year',
            VALUE_OPTIONAL),
          'responsible' => new external_value(
            PARAM_INT,
            'ID of the responsible user',
            VALUE_DEFAULT,
            $USER->id),
          'assistants' => new external_multiple_structure(
            new external_value(
              PARAM_INT,
              'ID of an assistant user'
            ),
            'Array of the assistant user IDs',
            VALUE_OPTIONAL
          ),
          'add-students' => new external_multiple_structure(
            new external_value(
              PARAM_INT,
              'ID of a student user'
            ),
            'Array of the student user IDs to add',
            VALUE_OPTIONAL
          ),
          'delete-students' => new external_multiple_structure(
            new external_value(
              PARAM_INT,
              'ID of a student user'
            ),
            'Array of the student user IDs to delete',
            VALUE_OPTIONAL
          ),
          'enroll-students' => new external_multiple_structure(
            new external_single_structure(array(
              'id' => new external_value(
                PARAM_INT,
                'ID of a student user'
              ),
              'period' => new external_value(
                PARAM_INT,
                'ID of a student user',
                VALUE_OPTIONAL
              ),
            )),
            'Array of the student user data (ID, period) to move',
            VALUE_OPTIONAL
          ),
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

      if (array_key_exists('program', $group)) {
        $group['programid'] = $group['program'];
      }
      if (array_key_exists('responsible', $group)) {
        $group['responsibleid'] = $group['responsible'];
      }

      group_exists($id);
      $group0 = $DB->get_record('tool_epman_group', array('id' => $id));
      if ($group0) {
        $group0 = (array) $group0;
      }

      if (!has_sys_capability('tool/epman:editgroup', $USER->id)) {
        value_unchanged($group0, $group, 'responsibleid', 'responsible user of this academic group');
        if (!group_responsible($id, $USER->id)) {
          value_unchanged($group0, $group, 'name', 'name of this academic group');
          value_unchanged($group0, $group, 'programid', 'education program of this academic group');
          value_unchanged($group0, $group, 'year', 'year of this academic group');
          if (array_key_exists('assistants', $group)) {
            throw new permission_exception("You don't have the right to change the set of assistant users of this academic group");
          }
          if (array_key_exists('delete-students', $group)) {
            throw new permission_exception("You don't have the right to delete students from this academic group");
          }
          if (array_key_exists('enroll-students', $group)) {
            throw new permission_exception("You don't have the right to advance/enroll the students of this academic group");
          }
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

      if (array_key_exists('assistants', $group)) {
        clear_group_assistants($group['id']);
        foreach ($group['assistants'] as $userid) {
          $DB->insert_record('tool_epman_group_assistant', array('userid' => $userid, 'groupid' => $group['id']), false);
        }
      }

      if (array_key_exists('add-students', $group)) {
        foreach ($group['add-students'] as $userid) {
          $DB->insert_record('tool_epman_group_student', array('userid' => $userid, 'groupid' => $group['id']), false);
        }
      }
      if (array_key_exists('delete-students', $group)) {
        foreach ($group['delete-students'] as $userid) {
          $DB->delete_record('tool_epman_group_student', array('userid' => $userid, 'groupid' => $group['id']));
        }
      }
      if (array_key_exists('enroll-students', $group)) {
        foreach ($group['enroll-students'] as $student) {
          $DB->update_record('tool_epman_group_student', array(
              'userid' => $student['id'],
              'groupid' => $group['id'],
              'period' => $student['period'],
          ));
        }
      }
      if (array_key_exists('delete-students', $group) || array_key_exists('enroll-students', $group)) {
        sync_enrolments($group['id']);
      }


      return self::get_group($group['id']);
    }

    /**
     * Returns the description of the `update_group` method's
     * return value.
     *
     * @return external_description
     */
    public static function update_group_returns() {
      return self::get_group_returns();
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
      $DB->delete_records('tool_epman_group', array('id' => $id));
      sync_enrolments($id);
      
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
