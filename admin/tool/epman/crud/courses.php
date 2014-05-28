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
 * for the courses (read only).
 *
 * @package    tool
 * @subpackage epman
 * @copyright  2014 Paul Wolneykien <manowar@altlinux.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("base.php");
require_once("helpers.php");

class epman_course_external extends crud_external_api {

  /* Define the `list_courses` implementation functions. */
  
  /**
   * Returns the description of the `list_courses` method's
   * parameters.
   *
   * @return external_function_parameters
   */
  public static function list_courses_parameters() {
    return new external_function_parameters(array(
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
   * Returns the list of courses matching the given pattern.
   *
   * @return array of education program modules
   */
   public static function list_courses($like, $skip = 0, $limit = null) {
      global $DB;

      $params = self::validate_parameters(
        self::list_courses_parameters(),
        array('like' => $like, 'skip' => $skip, 'limit' => $limit)
      );
      $like = $params['like'];
      $skip = $params['skip'];
      $limit = $params['limit'];

      if ($like) {
        $like = "%".preg_replace('/s+/', '%', $like)."%";
      }

      $where = ($like ? implode(' or ', array_map(function($field) {
              return $DB->sql_like($field, '?', false);
            }, array('c.id',
                     'c.shortname',
                     'c.fullname',
                     $DB->sql_concat_join(' ', array('cc.name', 'c.shortname')),
                     $DB->sql_concat_join(' ', array('cc.name', 'c.fullname'))))) : null);
      $courses = $DB->get_records_sql(
        'select c.id, c.shortname, c.fullname, cc.name as category from {course} c left join {course_categories} cc on c.category = cc.id'.
        ($where ? " where $where" : "").
        ' order by fullname',
        ($like ? array($like, $like, $like, $like, $like) : null),
        $skip,
        $limit);

      return array_map(
        function($course) {
          return array(
              'id' => $course->id,
              'shortname' => $course->shortname,
              'name' => $course->fullname,
              'category' => $course->category);
        },
        $courses
      );
    }

    /**
     * Returns the description of the `list_courses` method's
     * return value.
     *
     * @return external_description
     */
    public static function list_courses_returns() {
      return new external_multiple_structure(
        new external_single_structure(array(
          'id' => new external_value(
            PARAM_INT,
            'ID of the course'),
          'shortname' => new external_value(
            PARAM_TEXT,
            'Short name of the course',
            VALUE_OPTIONAL),
          'name' => new external_value(
            PARAM_TEXT,
            'Full name of the course',
            VALUE_OPTIONAL),
        )));
    }


    /**
     * Returns the description of the `get_course` method's
     * parameters.
     *
     * @return external_function_parameters
     */
    public static function get_course_parameters() {
      return new external_function_parameters(array(
        'id' => new external_value(
          PARAM_INT,
          'The ID of the course to get'),
    ));
  }

  /**
   * Returns the course's data.
   *
   * @return array (course)
   */
    public static function get_course($id) {
      global $DB;

      $params = self::validate_parameters(
        self::get_course_parameters(),
        array('id' => $id)
      );
      $id = $params['id'];

      course_exists($id);

      $course = $DB->get_record('course', array('id' => $id), 'id, shortname, fullname');

      return array('id' => $course->id, 'shortname' => $course->shortname, 'name' => $course->fullname);
    }

    /**
     * Returns the description of the `get_course` method's
     * return value.
     *
     * @return external_description (course)
     */
    public static function get_course_returns() {
      return new external_single_structure(array(
        'id' => new external_value(
          PARAM_INT,
          'ID of the course course'),
        'shortname' => new external_value(
          PARAM_TEXT,
          'Short name of the course',
          VALUE_OPTIONAL),
        'name' => new external_value(
          PARAM_TEXT,
          'Full name of the course',
          VALUE_OPTIONAL),
      ));
    }

}
?>
