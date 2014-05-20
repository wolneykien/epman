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
 * User-select templates.
 *
 * @package    tool
 * @subpackage epman
 * @copyright  2014 Paul Wolneykien <manowar@altlinux.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
?>

<!-- User-select templates -->
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
