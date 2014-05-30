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

require_once("$CFG->libdir/moodlelib.php");
require_once("$CFG->libdir/externallib.php");
require_once("$CFG->libdir/enrollib.php");


/* Define the helper functions. */

/**
 * Clears the assistant user set for the given education
 * program.
 */
function clear_program_assistants($programid) {
  global $DB;
  
  program_exists($programid);
  $DB->delete_records('tool_epman_program_assistant', array('programid' => $programid));
}

/**
 * Clears the module set for the given education program.
 */
function clear_program_modules($programid) {
  global $DB;
  
  program_exists($programid);
  $DB->delete_records('tool_epman_module', array('programid' => $programid));
}

class object_not_found_exception extends invalid_parameter_exception {

  public function __construct($msg) {
    parent::__construct($msg);
    $this->http_response_code = 404;
  }

}

class object_already_exists_exception extends invalid_parameter_exception {

  public function __construct($msg) {
    parent::__construct($msg);
    $this->http_response_code = 409;
  }

}

/**
 * Checks if the course with the given ID exists.
 *
 * @throw object_not_found_exception
 */
function course_exists($courseid) {
  global $DB;
  
  if (!$DB->record_exists('course', array('id' => $courseid))) {
    throw new object_not_found_exception("Course doesn't exist: $courseid");
  }
}

/**
 * Clears the course set for the given education program module.
 */
function clear_module_courses($moduleid) {
  global $DB;
  
  module_exists($moduleid);
  $DB->delete_records('tool_epman_module_course', array('moduleid' => $moduleid));
}

/**
 * Checks if the education program with the given ID exists.
 *
 * @throw object_not_found_exception
 */
function program_exists($programid) {
  global $DB;
  
  if (!$DB->record_exists('tool_epman_program', array('id' => $programid))) {
    throw new object_not_found_exception("Program doesn't exist: $programid");
  }
}

/**
 * Checks if the education program module with the given ID exists.
 *
 * @throw object_not_found_exception
 */
function module_exists($moduleid) {
  global $DB;
  
  if (!$DB->record_exists('tool_epman_module', array('id' => $moduleid))) {
    throw new object_not_found_exception("Module doesn't exist: $moduleid");
  }
}

/**
 * Checks if the user with the given ID exists.
 *
 * @throw object_not_found_exception
 */
function user_exists($userid) {
  global $DB;
  
  if (!$DB->record_exists('user', array('id' => $userid))) {
    throw new object_not_found_exception("The user doesn't exist: $userid");
  }
}

/**
 * Checks if the user with the given ID doesn't exist.
 *
 * @throw object_not_found_exception
 */
function user_not_exists($username) {
  global $DB;
  
  if ($DB->record_exists('user', array('username' => $userid))) {
    throw new object_already_exists_exception("The user already exists: $username");
  }
}

/**
 * Checks if the academic group with the given ID exists.
 *
 * @throw object_not_found_exception
 */
function group_exists($groupid) {
  global $DB;
  
  if (!$DB->record_exists('tool_epman_group', array('id' => $groupid))) {
    throw new object_not_found_exception("Group doesn't exist: $groupid");
  }
}

/**
 * Clears the assistant user set for the given academic
 * group.
 */
function clear_group_assistants($groupid) {
  global $DB;
  
  group_exists($groupid);
  $DB->delete_records('tool_epman_group_assistant', array('groupid' => $groupid));
}

/**
 * Clears the assistant user set for the given academic
 * group.
 */
function clear_group_students($groupid) {
  global $DB;
  
  group_exists($groupid);
  $DB->delete_records('tool_epman_group_student', array('groupid' => $groupid));
}

/**
 * Checks if the given user (id) has the given system capability.
 */
function has_sys_capability($capability, $userid) {
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
function program_responsible($programid, $userid) {
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
function program_assistant($programid, $userid) {
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
 * Returns the last module period within the given
 * education program or 0.
 */
function get_last_module_period($programid) {
  global $DB;

  $period = $DB->get_field_sql(
    'select period from {tool_epman_module} '.
    'where id = (select max(id) from {tool_epman_module} where programid = :programid)',
    array('programid' => $programid)
  );

  if ($period) {
    return $period;
  } else {
    return 0;
  }
}

/**
 * Checks if the given user (id) is responsible for the
 * given academic group (id).
 */
function group_responsible($groupid, $userid) {
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
 * Checks if the given user (id) is an assistant of the
 * given academic group (id).
 */
function group_assistant($groupid, $userid) {
  global $DB, $USER;
  
  if (!isset($userid)) {
    $userid = $USER->id;
  }
  
  group_exists($groupid);
  user_exists($userid);
  
  return $DB->record_exists(
    'tool_epman_group_assistant',
    array(
      'groupid' => $groupid,
      'uerid' => $userid
    )
  );
}

/**
 * Checks if the given user (id) is a student of the
 * given academic group (id).
 */
function group_student($groupid, $userid) {
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

class permission_exception extends moodle_exception {

  public function __construct($msg) {
    parent::__construct('permission', 'debug', '', null, $msg);
    $this->http_response_code = 403;
  }

}

/**
 * Checks that the given field is not being modified.
 */
function value_unchanged($currentmodel, $newmodel, $key, $title) {
  if (array_key_exists($key, $newmodel) && $currentmodel[$key] != $newmodel[$key]) {
    throw new permission_exception("You don't have the right to change the ".($title ? $title : $key));
  }
}

/**
 * Returns the epman enrol plugin or raises the error.
 */
function get_enrol() {
  $enrol = enrol_get_plugin('epman');
  if (!$enrol) {
    throw new moodle_exception("Enrol plugin is disabled, can't manage student enrolments");
  }
  return $enrol;
}

/**
 * Synchronize the enrolment data for all of the users who are
 * enroled in accordance with the group membership data but not with
 * the former.
 */
function sync_new_enrolments($groupid) {
  global $DB;

  $enrol = get_enrol();

  $newenrols = $DB->get_recordset_sql(
    'select e.*, mc.courseid as newcourseid, gs.userid from '.
    '{tool_epman_group_student} gs '.
    'left join {tool_epman_group} g on g.id = gs.groupid '.
    'left join {tool_epman_module} m on m.programid = g.programid '.
    'and m.period = gs.period '.
    'left join {tool_epman_module_course} mc on mc.moduleid = m.id '.
    'left join {enrol} e on e.courseid = mc.courseid '.
    'left join {user_enrolments} ue on ue.enrolid = e.id '.
    'and ue.userid = gs.userid '.
    'where g.id = :groupid and e.enrol = :name and gs.userid is not null '.
    'and ue.userid is null',
    array('groupid' => $groupid, 'name' => $enrol->get_name()));

  $newinstances = array();
  foreach ($newenrols as $newenrol) {
    $userid = $newenrol->userid;
    $courseid = $newenrol->newcourseid;
    if (!isset($newenrol->id)) {
      if (!array_key_exists($courseid, $newinstances)) {
        $course = new stdObject(array('id' => $courseid));
        debugging("Add new epman enrol instance for the course $courseid");
        $enrolid = $enrol->add_instance($course);
        $newenrol = $DB->get_record('enrol', array('id' => $enrolid));
        $newinstances[$courseid] = $newenrol;
      } else {
        $newenrol = $newinstances[$courseid];
      }
    }
    debugging("Enrol the user $userid to the course $courseid");
    $enrol->enrol_user($newenrol, $userid);
  }
  $newenrols->close();
}

/**
 * Synchronize the enrolment data for all of the users who are
 * not enroled in accordance with the group membership data but
 * still are enroled with the former.
 */
function sync_old_enrolments($groupid) {
  global $DB;

  $enrol = get_enrol();

  $oldenrols = $DB->get_recordset_sql(
    'select e.*, gs.userid from '.
    '{enrol} e '.
    'left join {user_enrolments} ue on ue.enrolid = e.id '.
    'left join {tool_epman_module_course} mc on mc.courseid = e.courseid '.
    'left join {tool_epman_module} m on m.id = mc.moduleid '.
    'left join {tool_epman_group} g on g.programid = m.programid '.
    'left join {tool_epman_group_student} gs on gs.groupid = g.id '.
    'and gs.period = m.period '.
    'where g.id = :groupid and e.enrol = :name and ue.userid is not null '.
    'and gs.userid is null',
    array('groupid' => $groupid, 'name' => $enrol->get_name()));

  foreach ($oldenrols as $oldenrol) {
    debugging("Un-enrol the user $userid from the course $courseid");
    $enrol->unenrol_user($oldenrol, $oldenrol->userid);
  }
  $oldenrols->close();
}

/**
 * Synchronize the enrolment data in accordance with the group
 * membership data.
 */
function sync_enrolments($groupid) {
  sync_old_enrolments($groupid);
  sync_new_enrolments($groupid);
}

/**
 * Creates a new user account with the specified params.
 */
function create_moodle_user(array $params) {
  $user = create_user_record($params['username'], $params['password']);
  if ($user && $user->id) {
    $params['id'] = $user->id;
    update_user($params);
    return $user->id;
  } else {
    throw new moodle_exception("Unable to register the new user account");
  }
}

/**
 * Updates the user account data with the specified params.
 */
function update_moodle_user(array $params) {
  global $DB;

  $user = $DB->get_record('user', array('id' => $params['id']));
  if ($user && $user->id) {
    $user->firstname = $params['firstname'];
    $user->lastname = $params['lastname'];
    $user->email = $params['email'];
    $DB->update_record('user', $user);
  } else {
    throw new object_not_found_exception("User account not found: ".$params['id']);
  }
}

/**
 * Deletes the user account with the given ID.
 */
function delete_moodle_user($userid) {
  global $DB;

  $user = $DB->get_record('user', array('id' => $userid));
  if ($user && $user->id) {
    delete_user($user);
  } else {
    throw new object_not_found_exception("User account not found: $userid");
  }
}

/**
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
