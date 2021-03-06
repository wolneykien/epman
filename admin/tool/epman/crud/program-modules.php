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
 * for the education program modules.
 *
 * @package    tool
 * @subpackage epman
 * @copyright  2014 Paul Wolneykien <manowar@altlinux.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("base.php");
require_once("helpers.php");

class epman_module_external extends crud_external_api {

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
      $modules = $DB->get_records('tool_epman_module', array('programid' => $programid), 'startdate');

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
          'startdate' => new external_value(
            PARAM_INT,
            'Module start date'),
          'length' => new external_value(
            PARAM_INT,
            'Module length'),
          'period' => new external_value(
            PARAM_INT,
            'Education period number'),
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
        'programid' => new external_value(
          PARAM_INT,
          'Education program ID'),
        'id' => new external_value(
          PARAM_INT,
          'The ID of the education program module to get'),
    ));
  }

  /**
   * Returns the complete education program module's data.
   *
   * @return array (education program module)
   */
    public static function get_module($programid, $id) {
      global $DB;

      $params = self::validate_parameters(
        self::get_module_parameters(),
        array('programid' => $programid, 'id' => $id)
      );
      $programid = $params['programid'];
      $id = $params['id'];

      program_exists($programid);
      module_exists($id);

      $courses = $DB->get_recordset_sql(
        'select m.*, mc.courseid, mc.coursetype, c.fullname '.
        'from {tool_epman_module} m '.
        'left join {tool_epman_module_course} mc '.
        'on mc.moduleid = m.id '.
        'left join {course} c on c.id = mc.courseid '.
        'where m.programid = :programid and m.id = :id '.
        'order by m.startdate, mc.coursetype, c.fullname',
        array('programid' => $programid, 'id' => $id));

      foreach ($courses as $rec) {
        if (!isset($module)) {
          $module = array(
            'id' => $rec->id,
            'programid' => $rec->programid,
            'startdate' => $rec->startdate,
            'length' => $rec->length,
            'period' => $rec->period,
            'courses' => array());
        }
        if ($rec->courseid) {
          $module['courses'][] = array(
              'id' => $rec->courseid,
              'name' => $rec->fullname,
              'type' => $rec->coursetype);
        }
      }

      $courses->close();

      return $module;
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
          'startdate' => new external_value(
            PARAM_INT,
            'Module start date'),
          'length' => new external_value(
            PARAM_INT,
            'Module length'),
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
            ))
          ),
      ));
    }


    /* Define the `create_module` implementation functions. */

    /**
     * Returns the description of the `create_module` method's
     * parameters.
     *
     * @return external_function_parameters
     */
    public static function create_module_parameters() {
      return new external_function_parameters(array(
        'programid' => new external_value(
          PARAM_INT,
          'Education program ID'),
        'model' => new external_single_structure(array(
          'startdate' => new external_value(
            PARAM_INT,
            'Module start date'),
          'length' => new external_value(
            PARAM_INT,
            'Module length',
            VALUE_DEFAULT,
            30),
          'period' => new external_value(
            PARAM_INT,
            'Education period number',
            VALUE_DEFAULT,
            -1),
          'courses' => new external_multiple_structure(
            new external_single_structure(array(
              'id' => new external_value(
                PARAM_INT,
                'ID of the education course'
              ),
              'type' => new external_value(
                PARAM_INT,
                'Type of the education course'
              ),
            )),
            'Array of the course reference data: {id, type}',
            VALUE_OPTIONAL
          ),
        )),
      ));
    }

    /**
     * Creates a new module within the given education program (ID).
     *
     * @return array new module
     */
    public static function create_module($programid, array $model) {
      global $DB, $USER;

      $params = self::validate_parameters(
        self::create_module_parameters(),
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
      if ($module['period'] < 0) {
        $module['period'] = get_last_module_period($programid);
      }
      $module['id'] = $DB->insert_record('tool_epman_module', $module);

      if (array_key_exists('courses', $module)) {
        clear_module_courses($module['id']);
        foreach ($module['courses'] as $course) {
          $DB->insert_record('tool_epman_module_course', array('moduleid' => $module['id'], 'courseid' => $course['id'], 'coursetype' => $course['type']), false);
        }
        sync_enrolments(null);
      }

      return self::get_module($programid, $module['id']);
    }

    /**
     * Returns the description of the `create_module` method's
     * return value.
     *
     * @return external_description
     */
    public static function create_module_returns() {
      return self::get_module_returns();
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
          'startdate' => new external_value(
            PARAM_INT,
            'Module start date',
            VALUE_OPTIONAL),
          'length' => new external_value(
            PARAM_INT,
            'Module length, days',
            VALUE_OPTIONAL),
          'period' => new external_value(
            PARAM_INT,
            'Education period number',
            VALUE_OPTIONAL),
          'courses' => new external_multiple_structure(
            new external_single_structure(array(
              'id' => new external_value(
                PARAM_INT,
                'ID of the education course'
              ),
              'type' => new external_value(
                PARAM_INT,
                'Type of the education course'
              ),
            )),
            'Array of the course reference data: {id, type}',
            VALUE_OPTIONAL
          ),
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
      module_exists($id);

      if (!has_sys_capability('tool/epman:editprogram', $USER->id)) {
        if (!program_responsible($programid, $USER->id)) {
          if (!program_assistant($programid, $USER->id)) {
            throw new moodle_exception("You don't have right to modify this education program");
          }
        }
      }

      $DB->update_record('tool_epman_module', $module);

      if (array_key_exists('courses', $module)) {
        clear_module_courses($module['id']);
        foreach ($module['courses'] as $course) {
          $DB->insert_record('tool_epman_module_course', array('moduleid' => $module['id'], 'courseid' => $course['id'], 'coursetype' => $course['type']), false);
        }
        sync_enrolments(null);
      }

      return self::get_module($programid, $module['id']);
    }

    /**
     * Returns the description of the `update_module` method's
     * return value.
     *
     * @return external_description
     */
    public static function update_module_returns() {
      return self::get_module_returns();
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
     * Deletes the given education program module.
     *
     * @return bool successful result flag
     */
    public static function delete_module($programid, $id) {
      global $USER, $DB;

      $params = self::validate_parameters(
        self::delete_module_parameters(),
        array('programid' => $programid, 'id' => $id)
      );
      $programid = $params['programid'];
      $id = $params['id'];

      program_exists($programid);
      module_exists($id);

      if (!has_sys_capability('tool/epman:editprogram', $USER->id)) {
        if (!program_responsible($programid, $USER->id)) {
          if (!program_assistant($programid, $USER->id)) {
            throw new moodle_exception("You don't have right to modify this education program");
          }
        }
      }

      clear_module_courses($id);
      $DB->delete_records('tool_epman_module', array('id' => $id));
      sync_enrolments(null);
      
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
