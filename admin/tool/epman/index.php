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
 * The front page of the education process management module.
 *
 * @package    tool
 * @subpackage epman
 * @copyright  2014 Paul Wolneykien <manowar@altlinux.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('toolepman');
$PAGE->set_pagelayout('maintenance');

function get_token() {
  global $DB, $USER;

  $service = $DB->get_record('external_services', array('shortname' => 'epman'));
  if (empty($service)) {
    throw new webservice_access_exception(get_string('servicenotavailable', 'webservice'));
  }

  $token = $DB->get_record(
      'external_tokens',
      array(
          'userid' => $USER->id,
          'creatorid' => $USER->id,
          'sid' => session_id(),
          'externalserviceid' => $service->id,
      )
  );

  if (empty($token)) {
    $sitecontext = get_context_instance(CONTEXT_SYSTEM);
    $tokenhash = external_create_service_token('epman', $sitecontext);
    return $tokenhash;
  } else {
    return $token->token;
  }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'tool_epman'));
$token = get_token();
echo "<h4>Token: $token</h4>\n";
echo $OUTPUT->footer();
?>
