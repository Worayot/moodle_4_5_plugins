<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/analytics:view' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'student' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'admin' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'moodle/course:view'
    ],
];