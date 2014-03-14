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
 * process management module.
 *
 * @package    tool
 * @subpackage epman
 * @copyright  2014 Paul Wolneykien <manowar@altlinux.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->libdir/externallib.php");

class epman_external extends external_api {

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
    ));
  }

  /**
   * Returns the list of education programs.
   *
   * @return array of education programs
   */
    public static function list_programs($userid) {
      global $DB;

      $params = self::validate_parameters(
        self::list_programs_parameters(),
        array('userid' => $userid)
      );

      if ($userid) {
        return $DB->get_records_sql(
            'select p.id, '.
            'max(p.name) as name, '.
            'max(p.description) as description, '.
            'max(p.year) as year, '.
            'max(p.responsibleid) as responsibleid '.
            'from {tool_epman_program} p '.
            'left join {tool_epman_program_assistant} pa '.
            'on pa.programid = p.id '.
            'where p.responsibleid = ? or pa.userid = ? '.
            'group by p.id '.
            'order by year, name',
            array($userid, $userid));
      } else {
        return $DB->get_records('tool_epman_program', null, 'year, name');
      }
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
          'responsibleid' => new external_value(
            PARAM_INT,
            'ID of the responsible user'),
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
      global $DB, $USER;

      $params = self::validate_parameters(
        self::list_programs_parameters(),
        array('id' => $id)
      );

      $program = new stdClass();

      $courses = $DB->get_records_sql(
        'select p.*, pm.position, pm.moduleid, '.
        'mc.courseid, c.fullname '.
        'from {tool_epman_program} p left join '.
        '{tool_epman_program_module} pm '.
        'on pm.programid = p.id '.
        'left join {tool_epman_module_course} mc '.
        'on mc.moduleid = pm.moduleid '.
        'left join {course} c on c.id = mc.courseid '.
        'where p.id = ? '.
        'order by pm.position, c.fullname',
        array('id' => $id));

      foreach ($courses as $rec) {
        if (!isset($program->id)) {
          $program->id = $rec->id;
          $program->name = $rec->name;
          $program->description = $rec->description;
          $program->year = $rec->year;
          $program->responsible = new stdObject(array(
            'id' => $rec->responsibleid));
        }
        if (!isset($program->modules)) {
          $program->modules = array();
        }
        $module = end($program->modules);
        if (!$module || $module->id != $rec->moduleid) {
          $module = new stdObject(array(
            'id' => $rec->moduleid,
            'length' => $rec->length,
            'courses' => array()));
          $program->modules[] = $module;
        }
        $module->courses[] = new stdObject(array(
          'id' => $rec->courseid,
          'name' => $rec->fullname));
      }

      $responsible = $DB->get_record('user', array('id' => $program->responsible->id));
      $program->responsible = new stdObject(array(
        'id' => $responsible->id,
        'username' => $responsible->username,
        'firstname' => $responsible->firstname,
        'lastname' => $responsible->lastname,
        'email' => $responsible->email));

      $assistants = $DB->get_records_sql(
        'select p.id, pa.userid, u.username, '.
        'u.firstname, u.lastname, u.email '.
        'from {tool_epman_program} p left join '.
        '{tool_epman_program_assistant} pa '.
        'on pa.programid = p.id '.
        'left join {user} u on u.id = pa.userid '.
        'where p.id = ? '.
        'order by u.username',
        array('id' => $id));

      $program->assistants = array();
      foreach ($assistants as $rec) {
        $program->assistants[] = new stdObject(array(
          'id' => $rec->userid,
          'username' => $rec->username,
          'firstname' => $rec->firstname,
          'lastname' => $rec->lastname,
          'email' => $rec->email));
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
              'System name of the responsible user'),
            'firstname' => new external_value(
              PARAM_TEXT,
              'First name of the responsible user'),
            'lastname' => new external_value(
              PARAM_TEXT,
              'Last name of the responsible user'),
            'email' => new external_value(
              PARAM_TEXT,
              'E-mail of the responsible user'),
          )),
          'modules' => new external_multiple_structure(
            new external_single_structure(array(
              'id' => new external_value(
                PARAM_INT,
                'ID of the module'),
              'length' => new external_value(
                PARAM_INT,
                'Length of the module (days)'),
              'courses' => new external_multiple_structure(
                new external_single_structure(array(
                  'id' => new external_value(
                    PARAM_INT,
                    'ID of the course'),
                  'name' => new external_value(
                    PARAM_INT,
                    'Name of the course'),
              ))),
          ))),
          'assistants' => new external_multiple_structure(
            new external_single_structure(array(
              'id' => new external_value(
                PARAM_INT,
                'ID of the assistant user'),
              'username' => new external_value(
                PARAM_TEXT,
                'System name of the assistant user'),
              'firstname' => new external_value(
                PARAM_TEXT,
                'First name of the assistant user'),
              'lastname' => new external_value(
                PARAM_TEXT,
                'Last name of the assistant user'),
              'email' => new external_value(
                PARAM_TEXT,
                'E-mail of the assistant user'),
            ))),
        )));
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
          'Formal learning year'),
        'responsibleid' => new external_value(
          PARAM_INT,
          'ID of the responsible user',
          VALUE_DEFAULT,
          $USER->id),
        'modules' => new external_multiple_structure(
          new external_value(
            PARAM_INT,
            'Program module ID'),
          VALUE_DEFAULT,
          array()
        ),
        'assistants' => new external_multiple_structure(
          new external_value(
            PARAM_INT,
            'Assistant user ID'),
          VALUE_DEFAULT,
          array()
        ),
      ));
    }

    /**
     * Creates a new education program.
     *
     * @return int new program ID
     */
    public static function create_program($name, $desc = '', $year, $respid, array $modules = array(), array $assistants = array()) {
      global $USER, $DB;

      if (!isset($respid)) {
        $respid = $USER->id;
      }

      $params = self::validate_parameters(
        self::create_program_parameters(),
        array('name' => $name, 'description' => $desc, 'year' => $year, 'responsibleid' => $respid, 'modules' => $modules, 'assistants' => $assistants)
      );

      user_exists($respid);

      $program = new stdObject(array(
          'name' => $name,
          'description' => $desc,
          'year' => $year,
          'responsibleid' => $respid));

      $program->id = $DB->insert_record('tool_epman_program', $program);

      if (!empty($modules)) {
        foreach ($modules as $moduleid) {
          add_module($program->id, $moduleid);
        }
      }

      if (!empty($assistants)) {
        foreach ($assistants as $userid) {
          add_assistant($program->id, $userid);
        }
      }
      
      return $program->id;
    }

    /**
     * Returns the description of the `create_program` method's
     * return value.
     *
     * @return external_description
     */
    public static function create_program_returns() {
      return new external_value(
        PARAM_INT,
        'Education program ID'
      );
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
        'name' => new external_value(
          PARAM_TEXT,
          'Education program name'),
        'description' => new external_value(
          PARAM_TEXT,
          'Short description of the program',
          VALUE_DEFAULT,
          ''),
        'responsibleid' => new external_value(
          PARAM_INT,
          'ID of the responsible user',
          VALUE_DEFAULT,
          $USER->id),
        'modules' => new external_multiple_structure(
          new external_value(
            PARAM_INT,
            'Program module ID'),
          VALUE_DEFAULT,
          array()
        ),
        'assistants' => new external_multiple_structure(
          new external_value(
            PARAM_INT,
            'Assistant user ID'),
          VALUE_DEFAULT,
          array()
        ),
      ));
    }

    /**
     * Updates the education program with the given ID.
     *
     * @return boolean success flag
     */
    public static function update_program($id, $name, $desc = '', $respid, $modules = array(), $assistants = array()) {
      global $USER, $DB;

      if (!isset($respid)) {
        $respid = $USER->id;
      }

      $params = self::validate_parameters(
        self::create_program_parameters(),
        array('id' => $id, 'name' => $name, 'description' => $desc, 'responsibleid' => $respid, 'modules' => $modules, 'assistants' => $assistants)
      );

      program_exists($id);

      $program = $DB->get_record('tool_epman_program', array('id' => $id));

      $change_assistants = true;
      if (!has_sys_capability('tool/epman:editprogram', $USER->id)) {
        if (!program_responsible($id, $USER->id)) {
          if (!program_assistant($id, $USER->id)) {
            throw new moodle_exception("You don't have right to modify this education program");
          } else {
            $assistants0 = get_program_assistants($id);
            if (count(array_diff($assistants0, $assistants)) ||
                count(array_diff($assistants, $assistants0))) {
              throw new moodle_exception("You don't have right to change the set of assistant users of this education program");
            } else {
              $change_assistants = false;
            }
          }
        }
        if ($respid != $program->responsibleid) {
          throw new moodle_exception("You don't have right to change the responsible user of this education program");
        }
      } else {
        user_exists($respid);
      }

      $DB->update_record('tool_epman_program', $program);

      clear_program_modules($id);
      if (!empty($modules)) {
        foreach ($modules as $moduleid) {
          add_module($program->id, $moduleid);
        }
      }

      if ($change_assistants) {
        clear_program_assistants($id);
        if (!empty($assistants)) {
          foreach ($assistants as $userid) {
            add_assistant($program->id, $userid);
          }
        }
      }

      return true;
    }

    /**
     * Returns the description of the `update_program` method's
     * return value.
     *
     * @return external_description
     */
    public static function update_program_returns() {
      return new external_value(
        PARAM_BOOL,
        'Successfull return flag'
      );
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
     * Deletes a new education program.
     *
     * @return int new program ID
     */
    public static function delete_program($id) {
      global $USER, $DB;

      $params = self::validate_parameters(
        self::create_program_parameters(),
        array('id' => $id)
      );

      if (!has_sys_capability('tool/epman:editprogram', $USER->id) &&
          !program_responsible($id, $USER->id)) {
        throw new moodle_exception("You don't have right to delete this education program");
      }

      program_exists($id);
      clear_program_modules($id);
      clear_program_assistants($id);
      $DB->delete_record('tool_epman_program', array('id' => $id));
      
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


    /* Define the helper functions. */

    /**
     * Clears the assistant user set for the given education
     * program.
     */
    public static function clear_program_assistants($programid) {
      global $DB;

      program_exists($programid);
      $DB->delete_records('tool_epman_program_assistant', array('programid' => $programid));
    }

    /**
     * Clears the module set for the given education program.
     */
    public static function clear_program_modules($programid) {
      global $DB;

      program_exists($programid);
      $DB->delete_records('tool_epman_program_module', array('programid' => $programid));
    }
    
    /**
     * Connects the module and the education program with the
     * given IDs.
     *
     */
    public static function add_module($programid, $moduleid) {
      global $DB;

      program_exists($programid);
      module_exists($moduleid);

      $DB->insert_record('tool_epman_program_module',
                         array('programid' => $programid,
                               'moduleid' => $moduleid),
                         false);
    }

    /**
     * Gets the array of modules (IDs) for the given education
     * program.
     */
    public static function get_program_modules($id) {
      global $DB;

      program_exists($id);

      return $DB->get_fieldset('tool_epman_program_module', 'moduleid', 'programid = ?', $id);
    }
    
    /**
     * Connects the assistant user and the education program with the
     * given IDs.
     *
     */
    public static function add_assistant($programid, $userid) {
      global $DB;

      program_exists($programid);
      user_exists($userid);

      $DB->insert_record('tool_epman_program_assistant',
                         array('programid' => $programid,
                               'userid' => $userid),
                         false);
    }

    /**
     * Gets the array of assistant users (IDs) for the given
     * education program.
     */
    public static function get_program_assistants($id) {
      global $DB;

      program_exists($id);

      return $DB->get_fieldset('tool_epman_program_assistant', 'userid', 'programid = ?', $id);
    }

    /**
     * Checks if the education program with the given ID exists.
     *
     * @throw invalid_parameter_exception
     */
    public static function program_exists($programid) {
      global $DB;

      if (!$DB->record_exists('tool_epman_programs', array('id' => $programid))) {
        throw new invalid_parameter_exception("Program doesn't exist: $programid");
      }
    }

    /**
     * Checks if the education program module with the given ID exists.
     *
     * @throw invalid_parameter_exception
     */
    public static function module_exists($moduleid) {
      global $DB;

      if (!$DB->record_exists('tool_epman_modules', array('id' => $moduleid))) {
        throw new invalid_parameter_exception("Module doesn't exist: $moduleid");
      }
    }

    /**
     * Checks if the user with the given ID exists.
     *
     * @throw invalid_parameter_exception
     */
    public static function user_exists($userid) {
      global $DB;

      if (!$DB->record_exists('user', array('id' => $userid))) {
        throw new invalid_parameter_exception("Responsible user doesn't exist: $userid");
      }
    }

    /**
     * Checks if the given user (id) has the given system capability.
     */
    public static function has_sys_capability($capability, $userid) {
      global $USER;

      if (!isset($userid)) {
        $userid = $USER->id;
      }

      user_exists($userid);
      $systemctx = get_context_instance(CONTEXT_SYSTEM);
      return has_capability($capability, $systemctx, $userid);
    }

    /**
     * Checks if the given user (id) is responsible for the
     * given education program (id).
     */
    public static function program_responsible($programid, $userid) {
      global $DB, $USER;
      
      if (!isset($userid)) {
        $userid = $USER->id;
      }

      program_exists($programid);
      user_exists($userid);

      return $DB->record_exists(
          'tool_epman_program',
          array(
            'id' => $programid,
            'responsibleid' => $userid
          )
      );
    }

    /**
     * Checks if the given user (id) is responsible for the
     * given education program (id).
     */
    public static function program_assistant($programid, $userid) {
      global $DB, $USER;
      
      if (!isset($userid)) {
        $userid = $USER->id;
      }

      program_exists($programid);
      user_exists($userid);

      return $DB->record_exists(
          'tool_epman_program_assistant',
          array(
            'programid' => $programid,
            'uerid' => $userid
          )
      );
    }

}

/*
 * Simple stdClass creation.
 *
 */
class stdObject {

    public function __construct(array $arguments = array()) {
        if (!empty($arguments)) {
            foreach ($arguments as $property => $argument) {
              $this->{$property} = $argument;
            }
        }
    }

}

?>
