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
), true);

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
          <a href="<@= p.responsible && p.responsible.id ? '/user/profile.php?id=' + p.responsible.id : '' @>">
            <@= p.responsible && p.responsible.id ? p.responsible.firstname + " " + p.responsible.lastname : "<?php echo get_string('notspecified', 'tool_epman'); ?>" @>
          </a>
        </div>
      <@ } @>
      <div class="link-button right groups">
        <a href="../groups/index.php?programid=<@= p.id @>">
          <?php echo get_string('groups', 'tool_epman'); ?>
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
    <span class="record-description">
      <@= (p.description && p.description.length > 0) ? p.description : "<?php echo get_string('notspecified', 'tool_epman'); ?>" @>
    </span>
  </div>
  <div id="program-<@= p.id @>-modules" class="program-module-list">
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
                  case "1": return "<?php echo get_string('N1day', 'tool_epman'); ?>".replace(/%i/, len);
                  case "2": return "<?php echo get_string('N2days', 'tool_epman'); ?>".replace(/%i/, len);
                  case "3": return "<?php echo get_string('N3days', 'tool_epman'); ?>".replace(/%i/, len);
                  case "4": return "<?php echo get_string('N4days', 'tool_epman'); ?>".replace(/%i/, len);
                  case "5": return "<?php echo get_string('N5days', 'tool_epman'); ?>".replace(/%i/, len);
                   default: return "<?php echo get_string('Ndays', 'tool_epman'); ?>".replace(/%i/, len);
              }
            })(m.length) @>
          </span>
        </span>
      </div>
    </div>
    <div class="module-course-list">
      <ul>
      <@ _.forEach(m.courses, function (c) { @>
        <li><@= c.name @></li>
      <@ }); @>
      </ul>
    </div>
  </div>
</div>

<!-- Page -->
<div class="tool-epman">
  <div id="filter" class="panel right vspace">
    <span id="my" class="link-button switch right responsible">
      <?php echo get_string('myprograms', 'tool_epman'); ?>
    </span>
  </div>
  <div id="program-list" class="record-list">
  </div>
</div>
<?php
echo $OUTPUT->footer();
?>
