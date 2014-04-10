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
 * for the users (read only).
 *
 * @package    tool
 * @subpackage epman
 * @copyright  2014 Paul Wolneykien <manowar@altlinux.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("base.php");
require_once("helpers.php");

class epman_user_external extends crud_external_api {

  /* Define the `list_users` implementation functions. */
  
  /**
   * Returns the description of the `list_users` method's
   * parameters.
   *
   * @return external_function_parameters
   */
  public static function list_users_parameters() {
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
   * Returns the list of users matching the given pattern.
   *
   * @return array of education program modules
   */
   public static function list_users($like, $skip = 0, $limit = null) {
      global $DB;

      $params = self::validate_parameters(
        self::list_users_parameters(),
        array('like' => $like, 'skip' => $skip, 'limit' => $limit)
      );
      $like = $params['like'];
      $skip = $params['skip'];
      $limit = $params['limit'];

      $like = "%${like}%";

      $users = $DB->get_records_select(
        'user',
        'username like ? or '.
        'firstname like ? or '.
        'lastname like ? or '.
        'email like ?',
        array($like, $like, $like, $like),
        '',
        'id, username, firstname, lastname, email',
        $skip,
        $limit);

      return array_map(
        function($user) {
          return (array) $user;
        },
        $users
      );
    }

    /**
     * Returns the description of the `list_users` method's
     * return value.
     *
     * @return external_description
     */
    public static function list_users_returns() {
      return new external_multiple_structure(
        new external_single_structure(array(
          'id' => new external_value(
            PARAM_INT,
            'ID of the user user'),
          'username' => new external_value(
            PARAM_TEXT,
            'System name of the user user',
            VALUE_OPTIONAL),
          'firstname' => new external_value(
            PARAM_TEXT,
            'First name of the user user',
            VALUE_OPTIONAL),
          'lastname' => new external_value(
            PARAM_TEXT,
            'Last name of the user user',
            VALUE_OPTIONAL),
          'email' => new external_value(
            PARAM_TEXT,
            'E-mail of the user user',
            VALUE_OPTIONAL),
        )));
    }


    /**
     * Returns the description of the `get_user` method's
     * parameters.
     *
     * @return external_function_parameters
     */
    public static function get_user_parameters() {
      return new external_function_parameters(array(
        'id' => new external_value(
          PARAM_INT,
          'The ID of the user to get'),
    ));
  }

  /**
   * Returns the user's data.
   *
   * @return array (user)
   */
    public static function get_user($id) {
      global $DB;

      $params = self::validate_parameters(
        self::get_user_parameters(),
        array('id' => $id)
      );
      $id = $params['id'];

      user_exists($id);

      $user = $DB->get_record('user', array('id' => $id));

      return (array) $user;
    }

    /**
     * Returns the description of the `get_user` method's
     * return value.
     *
     * @return external_description (user)
     */
    public static function get_user_returns() {
      return new external_single_structure(array(
        'id' => new external_value(
          PARAM_INT,
           'ID of the user user'),
        'username' => new external_value(
          PARAM_TEXT,
          'System name of the user user',
          VALUE_OPTIONAL),
        'firstname' => new external_value(
          PARAM_TEXT,
          'First name of the user user',
          VALUE_OPTIONAL),
        'lastname' => new external_value(
          PARAM_TEXT,
          'Last name of the user user',
          VALUE_OPTIONAL),
        'email' => new external_value(
          PARAM_TEXT,
          'E-mail of the user user',
          VALUE_OPTIONAL),
      ));
    }

}
?>
