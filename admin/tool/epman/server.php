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

class wshelper extends webservice {

  public function __construct($serviceid) {
    $this->capabilities = $this->get_service_required_capabilities($serviceid);
    $this->systemctx = get_context_instance(CONTEXT_SYSTEM);
  }

  public function check_function_access($userid, $functionname) {
    $caps = null;
    if (isset($this->capabilities) &&
        isset($this->capabilities[$functionname])) {
      $caps = $this->capabilities[$functionname];
    }
    if (!empty($caps)) {
      foreach ($caps as $cap) {
        if (!has_capability($cap, $this->systemctx, $userid)) {
          throw new webservice_access_exception(get_string('missingrequiredcapability', 'webservice', $cap));
        }
      }
    }
  }

}

class epman_rest_server extends webservice_rest_server {

  public function __construct() {
    global $DB;

    parent::__construct(WEBSERVICE_AUTHMETHOD_SESSION_TOKEN);
    $this->wsname = 'epman';
    $this->restformat = 'json';

    $service = $DB->get_record('external_services', array('shortname' => 'epman'));
    if (!empty($service) && isset($service->id)) {
      $this->serviceid = $service->id;
      $this->wshelper = new wshelper($this->serviceid);
    }
  }

  public function authenticate_user() {
    parent::authenticate_user();

    if (isset($this->wshelper)) {
      $this->wshelper->check_function_access($this->userid, $this->functionname);
    } else {
      throw new webservice_access_exception(get_string('servicenotavailable', 'webservice'));
    }
  }

  protected function parse_request() {
    $params = array_merge($_GET,$_POST);
    if (isset($params['model'])) {
      //debugging('JSON: '.$params['model']);
      $model = json_decode($params['model'], true);
      //debugging('Model: '.json_encode($model));
      $_POST['model'] = $model;
      unset($_GET['model']);
    }
    if (isset($params['id']) && $params['id'] == '') {
      unset($_GET['id']);
      unset($_POST['id']);
    }
    return parent::parse_request();
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
