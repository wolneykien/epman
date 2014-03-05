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
        'functions' => array (
            'tool_epman_get_programs',
            'tool_epman_create_program',
            'tool_epman_get_program',
            'tool_epman_update_program',
            'tool_epman_delete_program',
        ),
        'requiredcapability' => 'tool/epman:view',
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
    ),
    'tool_epman_create_program' => array(
        'classname'   => 'epman_external',
        'methodname'  => 'create_program',
        'classpath'   => 'admin/tool/epman/externallib.php',
        'description' => 'Add to the system a new education program with the specified parameters',
        'capabilities' => 'tool/epman:editprogram',
        'type'        => 'write',
    ),
    'tool_epman_get_program' => array(
        'classname'   => 'epman_external',
        'methodname'  => 'get_program',
        'classpath'   => 'admin/tool/epman/externallib.php',
        'description' => 'Returns the full definition of the specified education program',
        'type'        => 'read',
    ),
    'tool_epman_update_program' => array(
        'classname'   => 'epman_external',
        'methodname'  => 'update_program',
        'classpath'   => 'admin/tool/epman/externallib.php',
        'description' => 'Updates the specified education program definition',
        'capabilities' => 'tool/epman:editprogram',
        'type'        => 'write',
    ),
    'tool_epman_delete_program' => array(
        'classname'   => 'epman_external',
        'methodname'  => 'delete_program',
        'classpath'   => 'admin/tool/epman/externallib.php',
        'description' => 'Deletes the definition of the specified education program',
        'capabilities' => 'tool/epman:editprogram',
        'type'        => 'write',
    ),
);
?>
