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
 * process management module. This module defines the base class for
 * all WS-implementations.
 *
 * @package    tool
 * @subpackage epman
 * @copyright  2014 Paul Wolneykien <manowar@altlinux.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->libdir/externallib.php");

class crud_external_api extends external_api {

  /**
   * Filter out all unexpected params than validate.
   *
   */
  public static function validate_parameters(external_description $description, $params) {

    $newparams = array();
    foreach ($params as $key => $value) {
      if (array_key_exists($key, $description->keys)) {
        $newparams[$key] = $params[$key];
      }
    }

    return parent::validate_parameters($description, $newparams);
  }

}

?>
