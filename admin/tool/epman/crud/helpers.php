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
 * process management module. This module defines helper functions.
 *
 * @package    tool
 * @subpackage epman
 * @copyright  2014 Paul Wolneykien <manowar@altlinux.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->libdir/externallib.php");


/* Define the helper functions. */

/**
 * Clears the assistant user set for the given education
 * program.
 */
public function clear_program_assistants($programid) {
  global $DB;
  
  program_exists($programid);
  $DB->delete_records('tool_epman_program_assistant', array('programid' => $programid));
}

/**
 * Clears the module set for the given education program.
 */
public function clear_program_modules($programid) {
  global $DB;
  
  program_exists($programid);
  $DB->delete_records('tool_epman_module', array('programid' => $programid));
}

/**
 * Clears the course set for the given education program module.
 */
public function clear_module_courses($moduleid) {
  global $DB;
  
  module_exists($moduleid);
  $DB->delete_records('tool_epman_module_course', array('moduleid' => $moduleid));
}

/**
 * Checks if the education program with the given ID exists.
 *
 * @throw invalid_parameter_exception
 */
public function program_exists($programid) {
  global $DB;
  
  if (!$DB->record_exists('tool_epman_program', array('id' => $programid))) {
    throw new invalid_parameter_exception("Program doesn't exist: $programid");
  }
}

/**
 * Checks if the education program module with the given ID exists.
 *
 * @throw invalid_parameter_exception
 */
public function module_exists($moduleid) {
  global $DB;
  
  if (!$DB->record_exists('tool_epman_modules', array('id' => $moduleid))) {
    throw new invalid_parameter_exception("Module doesn't exist: $moduleid");
  }
}

/**
 * Checks if the user with the given ID exists.
 *
 * @throw invalid_parameter_exception
 */
public function user_exists($userid) {
  global $DB;
  
  if (!$DB->record_exists('user', array('id' => $userid))) {
    throw new invalid_parameter_exception("Responsible user doesn't exist: $userid");
  }
}

/**
 * Checks if the academic group with the given ID exists.
 *
 * @throw invalid_parameter_exception
 */
public function group_exists($groupid) {
  global $DB;
  
  if (!$DB->record_exists('tool_epman_group', array('id' => $programid))) {
    throw new invalid_parameter_exception("Group doesn't exist: $groupid");
  }
}

/**
 * Clears the assistant user set for the given academic
 * group.
 */
public function clear_group_assistants($groupid) {
  global $DB;
  
  group_exists($groupid);
  $DB->delete_records('tool_epman_group_assistant', array('groupid' => $groupid));
}

/**
 * Clears the assistant user set for the given academic
 * group.
 */
public function clear_group_students($groupid) {
  global $DB;
  
  group_exists($groupid);
  $DB->delete_records('tool_epman_group_student', array('groupid' => $groupid));
}

/**
 * Checks if the given user (id) has the given system capability.
 */
public function has_sys_capability($capability, $userid) {
  global $USER;
  
  if (!isset($userid)) {
    $userid = $USER->id;
  }
  
  user_exists($userid);
  $systemctx = get_context_instance(CONTEXT_SYSTEM);
  return has_capability($capability, $systemctx, $userid);
}

/**
 * Checks if the given user (id) is responsible for the
 * given education program (id).
 */
public function program_responsible($programid, $userid) {
  global $DB, $USER;
  
  if (!isset($userid)) {
    $userid = $USER->id;
  }
  
  program_exists($programid);
  user_exists($userid);
  
  return $DB->record_exists(
    'tool_epman_program',
    array(
      'id' => $programid,
      'responsibleid' => $userid
    )
  );
}

/**
 * Checks if the given user (id) is responsible for the
 * given education program (id).
 */
public function program_assistant($programid, $userid) {
  global $DB, $USER;
  
  if (!isset($userid)) {
    $userid = $USER->id;
  }
  
  program_exists($programid);
  user_exists($userid);
  
  return $DB->record_exists(
    'tool_epman_program_assistant',
    array(
      'programid' => $programid,
      'uerid' => $userid
    )
  );
}

/**
 * Returns the position for the next module within the given
 * education program.
 */
public function get_next_module_position($programid) {
  global $DB;

  $position = $DB->get_record_sql(
    'select max(position) from {tool_epman_module} '.
    'where programid = ?',
    array('programid' => $programid)
  );

  if ($position >= 0) {
    return $position + 1;
  } else {
    return 0;
  }
}

/**
 * Checks if the given user (id) is responsible for the
 * given academic group (id).
 */
public function group_responsible($groupid, $userid) {
  global $DB, $USER;
  
  if (!isset($userid)) {
    $userid = $USER->id;
  }
  
  group_exists($groupid);
  user_exists($userid);
  
  return $DB->record_exists(
    'tool_epman_group',
    array(
      'id' => $groupid,
      'responsibleid' => $userid
    )
  );
}

/**
 * Checks if the given user (id) is a student of the
 * given academic group (id).
 */
public function group_student($groupid, $userid) {
  global $DB, $USER;
  
  if (!isset($userid)) {
    $userid = $USER->id;
  }
  
  group_exists($groupid);
  user_exists($userid);
  
  return $DB->record_exists(
    'tool_epman_group_student',
    array(
      'groupid' => $groupid,
      'uerid' => $userid
    )
  );
}


/*
 * Simple stdClass creation.
 *
 */
class stdObject {

  public function __construct(array $arguments = array()) {
    if (!empty($arguments)) {
      foreach ($arguments as $property => $argument) {
        $this->{$property} = $argument;
      }
    }
  }

}

?>
