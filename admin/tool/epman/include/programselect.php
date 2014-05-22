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
 * Program-select templates.
 *
 * @package    tool
 * @subpackage epman
 * @copyright  2014 Paul Wolneykien <manowar@altlinux.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
?>

<!-- Program-select templates -->
<div id="programselect-template" style="display: none;">
  <div role="multiselect-box" class="multiselect-box">
  <@ _.each(collection, function (program) {
        if (program.id) { @>
        <span data-id="<@= program.id @>" class="link-button program deletable">
          <a href="<@= '/program/view.php?id=' + program.id @>" target="_blank">
            <@= program.name @>
          </a>
          <span role="delete-button" class="delete-button"></span>
        </span>
        <@ }
     }); @>
    <span role="search" class="search">
      <span class="prompt"><@= defValue && max == 1 ? "←" : "+" @></span>
      <span role="keyword-input" class="keyword-input" contenteditable="true" style="outline: none;"></span>
      <span role="placeholder" class="placeholder"><?php echo get_string('starttyping_program', 'tool_epman'); ?></span>
    </span>
  </div>
</div>
<div id="program-searchlist-template" style="display: none;">
  <div role="search-list" class="search-list-overlay" style="display: none;">
  <@ _.each(collection, function (program) { @>
    <span class="search-list-item" role="search-list-item" data-id="<@= program.id @>">
      <@= program.name.format() @>
    </span>
  <@ }); @>
  </div>
</div>
