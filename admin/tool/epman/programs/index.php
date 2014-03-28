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
 * The education program list page of the education process management module.
 *
 * @package    tool
 * @subpackage epman
 * @copyright  2014 Paul Wolneykien <manowar@altlinux.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('../locallib.php');

$PAGE->requires->css('/admin/tool/epman/styles/redmond/jquery-ui-1.10.3.custom.css');
//$PAGE->requires->css('/admin/tool/epman/styles/epman.css');

$PAGE->requires->js('/admin/tool/epman/js/lib/jquery.js');
$PAGE->requires->js('/admin/tool/epman/js/lib/jquery-ui-1.10.3.custom.js');
$PAGE->requires->js('/admin/tool/epman/js/lib/underscore.js');
$PAGE->requires->js('/admin/tool/epman/js/lib/backbone.js');
$PAGE->requires->js('/admin/tool/epman/js/lib/local-config.js');

$PAGE->requires->data_for_js('toolEpmanPageOptions', array(
    'restRoot' => "../rest.php",
    'restParams' => array(
        'wstoken' => get_token(),
    ),
    'emulateHTTP' => true,
    'emulateJSON' => true,
), true);

$PAGE->requires->js('/admin/tool/epman/js/programs.js');

admin_externalpage_setup('toolepman');
$PAGE->set_pagelayout('maintenance');
?>
<?php
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('programlistheading', 'tool_epman'));
?>
<div class="tool-epman">
  <div class="panel right vspace">
    <span class="link-button switch right responsible">
      <?php echo get_string('myprograms', 'tool_epman'); ?>
    </span>
  </div>
  <div id="program-list" class="record-list" style="display: none;">
    <div id="program-<@= p.get('id') @>" class="record">
      <div class="record-header show-more">
        <@= p.get('name') @>
        <div class="link-button right responsible">
          <a href="<@= p.get('responsible') && p.get('responsible').id ? '/user/profile.php?id=' + p.get('responsible').id : '' @>">
            <@= p.get('responsible') && p.get('responsible').id ? p.get('responsible').firstname + " " + p.get('responsible').lastname : "<?php echo get_string('notspecified', 'tool_epman'); ?>" @>
          </a>
        </div>
        <div class="link-button right groups">
          <a href="../groups/index.php?programid=<@= p.get('id') @>">
            <?php echo get_string('groups', 'tool_epman'); ?>
          </a>
        </div>
     </div>
    </div>
  </div>
</div>
<?php
echo $OUTPUT->footer();
?>
