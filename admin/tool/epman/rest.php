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

require("../../../config.php");
require_once("$CFG->dirroot/webservice/rest/locallib.php");

class wshelper extends webservice {

  public function __construct($shortname) {
    $service = $this->get_external_service_by_shortname($shortname);
    if (!empty($service) && isset($service->id)) {
      $this->serviceid = $service->id;
      $this->capabilities = $this->get_service_required_capabilities($this->serviceid);
    }
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

  public function service_function_exists($functionname, $serviceid = null) {
    if (!isset($serviceid) || !$serviceid) {
      $serviceid = $this->serviceid;
    }
    return parent::service_function_exists($functionname, $serviceid);
  }

}

class epman_rest_server extends webservice_rest_server {

  public function __construct() {
    global $DB;

    parent::__construct(WEBSERVICE_AUTHMETHOD_SESSION_TOKEN);
    $this->wsname = 'epman';
    $this->restformat = 'json';
    $this->wshelper = new wshelper('epman');
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
    try {
      return $this->do_parse_request();
    } catch (Exception $ex) {
      $this->set_response_code($ex);
      throw $ex;
    }
  }

  protected function do_parse_request() {
    $params = array_merge($_GET,$_POST);

    if (substr($_SERVER['REQUEST_URI'], 0, strlen($_SERVER['SCRIPT_NAME'])) == $_SERVER['SCRIPT_NAME']) {
      $crud_path = substr($_SERVER['REQUEST_URI'], strlen($_SERVER['SCRIPT_NAME']));
      if (substr($crud_path, strlen($crud_path) - strlen($_SERVER['QUERY_STRING']), strlen($_SERVER['QUERY_STRING'])) == $_SERVER['QUERY_STRING'])
      {
        $crud_path = substr($crud_path, 0, strlen($crud_path) - strlen($_SERVER['QUERY_STRING']) - 1);
        debugging("CRUD path: $crud_path");
        $crud_path = explode('/', $crud_path);
      }
    }

    if (!isset($crud_path) || empty($crud_path)) {
      throw new invalid_parameter_exception("CRUD path is empty");
    }

    if (isset($params['_method'])) {
      $method = $params['_method'];
    } else {
      $method = $_SERVER['REQUEST_METHOD'];
    }
    if (!isset($method)) {
      throw new moodle_exception("HTTP method is unknown");
    }

    if (!isset($params['wsfunction'])) {
      $wsobjs = array();
      foreach ($crud_path as $el) {
        if ($el == "") {
          continue;
        }
        if (preg_match('/^[0-9]+$/', $el)) {
          if (!empty($wsobjs)) {
            $wsobjs[count($wsobjs) - 1]['id'] = $el;            
          } else {
            throw new invalid_parameter_exception("Invalid CRUD path specified");
          }
        } elseif (substr($el, strlen($el) - 1, 1) == 's') {
          $wsobjs[] = array('name' => substr($el, 0, strlen($el) - 1), 'id'  => '*');
        } else {
          $wsobjs[] = array('name' => $el, 'id' => '*');
        }
      }

      if (empty($wsobjs)) {
        throw new moodle_exception("Error parsing the CRUD path");
      }

      $wsfunctionsuf = '';
      $wsargs = array();
      foreach ($wsobjs as $i => $wsobj) {
        $wsfunctionsuf = $wsfunctionsuf."_".$wsobj['name'];
        if ($wsobj['id'] != '*') {
          if ($i < (count($wsobjs) - 1)) {
            $wsargs[$wsobj['name']."id"] = $wsobj['id'];
          } else {
            $wsargs['id'] = $wsobj['id'];
          }
        }
      }

      if ($method == 'GET') {
        $lastobj = $wsobjs[count($wsobjs) - 1];
        if ($lastobj['id'] == '*') {
          $wsfunctions = array("get".$wsfunctionsuf."s", "list".$wsfunctionsuf."s", "read".$wsfunctionsuf."s");
        } else {
          $wsfunctions = array("get".$wsfunctionsuf, "list".$wsfunctionsuf, "read".$wsfunctionsuf);
        }
      } elseif ($method == 'POST') {
        $wsfunctions = array("create".$wsfunctionsuf, "add".$wsfunctionsuf);
      } elseif ($method == 'PUT' || $method == 'PATCH') {
        $wsfunctions = array("update".$wsfunctionsuf);
      } elseif ($method == 'DELETE') {
        $wsfunctions = array("delete".$wsfunctionsuf);
      }

      if (isset($wsfunctions)) {
        foreach ($wsfunctions as $name) {
          if ($this->wshelper->service_function_exists("tool_epman_".$name)) {
            $wsfunction = "tool_epman_".$name;
          }
        }
      }

      if (!isset($wsfunction)) {
        if ($method == 'GET') {
          throw new invalid_parameter_exception("No suitable READ function found for ".implode("/", $crud_path));
        } elseif ($method == 'POST') {
          throw new invalid_parameter_exception("No suitable CREATE function found for ".implode("/", $crud_path));
        } elseif ($method == 'PUT' || $method == 'PATCH') {
          throw new invalid_parameter_exception("No suitable UPDATE function found for ".implode("/", $crud_path));
        } elseif ($method == 'DELETE') {
          throw new invalid_parameter_exception("No suitable DELETE function found for ".implode("/", $crud_path));
        }
      }

      $params['wsfunction'] = $wsfunction;
      $_GET['wsfunction'] = $wsfunction;
      foreach ($wsargs as $wsargname => $wsarg) {
        debugging("WS argument: $wsargname => $wsarg");
        $params[$wsargname] = $wsarg;
        $_GET[$wsargname] = $wsarg;
      }
    }

    debugging("WS function: ".$params['wsfunction']);

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

  protected function execute() {
    try {
      parent::execute();
    } catch (Exception $ex) {
      $this->set_response_code($ex);
      throw $ex;
    }
  }

  protected function load_function_info() {
    try {
      parent::load_function_info();
    } catch (Exception $ex) {
      $this->set_response_code($ex);
      throw $ex;
    }
  }

  protected function send_response() {
    try {
      if ($this->function->returns_desc != null) {
        $validatedvalues = external_api::clean_returnvalue($this->function->returns_desc, $this->returns);
      } else {
        $validatedvalues = null;
      }
    } catch (Exception $ex) {
      $exception = $ex;
    }

    if (!empty($exception)) {
      $response =  $this->generate_error($exception);
      $this->set_response_code($exception);
    } else {
      $response = json_encode($validatedvalues);
    }

    $this->send_headers();
    echo $response;
  }

  protected function set_response_code($exception = null) {
    if (isset($exception->http_response_code)) {
      $this->response_code = $exception->http_response_code;
    }
    if ($exception && (!isset($this->response_code) || $this->response_code == 200)) {
      if ($exception instanceof invalid_parameter_exception) {
        $this->response_code = 400;
      } elseif ($exception instanceof webservice_access_exception) {
        $this->response_code = 403;
      } else {
        $this->response_code = 500;
      }
    }
  }

  protected function send_headers() {
    if (isset($this->response_code)) {
      switch ($this->response_code) {
      case 400:
        header($_SERVER['SERVER_PROTOCOL']." 400 Bad Request", true, 400);
        break;
      case 403:
        header($_SERVER['SERVER_PROTOCOL']." 403 Forbidden", true, 403);
        break;
      case 404:
        header($_SERVER['SERVER_PROTOCOL']." 404 Not Found", true, 404);
        break;
      case 500:
        header($_SERVER['SERVER_PROTOCOL']." 500 Internal Server Error", true, 500);
        break;
      }
    }
    parent::send_headers();
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
