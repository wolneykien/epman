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
 * The academic groups list page of the education process management module.
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
    'user' => $USER,
    'i18n' => array(
      'OK' => get_string('OK', 'tool_epman'),
      'Cancel' => get_string('Cancel', 'tool_epman'),
      'Close' => get_string('Close', 'tool_epman'),
      'Yes' => get_string('Yes', 'tool_epman'),
      'No' => get_string('No', 'tool_epman'),
      'Confirmation' => get_string('Confirmation', 'tool_epman'),
      'courseyear' => get_string('courseyear', 'tool_epman'),
      'N_days' => get_string('N_days', 'tool_epman'),
      'Ndays' => get_string('Ndays', 'tool_epman'),
      'Nth_period' => get_string('Nth_period', 'tool_epman'),
      'N_day_vacation' => get_string('N_day_vacation', 'tool_epman'),
      'N_day_overlap' => get_string('N_day_overlap', 'tool_epman'),
      'dateFormat' => get_string('dateFormat', 'tool_epman'),
      'Delete_selected_students_?' => get_string('Delete_selected_students_Q', 'tool_epman'),
      'Delete_the_academic_group_?' => get_string('Delete_the_academic_group_Q', 'tool_epman'),
    ),
), true);

$PAGE->requires->js('/admin/tool/epman/js/common.js');
$PAGE->requires->js('/admin/tool/epman/js/userselect.js');
$PAGE->requires->js('/admin/tool/epman/js/programselect.js');
$PAGE->requires->js('/admin/tool/epman/js/groups.js');

admin_externalpage_setup('toolepman');
$PAGE->set_pagelayout('maintenance');
?>
<?php
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('grouplistheading', 'tool_epman'));
?>

<!-- Templates -->
<div id="list-section-template" style="display: none;">
  <div id="year-<@= year @>" class="list-section">
    <div class="list-section-header">
      <span>
        <@= decline('courseyear', year) @>
      </span>
      <hr />
    </div>
  </div>
</div>
<div id="record-template" style="display: none;">
  <div id="group-<@= g.id @>" class="record collapsed">
    <div data-id="<@= g.id @>" class="record-header show-more">
      <@= g.name @>
      <@ if (!f.my || !g.responsible || g.responsible.id != <?php echo $USER->id; ?>) { @>
        <div class="link-button right responsible">
          <@ if (g.responsible && g.responsible.id) { @>
          <a href="<@= '/user/profile.php?id=' + g.responsible.id @>">
            <@= g.responsible.firstname + " " + g.responsible.lastname @>
          </a>
          <@ } else { @>
          <span class="comment"><?php echo get_string('notspecified', 'tool_epman'); ?></span>
          <@ } @>
        </div>
      <@ } @>
      <div class="link-button light nolink edit">
        <a role="edit-button" href="javascript:void(0)">
          <?php echo get_string('Edit_group', 'tool_epman'); ?>
        </a>
      </div>
      <div class="link-button light nolink delete">
        <a role="delete-button" href="javascript:void(0)">
          <?php echo get_string('Delete_group', 'tool_epman'); ?>
        </a>
      </div>
    </div>
    <div class="record-body" style="display: none;">
    </div>
  </div>
</div>
<div id="record-body-template" style="display: none;">
  <div class="name-value">
    <span><?php echo get_string('Education_program', 'tool_epman'); ?></span>
    <@ if (g.program && g.program.name && g.program.name.length > 0) { @>
    <span>
      <a href="../programs/index.php#<@= g.program.id @>">
        <@= g.program.name @>
      </a>
    </span>
    <@ } else { @>
    <span class="comment"><?php echo get_string('notspecified', 'tool_epman'); ?></span>
    <@ } @>
  </div>
  <div class="name-value">
    <span><?php echo get_string('Responsible', 'tool_epman'); ?></span>
    <@ if (g.responsible && g.responsible.id) { @>
    <a href="<@= '/user/profile.php?id=' + g.responsible.id @>">
      <@= g.responsible.firstname + " " + g.responsible.lastname @>
    </a>
    <@ } else { @>
    <span class="comment"><?php echo get_string('notspecified', 'tool_epman'); ?></span>
    <@ } @>
  </div>
  <div class="name-value">
    <span><?php echo get_string('Assistants', 'tool_epman'); ?></span>
    <@ if (g.assistants && g.assistants.length > 0) { @>
    <span>
    <@ _.each(g.assistants, function (a, i) {
        if (i > 0) { @>, <@ } @>
        <a href="<@= '/user/profile.php?id=' + a.id @>">
          <@= a.firstname + " " + a.lastname @>
        </a>
    <@ }); @>
    </span>
    <@ } else { @>
    <span class="comment"><?php echo get_string('notspecified', 'tool_epman'); ?></span>
    <@ } @>
  </div>
  <div id="group-<@= g.id @>-students" class="group-students">
    <div class="section-header">
      <span><?php echo get_string('Students', 'tool_epman'); ?></span>
    <@ if (action.deleteStudents || action.copyStudents) { @>
      <@ if (action.deleteStudents) { @>
      <div role="delete-students-button" class="link-button nolink delete <@= someMarked(action.markers) ? '' : 'disabled' @>">
        <a href="javascript:void(0)">
          <?php echo get_string('Delete_selected_students', 'tool_epman'); ?>
        </a>
      </div>
      <@ } else if (action.copyStudents) { @>
      <div role="copy-students-button" class="link-button nolink copy <@= someMarked(action.markers) ? '' : 'disabled' @>">
        <a href="javascript:void(0)">
          <?php echo get_string('Copy_selected_students', 'tool_epman'); ?>
        </a>
      </div>
      <@ } @>
      <div role="cancel-action-button" class="link-button nolink cancel">
        <a href="javascript:void(0)">
          <?php echo get_string('Cancel', 'tool_epman'); ?>
        </a>
      </div>
      <div role="select-all-button" class="link-button nolink right">
        <a href="javascript:void(0)">
          <@ if (!allMarked(action.markers)) { @>
          <?php echo get_string('Select_all', 'tool_epman'); ?>
          <@ } else { @>
          <?php echo get_string('Select_none', 'tool_epman'); ?>
          <@ } @>
        </a>
      </div>
    <@ } else { @>
      <div role="add-students-button" class="link-button light nolink add">
        <a href="javascript:void(0)">
          <?php echo get_string('Add_students', 'tool_epman'); ?>
        </a>
      </div>
      <@ if (!_.isEmpty(g.students)) { @>
      <div role="delete-students-button" class="link-button light nolink delete">
        <a href="javascript:void(0)">
          <?php echo get_string('Delete_students', 'tool_epman'); ?>
        </a>
      </div>
      <div role="copy-students-button" class="link-button light nolink copy">
        <a href="javascript:void(0)">
          <?php echo get_string('Copy_students', 'tool_epman'); ?>
        </a>
      </div>
      <@ } @>
      <@ if (!_.isEmpty(clipboard("students"))) { @>
      <div role="paste-students-button" class="link-button light nolink paste">
        <a href="javascript:void(0)">
          <?php echo get_string('Paste_students', 'tool_epman'); ?>
        </a>
      </div>
      <@ } @>
    <@ } @>
    </div>
    <@ if (!g.students || _.isEmpty(g.students)) { @>
    <span class="comment"><?php echo get_string('nostudents', 'tool_epman'); ?></span>
    <@ } else { @>
    <div class="group-student-list">
    </div>
    <@ } @>
  </div>
</div>
<div id="letter-template" style="display: none;">
  <div class="letter">
    <@= letter @>
  </div>
</div>
<div id="student-list-template" style="display: none;">
  <div class="students-column">
  <@ _.each(students, function (s) { @>
    <@ if (s.lastname[0] != letter) {
      letter = s.lastname[0]; @>
    <div class="letter">
      <@= letter @>
    </div>
    <@ } @>
    <div class="selectable-box">
    <@ if (action.deleteStudents || action.copyStudents) { @>
    <div class="selector">
      <input role="marker" data-id="<@= s.id @>" type="checkbox" name="selectedStudents"></input>
    </div>
    <@ } @>
    <div class="group-student">
      <a href="<@= '/user/profile.php?id=' + s.id @>">
        <@= s.lastname + " " + s.firstname @>
      </a>
    </div>
    </div>
  <@ }); @>
  </div>
</div>

<?php include "../include/userselect.php"; ?>
<?php include "../include/programselect.php"; ?>

<!-- Dialog templates -->

<?php include "../include/dialogs.php"; ?>

<div id="group-dialog-template" style="display: none;">
  <div class="tool-epman dialog" title="<@= g.id ? '<?php echo get_string('Academic_group_edit', 'tool_epman'); ?>' : '<?php echo get_string('New_academic_group', 'tool_epman'); ?>' @>">
    <table class="name-value-table">
      <tr class="name-value">
        <td><?php echo get_string('groupName', 'tool_epman'); ?></td>
        <td class="fill"><input type="text" name="name" value="<@= g.name @>" placeholder="<?php echo get_string('Enter_the_name_of_the_group', 'tool_epman'); ?>"></input></td>
        <td><?php echo get_string('Year', 'tool_epman'); ?></td>
        <td><input type="text" class="year-spinner" name="year" value="<@= g.year @>" placeholder="<@= '' + minyear + ' - ' + maxyear @>"></input></td>
      </tr>
    </table>
    <table class="name-value-table">
      <tr class="name-value">
        <td><?php echo get_string('Responsible', 'tool_epman'); ?></td>
        <td role="select-responsible"></td>
      </tr>
      <tr class="name-value">
        <td><?php echo get_string('Assistants', 'tool_epman'); ?></td>
        <td role="select-assistants"></td>
      </tr>
      <tr class="name-value">
        <td><?php echo get_string('Education_program', 'tool_epman'); ?></td>
        <td role="select-program"></td>
      </tr>
    </table>
  </div>
</div>
<div id="add-students-dialog-template" style="display: none;">
  <div class="tool-epman dialog" title="<?php echo get_string('Add_students', 'tool_epman'); ?>">
    <table class="name-value-table">
      <tr class="name-value">
        <td><?php echo get_string('Students', 'tool_epman'); ?></td>
        <td role="select-students"></td>
      </tr>
    </table>
    <hr />
    <table role="userdata" class="name-value-table">
      <tr class="name-value">
        <td style="white-space: nowrap;"><?php echo get_string('Last_name', 'tool_epman'); ?></td>
        <td><input type="text" name="lastname"></input></td>
        <td style="white-space: nowrap;"><?php echo get_string('First_name', 'tool_epman'); ?></td>
        <td><input type="text" name="firstname"></input></td>
      </tr>
      <tr class="name-value">
        <td style="white-space: nowrap;"><?php echo get_string('Username', 'tool_epman'); ?></td>
        <td><input type="text" name="username"></input></td>
        <td style="white-space: nowrap;"><?php echo get_string('Email', 'tool_epman'); ?></td>
        <td><input type="text" name="email"></input></td>
      </tr>
    </table>
    <div class="buttons">
      <input type="button" name="add-to-list" value="&uarr;&nbsp;<?php echo get_string('Add_to_list', 'tool_epman'); ?>"></input>
    </div>
  </div>
</div>

<?php include "../include/misc.php"; ?>

<!-- Page -->
<div id="tool-epman" class="tool-epman">
  <div role="page-header" id="filter" class="panel vspace">
    <div class="year-links">
    </div>
    <span id="filter-program" class="filter-input">
    </span>
    <span id="filter-my" class="link-button switch right responsible">
      <?php echo get_string('mygroups', 'tool_epman'); ?>
    </span>
    <div class="link-button nolink add" style="display: block;">
      <a id="add-group-button" href="javascript:void(0)">
        <?php echo get_string('Add_group', 'tool_epman'); ?>
      </a>
    </div>
  </div>
  <div id="group-list" class="record-list">
  </div>
  <div role="page-footer" class="page-footer" style="display: none;">
    <span class="year-links">
    </span>
    <a class="link-button right gotop" onclick="document.getElementById('filter').scrollIntoView();">
      <?php echo get_string('gotop', 'tool_epman'); ?>
    </a>
  </div>
</div>
<?php
echo $OUTPUT->footer();
?>
