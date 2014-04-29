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
            'tool_epman_get_program_modules',
            'tool_epman_create_program_module',
            'tool_epman_get_program_module',
            'tool_epman_update_program_module',
            'tool_epman_delete_program_module',

            /* Module courses */
            'tool_epman_get_program_module_courses',
            'tool_epman_add_program_module_course',
            'tool_epman_get_program_module_course',
            'tool_epman_update_program_module_course',
            'tool_epman_delete_program_module_course',

            /* Program assistants */
            'tool_epman_get_program_assistants',
            'tool_epman_add_program_assistant',
            'tool_epman_get_program_assistant',
            /* no update */
            'tool_epman_delete_program_assistant',

            /* Groups */
            'tool_epman_get_groups',
            'tool_epman_create_group',
            'tool_epman_get_group',
            'tool_epman_update_group',
            'tool_epman_delete_group',

            /* Group assistants */
            'tool_epman_get_group_assistants',
            'tool_epman_add_group_assistant',
            'tool_epman_get_group_assistant',
            /* no update */
            'tool_epman_delete_group_assistant',

            /* Group students */
            'tool_epman_get_group_students',
            'tool_epman_add_group_student',
            'tool_epman_get_group_student',
            'tool_epman_update_group_student',
            'tool_epman_delete_group_student',

            /* Users (read-only) */
            'tool_epman_get_users',
            'tool_epman_get_user',

            /* Courses (read-only) */
            'tool_epman_get_courses',
            'tool_epman_get_course',
            
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
        'classpath'   => 'admin/tool/epman/crud/programs.php',
        'description' => 'Returns the list of education programs defined in the system',
        'type'        => 'read',
    ),
    'tool_epman_create_program' => array(
        'classname'   => 'epman_program_external',
        'methodname'  => 'create_program',
        'classpath'   => 'admin/tool/epman/crud/programs.php',
        'description' => 'Add to the system a new education program with the specified parameters',
        'capabilities' => 'tool/epman:editprogram',
        'type'        => 'write',
    ),
    'tool_epman_get_program' => array(
        'classname'   => 'epman_program_external',
        'methodname'  => 'get_program',
        'classpath'   => 'admin/tool/epman/crud/programs.php',
        'description' => 'Returns the full definition of the specified education program',
        'type'        => 'read',
    ),
    'tool_epman_update_program' => array(
        'classname'   => 'epman_program_external',
        'methodname'  => 'update_program',
        'classpath'   => 'admin/tool/epman/crud/programs.php',
        'description' => 'Updates the specified education program definition',
        'type'        => 'write',
    ),
    'tool_epman_delete_program' => array(
        'classname'   => 'epman_program_external',
        'methodname'  => 'delete_program',
        'classpath'   => 'admin/tool/epman/crud/programs.php',
        'description' => 'Deletes the definition of the specified education program',
        'type'        => 'write',
    ),


    /* Education program modules */
    
    'tool_epman_get_program_modules' => array(
        'classname'   => 'epman_module_external',
        'methodname'  => 'list_modules',
        'classpath'   => 'admin/tool/epman/crud/program-modules.php',
        'description' => 'Returns the list of education program modules defined in the system',
        'type'        => 'read',
    ),
    'tool_epman_create_program_module' => array(
        'classname'   => 'epman_module_external',
        'methodname'  => 'create_module',
        'classpath'   => 'admin/tool/epman/crud/program-modules.php',
        'description' => 'Add to the system a new education program module with the specified parameters',
        'type'        => 'write',
    ),
    'tool_epman_get_program_module' => array(
        'classname'   => 'epman_module_external',
        'methodname'  => 'get_module',
        'classpath'   => 'admin/tool/epman/crud/program-modules.php',
        'description' => 'Returns the full definition of the specified education program module',
        'type'        => 'read',
    ),
    'tool_epman_update_program_module' => array(
        'classname'   => 'epman_module_external',
        'methodname'  => 'update_module',
        'classpath'   => 'admin/tool/epman/crud/program-modules.php',
        'description' => 'Updates the specified education program module definition',
        'type'        => 'write',
    ),
    'tool_epman_delete_program_module' => array(
        'classname'   => 'epman_module_external',
        'methodname'  => 'delete_module',
        'classpath'   => 'admin/tool/epman/crud/program-modules.php',
        'description' => 'Deletes the definition of the specified education program module',
        'type'        => 'write',
    ),


    /* Education program module courses */
    
    'tool_epman_get_program_module_courses' => array(
        'classname'   => 'epman_module_course_external',
        'methodname'  => 'list_module_courses',
        'classpath'   => 'admin/tool/epman/crud/module-courses.php',
        'description' => 'Returns the list of education program module courses defined in the system',
        'type'        => 'read',
    ),
    'tool_epman_add_program_module_course' => array(
        'classname'   => 'epman_module_course_external',
        'methodname'  => 'add_module_course',
        'classpath'   => 'admin/tool/epman/crud/module-courses.php',
        'description' => 'Add to the system a new education program module course with the specified parameters',
        'type'        => 'write',
    ),
    'tool_epman_get_program_module_course' => array(
        'classname'   => 'epman_module_course_external',
        'methodname'  => 'get_module_course',
        'classpath'   => 'admin/tool/epman/crud/module-courses.php',
        'description' => 'Returns the full definition of the specified education program module course',
        'type'        => 'read',
    ),
    'tool_epman_update_program_module_course' => array(
        'classname'   => 'epman_module_course_external',
        'methodname'  => 'update_module_course',
        'classpath'   => 'admin/tool/epman/crud/module-courses.php',
        'description' => 'Updates the given education program module course',
        'type'        => 'write',
    ),
    'tool_epman_delete_program_module_course' => array(
        'classname'   => 'epman_module_course_external',
        'methodname'  => 'delete_module_course',
        'classpath'   => 'admin/tool/epman/crud/module-courses.php',
        'description' => 'Deletes the definition of the specified education program module course',
        'type'        => 'write',
    ),


    /* Education program assistant users */
    
    'tool_epman_get_program_assistants' => array(
        'classname'   => 'epman_program_assistant_external',
        'methodname'  => 'list_program_assistants',
        'classpath'   => 'admin/tool/epman/crud/program-assistants.php',
        'description' => 'Returns the list of education program assistant users defined in the system',
        'type'        => 'read',
    ),
    'tool_epman_add_program_assistant' => array(
        'classname'   => 'epman_program_assistant_external',
        'methodname'  => 'add_program_assistant',
        'classpath'   => 'admin/tool/epman/crud/program-assistants.php',
        'description' => 'Add to the system a new education program assistant user with the specified parameters',
        'type'        => 'write',
    ),
    'tool_epman_get_program_assistant' => array(
        'classname'   => 'epman_program_assistant_external',
        'methodname'  => 'get_program_assistant',
        'classpath'   => 'admin/tool/epman/crud/program-assistants.php',
        'description' => 'Returns the full definition of the specified education program assistant user',
        'type'        => 'read',
    ),
    'tool_epman_delete_program_assistant' => array(
        'classname'   => 'epman_program_assistant_external',
        'methodname'  => 'delete_program_assistant',
        'classpath'   => 'admin/tool/epman/crud/program-assistants.php',
        'description' => 'Deletes the definition of the specified education program assistant user',
        'type'        => 'write',
    ),


    /* Academic groups */

    'tool_epman_get_groups' => array(
        'classname'   => 'epman_group_external',
        'methodname'  => 'list_groups',
        'classpath'   => 'admin/tool/epman/crud/groups.php',
        'description' => 'Returns the list of academic groups defined in the system',
        'type'        => 'read',
    ),
    'tool_epman_create_group' => array(
        'classname'   => 'epman_group_external',
        'methodname'  => 'create_group',
        'classpath'   => 'admin/tool/epman/crud/groups.php',
        'description' => 'Add to the system a new academic group with the specified parameters',
        'capabilities' => 'tool/epman:editgroup',
        'type'        => 'write',
    ),
    'tool_epman_get_group' => array(
        'classname'   => 'epman_group_external',
        'methodname'  => 'get_group',
        'classpath'   => 'admin/tool/epman/crud/groups.php',
        'description' => 'Returns the full definition of the specified academic group',
        'type'        => 'read',
    ),
    'tool_epman_update_group' => array(
        'classname'   => 'epman_group_external',
        'methodname'  => 'update_group',
        'classpath'   => 'admin/tool/epman/crud/groups.php',
        'description' => 'Updates the specified academic group definition',
        'type'        => 'write',
    ),
    'tool_epman_delete_group' => array(
        'classname'   => 'epman_group_external',
        'methodname'  => 'delete_group',
        'classpath'   => 'admin/tool/epman/crud/groups.php',
        'description' => 'Deletes the definition of the specified academic group',
        'type'        => 'write',
    ),


    /* Academic group assistant users */
    
    'tool_epman_get_group_assistants' => array(
        'classname'   => 'epman_group_assistant_external',
        'methodname'  => 'list_group_assistants',
        'classpath'   => 'admin/tool/epman/crud/group-assistants.php',
        'description' => 'Returns the list of academic group assistant users defined in the system',
        'type'        => 'read',
    ),
    'tool_epman_add_group_assistant' => array(
        'classname'   => 'epman_group_assistant_external',
        'methodname'  => 'add_group_assistant',
        'classpath'   => 'admin/tool/epman/crud/group-assistants.php',
        'description' => 'Add to the system a new academic group assistant user with the specified parameters',
        'type'        => 'write',
    ),
    'tool_epman_get_group_assistant' => array(
        'classname'   => 'epman_group_assistant_external',
        'methodname'  => 'get_group_assistant',
        'classpath'   => 'admin/tool/epman/crud/group-assistants.php',
        'description' => 'Returns the full definition of the specified academic group assistant user',
        'type'        => 'read',
    ),
    'tool_epman_delete_group_assistant' => array(
        'classname'   => 'epman_group_assistant_external',
        'methodname'  => 'delete_group_assistant',
        'classpath'   => 'admin/tool/epman/crud/group-assistants.php',
        'description' => 'Deletes the definition of the specified academic group assistant user',
        'type'        => 'write',
    ),


    /* Academic group student users */
    
    'tool_epman_get_group_students' => array(
        'classname'   => 'epman_group_student_external',
        'methodname'  => 'list_group_students',
        'classpath'   => 'admin/tool/epman/crud/group-students.php',
        'description' => 'Returns the list of academic group student users defined in the system',
        'type'        => 'read',
    ),
    'tool_epman_add_group_student' => array(
        'classname'   => 'epman_group_student_external',
        'methodname'  => 'add_group_student',
        'classpath'   => 'admin/tool/epman/crud/group-students.php',
        'description' => 'Add to the system a new academic group student user with the specified parameters',
        'type'        => 'write',
    ),
    'tool_epman_get_group_student' => array(
        'classname'   => 'epman_group_student_external',
        'methodname'  => 'get_group_student',
        'classpath'   => 'admin/tool/epman/crud/group-students.php',
        'description' => 'Returns the full definition of the specified academic group student user',
        'type'        => 'read',
    ),
    'tool_epman_update_group_student' => array(
        'classname'   => 'epman_group_student_external',
        'methodname'  => 'update_group_student',
        'classpath'   => 'admin/tool/epman/crud/group-students.php',
        'description' => 'Updates group membership data for the given student',
        'type'        => 'write',
    ),
    'tool_epman_delete_group_student' => array(
        'classname'   => 'epman_group_student_external',
        'methodname'  => 'delete_group_student',
        'classpath'   => 'admin/tool/epman/crud/group-students.php',
        'description' => 'Deletes the definition of the specified academic group student user',
        'type'        => 'write',
    ),


    /* Users (read-only) */
    
    'tool_epman_get_users' => array(
        'classname'   => 'epman_user_external',
        'methodname'  => 'list_users',
        'classpath'   => 'admin/tool/epman/crud/users.php',
        'description' => 'Returns the list of users defined in the system',
        'type'        => 'read',
    ),
    'tool_epman_get_user' => array(
        'classname'   => 'epman_user_external',
        'methodname'  => 'get_user',
        'classpath'   => 'admin/tool/epman/crud/users.php',
        'description' => 'Returns the definition of the specified user (by id)',
        'type'        => 'read',
    ),


    /* Courses (read-only) */
    
    'tool_epman_get_courses' => array(
        'classname'   => 'epman_course_external',
        'methodname'  => 'list_courses',
        'classpath'   => 'admin/tool/epman/crud/courses.php',
        'description' => 'Returns the list of courses defined in the system',
        'type'        => 'read',
    ),
    'tool_epman_get_course' => array(
        'classname'   => 'epman_course_external',
        'methodname'  => 'get_course',
        'classpath'   => 'admin/tool/epman/crud/courses.php',
        'description' => 'Returns the definition of the specified course (by id)',
        'type'        => 'read',
    ),

);
?>
