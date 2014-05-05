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
 * for the education module courses.
 *
 * @package    tool
 * @subpackage epman
 * @copyright  2014 Paul Wolneykien <manowar@altlinux.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("base.php");
require_once("helpers.php");

class epman_module_course_external extends crud_external_api {

  /* Define the `list_courses` implementation functions. */
  
  /**
   * Returns the description of the `list_module_courses` method's
   * parameters.
   *
   * @return external_function_parameters
   */
  public static function list_module_courses_parameters() {
    return new external_function_parameters(array(
      'moduleid' => new external_value(
        PARAM_INT,
        'Education module ID'),
    ));
  }

  /**
   * Returns the list of education module courses.
   *
   * @return array of education module modules
   */
    public static function list_module_courses($moduleid) {
      global $DB;

      $params = self::validate_parameters(
        self::list_module_courses_parameters(),
        array('moduleid' => $moduleid)
      );
      $moduleid = $params['moduleid'];

      module_exists($moduleid);

      $courses = $DB->get_records_sql(
        'select mc.courseid as id, c.fullname, '.
        'mc.moduleid, mc.courseid, mc.coursetype '.
        'from {tool_epman_module_course} mc '.
        'left join {course} c on c.id = mc.courseid '
        'where mc.moduleid = :moduleid '.
        'order by fullname',
        array('moduleid' => $moduleid)
      );

      return array_map(
        function($course) {
          return (array) $course;
        },
        $courses
      );
    }

    /**
     * Returns the description of the `list_module_courses` method's
     * return value.
     *
     * @return external_description
     */
    public static function list_module_courses_returns() {
      return new external_multiple_structure(
        new external_single_structure(array(
          'id' => new external_value(
            PARAM_INT,
            'Module course ID'),
          'moduleid' => new external_value(
            PARAM_INT,
            'Education module ID'),
          'courseid' => new external_value(
            PARAM_INT,
            'Course ID'),
          'fullname' => new external_value(
            PARAM_TEXT,
            'Full name of the course',
            VALUE_OPTIONAL),
          'coursetype' => new external_value(
            PARAM_INT,
            'Course type',
            VALUE_OPTIONAL),
        )));
    }


    /**
     * Returns the description of the `get_module_course` method's
     * parameters.
     *
     * @return external_function_parameters
     */
    public static function get_module_course_parameters() {
      return new external_function_parameters(array(
        'moduleid' => new external_value(
          PARAM_INT,
          'Education module ID'),
        'id' => new external_value(
          PARAM_INT,
          'The ID of the education module course to get'),
    ));
  }

  /**
   * Returns the complete education module module_course's data.
   *
   * @return array (education module course)
   */
    public static function get_module_course($moduleid, $id) {
      global $DB;

      $params = self::validate_parameters(
        self::get_module_course_parameters(),
        array('moduleid' => $moduleid, 'id' => $id)
      );
      $moduleid = $params['moduleid'];
      $id = $params['id'];

      module_exists($moduleid);

      $course = $DB->get_record_sql(
        'select mc.courseid as id, c.fullname, '.
        'mc.moduleid, mc.courseid, mc.coursetype '.
        'from {tool_epman_module_course} mc '.
        'left join {course} c on c.id = mc.courseid '
        'where mc.moduleid = :moduleid',
        array('moduleid' => $id));
      
      return (array) $course;
    }

    /**
     * Returns the description of the `get_module_course` method's
     * return value.
     *
     * @return external_description
     */
    public static function get_module_course_returns() {
      return new external_single_structure(array(
        'id' => new external_value(
          PARAM_INT,
           'Module course ID'),
        'moduleid' => new external_value(
          PARAM_INT,
          'Education module ID'),
        'courseid' => new external_value(
          PARAM_INT,
          'Course ID'),
        'fullname' => new external_value(
          PARAM_TEXT,
          'Full name of the course',
          VALUE_OPTIONAL),
        'coursetype' => new external_value(
          PARAM_INT,
          'Course type',
          VALUE_OPTIONAL),
      ));
    }


    /* Define the `add_module_course` implementation functions. */

    /**
     * Returns the description of the `add_module_course` method's
     * parameters.
     *
     * @return external_function_parameters
     */
    public static function add_module_course_parameters() {
      return new external_function_parameters(array(
        'moduleid' => new external_value(
          PARAM_INT,
          'Education module ID'),
        'model' => new external_single_structure(array(
          'courseid' => new external_value(
            PARAM_INT,
            'Course ID'),
          'coursetype' => new external_value(
            PARAM_INT,
            'Course type',
            VALUE_DEFAULT,
            1),
        )),
      ));
    }

    /**
     * Adds the given course to the given education module.
     *
     * @return array course
     */
    public static function add_module_course($moduleid, array $model) {
      global $DB, $USER;

      $params = self::validate_parameters(
        self::add_module_course_parameters(),
        array('moduleid' => $moduleid, 'model' => $model)
      );
      $moduleid = $params['moduleid'];
      $course = $params['model'];

      module_exists($moduleid);
      course_exists($course['courseid']);

      if (!has_sys_capability('tool/epman:editmodule', $USER->id)) {
        if (!module_responsible($moduleid, $USER->id)) {
          if (!module_assistant($moduleid, $USER->id)) {
            throw new moodle_exception("You don't have right to modify the course set of this education module");
          }
        }
      }

      $course['moduleid'] = $moduleid;
      $DB->insert_record('tool_epman_module_course', $course, false);

      return self::get_module_course($moduleid, $course['courseid']);
    }

    /**
     * Returns the description of the `add_module_course` method's
     * return value.
     *
     * @return external_description
     */
    public static function add_module_course_returns() {
      return self::get_module_course_returns();
    }


    /* Define the `update_module_course` implementation functions. */

    /**
     * Returns the description of the `update_module_course` method's
     * parameters.
     *
     * @return external_function_parameters
     */
    public static function update_module_course_parameters() {
      return new external_function_parameters(array(
        'moduleid' => new external_value(
          PARAM_INT,
          'Education module ID'),
        'model' => new external_single_structure(array(
          'coursetype' => new external_value(
            PARAM_INT,
            'Course type',
            VALUE_OPTIONAL),
        )),
      ));
    }

    /**
     * Updates the membership info for the given course.
     *
     * @return array course (updated fields)
     */
    public static function update_module_course($moduleid, array $model) {
      global $DB, $USER;

      $params = self::validate_parameters(
        self::update_module_course_parameters(),
        array('moduleid' => $moduleid, 'model' => $model)
      );
      $moduleid = $params['moduleid'];
      $course = $params['model'];

      module_exists($moduleid);
      course_exists($course['courseid']);

      $course['moduleid'] = $moduleid;
      $course0 = $DB->get_record('tool_epman_module_course', array('moduleid' => $course['moduleid'], 'courseid' => $course['courseid']));
      if ($course0) {
        $course0 = (array) $course0;
      }

      if (!has_sys_capability('tool/epman:editmodule', $USER->id)) {
        if (!module_responsible($moduleid, $USER->id)) {
          if (!module_assistant($moduleid, $USER->id)) {
            throw new moodle_exception("You don't have right to modify the course set of this education module");
          }
        }
      }

      $DB->update_record('tool_epman_module_course', $course);

      return $course;
    }

    /**
     * Returns the description of the `update_module_course` method's
     * return value.
     *
     * @return external_description
     */
    public static function update_module_course_returns() {
      return new external_single_structure(array(
        'coursetype' => new external_value(
          PARAM_INT,
          'Course type',
          VALUE_OPTIONAL),
      ));
    }

    
    /* Define the `delete_module_course` implementation functions. */

    /**
     * Returns the description of the `delete_module_course` method's
     * parameters.
     *
     * @return external_function_parameters
     */
    public static function delete_module_course_parameters() {
      return new external_function_parameters(array(
        'moduleid' => new external_value(
          PARAM_INT,
          'Education module ID'),
        'id' => new external_value(
          PARAM_INT,
          'Education module course ID'),
      ));
    }

    /**
     * Removes the given course from the set of courses of
     * the given education module.
     *
     * @return bool success flag
     */
    public static function delete_module_course($moduleid, $id) {
      global $USER, $DB;

      $params = self::validate_parameters(
        self::delete_module_course_parameters(),
        array('moduleid' => $moduleid, 'id' => $id)
      );
      $moduleid = $params['moduleid'];
      $id = $params['id'];

      module_exists($moduleid);

      if (module_course($moduleid, $id)) {
        if (!has_sys_capability('tool/epman:editmodule', $USER->id)) {
          if (!module_responsible($moduleid, $USER->id)) {
            if (!module_assistant($moduleid, $USER->id)) {
              throw new moodle_exception("You don't have right to modify the course set of this education module");
            }
          }
        }
        $DB->delete_records('tool_epman_module_course', array('moduleid' => $moduleid, 'courseid' => $id));
        return true;
      } else {
        return false;
      }
    }

    /**
     * Returns the description of the `delete_module_course` method's
     * return value.
     *
     * @return external_description
     */
    public static function delete_module_course_returns() {
      return new external_value(
        PARAM_BOOL,
        'Successfull return flag'
      );
    }

}

?>
