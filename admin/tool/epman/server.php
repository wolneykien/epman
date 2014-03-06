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
 * REST web service entry point for the embedded apps of the
 * education process management module.
 *
 * @package    tool
 * @subpackage epman
 * @copyright  2014 Paul Wolneykien <manowar@altlinux.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// disable moodle specific debug messages and any errors in output
define('NO_DEBUG_DISPLAY', true);
define('NO_MOODLE_COOKIES', true);

require('../../../config.php');
require_once("$CFG->dirroot/webservice/rest/locallib.php");

class epman_rest_server extends webservice_rest_server {

  public function __construct() {
    parent::__construct(WEBSERVICE_AUTHMETHOD_SESSION_TOKEN);
    $this->wsname = 'epman';
    $this->restformat = 'json';
  }

}

if (!webservice_protocol_is_enabled('rest')) {
  debugging('The server died because the web services or the REST protocol are not enable', DEBUG_DEVELOPER);
  die;
}

if(isset($_GET['moodlewsrestformat'])) {
    unset($_GET['moodlewsrestformat']);
}
if(isset($_POST['moodlewsrestformat'])) {
    unset($_POST['moodlewsrestformat']);
}

$server = new epman_rest_server();
$server->run();
die;
?>
