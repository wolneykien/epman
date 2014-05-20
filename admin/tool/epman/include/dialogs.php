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
 * Useful dialog templates.
 *
 * @package    tool
 * @subpackage epman
 * @copyright  2014 Paul Wolneykien <manowar@altlinux.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
?>

<!-- Message dialog templates -->
<div id="message-dialog-template" style="display: none;">
  <div class="tool-epman dialog message" title="<@= (typeof title == 'undefined') ? '<?php echo get_string('Message', 'tool_epman'); ?>' : title @>">
  <@ if (typeof message != 'undefined') { @>
    <span><@= message @></span>
  <@ } @>
  </div>
</div>
<div id="error-dialog-template" style="display: none;">
  <div class="tool-epman dialog error" title="<@= (typeof title == 'undefined') ? '<?php echo get_string('Error_message', 'tool_epman'); ?>' : title @>">
    <div class="name-value">
      <span><@= (typeof heading == 'undefined') ? "<?php echo get_string('Error', 'tool_epman'); ?>" : heading @></span>
      <span><@= (typeof message == 'undefined') ? "<?php echo get_string('unknown_error', 'tool_epman'); ?>" : message @></span>
    </div>
  </div>
</div>
