<?php

defined('MOODLE_INTERNAL') || die();
 
$capabilities = array(
 
    'block/incident:viewpages' => array(
 
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
             'guest' => CAP_PREVENT,
            'student' => CAP_PREVENT,
            'teacher' => CAP_PREVENT,
            'editingteacher' => CAP_PREVENT,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'staffmanager'=>CAP_PREVENT
        )
    ),
 
    'block/incident:managepages' => array(
 
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archtypes' => array(
            'guest' => CAP_PREVENT,
            'student' => CAP_PREVENT,
            'teacher' => CAP_PREVENT,
            'editingteacher' => CAP_PREVENT,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'staffmanager'=>CAP_PREVENT
        )       
    ),
    
    'block/incident:myaddinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        ),
 
        'clonepermissionsfrom' => 'moodle/my:manageblocks'
    ),
 
    'block/incident:addinstance' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
 
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_PREVENT,
            'manager' => CAP_ALLOW
        ),
 
        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    )
);