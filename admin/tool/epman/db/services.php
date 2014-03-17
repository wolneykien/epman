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
    'epman' => array(
        'functions' => array (
            /* Programs */
            'tool_epman_get_programs',
            'tool_epman_create_program',
            'tool_epman_get_program',
            'tool_epman_update_program',
            'tool_epman_delete_program',

            /* Modules */
            'tool_epman_get_modules',
            'tool_epman_create_module',
            'tool_epman_get_module',
            'tool_epman_update_module',
            'tool_epman_delete_module',
        ),
        'requiredcapability' => 'tool/epman:view',
        'restrictedusers' => 0,
        'shortname' => 'epman',
        'enabled' => 1,
    ),
);

$functions = array(

    /* Education programs */

    'tool_epman_get_programs' => array(
        'classname'   => 'epman_program_external',
        'methodname'  => 'list_programs',
        'classpath'   => 'admin/tool/epman/crudprograms.php',
        'description' => 'Returns the list of education programs defined in the system',
        'type'        => 'read',
    ),
    'tool_epman_create_program' => array(
        'classname'   => 'epman_program_external',
        'methodname'  => 'create_program',
        'classpath'   => 'admin/tool/epman/crudprograms.php',
        'description' => 'Add to the system a new education program with the specified parameters',
        'capabilities' => 'tool/epman:editprogram',
        'type'        => 'write',
    ),
    'tool_epman_get_program' => array(
        'classname'   => 'epman_program_external',
        'methodname'  => 'get_program',
        'classpath'   => 'admin/tool/epman/crudprograms.php',
        'description' => 'Returns the full definition of the specified education program',
        'type'        => 'read',
    ),
    'tool_epman_update_program' => array(
        'classname'   => 'epman_program_external',
        'methodname'  => 'update_program',
        'classpath'   => 'admin/tool/epman/crudprograms.php',
        'description' => 'Updates the specified education program definition',
        'type'        => 'write',
    ),
    'tool_epman_delete_program' => array(
        'classname'   => 'epman_program_external',
        'methodname'  => 'delete_program',
        'classpath'   => 'admin/tool/epman/crudprograms.php',
        'description' => 'Deletes the definition of the specified education program',
        'type'        => 'write',
    ),


    /* Education program modules */
    
    'tool_epman_get_modules' => array(
        'classname'   => 'epman_module_external',
        'methodname'  => 'list_modules',
        'classpath'   => 'admin/tool/epman/crudmodules.php',
        'description' => 'Returns the list of education program modules defined in the system',
        'type'        => 'read',
    ),
    'tool_epman_create_module' => array(
        'classname'   => 'epman_module_external',
        'methodname'  => 'create_module',
        'classpath'   => 'admin/tool/epman/crudmodules.php',
        'description' => 'Add to the system a new education program module with the specified parameters',
        'type'        => 'write',
    ),
    'tool_epman_get_module' => array(
        'classname'   => 'epman_module_external',
        'methodname'  => 'get_module',
        'classpath'   => 'admin/tool/epman/crudmodules.php',
        'description' => 'Returns the full definition of the specified education program module',
        'type'        => 'read',
    ),
    'tool_epman_update_module' => array(
        'classname'   => 'epman_module_external',
        'methodname'  => 'update_module',
        'classpath'   => 'admin/tool/epman/crudmodules.php',
        'description' => 'Updates the specified education program module definition',
        'type'        => 'write',
    ),
    'tool_epman_delete_module' => array(
        'classname'   => 'epman_module_external',
        'methodname'  => 'delete_module',
        'classpath'   => 'admin/tool/epman/crudmodules.php',
        'description' => 'Deletes the definition of the specified education program module',
        'type'        => 'write',
    ),
);
?>
