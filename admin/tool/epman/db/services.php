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
 * Here are defined the core web services of the education process
 * management module.
 *
 * @package    tool
 * @subpackage epman
 * @copyright  2014 Paul Wolneykien <manowar@altlinux.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$services = array(
    'epman_programs' => array(
        'functions' => array ('tool_epman_get_programs'),
        'requiredcapability' => '',
        'restrictedusers' => 0,
        'shortname' => 'epman_programs',
        'enabled' => 1,
    ),
);

$functions = array(
    'tool_epman_get_programs' => array(
        'classname'   => 'epman_external',
        'methodname'  => 'list_programs',
        'classpath'   => 'admin/tool/epman/externallib.php',
        'description' => 'Returns the list of education programs defined in the system',
        'type'        => 'read',
    )
);
?>
