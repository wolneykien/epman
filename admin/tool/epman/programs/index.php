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
    'user' => $USER,
    'i18n' => array(
      'OK' => get_string('OK', 'tool_epman'),
      'Cancel' => get_string('Cancel', 'tool_epman'),
      'Close' => get_string('Close', 'tool_epman'),
      'dateFormat' => get_string('dateFormat', 'tool_epman'),
    ),
), true);

$PAGE->requires->js('/admin/tool/epman/js/common.js');
$PAGE->requires->js('/admin/tool/epman/js/userselect.js');
$PAGE->requires->js('/admin/tool/epman/js/courseselect.js');
$PAGE->requires->js('/admin/tool/epman/js/programs.js');

admin_externalpage_setup('toolepman');
$PAGE->set_pagelayout('maintenance');
?>
<?php
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('programlistheading', 'tool_epman'));
?>

<!-- Templates -->
<div id="list-section-template" style="display: none;">
  <div id="year-<@= year @>" class="list-section">
    <div class="list-section-header">
      <span>
        <@= (function (year) {
          switch (year) {
              case 1: return "<?php echo get_string('courseyear1', 'tool_epman'); ?>";
              case 2: return "<?php echo get_string('courseyear2', 'tool_epman'); ?>";
              case 3: return "<?php echo get_string('courseyear3', 'tool_epman'); ?>";
              case 4: return "<?php echo get_string('courseyear4', 'tool_epman'); ?>";
              case 5: return "<?php echo get_string('courseyear5', 'tool_epman'); ?>";
              case 6: return "<?php echo get_string('courseyear6', 'tool_epman'); ?>";
             default: return "<?php echo get_string('courseyear', 'tool_epman'); ?>".replace(/%i/, year);
          }
        })(year) @>
      </span>
      <hr />
    </div>
  </div>
</div>
<div id="record-template" style="display: none;">
  <div id="program-<@= p.id @>" class="record collapsed">
    <div class="record-header show-more">
      <@= p.name @>
      <@ if (!f.my || !p.responsible || p.responsible.id != <?php echo $USER->id; ?>) { @>
        <div class="link-button right responsible">
          <@ if (p.responsible && p.responsible.id) { @>
          <a href="<@= '/user/profile.php?id=' + p.responsible.id @>">
            <@= p.responsible.firstname + " " + p.responsible.lastname @>
          </a>
          <@ } else { @>
          <span class="comment"><?php echo get_string('notspecified', 'tool_epman'); ?></span>
          <@ } @>
        </div>
      <@ } @>
      <div class="link-button light nolink edit">
        <a role="edit-button" href="javascript:void(0)">
          <?php echo get_string('Edit_program', 'tool_epman'); ?>
        </a>
      </div>
      <div class="link-button light nolink delete">
        <a href="javascript:void(0)">
          <?php echo get_string('Delete_program', 'tool_epman'); ?>
        </a>
      </div>
      <div class="link-button right groups">
        <a href="../groups/index.php?programid=<@= p.id @>">
          <?php echo get_string('Groups', 'tool_epman'); ?>
        </a>
      </div>
    </div>
    <div class="record-body" style="display: none;">
    </div>
  </div>
</div>
<div id="record-body-template" style="display: none;">
  <div class="name-value">
    <span><?php echo get_string('Description', 'tool_epman'); ?></span>
    <@ if (p.description && p.description.length > 0) { @>
    <span class="description"><@= p.description @></span>
    <@ } else { @>
    <span class="comment"><?php echo get_string('notspecified', 'tool_epman'); ?></span>
    <@ } @>
  </div>
  <div class="name-value">
    <span><?php echo get_string('Responsible', 'tool_epman'); ?></span>
    <@ if (p.responsible && p.responsible.id) { @>
    <a href="<@= '/user/profile.php?id=' + p.responsible.id @>">
      <@= p.responsible.firstname + " " + p.responsible.lastname @>
    </a>
    <@ } else { @>
    <span class="comment"><?php echo get_string('notspecified', 'tool_epman'); ?></span>
    <@ } @>
  </div>
  <div class="name-value">
    <span><?php echo get_string('Assistants', 'tool_epman'); ?></span>
    <@ if (p.assistants && p.assistants.length > 0) { @>
    <span>
    <@ _.each(p.assistants, function (a, i) {
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
  <div class="section-header">
    <span><?php echo get_string('Modules', 'tool_epman'); ?></span>
    <div role="add-module-button" class="link-button light nolink add">
      <a href="javascript:void(0)">
        <?php echo get_string('Add_module', 'tool_epman'); ?>
      </a>
    </div>
    <div class="link-button light nolink delete">
      <a href="javascript:void(0)">
        <?php echo get_string('Delete_modules', 'tool_epman'); ?>
      </a>
    </div>
  </div>
  <div id="program-<@= p.id @>-modules" class="program-modules">
    <@ if (!p.modules || _.isEmpty(p.modules)) { @>
    <span class="comment"><?php echo get_string('nomodules', 'tool_epman'); ?></span>
    <@ } else { @>
    <div class="program-module-list">
    </div>
    <@ } @>
  </div>
</div>
<div id="modules-period-template" style="display: none;">
  <div id="module-<@= m.id @>-period-<@= m.period + 1 @>" class="modules-period">
    <div class="modules-period-header">
      <@= (function (period) {
        switch (("" + period).substr(-1)) {
          case "1": return "<?php echo get_string('N1st_period', 'tool_epman'); ?>".replace(/%i/, period);
          case "2": return "<?php echo get_string('N2nd_period', 'tool_epman'); ?>".replace(/%i/, period);
          case "3": return "<?php echo get_string('N3rd_period', 'tool_epman'); ?>".replace(/%i/, period);
          case "4": return "<?php echo get_string('N4th_period', 'tool_epman'); ?>".replace(/%i/, period);
          case "5": return "<?php echo get_string('N5th_period', 'tool_epman'); ?>".replace(/%i/, period);
           default: return "<?php echo get_string('Nth_period', 'tool_epman'); ?>".replace(/%i/, period);
        }
      })(m.period + 1) @>
    </div>
  </div>
</div>
<div id="module-template" style="display: none;">
  <div id="module-<@= m.id @>" class="program-module">
    <div class="module-header">
      <div class="name-value">
        <span><?php echo get_string('moduleStart', 'tool_epman'); ?></span>
        <span><@= (new Date(m.startdate * 1000)).toLocaleDateString() @></span>
      </div>
      <div class="name-value">
        <span><?php echo get_string('moduleEnd', 'tool_epman'); ?></span>
        <span>
          <@= (new Date((m.startdate + m.length * 24 * 3600) * 1000)).toLocaleDateString() @>
          <span class="comment">
            <@= (function (len) {
              switch (("" + len).substr(-1)) {
                  case "1": return "<?php echo get_string('N1_day', 'tool_epman'); ?>".replace(/%i/, len);
                  case "2": return "<?php echo get_string('N2_days', 'tool_epman'); ?>".replace(/%i/, len);
                  case "3": return "<?php echo get_string('N3_days', 'tool_epman'); ?>".replace(/%i/, len);
                  case "4": return "<?php echo get_string('N4_days', 'tool_epman'); ?>".replace(/%i/, len);
                  case "5": return "<?php echo get_string('N5_days', 'tool_epman'); ?>".replace(/%i/, len);
                   default: return "<?php echo get_string('N_days', 'tool_epman'); ?>".replace(/%i/, len);
              }
            })(m.length) @>
          </span>
        </span>
      </div>
      <div class="link-button light nolink edit">
        <a href="javascript:void(0)">
          <?php echo get_string('Edit_module', 'tool_epman'); ?>
        </a>
      </div>
    </div>
    <div class="module-courses">
      <ul class="module-course-list">
      <@ _.each(m.courses, function (c) { @>
        <li>
          <a href="/course/view.php?id=<@= c.id @>"><@= c.name @></a>
        </li>
      <@ }); @>
      </ul>
    </div>
  </div>
</div>
<div id="vacation-template" style="display: none;">
  <div class="program-vacation">
    <span>
      <@= (function (len) {
        switch (("" + len).substr(-1)) {
          case "1": return "<?php echo get_string('vacation_N1day', 'tool_epman'); ?>".replace(/%i/, len);
          case "2": return "<?php echo get_string('vacation_N2days', 'tool_epman'); ?>".replace(/%i/, len);
          case "3": return "<?php echo get_string('vacation_N3days', 'tool_epman'); ?>".replace(/%i/, len);
          case "4": return "<?php echo get_string('vacation_N4days', 'tool_epman'); ?>".replace(/%i/, len);
          case "5": return "<?php echo get_string('vacation_N5days', 'tool_epman'); ?>".replace(/%i/, len);
           default: return "<?php echo get_string('vacation_Ndays', 'tool_epman'); ?>".replace(/%i/, len);
        }
      })(length) @>
    </span>
  </div>
</div>
<div id="userselect-template" style="display: none;">
  <div role="multiselect-box" class="multiselect-box">
  <@ if (_.isEmpty(collection) && defValue) { @>
    <span data-id="<@= defValue.id @>" class="link-button light responsible">
      <a href="<@= '/user/profile.php?id=' + defValue.id @>" target="_blank">
        <@= defValue.firstname + " " + defValue.lastname @>
      </a>
    </span>
  <@ } @>
  <@ _.each(collection, function (user) {
        if (user.id) { @>
        <span data-id="<@= user.id @>" class="link-button responsible deletable">
          <a href="<@= '/user/profile.php?id=' + user.id @>" target="_blank">
            <@= user.firstname + " " + user.lastname @>
          </a>
          <span role="delete-button" class="delete-button"></span>
        </span>
        <@ }
     }); @>
    <span role="search" class="search">
      <span class="prompt"><@= defValue && max == 1 ? "←" : "+" @></span>
      <span role="keyword-input" class="keyword-input" contenteditable="true" style="outline: none;"></span>
      <span role="placeholder" class="placeholder"><?php echo get_string('starttyping_user', 'tool_epman'); ?></span>
    </span>
  </div>
</div>
<div id="user-search-list-template" style="display: none;">
  <div role="search-list" class="search-list-overlay" style="display: none;">
  <@ _.each(collection, function (user) { @>
    <span class="search-list-item" role="search-list-item" data-id="<@= user.id @>">
      <@= user.firstname.format() + " " + user.lastname.format() @>
      <@ if (user.firstname.noMatches() && user.lastname.noMatches()) {
        if (user.email.hasMatches()) { @>
          <@= " (" + user.email.format() + ")" @>
        <@ } else if (user.username.hasMatches()) { @>
          <@= " (" + user.username.format() + ")" @>
        <@ }
      } @>
    </span>
  <@ }); @>
  </div>
</div>
<div id="courseselect-template" style="display: none;">
  <div role="multiselect-box" class="multiselect-box">
  <@ if (_.isEmpty(collection) && defValue) { @>
    <span data-id="<@= defValue.id @>" class="link-button light course">
      <a href="<@= '/course/view.php?id=' + defValue.id @>" target="_blank">
        <@= defValue.fullname @>
      </a>
    </span>
  <@ } @>
  <@ _.each(collection, function (course) {
        if (course.id) { @>
        <span data-id="<@= course.id @>" class="link-button course deletable">
          <a href="<@= '/course/view.php?id=' + course.id @>" target="_blank">
            <@= course.fullname @>
          </a>
          <span role="delete-button" class="delete-button"></span>
        </span>
        <@ }
     }); @>
    <span role="search" class="search">
      <span class="prompt"><@= defValue && max == 1 ? "←" : "+" @></span>
      <span role="keyword-input" class="keyword-input" contenteditable="true" style="outline: none;"></span>
      <span role="placeholder" class="placeholder"><?php echo get_string('starttyping_course', 'tool_epman'); ?></span>
    </span>
  </div>
</div>
<div id="course-searchlist-template" style="display: none;">
  <div role="search-list" class="search-list-overlay" style="display: none;">
  <@ _.each(collection, function (course) { @>
    <span class="search-list-item" role="search-list-item" data-id="<@= course.id @>">
      <@= course.fullname.format() @>
    </span>
  <@ }); @>
  </div>
</div>

<!-- Dialog templates -->
<div id="program-dialog-template" style="display: none;">
  <div class="tool-epman dialog" title="<@= p.id ? '<?php echo get_string('Education_program_edit', 'tool_epman'); ?>' : '<?php echo get_string('New_education_program', 'tool_epman'); ?>' @>">
    <table class="name-value-table">
      <tr class="name-value">
        <td><?php echo get_string('programName', 'tool_epman'); ?></td>
        <td class="fill"><input type="text" name="name" value="<@= p.name @>" placeholder="<?php echo get_string('Enter_the_name_of_the_program', 'tool_epman'); ?>"></input></td>
        <td><?php echo get_string('Year', 'tool_epman'); ?></td>
        <td><input type="text" class="year-spinner" name="year" value="<@= p.year @>" placeholder="<@= '<?php echo get_string('m-n', 'tool_epman'); ?>'.replace(/%i/, minyear).replace(/%i/, maxyear) @>"></input></td>
      </tr>
      <tr>
        <td colspan="4" class="fullrow">
          <textarea name="description" class="description" placeholder="<?php echo get_string('Enter_the_programs_description', 'tool_epman'); ?>"><@= p.description @></textarea>
        </td>
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
    </table>
  </div>
</div>
<div id="error-dialog-template" style="display: none;">
  <div class="tool-epman dialog error" title="<?php echo get_string('Error_message', 'tool_epman'); ?>">
    <div class="name-value">
      <span><?php echo get_string('Error', 'tool_epman'); ?></span>
      <span><@= (typeof message == 'undefined') ? "<?php echo get_string('unknown_error', 'tool_epman'); ?>" : message @></span>
    </div>
  </div>
</div>
<div id="module-dialog-template" style="display: none;">
  <div class="tool-epman dialog" title="<@= m.id ? '<?php echo get_string('Education_program_module_edit', 'tool_epman'); ?>' : '<?php echo get_string('New_education_program_module', 'tool_epman'); ?>' @>">
    <table class="name-value-table">
      <tr class="name-value">
        <td style="white-space: nowrap;"><?php echo get_string('Edication_period', 'tool_epman'); ?></td>
        <td><input type="text" class="period-spinner" name="period"></input></td>
      </tr>
    </table>
    <table class="name-value-table">
      <tr class="name-value">
        <td><?php echo get_string('moduleStart', 'tool_epman'); ?></td>
        <td><input type="text" name="startdate" placeholder="<?php echo get_string('dateFormatLabel', 'tool_epman'); ?>"></input></td>
        <td><?php echo get_string('moduleEnd', 'tool_epman'); ?></td>
        <td><input type="text" name="enddate" placeholder="<?php echo get_string('dateFormatLabel', 'tool_epman'); ?>"></input></td>
        <td><?php echo get_string('moduleLength', 'tool_epman'); ?></td>
        <td><table><tr>
          <td><input type="text" class="length-spinner" name="length" value="<@= m.length @>"></input></td>
          <td><span role="days" class="days-suffix">
            &amp;nbsp;
            <@= (function (len) {
              switch (("" + len).substr(-1)) {
                case "1": return "<?php echo get_string('N1day', 'tool_epman'); ?>";
                case "2": return "<?php echo get_string('N2days', 'tool_epman'); ?>";
                case "3": return "<?php echo get_string('N3days', 'tool_epman'); ?>";
                case "4": return "<?php echo get_string('N4days', 'tool_epman'); ?>";
                case "5": return "<?php echo get_string('N5days', 'tool_epman'); ?>";
                 default: return "<?php echo get_string('Ndays', 'tool_epman'); ?>";
              }
            })(m.length) @>
          </span></td>
        </tr></table></td>
      </tr>
    </table>
    <table class="name-value-table">
      <tr class="name-value">
        <td><?php echo get_string('Courses', 'tool_epman'); ?></td>
      </tr>
      <tr>
        <td role="select-courses"></td>
      </tr>
    </table>
  </div>
</div>

<!-- Page -->
<div class="tool-epman">
  <div id="filter" class="panel vspace">
    <div class="link-button nolink add">
      <a id="add-program-button" href="javascript:void(0)">
        <?php echo get_string('Add_program', 'tool_epman'); ?>
      </a>
    </div>
    <span id="my" class="link-button switch right responsible">
      <?php echo get_string('myprograms', 'tool_epman'); ?>
    </span>
  </div>
  <div id="program-list" class="record-list">
  </div>
  <div id="footer" class="page-footer" style="display: none;">
    <a class="link-button right gotop" onclick="document.getElementById('filter').scrollIntoView();">
      <?php echo get_string('gotop', 'tool_epman'); ?>
    </a>
  </div>
</div>
<?php
echo $OUTPUT->footer();
?>
