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
 * education program modules.
 *
 * @package    tool
 * @subpackage epman
 * @copyright  2014 Paul Wolneykien <manowar@altlinux.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->libdir/externallib.php");
require_once("helpers.php");

class epman_module_external extends external_api {

  /* Define the `list_modules` implementation functions. */
  
  /**
   * Returns the description of the `list_modules` method's
   * parameters.
   *
   * @return external_function_parameters
   */
  public static function list_modules_parameters() {
    return new external_function_parameters(array(
      'programid' => new external_value(
        PARAM_INT,
        'Education program ID'),
    ));
  }

  /**
   * Returns the list of education program modules.
   *
   * @return array of education program modules
   */
    public static function list_modules($programid) {
      global $DB;

      $params = self::validate_parameters(
        self::list_modules_parameters(),
        array('programid' => $programid)
      );
      $programid = $params['programid'];

      program_exists($programid);
      $modules = $DB->get_records('tool_epman_module', array('programid' => $programid), 'position');

      return array_map(
        function($module) {
          return (array) $module;
        },
        $modules
      );
    }

    /**
     * Returns the description of the `list_modules` method's
     * return value.
     *
     * @return external_description
     */
    public static function list_modules_returns() {
      return new external_multiple_structure(
        new external_single_structure(array(
          'id' => new external_value(
            PARAM_INT,
            'Education program module ID'),
          'programid' => new external_value(
            PARAM_INT,
            'Education program ID'),
          'position' => new external_value(
            PARAM_INT,
            'Module position'),
          'length' => new external_value(
            PARAM_INT,
            'Module length'),
        )));
    }


    /**
     * Returns the description of the `get_module` method's
     * parameters.
     *
     * @return external_function_parameters
     */
    public static function get_module_parameters() {
      return new external_function_parameters(array(
        'id' => new external_value(
          PARAM_INT,
          'The ID of the education program module to get'),
    ));
  }

  /**
   * Returns the complete education program module's data.
   *
   * @return array (education program)
   */
    public static function get_module($id) {
      global $DB;

      $params = self::validate_parameters(
        self::get_module_parameters(),
        array('id' => $id)
      );
      $id = $params['id'];

      $courses = $DB->get_records_sql(
        'select m.*, mc.courseid, c.fullname '.
        'from {tool_epman_module} m '.
        'left join {tool_epman_module_course} mc '.
        'on mc.moduleid = m.id '.
        'left join {course} c on c.id = mc.courseid '.
        'where m.id = ? '.
        'order by m.position, c.fullname',
        array('id' => $id));

      foreach ($courses as $rec) {
        if (!isset($module)) {
          $module = array(
            'id' => $rec->id,
            'programid' => $rec->programid,
            'position' => $rec->position,
            'length' => $rec->length,
            'courses' => array());
        }
        $module['courses'][] = array(
          'id' => $rec->courseid,
          'name' => $rec->fullname);
      }

      return $program;
    }

    /**
     * Returns the description of the `get_module` method's
     * return value.
     *
     * @return external_description
     */
    public static function get_module_returns() {
      return new external_single_structure(array(
         'id' => new external_value(
            PARAM_INT,
            'Education program ID'),
          'programid' => new external_value(
            PARAM_INT,
            'Education program ID'),
          'position' => new external_value(
            PARAM_INT,
            'Module position'),
          'length' => new external_value(
            PARAM_INT,
            'Module length, days'),
          'courses' => new external_multiple_structure(
            new external_single_structure(array(
              'id' => new external_value(
                PARAM_INT,
                'ID of the course'),
              'name' => new external_value(
                PARAM_TEXT,
                'Name of the course',
                VALUE_OPTIONAL),
              ))),
      ));
    }


    /* Define the `create_module` implementation functions. */

    /**
     * Returns the description of the `create_module` method's
     * parameters.
     *
     * @return external_function_parameters
     */
    public static function create_module_parameters($programid = null) {
      return new external_function_parameters(array(
        'programid' => new external_value(
          PARAM_INT,
          'Education program ID'),
        'model' => new external_single_structure(array(
          'position' => new external_value(
            PARAM_INT,
            'Module position',
            VALUE_DEFAULT,
            $programid ? get_next_module_position($programid) : 0),
          'length' => new external_value(
            PARAM_INT,
            'Module length, days',
            VALUE_DEFAULT,
            0),
        )),
      ));
    }

    /**
     * Creates a new module within the given education program (ID).
     *
     * @return int new module ID
     */
    public static function create_module($programid, array $model) {
      global $DB, $USER;

      $params = self::validate_parameters(
        self::create_module_parameters($programid),
        array('programid' => $programid, 'model' => $model)
      );
      $programid = $params['programid'];
      $module = $params['model'];

      program_exists($programid);

      if (!has_sys_capability('tool/epman:editprogram', $USER->id)) {
        if (!program_responsible($programid, $USER->id)) {
          if (!program_assistant($programid, $USER->id)) {
            throw new moodle_exception("You don't have right to modify this education program");
          }
        }
      }

      $module['programid'] = $programid;
      $module['id'] = $DB->insert_record('tool_epman_module', $module);

      return $module;
    }

    /**
     * Returns the description of the `create_module` method's
     * return value.
     *
     * @return external_description
     */
    public static function create_module_returns() {
      return new external_single_structure(array(
        'id' => new external_value(
          PARAM_INT,
          'Education program module ID'),
        'programid' => new external_value(
          PARAM_INT,
          'Education program name ID'),
        'position' => new external_value(
          PARAM_INT,
          'Module position'),
        'length' => new external_value(
          PARAM_INT,
          'Module length, days'),
      ));
    }


    /* Define the `update_module` implementation functions. */

    /**
     * Returns the description of the `update_module` method's
     * parameters.
     *
     * @return external_function_parameters
     */
    public static function update_module_parameters() {
      return new external_function_parameters(array(
        'programid' => new external_value(
          PARAM_INT,
          'Education program ID'),
        'id' => new external_value(
          PARAM_INT,
          'Education program module ID'),
        'model' => new external_single_structure(array(
          'position' => new external_value(
            PARAM_INT,
            'Module position',
            VALUE_DEFAULT,
            $programid ? get_next_module_position($programid) : 0),
          'length' => new external_value(
            PARAM_INT,
            'Module length, days',
            VALUE_DEFAULT,
            0),
        )),
      ));
    }

    /**
     * Updates the given module (ID) of the given education program (ID).
     *
     * @return boolean success flag
     */
    public static function update_module($programid, $id, array $model) {
      global $DB, $USER;

      $params = self::validate_parameters(
        self::update_module_parameters(),
        array('programid' => $programid, 'id' => $id, 'model' => $model)
      );
      $programid = $params['programid'];
      $id = $params['id'];
      $module = $params['model'];
      $module['id'] = $id;

      program_exists($programid);
      program_module_exists($id);

      if (!has_sys_capability('tool/epman:editprogram', $USER->id)) {
        if (!program_responsible($programid, $USER->id)) {
          if (!program_assistant($programid, $USER->id)) {
            throw new moodle_exception("You don't have right to modify this education program");
          }
        }
      }

      $DB->update_record('tool_epman_module', $module);

      return $module;
    }

    /**
     * Returns the description of the `update_module` method's
     * return value.
     *
     * @return external_description
     */
    public static function update_module_returns() {
      return new external_single_structure(array(
        'position' => new external_value(
          PARAM_INT,
          'Module position',
          VALUE_OPTIONAL),
        'length' => new external_value(
          PARAM_INT,
          'Module length, days',
          VALUE_OPTIONAL),
      ));
    }

    
    /* Define the `delete_module` implementation functions. */

    /**
     * Returns the description of the `delete_module` method's
     * parameters.
     *
     * @return external_function_parameters
     */
    public static function delete_module_parameters() {
      return new external_function_parameters(array(
        'programid' => new external_value(
          PARAM_INT,
          'Education program ID'),
        'id' => new external_value(
          PARAM_INT,
          'Education program module ID'),
      ));
    }

    /**
     * Deletes a new education program.
     *
     * @return int new program ID
     */
    public static function delete_program($programid, $id) {
      global $USER, $DB;

      $params = self::validate_parameters(
        self::delete_module_parameters(),
        array('programid' => $programid, 'id' => $id)
      );
      $programid = $params['programid'];
      $id = $params['id'];

      program_exists($programid);
      program_module_exists($id);

      if (!has_sys_capability('tool/epman:editprogram', $USER->id)) {
        if (!program_responsible($programid, $USER->id)) {
          if (!program_assistant($programid, $USER->id)) {
            throw new moodle_exception("You don't have right to modify this education program");
          }
        }
      }

      clear_module_courses($id);
      $DB->delete_record('tool_epman_module', array('id' => $id));
      
      return true;
    }

    /**
     * Returns the description of the `delete_module` method's
     * return value.
     *
     * @return external_description
     */
    public static function delete_module_returns() {
      return new external_value(
        PARAM_BOOL,
        'Successfull return flag'
      );
    }

}

?>
