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
 * for the education programs.
 *
 * @package    tool
 * @subpackage epman
 * @copyright  2014 Paul Wolneykien <manowar@altlinux.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("base.php");
require_once("helpers.php");

class epman_program_external extends crud_external_api {

  /* Define the `list_programs` implementation functions. */
  
  /**
   * Returns the description of the `list_programs` method's
   * parameters.
   *
   * @return external_function_parameters
   */
  public static function list_programs_parameters() {
    return new external_function_parameters(array(
      'userid' => new external_value(
        PARAM_INT,
        'Output only the programs editable by the given user (id)',
        VALUE_DEFAULT,
        0),
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
   * Returns the list of education programs.
   *
   * @return array of education programs
   */
    public static function list_programs($userid = 0, $like = null, $skip = 0, $limit = null) {
      global $DB;

      $params = self::validate_parameters(
        self::list_programs_parameters(),
        array('userid' => $userid, 'like' => $like, 'skip' => $skip, 'limit' => $limit)
      );
      $userid = $params['userid'];
      $like = $params['like'];
      $skip = $params['skip'];
      $limit = $params['limit'];

      if ($like) {
        $like = "%".preg_replace('/\s+/', '%', $like)."%";
      }

      if ($userid) {
        $programs = $DB->get_records_sql(
            'select p.id, '.
            'max(p.name) as name, '.
            'max(p.description) as description, '.
            'max(p.year) as year, '.
            'max(p.responsibleid) as responsibleid, '.
            'max(u.username) as username, '.
            'max(u.firstname) as firstname, '.
            'max(u.lastname) as lastname, '.
            'max(u.email) as email '.
            'from {tool_epman_program} p '.
            'left join {tool_epman_program_assistant} pa '.
            'on pa.programid = p.id '.
            'left join {user} u '.
            'on u.id = p.responsibleid '.
            'where (p.responsibleid = :userid1 or pa.userid = :userid2)'.
            ($like ? ' and '.$DB->sql_like('p.name', ':like', false) : '').
            ' group by p.id '.
            'order by year, name',
            array_merge(array('userid1' => $userid, 'userid2' => $userid),
                        ($like ? array('like' => $like) : array())),
            $skip,
            $limit);
      } else {
        $programs = $DB->get_records_sql(
            'select p.*, u.username, '.
            'u.firstname, u.lastname, u.email '.
            'from {tool_epman_program} p '.
            'left join {user} u '.
            'on u.id = p.responsibleid '.
            ($like ? 'where '.$DB->sql_like('p.name', ':like', false) : '').
            ' order by year, name',
            ($like ? array('like' => $like) : null),
            $skip,
            $limit);
      }

      return array_map(
        function($program) {
          $program = (array) $program;
          $program['responsible'] = array(
            'id' => $program['responsibleid'],
            'username' => $program['username'],
            'firstname' => $program['firstname'],
            'lastname' => $program['lastname'],
            'email' => $program['email'],
          );
          unset($program['responsibleid']);
          return $program;
        },
        $programs
      );
    }

    /**
     * Returns the description of the `list_programs` method's
     * return value.
     *
     * @return external_description
     */
    public static function list_programs_returns() {
      return new external_multiple_structure(
        new external_single_structure(array(
          'id' => new external_value(
            PARAM_INT,
            'Education program ID'),
          'name' => new external_value(
            PARAM_TEXT,
            'Education program name'),
          'description' => new external_value(
            PARAM_TEXT,
            'Short description of the program'),
          'year' => new external_value(
            PARAM_INT,
            'Formal learning year'),
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
     * Returns the description of the `get_program` method's
     * parameters.
     *
     * @return external_function_parameters
     */
    public static function get_program_parameters() {
      return new external_function_parameters(array(
        'id' => new external_value(
          PARAM_INT,
          'The ID of the education program to get'),
    ));
  }

  /**
   * Returns the complete education program's data.
   *
   * @return array (education program)
   */
    public static function get_program($id) {
      global $DB;

      $params = self::validate_parameters(
        self::get_program_parameters(),
        array('id' => $id)
      );
      $id = $params['id'];

      program_exists($id);

      $courses = $DB->get_recordset_sql(
        'select p.*, m.id as moduleid, m.startdate, m.period, '.
        'm.length, mc.courseid, mc.coursetype, c.fullname '.
        'from {tool_epman_program} p '.
        'left join {tool_epman_module} m '.
        'on m.programid = p.id '.
        'left join {tool_epman_module_course} mc '.
        'on mc.moduleid = m.id '.
        'left join {course} c on c.id = mc.courseid '.
        'where p.id = :id '.
        'order by m.startdate, mc.coursetype, c.fullname',
        array('id' => $id));

      foreach ($courses as $rec) {
        if (!isset($program)) {
          $program = array(
            'id' => $rec->id,
            'name' => $rec->name,
            'description' => $rec->description,
            'year' => $rec->year,
            'responsible' => array('id' => $rec->responsibleid),
            'modules' => array());
        }
        $lastmodule = count($program['modules']) - 1;
        if ($rec->moduleid &&
            ($lastmodule < 0 ||
             $program['modules'][$lastmodule]['id'] != $rec->moduleid))
        {
          $program['modules'][] = array(
            'id' => $rec->moduleid,
            'startdate' => $rec->startdate,
            'period' => $rec->period,
            'length' => $rec->length,
            'courses' => array());
          $lastmodule = $lastmodule + 1;
        }
        if ($rec->courseid && $lastmodule >= 0) {
          $program['modules'][$lastmodule]['courses'][] = array(
            'id' => $rec->courseid,
            'name' => $rec->fullname,
            'type' => $rec->coursetype);
        }
      }

      $courses->close();

      $responsible = $DB->get_record('user', array('id' => $program['responsible']['id']));
      if ($responsible) {
        $program['responsible'] = array(
          'id' => $responsible->id,
          'username' => $responsible->username,
          'firstname' => $responsible->firstname,
          'lastname' => $responsible->lastname,
          'email' => $responsible->email);
      }

      $assistants = $DB->get_records_sql(
        'select pa.id, p.id as programid, pa.userid, u.username, '.
        'u.firstname, u.lastname, u.email '.
        'from {tool_epman_program} p left join '.
        '{tool_epman_program_assistant} pa '.
        'on pa.programid = p.id '.
        'left join {user} u on u.id = pa.userid '.
        'where p.id = :id and pa.userid is not null '.
        'order by u.lastname',
        array('id' => $id));

      $program['assistants'] = array();
      foreach ($assistants as $rec) {
        $program['assistants'][] = array(
          'id' => $rec->userid,
          'username' => $rec->username,
          'firstname' => $rec->firstname,
          'lastname' => $rec->lastname,
          'email' => $rec->email);
      }

      return $program;
    }

    /**
     * Returns the description of the `get_program` method's
     * return value.
     *
     * @return external_description
     */
    public static function get_program_returns() {
      return new external_single_structure(array(
          'id' => new external_value(
            PARAM_INT,
            'Education program ID'),
          'name' => new external_value(
            PARAM_TEXT,
            'Education program name'),
          'description' => new external_value(
            PARAM_TEXT,
            'Short description of the program',
            VALUE_OPTIONAL),
          'year' => new external_value(
            PARAM_INT,
            'Formal learning year'),
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
          'modules' => new external_multiple_structure(
            new external_single_structure(array(
              'id' => new external_value(
                PARAM_INT,
                'ID of the module'),
              'startdate' => new external_value(
                PARAM_INT,
                'Module start date'),
              'length' => new external_value(
                PARAM_INT,
                'Length of the module (days)'),
              'period' => new external_value(
                PARAM_INT,
                'Education period number'),
              'courses' => new external_multiple_structure(
                new external_single_structure(array(
                  'id' => new external_value(
                    PARAM_INT,
                    'ID of the course'),
                  'name' => new external_value(
                    PARAM_TEXT,
                    'Name of the course',
                    VALUE_OPTIONAL),
                  'type' => new external_value(
                    PARAM_INT,
                    'Type of the course'),
              ))),
          ))),
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
            ))),
        ));
    }


    /* Define the `create_program` implementation functions. */

    /**
     * Returns the description of the `create_program` method's
     * parameters.
     *
     * @return external_function_parameters
     */
    public static function create_program_parameters() {
      global $USER;

      return new external_function_parameters(array(
        'model' => new external_single_structure(array(
          'name' => new external_value(
            PARAM_TEXT,
            'Education program name'),
          'description' => new external_value(
            PARAM_TEXT,
            'Short description of the program',
            VALUE_DEFAULT,
            ''),
          'year' => new external_value(
            PARAM_INT,
            'Formal learning year',
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
        )),
      ));
    }

    /**
     * Creates a new education program.
     *
     * @return array new program
     */
    public static function create_program(array $model) {
      global $USER, $DB;

      $params = self::validate_parameters(
        self::create_program_parameters(),
        array('model' => $model)
      );
      $program = $params['model'];
      $program['responsibleid'] = $program['responsible'];

      user_exists($program['responsibleid']);

      $program['id'] = $DB->insert_record('tool_epman_program', $program);

      if (array_key_exists('assistants', $program)) {
        clear_program_assistants($program['id']);
        foreach ($program['assistants'] as $userid) {
          $DB->insert_record('tool_epman_program_assistant', array('userid' => $userid, 'programid' => $program['id']), false);
        }
      }

      return self::get_program($program['id']);
    }

    /**
     * Returns the description of the `create_program` method's
     * return value.
     *
     * @return external_description
     */
    public static function create_program_returns() {
      return self::get_program_returns();
    }


    /* Define the `update_program` implementation functions. */

    /**
     * Returns the description of the `update_program` method's
     * parameters.
     *
     * @return external_function_parameters
     */
    public static function update_program_parameters() {
      global $USER;

      return new external_function_parameters(array(
        'id' => new external_value(
          PARAM_INT,
          'Education program ID'),
        'model' => new external_single_structure(array(
          'name' => new external_value(
            PARAM_TEXT,
            'Education program name',
            VALUE_OPTIONAL),
          'description' => new external_value(
            PARAM_TEXT,
            'Short description of the program',
            VALUE_OPTIONAL),
          'year' => new external_value(
            PARAM_INT,
            'Formal learning year',
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
        )),
      ));
    }

    /**
     * Updates the education program with the given ID.
     *
     * @return boolean success flag
     */
    public static function update_program($id, array $model) {
      global $USER, $DB;

      $params = self::validate_parameters(
        self::update_program_parameters(),
        array('id' => $id, 'model' => $model)
      );
      $id = $params['id'];
      $program = $params['model'];
      $program['id'] = $id;
      $program['responsibleid'] = $program['responsible'];

      program_exists($id);

      $program0 = $DB->get_record('tool_epman_program', array('id' => $id));
      if ($program0) {
        $program0 = (array) $program0;
      }

      if (!has_sys_capability('tool/epman:editprogram', $USER->id)) {
        value_unchanged($program0, $program, 'responsibleid', 'responsible user of this education program');
        if (!program_responsible($id, $USER->id)) {
          value_unchanged($program0, $program, 'name', 'name of this education program');
          value_unchanged($program0, $program, 'year', 'year of this education program');
          value_unchanged($program0, $program, 'description', 'description of this education program');
          if (array_key_exists('assistants', $program)) {
            throw new permission_exception("You don't have the right to change the set of assistant users of this education program");
          }
          if (!program_assistant($id, $USER->id)) {
            throw new moodle_exception("You don't have right to modify this education program");
          }
        }
      } else {
        if (isset($program['responsibleid'])) {
          user_exists($program['responsibleid']);
        }
      }

      $DB->update_record('tool_epman_program', $program);

      if (array_key_exists('assistants', $program)) {
        clear_program_assistants($program['id']);
        foreach ($program['assistants'] as $userid) {
          $DB->insert_record('tool_epman_program_assistant', array('userid' => $userid, 'programid' => $program['id']), false);
        }
      }

      return self::get_program($program['id']);
    }

    /**
     * Returns the description of the `update_program` method's
     * return value.
     *
     * @return external_description
     */
    public static function update_program_returns() {
      return self::get_program_returns();
    }

    
    /* Define the `delete_program` implementation functions. */

    /**
     * Returns the description of the `delete_program` method's
     * parameters.
     *
     * @return external_function_parameters
     */
    public static function delete_program_parameters() {
      global $USER;

      return new external_function_parameters(array(
        'id' => new external_value(
          PARAM_INT,
          'Education program ID'),
      ));
    }

    /**
     * Deletes the given education program.
     *
     * @return bool successful result flag
     */
    public static function delete_program($id) {
      global $USER, $DB;

      $params = self::validate_parameters(
        self::delete_program_parameters(),
        array('id' => $id)
      );
      $id = $params['id'];

      if (!has_sys_capability('tool/epman:editprogram', $USER->id) &&
          !program_responsible($id, $USER->id)) {
        throw new moodle_exception("You don't have right to delete this education program");
      }

      program_exists($id);
      clear_program_modules($id);
      clear_program_assistants($id);
      $DB->delete_records('tool_epman_program', array('id' => $id));
      
      return true;
    }

    /**
     * Returns the description of the `delete_program` method's
     * return value.
     *
     * @return external_description
     */
    public static function delete_program_returns() {
      return new external_value(
        PARAM_BOOL,
        'Successfull return flag'
      );
    }

}

?>
