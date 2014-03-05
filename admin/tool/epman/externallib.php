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
  
  /**
   * Returns description of the `list_programs` method
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
    public static function list_programs($userid = 0) {
      global $USER;

      //Parameter validation
      //REQUIRED
      $params = self::validate_parameters(
          self::list_programs_parameters(),
          array('userid' => $userid)
      );

      $programs = array();
      
      return $programs;
    }

    /**
     * Returns description of the `list_programs` method
     * return value.
     *
     * @return external_description
     */
    public static function list_programs_returns() {
      return new external_multiple_structure(
        new external_single_structure(array(
          'id' => new external_value(
            PARAM_INT,
            'education program ID'),
          'name' => new external_value(
            PARAM_TEXT,
            'education program name'),
          'description' => new external_value(
            PARAM_TEXT,
            'short description of the program'),
          'responsibleid' => new external_value(
            PARAM_INT,
            'ID of the responsible user'),
          'modules' => new external_multiple_structure(
            new external_value(
              PARAM_INT,
              'Program module ID')
          ),
          'assistants' => new external_multiple_structure(
            new external_value(
              PARAM_INT,
              'Assistant user ID')
          ),
        )));
    }

}

?>
